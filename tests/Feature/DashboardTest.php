<?php

namespace Tests\Feature;

use App\Enums\StatoConsegna;
use App\Enums\TipoMovimento;
use App\Livewire\Dashboard;
use App\Models\Collega;
use App\Models\Commessa;
use App\Models\Consegna;
use App\Models\IndumentoVariante;
use App\Models\User;
use App\Services\MagazzinoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_dashboard_si_apre_anche_senza_dati(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Unità disponibili')
            ->assertSee('Nessuna consegna registrata')
            ->assertSee('Scorte in ordine');
    }

    public function test_i_kpi_sono_calcolati_sui_dati_reali(): void
    {
        $operatore = User::factory()->create();
        $magazzino = app(MagazzinoService::class);

        $ok = IndumentoVariante::factory()->create(['scorta_minima' => 2]);
        $bassa = IndumentoVariante::factory()->create(['scorta_minima' => 5]);
        $esaurita = IndumentoVariante::factory()->create(['scorta_minima' => 1]);
        $inattiva = IndumentoVariante::factory()->create(['attiva' => false]);

        $magazzino->registraMovimenti(TipoMovimento::Carico, [
            ['variante_id' => $ok->id, 'quantita' => 10],
            ['variante_id' => $bassa->id, 'quantita' => 3],
            ['variante_id' => $inattiva->id, 'quantita' => 100],
        ], $operatore);

        Collega::factory()->count(3)->create();
        Collega::factory()->create(['attivo' => false]);

        $consegna = $this->creaConsegna($operatore, now());
        $this->creaConsegna($operatore, now()->subMonths(2));
        $annullata = $this->creaConsegna($operatore, now());
        $annullata->stato = StatoConsegna::Annullata;
        $annullata->save();

        $componente = Livewire::actingAs($operatore)->test(Dashboard::class);

        $this->assertSame(13, $componente->instance()->unitaDisponibili);
        $this->assertSame(1, $componente->instance()->meseCorrente['consegne']);
        $this->assertSame(['totale' => 2, 'esaurite' => 1], $componente->instance()->sottoScorta);
        $this->assertSame(['totale' => 4, 'attivi' => 3], $componente->instance()->colleghi);

        $componente->assertSee($consegna->numero)
            ->assertSee($esaurita->indumento->nome)
            ->assertSee('Esaurito')
            ->assertSee('Scorta bassa');
    }

    public function test_le_funzioni_non_ancora_sviluppate_sono_segnalate_come_future(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertDontSee('disponibile dalla Fase 2')
            ->assertDontSee('disponibile dalla Fase 3')
            ->assertSee('Impostazioni – disponibile dalla Fase 5', escape: false);
    }

    public function test_le_voci_delle_fasi_2_e_3_sono_link_operativi(): void
    {
        $risposta = $this->actingAs(User::factory()->create())->get('/dashboard');

        foreach (['magazzino.index', 'carichi.create', 'colleghi.index', 'commesse.index', 'catalogo.index', 'movimenti.index', 'consegne.index', 'consegne.create'] as $rotta) {
            $risposta->assertSee('href="'.route($rotta).'"', escape: false);
        }
    }

    private function creaConsegna(User $operatore, $data): Consegna
    {
        static $progressivo = 0;
        $progressivo++;

        $consegna = new Consegna([
            'collega_id' => Collega::attivi()->firstOrFail()->id,
            'commessa_id' => Commessa::factory()->create()->id,
            'data_consegna' => $data,
            'user_id' => $operatore->id,
        ]);
        $consegna->anno = $data->year;
        $consegna->progressivo = $progressivo;
        $consegna->numero = Consegna::formattaNumero($data->year, $progressivo);
        $consegna->stato = StatoConsegna::Confermata;
        $consegna->save();

        return $consegna;
    }
}
