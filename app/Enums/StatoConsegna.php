<?php

namespace App\Enums;

enum StatoConsegna: string
{
    case Confermata = 'confermata';
    case Annullata = 'annullata';

    public function label(): string
    {
        return match ($this) {
            self::Confermata => 'Confermata',
            self::Annullata => 'Annullata',
        };
    }

    /** Variante del componente badge. */
    public function tone(): string
    {
        return match ($this) {
            self::Confermata => 'success',
            self::Annullata => 'danger',
        };
    }
}
