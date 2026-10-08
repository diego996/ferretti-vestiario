<?php

namespace App\Console\Commands;

use App\Services\MagazzinoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Procedura amministrativa: confronta la giacenza di ogni variante con la
 * somma dei movimenti. Non corregge nulla: segnala soltanto.
 */
class VerificaGiacenze extends Command
{
    protected $signature = 'magazzino:verifica';

    protected $description = 'Verifica che le giacenze coincidano con la somma dei movimenti';

    public function handle(MagazzinoService $magazzino): int
    {
        $discrepanze = $magazzino->discrepanze();

        if ($discrepanze->isEmpty()) {
            $this->components->info('Giacenze allineate con i movimenti.');

            return self::SUCCESS;
        }

        $this->components->error("Trovate {$discrepanze->count()} varianti non allineate.");
        $this->table(
            ['Variante', 'Giacenza', 'Saldo movimenti', 'Differenza'],
            $discrepanze->map(fn ($d) => [$d->id, $d->giacenza, $d->saldo_movimenti, $d->giacenza - $d->saldo_movimenti]),
        );

        Log::warning('Discrepanze giacenze/movimenti rilevate', ['varianti' => $discrepanze->pluck('id')->all()]);

        return self::FAILURE;
    }
}
