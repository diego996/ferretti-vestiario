<?php

namespace Database\Seeders;

use App\Models\CategoriaIndumento;
use Illuminate\Database\Seeder;

/**
 * Categorie di partenza, modificabili dall'anagrafica. Idempotente:
 * può essere eseguito anche in produzione senza duplicare i dati.
 */
class CategorieInizialiSeeder extends Seeder
{
    public const CATEGORIE = [
        'Pantaloni',
        'Polo e magliette',
        'Felpe',
        'Giacche',
        'Scarpe antinfortunistiche',
        'Altri DPI',
    ];

    public function run(): void
    {
        foreach (self::CATEGORIE as $ordine => $nome) {
            CategoriaIndumento::firstOrCreate(['nome' => $nome], ['ordinamento' => ($ordine + 1) * 10]);
        }
    }
}
