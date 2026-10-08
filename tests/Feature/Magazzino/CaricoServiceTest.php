<?php

namespace Tests\Feature\Magazzino;

use App\Models\Carico;
use App\Models\Indumento;
use App\Models\IndumentoVariante;
use App\Models\MovimentoMagazzino;
use App\Models\User;
use App\Services\CaricoService;
use App\Services\MagazzinoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CaricoServiceTest extends TestCase
{
    use RefreshDatabase;

    private CaricoService $carichi;

    private User $operatore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carichi = app(CaricoService::class);
        $this->operatore = User::factory()->create();
    }

    public function test_registra_testata_righe_aggregate_movimenti_e_giacenze(): void
    {
        $a = IndumentoVariante::factory()->create();
        $b = IndumentoVariante::factory()->create();
        $data = now()->subDay()->setTime(9, 0);

        $carico = $this->carichi->registra([
            ['variante_id' => $a->id, 'quantita' => 4],
            ['variante_id' => $b->id, 'quantita' => 2],
            ['variante_id' => $a->id, 'quantita' => '6'],
        ], $this->operatore, $data, '  DDT 123  ');

        $this->assertSame('DDT 123', $carico->note);
        $this->assertTrue($carico->data_carico->equalTo($data));
        $this->assertCount(2, $carico->righe);
        $this->assertSame(10, $carico->righe->firstWhere('variante_id', $a->id)->quantita);

        $this->assertSame(10, $a->fresh()->giacenza);
        $this->assertSame(2, $b->fresh()->giacenza);

        $movimenti = MovimentoMagazzino::where('origine_type', 'carico')->where('origine_id', $carico->id)->get();
        $this->assertCount(2, $movimenti);
        $this->assertTrue($movimenti->every(fn ($m) => $m->data_movimento->equalTo($data)));
        $this->assertTrue(app(MagazzinoService::class)->discrepanze()->isEmpty());
    }

    public function test_rifiuta_un_carico_senza_righe(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->carichi->registra([], $this->operatore);
    }

    public function test_rifiuta_una_data_futura(): void
    {
        $variante = IndumentoVariante::factory()->create();

        try {
            $this->carichi->registra([['variante_id' => $variante->id, 'quantita' => 1]], $this->operatore, now()->addHour());
            $this->fail('Attesa InvalidArgumentException');
        } catch (InvalidArgumentException) {
            $this->assertSame(0, Carico::count());
        }
    }

    public function test_rifiuta_taglie_o_indumenti_disattivati_senza_scrivere_nulla(): void
    {
        $attiva = IndumentoVariante::factory()->create();
        $inattiva = IndumentoVariante::factory()->create(['attiva' => false]);
        $diIndumentoInattivo = IndumentoVariante::factory()
            ->for(Indumento::factory()->state(['attivo' => false]))
            ->create();

        foreach ([$inattiva, $diIndumentoInattivo] as $variante) {
            try {
                $this->carichi->registra([
                    ['variante_id' => $attiva->id, 'quantita' => 1],
                    ['variante_id' => $variante->id, 'quantita' => 1],
                ], $this->operatore);
                $this->fail('Attesa InvalidArgumentException');
            } catch (InvalidArgumentException) {
                // atteso
            }
        }

        $this->assertSame(0, Carico::count());
        $this->assertSame(0, MovimentoMagazzino::count());
        $this->assertSame(0, $attiva->fresh()->giacenza);
    }
}
