<?php

namespace Tests\Feature;

use App\Models\Collega;
use App\Models\Consegna;
use App\Models\MovimentoMagazzino;
use App\Models\User;
use App\Services\MagazzinoService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_i_dati_demo_sono_coerenti_con_i_movimenti(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertGreaterThan(0, Consegna::count());
        $this->assertGreaterThan(0, MovimentoMagazzino::count());
        $this->assertSame(2, Consegna::where('stato', 'annullata')->count());
        $this->assertTrue(app(MagazzinoService::class)->discrepanze()->isEmpty());
    }

    public function test_l_utente_demo_non_puo_accedere(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertFalse(User::where('email', 'operatore.demo@example.invalid')->value('attivo'));
    }

    public function test_il_seeder_e_rieseguibile_senza_duplicare_le_anagrafiche(): void
    {
        $this->seed(DemoSeeder::class);
        $colleghi = Collega::count();

        $this->seed(DemoSeeder::class);

        $this->assertSame($colleghi, Collega::count());
        $this->assertTrue(app(MagazzinoService::class)->discrepanze()->isEmpty());
    }
}
