<?php

namespace Tests\Feature\Consegne;

use App\Enums\TipoMovimento;
use App\Livewire\Consegne\Dettaglio;
use App\Livewire\Consegne\Index;
use App\Livewire\Consegne\NuovaConsegna;
use App\Models\Collega;
use App\Models\Commessa;
use App\Models\Consegna;
use App\Models\Indumento;
use App\Models\IndumentoVariante;
use App\Models\User;
use App\Services\ConsegnaService;
use App\Services\MagazzinoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PagineConsegneTest extends TestCase
{
    use RefreshDatabase;

    private User $operatore;

    private Collega $collega;

    private Commessa $commessa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operatore = User::factory()->create();
        $this->actingAs($this->operatore);
        $this->collega = Collega::factory()->create(['cognome' => 'Rossi', 'nome' => 'Anna']);
        $this->commessa = Commessa::factory()->create(['codice' => 'C-77', 'nome' => 'Cantiere Nord']);
    }

    public function test_le_pagine_si_aprono_e_il_menu_le_abilita(): void
    {
        $consegna = $this->consegnaDi(2);

        $this->get('/consegne')->assertOk()->assertSee($consegna->numero)->assertSee('Rossi Anna');
        $this->get('/consegne/nuova')->assertOk()->assertSee('Articoli consegnati');
        $this->get("/consegne/{$consegna->id}")->assertOk()->assertSee('Cantiere Nord')->assertSee('Annulla consegna');
        $this->get('/dashboard')->assertSee('href="'.route('consegne.show', $consegna).'"', escape: false)
            ->assertDontSee('Disponibile dalla Fase 3');
    }

    public function test_nuova_consegna_completa_con_ricerche_e_reindirizzamento(): void
    {
        $indumento = Indumento::factory()->create(['nome' => 'Polo blu', 'codice' => 'POL-1']);
        $m = IndumentoVariante::factory()->for($indumento)->create(['taglia' => 'M']);
        $l = IndumentoVariante::factory()->for($indumento)->create(['taglia' => 'L']);
        $this->carica([$m->id => 4, $l->id => 1]);

        $pagina = Livewire::test(NuovaConsegna::class)
            ->set('cercaCollega', 'ross')
            ->assertSee('Rossi Anna')
            ->call('scegliPrimoCollega')
            ->assertSet('collegaId', $this->collega->id)
            ->set('cercaCommessa', 'C-77')
            ->call('scegliPrimaCommessa')
            ->assertSet('commessaId', $this->commessa->id)
            ->set('cercaArticolo', 'pol')
            ->assertSee('5 disp.')
            ->call('aggiungiPrimoArticolo')
            ->assertSet('cercaArticolo', '')
            ->assertSet('righe.0.variante_id', '')
            ->set('righe.0.variante_id', (string) $m->id)
            ->set('righe.0.quantita', '3')
            ->call('conferma')
            ->assertHasNoErrors();

        $consegna = Consegna::sole();
        $pagina->assertRedirect(route('consegne.show', $consegna));
        $this->assertSame($this->collega->id, $consegna->collega_id);
        $this->assertSame(1, $m->fresh()->giacenza);
    }

    public function test_un_articolo_con_una_sola_taglia_disponibile_la_propone(): void
    {
        $unica = IndumentoVariante::factory()->create(['taglia' => 'Unica']);
        $this->carica([$unica->id => 2]);

        Livewire::test(NuovaConsegna::class)
            ->call('aggiungiArticolo', $unica->indumento_id)
            ->assertSet('righe.0.variante_id', (string) $unica->id)
            ->assertSet('righe.0.quantita', '1');
    }

    public function test_campi_obbligatori_e_quantita_oltre_disponibilita(): void
    {
        $v = IndumentoVariante::factory()->create();
        $this->carica([$v->id => 2]);

        Livewire::test(NuovaConsegna::class)
            ->call('conferma')
            ->assertHasErrors(['collegaId', 'commessaId', 'righe'])
            ->call('scegliCollega', $this->collega->id)
            ->call('scegliCommessa', $this->commessa->id)
            ->call('aggiungiArticolo', $v->indumento_id)
            ->call('aggiungiArticolo', $v->indumento_id)
            ->set('righe.0.variante_id', (string) $v->id)
            ->set('righe.1.variante_id', (string) $v->id)
            ->set('righe.0.quantita', '2')
            ->set('righe.1.quantita', '1')
            ->assertSee('Oltre la disponibilità')
            ->call('conferma')
            ->assertHasErrors(['righe.0.quantita', 'righe.1.quantita']);

        $this->assertSame(0, Consegna::count());
    }

    public function test_disponibilita_cambiata_nel_frattempo_viene_segnalata(): void
    {
        $v = IndumentoVariante::factory()->create();
        $this->carica([$v->id => 2]);

        $pagina = Livewire::test(NuovaConsegna::class)
            ->call('scegliCollega', $this->collega->id)
            ->call('scegliCommessa', $this->commessa->id)
            ->call('aggiungiArticolo', $v->indumento_id)
            ->set('righe.0.variante_id', (string) $v->id)
            ->set('righe.0.quantita', '2');

        // Un altro operatore consegna nel frattempo.
        app(ConsegnaService::class)->registra($this->collega, $this->commessa, [['variante_id' => $v->id, 'quantita' => 1]], $this->operatore);

        $pagina->call('conferma')->assertHasErrors('righe.0.quantita');
        $this->assertSame(1, Consegna::count());
    }

    public function test_doppio_invio_crea_una_sola_consegna(): void
    {
        $v = IndumentoVariante::factory()->create();
        $this->carica([$v->id => 5]);

        $pagina = Livewire::test(NuovaConsegna::class)
            ->call('scegliCollega', $this->collega->id)
            ->call('scegliCommessa', $this->commessa->id)
            ->call('aggiungiArticolo', $v->indumento_id)
            ->set('righe.0.variante_id', (string) $v->id);

        $pagina->call('conferma');
        $pagina->call('conferma');

        $this->assertSame(1, Consegna::count());
        $this->assertSame(4, $v->fresh()->giacenza);
    }

    public function test_colleghi_e_commesse_disattivati_non_si_possono_scegliere(): void
    {
        $spento = Collega::factory()->create(['cognome' => 'Spento', 'attivo' => false]);

        Livewire::test(NuovaConsegna::class)
            ->set('cercaCollega', 'Spento')
            ->assertSee('Nessun collega attivo trovato');

        Livewire::withQueryParams(['collega' => $spento->id])
            ->test(NuovaConsegna::class)
            ->assertSet('collegaId', null);

        Livewire::withQueryParams(['collega' => $this->collega->id])
            ->test(NuovaConsegna::class)
            ->assertSet('collegaId', $this->collega->id);
    }

    public function test_annullamento_dal_dettaglio(): void
    {
        $consegna = $this->consegnaDi(2);
        $variante = $consegna->righe->first()->variante;

        Livewire::test(Dettaglio::class, ['consegna' => $consegna])
            ->call('annulla')
            ->assertHasErrors('motivo')
            ->set('motivo', 'Taglia sbagliata')
            ->call('annulla')
            ->assertHasNoErrors()
            ->assertDispatched('toast')
            ->assertSee('Consegna annullata')
            ->assertSee('Taglia sbagliata')
            ->assertDontSee('Conferma annullamento');

        $this->assertTrue($consegna->fresh()->isAnnullata());
        $this->assertSame(5, $variante->fresh()->giacenza);
    }

    public function test_elenco_filtra_per_stato_collega_commessa_e_numero(): void
    {
        $prima = $this->consegnaDi(1);
        $altroCollega = Collega::factory()->create(['cognome' => 'Verdi']);
        $seconda = app(ConsegnaService::class)->registra($altroCollega, Commessa::factory()->create(), [['variante_id' => $prima->righe->first()->variante_id, 'quantita' => 1]], $this->operatore);
        app(ConsegnaService::class)->annulla($seconda, $this->operatore, 'Errore');

        $pagina = Livewire::test(Index::class);
        $this->assertSame(2, $pagina->get('consegne')->total());
        $this->assertSame([$seconda->id], $pagina->set('stato', 'annullata')->get('consegne')->pluck('id')->all());
        $this->assertSame([$prima->id], $pagina->call('azzeraFiltri')->set('collega', (string) $this->collega->id)->get('consegne')->pluck('id')->all());
        $this->assertSame([$prima->id], $pagina->call('azzeraFiltri')->set('commessa', (string) $this->commessa->id)->get('consegne')->pluck('id')->all());
        $this->assertSame([$seconda->id], $pagina->call('azzeraFiltri')->set('cerca', 'verdi')->get('consegne')->pluck('id')->all());
        $this->assertSame([$prima->id], $pagina->set('cerca', $prima->numero)->get('consegne')->pluck('id')->all());
    }

    private function consegnaDi(int $quantita): Consegna
    {
        $v = IndumentoVariante::factory()->create();
        $this->carica([$v->id => 5]);

        return app(ConsegnaService::class)
            ->registra($this->collega, $this->commessa, [['variante_id' => $v->id, 'quantita' => $quantita]], $this->operatore)
            ->load('righe.variante');
    }

    /** @param array<int, int> $quantita */
    private function carica(array $quantita): void
    {
        app(MagazzinoService::class)->registraMovimenti(
            TipoMovimento::Carico,
            collect($quantita)->map(fn ($q, $id) => ['variante_id' => $id, 'quantita' => $q]),
            $this->operatore,
            data: now()->subMonth(),
        );
    }
}
