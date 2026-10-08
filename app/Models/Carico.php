<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Carico extends Model
{
    use HasFactory;

    protected $table = 'carichi';

    protected $fillable = ['data_carico', 'user_id', 'note'];

    protected function casts(): array
    {
        return [
            'data_carico' => 'datetime',
        ];
    }

    public function righe(): HasMany
    {
        return $this->hasMany(CaricoRiga::class, 'carico_id');
    }

    public function operatore(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function movimenti(): MorphMany
    {
        return $this->morphMany(MovimentoMagazzino::class, 'origine');
    }
}
