<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Commessa extends Model
{
    use HasFactory;

    protected $table = 'commesse';

    protected $fillable = ['codice', 'nome', 'descrizione', 'attiva'];

    protected $attributes = [
        'attiva' => true,
    ];

    protected function casts(): array
    {
        return [
            'attiva' => 'boolean',
        ];
    }

    /** "CODICE · Nome" oppure solo il nome se il codice manca. */
    protected function etichetta(): Attribute
    {
        return Attribute::get(fn () => $this->codice ? "{$this->codice} · {$this->nome}" : $this->nome);
    }

    public function consegne(): HasMany
    {
        return $this->hasMany(Consegna::class, 'commessa_id');
    }

    public function scopeAttive(Builder $query): void
    {
        $query->where('attiva', true);
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
