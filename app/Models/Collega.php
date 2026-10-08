<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Collega extends Model
{
    use HasFactory;

    protected $table = 'colleghi';

    protected $fillable = ['nome', 'cognome', 'matricola', 'attivo'];

    protected $attributes = [
        'attivo' => true,
    ];

    protected function casts(): array
    {
        return [
            'attivo' => 'boolean',
        ];
    }

    /** "Cognome Nome", come negli elenchi cartacei. */
    protected function nominativo(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->cognome} {$this->nome}"));
    }

    public function consegne(): HasMany
    {
        return $this->hasMany(Consegna::class, 'collega_id');
    }

    public function scopeAttivi(Builder $query): void
    {
        $query->where('attivo', true);
    }

    public function scopeOrdinati(Builder $query): void
    {
        $query->orderBy('cognome')->orderBy('nome');
    }

    /** Ricerca per nome, cognome o matricola: ogni parola deve comparire in almeno uno dei campi. */
    public function scopeCerca(Builder $query, ?string $testo): void
    {
        foreach (Str::of((string) $testo)->squish()->explode(' ')->filter() as $parola) {
            $query->where(fn (Builder $q) => $q
                ->where('cognome', 'like', "%{$parola}%")
                ->orWhere('nome', 'like', "%{$parola}%")
                ->orWhere('matricola', 'like', "%{$parola}%"));
        }
    }
}
