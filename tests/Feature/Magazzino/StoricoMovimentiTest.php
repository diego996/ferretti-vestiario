<?php

namespace Tests\Feature\Magazzino;

use App\Livewire\Movimenti\Index;
use App\Models\IndumentoVariante;
use App\Models\User;
use App\Services\CaricoService;
use App\Services\MagazzinoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StoricoMovimentiTest extends TestCase
{
    use RefreshDatabase;

    public function test_elenco_e_filtri(): void
    {
        $operatore = User::factory()->create(['name' => 'Mario Operatore']);
        $this->actingAs($operatore);

        $a = IndumentoVariante::factory()->create();
        $b = IndumentoVariante::factory()->create();

        $vecchio = app(CaricoService::class)->registra([['variante_id' => $a->id, 'quantita' => 4]], $operatore, now()->subDays(10));
        app(CaricoService::class)->registra([['variante_id' => $b->id, 'quantita' => 2]], $operatore);
        app(MagazzinoService::class)->rettifica($a, -1, $operatore, 'Capo rovinato');

        $this->get('/movimenti')->assertOk()->assertSee('Mario Operatore')->assertSee('Capo rovinato');

        $pagina = Livewire::test(Index::class);
        $this->assertSame(3, $pagina->get('movimenti')->total());

        $this->assertSame(1, $pagina->set('tipo', 'rettifica_negativa')->get('movimenti')->total());
        $this->assertSame(2, $pagina->set('tipo', '')->set('dal', now()->subDay()->toDateString())->get('movimenti')->total());
        $this->assertSame(1, $pagina->call('azzeraFiltri')->set('al', now()->subDays(5)->toDateString())->get('movimenti')->total());
        $this->assertSame(2, $pagina->call('azzeraFiltri')->set('variante', (string) $a->id)->get('movimenti')->total());

        $pagina->call('azzeraFiltri')->set('carico', (string) $vecchio->id)->assertSee('Carico del');
        $this->assertSame(1, $pagina->get('movimenti')->total());

        $this->assertSame(3, $pagina->set('carico', '')->set('dal', 'non-una-data')->get('movimenti')->total());
    }
}
