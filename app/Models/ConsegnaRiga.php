<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsegnaRiga extends Model
{
    protected $table = 'consegna_righe';

    protected $fillable = ['consegna_id', 'variante_id', 'quantita', 'indumento_codice', 'indumento_nome', 'taglia'];

    protected function casts(): array
    {
        return [
            'quantita' => 'integer',
        ];
    }

    public function consegna(): BelongsTo
    {
        return $this->belongsTo(Consegna::class, 'consegna_id');
    }

    public function variante(): BelongsTo
    {
        return $this->belongsTo(IndumentoVariante::class, 'variante_id');
    }
}
