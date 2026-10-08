<?php

namespace Tests\Feature\Consegne;

use App\Enums\StatoConsegna;
use App\Enums\TipoMovimento;
use App\Exceptions\GiacenzaInsufficienteException;
use App\Exceptions\OperazioneNonConsentitaException;
use App\Models\Collega;
use App\Models\Commessa;
use App\Models\Consegna;
use App\Models\Indumento;
use App\Models\IndumentoVariante;
use App\Models\MovimentoMagazzino;
use App\Models\User;
use App\Services\ConsegnaService;
use App\Services\MagazzinoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class ConsegnaServiceTest extends TestCase
{
    use RefreshDatabase;

    private ConsegnaService $consegne;

    private User $operatore;

    private Collega $collega;

    private Commessa $commessa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consegne = app(ConsegnaService::class);
        $this->operatore = User::factory()->create();
        $this->collega = Collega::factory()->create();
        $this->commessa = Commessa::factory()->create();
    }

    public function test_registra_consegna_con_numero_snapshot_e_scarico(): void
    {
        $indumento = Indumento::factory()->create(['codice' => 'POL-1', 'nome' => 'Polo']);
        $m = IndumentoVariante::factory()->for($indumento)->create(['taglia' => 'M']);
        $l = IndumentoVariante::factory()->for($indumento)->create(['taglia' => 'L']);
        $this->carica([$m->id => 5, $l->id => 2]);
        $data = Carbon::create(2026, 3, 10, 9, 15);

        $consegna = $this->consegne->registra($this->collega, $this->commessa, [
            ['variante_id' => $m->id, 'quantita' => 1],
            ['variante_id' => $l->id, 'quantita' => 2],
            ['variante_id' => $m->id, 'quantita' => '2'],
        ], $this->operatore, $data, ' Taglia M per il cantiere ');

        $this->assertSame('2026-00001', $consegna->numero);
        $this->assertSame(StatoConsegna::Confermata, $consegna->fresh()->stato);
        $this->assertSame('Taglia M per il cantiere', $consegna->note);
        $this->assertTrue($consegna->data_consegna->equalTo($data));

        $righe = $consegna->righe()->orderBy('variante_id')->get();
        $this->assertSame([3, 2], $righe->pluck('quantita')->all());
        $this->assertSame(['POL-1', 'Polo', 'M'], [$righe[0]->indumento_codice, $righe[0]->indumento_nome, $righe[0]->taglia]);

        $this->assertSame(2, $m->fresh()->giacenza);
        $this->assertSame(0, $l->fresh()->giacenza);
        $this->assertSame(2, $consegna->movimenti()->where('tipo', TipoMovimento::ScaricoConsegna)->count());
        $this->assertTrue(app(MagazzinoService::class)->discrepanze()->isEmpty());
    }

    public function test_la_numerazione_e_progressiva_e_riparte_ogni_anno(): void
    {
        $v = IndumentoVariante::factory()->create();
        $this->carica([$v->id => 10]);
        $riga = [['variante_id' => $v->id, 'quantita' => 1]];

        $numeri = collect([
            Carbon::create(2025, 12, 30, 10),
            Carbon::create(2025, 12, 31, 10),
            Carbon::create(2026, 1, 2, 10),
            Carbon::create(2026, 1, 3, 10),
        ])->map(fn ($data) => $this->consegne->registra($this->collega, $this->commessa, $riga, $this->operatore, $data)->numero);

        $this->assertSame(['2025-00001', '2025-00002', '2026-00001', '2026-00002'], $numeri->all());
    }

    public function test_giacenza_insufficiente_non_salva_nulla_e_non_consuma_il_numero(): void
    {
        $a = IndumentoVariante::factory()->create();
        $b = IndumentoVariante::factory()->create();
        $this->carica([$a->id => 3, $b->id => 1]);

        try {
            $this->consegne->registra($this->collega, $this->commessa, [
                ['variante_id' => $a->id, 'quantita' => 1],
                ['variante_id' => $b->id, 'quantita' => 2],
            ], $this->operatore);
            $this->fail('Attesa GiacenzaInsufficienteException');
        } catch (GiacenzaInsufficienteException $e) {
            $this->assertSame($b->id, $e->mancanze[0]['variante_id']);
        }

        $this->assertSame(0, Consegna::count());
        $this->assertSame(3, $a->fresh()->giacenza);
        $this->assertSame(2, MovimentoMagazzino::count());

        $consegna = $this->consegne->registra($this->collega, $this->commessa, [['variante_id' => $a->id, 'quantita' => 1]], $this->operatore);
        $this->assertSame(1, $consegna->progressivo);
    }

    public function test_stessa_chiave_di_idempotenza_restituisce_la_stessa_consegna(): void
    {
        $v = IndumentoVariante::factory()->create();
        $this->carica([$v->id => 5]);
        $chiave = (string) Str::uuid();
        $riga = [['variante_id' => $v->id, 'quantita' => 2]];

        $prima = $this->consegne->registra($this->collega, $this->commessa, $riga, $this->operatore, idempotencyKey: $chiave);
        $seconda = $this->consegne->registra($this->collega, $this->commessa, $riga, $this->operatore, idempotencyKey: $chiave);

        $this->assertTrue($prima->is($seconda));
        $this->assertSame(1, Consegna::count());
        $this->assertSame(3, $v->fresh()->giacenza);
    }

    public function test_rifiuta_collega_commessa_o_taglie_disattivati_e_date_future(): void
    {
        $v = IndumentoVariante::factory()->create();
        $spenta = IndumentoVariante::factory()->create(['attiva' => false]);
        $this->carica([$v->id => 5, $spenta->id => 5]);
        $riga = [['variante_id' => $v->id, 'quantita' => 1]];

        $casi = [
            fn () => $this->consegne->registra(Collega::factory()->create(['attivo' => false]), $this->commessa, $riga, $this->operatore),
            fn () => $this->consegne->registra($this->collega, Commessa::factory()->create(['attiva' => false]), $riga, $this->operatore),
            fn () => $this->consegne->registra($this->collega, $this->commessa, [['variante_id' => $spenta->id, 'quantita' => 1]], $this->operatore),
        ];

        foreach ($casi as $caso) {
            try {
                $caso();
                $this->fail('Attesa OperazioneNonConsentitaException');
            } catch (OperazioneNonConsentitaException) {
                // atteso
            }
        }

        try {
            $this->consegne->registra($this->collega, $this->commessa, $riga, $this->operatore, now()->addHour());
            $this->fail('Attesa InvalidArgumentException');
        } catch (InvalidArgumentException) {
            // atteso
        }

        $this->assertSame(0, Consegna::count());
    }

    public function test_annullamento_ripristina_le_giacenze_e_non_si_ripete(): void
    {
        $v = IndumentoVariante::factory()->create();
        $this->carica([$v->id => 5]);
        $consegna = $this->consegne->registra($this->collega, $this->commessa, [['variante_id' => $v->id, 'quantita' => 2]], $this->operatore);
        $altroOperatore = User::factory()->create();

        $annullata = $this->consegne->annulla($consegna, $altroOperatore, '  Taglia errata  ');

        $this->assertTrue($annullata->isAnnullata());
        $this->assertSame('Taglia errata', $annullata->motivo_annullamento);
        $this->assertSame($altroOperatore->id, $annullata->annullata_da);
        $this->assertNotNull($annullata->annullata_at);
        $this->assertSame(5, $v->fresh()->giacenza);
        $this->assertDatabaseHas('movimenti_magazzino', [
            'tipo' => TipoMovimento::CompensazioneAnnullamento->value,
            'quantita' => 2,
            'origine_type' => 'consegna',
            'origine_id' => $consegna->id,
            'user_id' => $altroOperatore->id,
        ]);
        $this->assertSame(2, (int) $consegna->righe()->sum('quantita'));

        $this->expectException(OperazioneNonConsentitaException::class);
        $this->consegne->annulla($consegna, $this->operatore, 'Di nuovo');
    }

    public function test_annullamento_richiede_motivazione(): void
    {
        $v = IndumentoVariante::factory()->create();
        $this->carica([$v->id => 1]);
        $consegna = $this->consegne->registra($this->collega, $this->commessa, [['variante_id' => $v->id, 'quantita' => 1]], $this->operatore);

        try {
            $this->consegne->annulla($consegna, $this->operatore, '   ');
            $this->fail('Attesa InvalidArgumentException');
        } catch (InvalidArgumentException) {
            $this->assertFalse($consegna->fresh()->isAnnullata());
            $this->assertSame(0, $v->fresh()->giacenza);
        }
    }

    /** @param array<int, int> $quantita */
    private function carica(array $quantita): void
    {
        app(MagazzinoService::class)->registraMovimenti(
            TipoMovimento::Carico,
            collect($quantita)->map(fn ($q, $id) => ['variante_id' => $id, 'quantita' => $q]),
            $this->operatore,
            data: Carbon::create(2025, 1, 1),
        );
    }
}
