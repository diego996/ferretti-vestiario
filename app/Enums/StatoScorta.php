<?php

namespace App\Enums;

/**
 * Stato di disponibilità di una variante.
 *
 * Esaurito:     giacenza = 0
 * Scorta bassa: 0 < giacenza <= scorta minima
 * Disponibile:  giacenza > scorta minima
 */
enum StatoScorta: string
{
    case Disponibile = 'disponibile';
    case ScortaBassa = 'scorta_bassa';
    case Esaurito = 'esaurito';

    public static function per(int $giacenza, int $scortaMinima): self
    {
        return match (true) {
            $giacenza <= 0 => self::Esaurito,
            $giacenza <= $scortaMinima => self::ScortaBassa,
            default => self::Disponibile,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Disponibile => 'Disponibile',
            self::ScortaBassa => 'Scorta bassa',
            self::Esaurito => 'Esaurito',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Disponibile => 'success',
            self::ScortaBassa => 'warning',
            self::Esaurito => 'danger',
        };
    }
}
