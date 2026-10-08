<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Crea un operatore. Non esiste registrazione pubblica: gli accessi
 * vengono creati solo da chi amministra il server.
 */
class CreaUtente extends Command
{
    protected $signature = 'vestiario:crea-utente';

    protected $description = 'Crea un operatore con accesso al gestionale';

    public function handle(): int
    {
        $dati = [
            'name' => text('Nome e cognome', required: true),
            'email' => text('Email (usata per l\'accesso)', required: true),
            'password' => password('Password (minimo 10 caratteri)', required: true),
        ];

        $validator = Validator::make($dati, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(10)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $errore) {
                $this->components->error($errore);
            }

            return self::FAILURE;
        }

        User::create($validator->validated());

        $this->components->info("Operatore {$dati['email']} creato.");

        return self::SUCCESS;
    }
}
