<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Aggiunge vincoli CHECK a livello database.
 *
 * Lo schema builder di Laravel non gestisce i CHECK: vengono applicati solo
 * su MySQL 8 / MariaDB. Su SQLite (usato dai test in memoria) il vincolo
 * viene saltato e l'integrità resta garantita dai service applicativi.
 */
class CheckConstraint
{
    public static function add(string $table, string $name, string $expression): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` CHECK ({$expression})");
    }
}
