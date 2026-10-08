<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Categorie iniziali sempre; dati dimostrativi solo fuori produzione.
     * Gli operatori si creano con: php artisan vestiario:crea-utente
     */
    public function run(): void
    {
        $this->call(CategorieInizialiSeeder::class);

        if (! app()->isProduction()) {
            $this->call(DemoSeeder::class);
        }
    }
}
