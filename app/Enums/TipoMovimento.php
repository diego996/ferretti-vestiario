<?php

namespace App\Enums;

enum TipoMovimento: string
{
    case Carico = 'carico';
    case ScaricoConsegna = 'scarico_consegna';
    case RettificaPositiva = 'rettifica_positiva';
    case RettificaNegativa = 'rettifica_negativa';
    case CompensazioneAnnullamento = 'compensazione_annullamento';

    public function label(): string
    {
        return match ($this) {
            self::Carico => 'Carico',
            self::ScaricoConsegna => 'Scarico consegna',
            self::RettificaPositiva => 'Rettifica positiva',
            self::RettificaNegativa => 'Rettifica negativa',
            self::CompensazioneAnnullamento => 'Compensazione annullamento',
        };
    }

    /** Variante del componente badge. */
    public function tone(): string
    {
        return match ($this) {
            self::Carico => 'primary',
            self::ScaricoConsegna => 'neutral',
            self::RettificaPositiva, self::RettificaNegativa => 'warning',
            self::CompensazioneAnnullamento => 'danger',
        };
    }

    /** Segno atteso della quantità: +1 aumenta la giacenza, -1 la riduce. */
    public function segno(): int
    {
        return match ($this) {
            self::Carico, self::RettificaPositiva, self::CompensazioneAnnullamento => 1,
            self::ScaricoConsegna, self::RettificaNegativa => -1,
        };
    }
}
