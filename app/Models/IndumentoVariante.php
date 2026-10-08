<?php

namespace App\Models;

use App\Enums\StatoScorta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * Variante (taglia) di un indumento.
 *
 * La colonna `giacenza` non è assegnabile e non può essere modificata tramite
 * il model: viene aggiornata esclusivamente da MagazzinoService con una query
 * diretta, nella stessa transazione che registra il movimento.
 */
class IndumentoVariante extends Model
{
    use HasFactory;

    protected $table = 'indumento_varianti';

    protected $fillable = ['indumento_id', 'taglia', 'ordinamento', 'scorta_minima', 'attiva'];

    protected $attributes = [
        'giacenza' => 0,
        'ordinamento' => 0,
        'scorta_minima' => 0,
        'attiva' => true,
    ];

    protected function casts(): array
    {
        return [
            'giacenza' => 'integer',
            'scorta_minima' => 'integer',
            'ordinamento' => 'integer',
            'attiva' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $variante) {
            if ((int) $variante->giacenza !== 0) {
                throw new LogicException('Una nuova variante parte da giacenza zero: usare un carico.');
            }
        });

        static::updating(function (self $variante) {
            if ($variante->isDirty('giacenza')) {
                throw new LogicException('La giacenza si modifica solo tramite le operazioni di magazzino.');
            }
        });
    }

    public function indumento(): BelongsTo
    {
        return $this->belongsTo(Indumento::class, 'indumento_id');
    }

    public function movimenti(): HasMany
    {
        return $this->hasMany(MovimentoMagazzino::class, 'variante_id');
    }

    public function statoScorta(): StatoScorta
    {
        return StatoScorta::per($this->giacenza, $this->scorta_minima);
    }

    public function scopeAttive(Builder $query): void
    {
        $query->where('attiva', true);
    }

    /** Filtra per stato di disponibilità, con le stesse soglie di StatoScorta::per(). */
    public function scopeConStato(Builder $query, StatoScorta $stato): void
    {
        $giacenza = $query->qualifyColumn('giacenza');
        $scorta = $query->qualifyColumn('scorta_minima');

        match ($stato) {
            StatoScorta::Esaurito => $query->where($giacenza, '<=', 0),
            StatoScorta::ScortaBassa => $query->where($giacenza, '>', 0)->whereColumn($giacenza, '<=', $scorta),
            StatoScorta::Disponibile => $query->where($giacenza, '>', 0)->whereColumn($giacenza, '>', $scorta),
        };
    }

    /** Varianti attive (di indumenti attivi) che richiedono attenzione: esaurite o sotto scorta. */
    public function scopeDaRiordinare(Builder $query): void
    {
        $query->where('attiva', true)
            ->whereColumn('giacenza', '<=', 'scorta_minima')
            ->whereHas('indumento', fn (Builder $q) => $q->where('attivo', true));
    }
}
