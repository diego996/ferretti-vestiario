<?php

namespace App\Models;

use App\Enums\TipoMovimento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * Movimento di magazzino: registro immutabile (append-only).
 * Viene creato solo da MagazzinoService; non è previsto aggiornamento né cancellazione.
 */
class MovimentoMagazzino extends Model
{
    protected $table = 'movimenti_magazzino';

    protected $fillable = [
        'variante_id', 'tipo', 'quantita', 'giacenza_risultante',
        'origine_type', 'origine_id', 'user_id', 'data_movimento', 'note',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoMovimento::class,
            'quantita' => 'integer',
            'giacenza_risultante' => 'integer',
            'data_movimento' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('I movimenti di magazzino non sono modificabili.'));
        static::deleting(fn () => throw new LogicException('I movimenti di magazzino non sono cancellabili.'));
    }

    public function variante(): BelongsTo
    {
        return $this->belongsTo(IndumentoVariante::class, 'variante_id');
    }

    public function operatore(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function origine(): MorphTo
    {
        return $this->morphTo();
    }

    /** Descrizione breve dell'operazione di origine (richiede `origine` caricata). */
    public function descrizioneOrigine(): ?string
    {
        return match (true) {
            $this->origine instanceof Consegna => "Consegna {$this->origine->numero}",
            $this->origine instanceof Carico => 'Carico del '.$this->origine->data_carico->format('d/m/Y'),
            default => null,
        };
    }
}
