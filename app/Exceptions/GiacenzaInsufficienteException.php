<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Sollevata quando uno scarico supera la giacenza disponibile.
 * Il messaggio è pensato per essere mostrato direttamente all'operatore.
 */
class GiacenzaInsufficienteException extends RuntimeException
{
    /**
     * @param  array<int, array{variante_id: int, descrizione: string, disponibile: int, richiesta: int}>  $mancanze
     */
    public function __construct(public readonly array $mancanze)
    {
        $dettagli = collect($mancanze)
            ->map(fn (array $m) => "{$m['descrizione']}: richiesti {$m['richiesta']}, disponibili {$m['disponibile']}")
            ->implode('; ');

        parent::__construct("Giacenza insufficiente. {$dettagli}.");
    }
}
