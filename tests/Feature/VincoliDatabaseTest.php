<?php

namespace Tests\Feature;

use App\Models\IndumentoVariante;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Vincoli a livello database: ultima difesa contro scritture fuori dai service.
 * I CHECK esistono solo su MySQL/MariaDB (eseguire la suite con DB_CONNECTION=mariadb).
 */
class VincoliDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_combinazione_indumento_taglia_e_univoca(): void
    {
        $variante = IndumentoVariante::factory()->create(['taglia' => 'M']);

        $this->expectException(QueryException::class);

        IndumentoVariante::factory()->create(['indumento_id' => $variante->indumento_id, 'taglia' => 'M']);
    }

    public function test_la_giacenza_non_puo_diventare_negativa(): void
    {
        $this->soloMysql();
        $variante = IndumentoVariante::factory()->create();

        $this->expectException(QueryException::class);

        DB::statement('UPDATE indumento_varianti SET giacenza = giacenza - 1 WHERE id = ?', [$variante->id]);
    }

    public function test_un_movimento_non_puo_avere_quantita_zero_o_tipo_sconosciuto(): void
    {
        $this->soloMysql();
        $variante = IndumentoVariante::factory()->create();
        $base = [
            'variante_id' => $variante->id,
            'giacenza_risultante' => 0,
            'user_id' => User::factory()->create()->id,
            'data_movimento' => now(),
        ];

        try {
            DB::table('movimenti_magazzino')->insert($base + ['tipo' => 'carico', 'quantita' => 0]);
            $this->fail('Quantità zero accettata');
        } catch (QueryException) {
        }

        $this->expectException(QueryException::class);
        DB::table('movimenti_magazzino')->insert($base + ['tipo' => 'regalo', 'quantita' => 1]);
    }

    private function soloMysql(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Vincoli CHECK verificabili solo su MySQL/MariaDB.');
        }
    }
}
