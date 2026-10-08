<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Indumento extends Model
{
    use HasFactory;

    protected $table = 'indumenti';

    protected $fillable = ['categoria_id', 'codice', 'nome', 'descrizione', 'attivo'];

    protected $attributes = [
        'attivo' => true,
    ];

    protected function casts(): array
    {
        return [
            'attivo' => 'boolean',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaIndumento::class, 'categoria_id');
    }

    public function varianti(): HasMany
    {
        return $this->hasMany(IndumentoVariante::class, 'indumento_id')
            ->orderBy('ordinamento')
            ->orderBy('taglia');
    }

    public function scopeAttivi(Builder $query): void
    {
        $query->where('attivo', true);
    }

    /** Ricerca per nome o codice: ogni parola deve comparire in almeno uno dei due. */
    public function scopeCerca(Builder $query, ?string $testo): void
    {
        foreach (Str::of((string) $testo)->squish()->explode(' ')->filter() as $parola) {
            $query->where(fn (Builder $q) => $q
                ->where($q->qualifyColumn('nome'), 'like', "%{$parola}%")
                ->orWhere($q->qualifyColumn('codice'), 'like', "%{$parola}%"));
        }
    }
}
