<?php

namespace Tests\Feature\Magazzino;

use App\Livewire\Carichi\NuovoCarico;
use App\Models\Carico;
use App\Models\IndumentoVariante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NuovoCaricoTest extends TestCase
{
    use RefreshDatabase;

    private User $operatore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operatore = User::factory()->create();
        $this->actingAs($this->operatore);
    }

    public function test_la_pagina_si_apre_anche_con_catalogo_vuoto(): void
    {
        $this->get('/carichi/nuovo')
            ->assertOk()
            ->assertSee('Nuovo carico')
            ->assertSee('Nessun indumento caricabile');
    }

    public function test_un_carico_valido_aggiorna_le_giacenze_e_azzera_il_modulo(): void
    {
        $m = IndumentoVariante::factory()->create(['taglia' => 'M']);
        $l = IndumentoVariante::factory()->create(['taglia' => 'L', 'indumento_id' => $m->indumento_id]);

        Livewire::test(NuovoCarico::class)
            ->assertSee($m->indumento->nome)
            ->set('note', 'DDT 77')
            ->set('righe.0.indumento_id', (string) $m->indumento_id)
            ->set('righe.0.variante_id', (string) $m->id)
            ->set('righe.0.quantita', '5')
            ->call('aggiungiRiga')
            ->set('righe.1.indumento_id', (string) $m->indumento_id)
            ->set('righe.1.variante_id', (string) $l->id)
            ->set('righe.1.quantita', '3')
            ->assertSee('8')
            ->call('conferma')
            ->assertHasNoErrors()
            ->assertDispatched('toast')
            ->assertSet('righe', [['indumento_id' => '', 'variante_id' => '', 'quantita' => '']])
            ->assertSet('note', '');

        $this->assertSame(5, $m->fresh()->giacenza);
        $this->assertSame(3, $l->fresh()->giacenza);
        $this->assertSame('DDT 77', Carico::sole()->note);
        $this->assertSame($this->operatore->id, Carico::sole()->user_id);
    }

    public function test_cambiare_indumento_azzera_la_taglia(): void
    {
        $a = IndumentoVariante::factory()->create();
        $b = IndumentoVariante::factory()->create();

        Livewire::test(NuovoCarico::class)
            ->set('righe.0.indumento_id', (string) $a->indumento_id)
            ->set('righe.0.variante_id', (string) $a->id)
            ->set('righe.0.indumento_id', (string) $b->indumento_id)
            ->assertSet('righe.0.variante_id', '');
    }

    public function test_righe_incomplete_quantita_non_valide_e_data_futura_sono_rifiutate(): void
    {
        $v = IndumentoVariante::factory()->create();

        Livewire::test(NuovoCarico::class)
            ->set('dataCarico', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('righe.0.indumento_id', (string) $v->indumento_id)
            ->set('righe.0.quantita', '0')
            ->call('conferma')
            ->assertHasErrors(['dataCarico', 'righe.0.variante_id', 'righe.0.quantita']);

        $this->assertSame(0, Carico::count());
    }

    public function test_una_taglia_disattivata_non_si_puo_caricare(): void
    {
        $v = IndumentoVariante::factory()->create(['attiva' => false]);

        Livewire::test(NuovoCarico::class)
            ->set('righe.0.indumento_id', (string) $v->indumento_id)
            ->set('righe.0.variante_id', (string) $v->id)
            ->set('righe.0.quantita', '2')
            ->call('conferma')
            ->assertHasErrors('righe.0.variante_id');

        $this->assertSame(0, $v->fresh()->giacenza);
    }

    public function test_rimuovere_l_ultima_riga_ne_lascia_una_vuota(): void
    {
        Livewire::test(NuovoCarico::class)
            ->call('rimuoviRiga', 0)
            ->assertCount('righe', 1);
    }
}
