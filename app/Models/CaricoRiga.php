<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaricoRiga extends Model
{
    protected $table = 'carico_righe';

    protected $fillable = ['carico_id', 'variante_id', 'quantita'];

    protected function casts(): array
    {
        return [
            'quantita' => 'integer',
        ];
    }

    public function carico(): BelongsTo
    {
        return $this->belongsTo(Carico::class, 'carico_id');
    }

    public function variante(): BelongsTo
    {
        return $this->belongsTo(IndumentoVariante::class, 'variante_id');
    }
}
