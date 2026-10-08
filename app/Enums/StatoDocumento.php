<?php

namespace App\Enums;

enum StatoDocumento: string
{
    case InAttesa = 'in_attesa';
    case Generato = 'generato';
    case Fallito = 'fallito';

    public function label(): string
    {
        return match ($this) {
            self::InAttesa => 'In attesa',
            self::Generato => 'Generato',
            self::Fallito => 'Generazione fallita',
        };
    }
}
