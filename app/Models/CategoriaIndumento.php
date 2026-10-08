<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaIndumento extends Model
{
    use HasFactory;

    protected $table = 'categorie_indumenti';

    protected $fillable = ['nome', 'descrizione', 'ordinamento', 'attiva'];

    protected $attributes = [
        'ordinamento' => 0,
        'attiva' => true,
    ];

    protected function casts(): array
    {
        return [
            'attiva' => 'boolean',
            'ordinamento' => 'integer',
        ];
    }

    public function indumenti(): HasMany
    {
        return $this->hasMany(Indumento::class, 'categoria_id');
    }

    public function scopeAttive(Builder $query): void
    {
        $query->where('attiva', true);
    }

    public function scopeOrdinate(Builder $query): void
    {
        $query->orderBy('ordinamento')->orderBy('nome');
    }
}
