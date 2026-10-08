<?php

namespace Tests\Feature\Magazzino;

use App\Enums\TipoMovimento;
use App\Livewire\Magazzino\Index;
use App\Models\Indumento;
use App\Models\IndumentoVariante;
use App\Models\MovimentoMagazzino;
use App\Models\User;
use App\Services\MagazzinoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MagazzinoPaginaTest extends TestCase
{
    use RefreshDatabase;

    private User $operatore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operatore = User::factory()->create();
        $this->actingAs($this->operatore);
    }

    public function test_la_pagina_si_apre_anche_senza_dati(): void
    {
        $this->get('/magazzino')->assertOk()->assertSee('Nessuna taglia trovata');
    }

    public function test_filtri_per_stato_ricerca_e_disattivati(): void
    {
        $polo = Indumento::factory()->create(['nome' => 'Polo blu', 'codice' => 'POL-1']);
        $ok = IndumentoVariante::factory()->for($polo)->create(['taglia' => 'M', 'scorta_minima' => 2]);
        $bassa = IndumentoVariante::factory()->for($polo)->create(['taglia' => 'L', 'scorta_minima' => 5]);
        $esaurita = IndumentoVariante::factory()->create(['taglia' => 'XL']);
        $spenta = IndumentoVariante::factory()->create(['taglia' => 'S', 'attiva' => false]);
        $this->carica([$ok->id => 10, $bassa->id => 3]);

        $pagina = Livewire::test(Index::class)
            ->assertSee('Polo blu')
            ->assertDontSee($spenta->indumento->nome)
            ->assertSet('conteggi', ['' => 3, 'disponibile' => 1, 'scorta_bassa' => 1, 'esaurito' => 1]);

        $this->assertSame([$esaurita->id], $pagina->set('stato', 'esaurito')->get('varianti')->pluck('id')->all());
        $this->assertSame([$bassa->id], $pagina->set('stato', 'scorta_bassa')->get('varianti')->pluck('id')->all());

        $pagina->set('stato', '')->set('cerca', 'pol-1');
        $this->assertEqualsCanonicalizing([$ok->id, $bassa->id], $pagina->get('varianti')->pluck('id')->all());

        $pagina->call('azzeraFiltri')->set('inattivi', true)->assertSee($spenta->indumento->nome);
    }

    public function test_rettifica_positiva_e_negativa_con_motivazione(): void
    {
        $v = IndumentoVariante::factory()->create();
        $this->carica([$v->id => 5]);

        Livewire::test(Index::class)
            ->call('apriVariante', $v->id)
            ->assertSee('Rettifica inventariale')
            ->set('rettificaVerso', 'riduzione')
            ->set('rettificaQuantita', '2')
            ->set('rettificaMotivo', 'Inventario: 2 capi danneggiati')
            ->call('registraRettifica')
            ->assertHasNoErrors()
            ->assertDispatched('toast')
            ->assertSet('rettificaQuantita', '')
            ->assertSee('Inventario: 2 capi danneggiati');

        $this->assertSame(3, $v->fresh()->giacenza);
        $this->assertDatabaseHas('movimenti_magazzino', [
            'variante_id' => $v->id,
            'tipo' => TipoMovimento::RettificaNegativa->value,
            'quantita' => -2,
            'note' => 'Inventario: 2 capi danneggiati',
        ]);
    }

    public function test_la_rettifica_richiede_motivazione_e_non_scende_sotto_zero(): void
    {
        $v = IndumentoVariante::factory()->create();
        $this->carica([$v->id => 1]);

        Livewire::test(Index::class)
            ->call('apriVariante', $v->id)
            ->set('rettificaQuantita', '1')
            ->call('registraRettifica')
            ->assertHasErrors('rettificaMotivo')
            ->set('rettificaVerso', 'riduzione')
            ->set('rettificaQuantita', '4')
            ->set('rettificaMotivo', 'Conteggio')
            ->call('registraRettifica')
            ->assertHasErrors('rettificaQuantita');

        $this->assertSame(1, $v->fresh()->giacenza);
        $this->assertSame(1, MovimentoMagazzino::count());
    }

    /** @param array<int, int> $quantita */
    private function carica(array $quantita): void
    {
        app(MagazzinoService::class)->registraMovimenti(
            TipoMovimento::Carico,
            collect($quantita)->map(fn ($q, $id) => ['variante_id' => $id, 'quantita' => $q]),
            $this->operatore,
        );
    }
}
