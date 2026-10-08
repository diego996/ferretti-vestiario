<?php

namespace Tests\Feature\Anagrafiche;

use App\Enums\TipoMovimento;
use App\Livewire\Catalogo\Categorie;
use App\Livewire\Catalogo\FormIndumento;
use App\Livewire\Catalogo\Index;
use App\Models\CategoriaIndumento;
use App\Models\Indumento;
use App\Models\IndumentoVariante;
use App\Models\User;
use App\Services\MagazzinoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogoTest extends TestCase
{
    use RefreshDatabase;

    private User $operatore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operatore = User::factory()->create();
        $this->actingAs($this->operatore);
    }

    public function test_le_pagine_del_catalogo_si_aprono(): void
    {
        $indumento = IndumentoVariante::factory()->create()->indumento;

        $this->get('/catalogo')->assertOk()->assertSee($indumento->nome);
        $this->get('/catalogo/categorie')->assertOk()->assertSee($indumento->categoria->nome);
        $this->get('/catalogo/nuovo')->assertOk()->assertSee('Nuovo indumento');
        $this->get("/catalogo/{$indumento->id}/modifica")->assertOk()->assertSee('Modifica indumento');
    }

    public function test_crea_un_indumento_con_taglie_da_preset(): void
    {
        $categoria = CategoriaIndumento::factory()->create();

        Livewire::test(FormIndumento::class)
            ->set('categoria_id', (string) $categoria->id)
            ->set('codice', ' POL-9 ')
            ->set('nome', 'Polo tecnica')
            ->call('aggiungiPreset', 'abbigliamento')
            ->assertCount('varianti', 5)
            ->call('aggiungiPreset', 'abbigliamento')
            ->assertCount('varianti', 5)
            ->set('varianti.0.scorta_minima', '4')
            ->call('salva')
            ->assertHasNoErrors()
            ->assertRedirect(route('catalogo.index'));

        $indumento = Indumento::where('codice', 'POL-9')->sole();
        $this->assertSame(['S', 'M', 'L', 'XL', 'XXL'], $indumento->varianti->pluck('taglia')->all());
        $this->assertSame([10, 20, 30, 40, 50], $indumento->varianti->pluck('ordinamento')->all());
        $this->assertSame(4, $indumento->varianti->first()->scorta_minima);
        $this->assertTrue($indumento->varianti->every(fn ($v) => $v->giacenza === 0));
    }

    public function test_validazione_taglie_duplicate_codice_univoco_e_almeno_una_taglia(): void
    {
        $esistente = Indumento::factory()->create(['codice' => 'X-1']);

        Livewire::test(FormIndumento::class)
            ->set('categoria_id', (string) $esistente->categoria_id)
            ->set('codice', 'X-1')
            ->set('nome', 'Doppione')
            ->set('varianti.0.taglia', 'm')
            ->call('aggiungiTaglia', 'M')
            ->call('salva')
            ->assertHasErrors(['codice', 'varianti.0.taglia', 'varianti.1.taglia'])
            ->call('rimuovi', 1)
            ->call('rimuovi', 0)
            ->call('salva')
            ->assertHasErrors('varianti');
    }

    public function test_modifica_riordina_rinomina_ed_elimina_solo_taglie_senza_movimenti(): void
    {
        $indumento = Indumento::factory()->create();
        $s = IndumentoVariante::factory()->for($indumento)->create(['taglia' => 'S', 'ordinamento' => 10]);
        $m = IndumentoVariante::factory()->for($indumento)->create(['taglia' => 'M', 'ordinamento' => 20]);
        $l = IndumentoVariante::factory()->for($indumento)->create(['taglia' => 'L', 'ordinamento' => 30]);
        app(MagazzinoService::class)->registraMovimenti(TipoMovimento::Carico, [['variante_id' => $s->id, 'quantita' => 3]], $this->operatore);

        Livewire::test(FormIndumento::class, ['indumento' => $indumento])
            ->assertSet('varianti.0.movimentata', true)
            ->call('rimuovi', 0)
            ->assertDispatched('toast')
            ->assertCount('varianti', 3)
            ->call('rimuovi', 2)
            ->assertSet('daEliminare', [$l->id])
            // Scambio dei nomi tra due taglie: non deve violare l'indice univoco.
            ->set('varianti.0.taglia', 'M')
            ->set('varianti.1.taglia', 'S')
            ->call('sposta', 1, -1)
            ->set('varianti.1.attiva', false)
            ->call('salva')
            ->assertHasNoErrors();

        $this->assertNull(IndumentoVariante::find($l->id));
        $this->assertSame('S', $m->fresh()->taglia);
        $this->assertSame(10, $m->fresh()->ordinamento);
        $this->assertSame('M', $s->fresh()->taglia);
        $this->assertSame(3, $s->fresh()->giacenza);
        $this->assertFalse($s->fresh()->attiva);
    }

    public function test_elenco_filtra_per_categoria_e_stato(): void
    {
        $attivo = Indumento::factory()->create(['nome' => 'Giacca']);
        $spento = Indumento::factory()->create(['nome' => 'Felpa vecchia', 'attivo' => false]);

        Livewire::test(Index::class)
            ->assertSee('Giacca')->assertDontSee('Felpa vecchia')
            ->set('stato', 'disattivati')
            ->assertSee('Felpa vecchia')->assertDontSee('Giacca')
            ->set('stato', 'tutti')->set('categoria', (string) $attivo->categoria_id)
            ->assertSee('Giacca')->assertDontSee('Felpa vecchia');
    }

    public function test_categorie_crea_e_modifica_con_nome_univoco(): void
    {
        $esistente = CategoriaIndumento::factory()->create(['nome' => 'Guanti']);

        Livewire::test(Categorie::class)
            ->call('nuova')
            ->set('nome', 'Guanti')
            ->call('salva')
            ->assertHasErrors('nome')
            ->set('nome', 'Cappelli')
            ->call('salva')
            ->assertHasNoErrors()
            ->assertSee('Cappelli')
            ->call('modifica', $esistente->id)
            ->set('attiva', false)
            ->call('salva')
            ->assertHasNoErrors();

        $this->assertFalse($esistente->fresh()->attiva);
        $this->assertDatabaseHas('categorie_indumenti', ['nome' => 'Cappelli']);
    }
}
