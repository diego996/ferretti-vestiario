<?php

namespace App\Models;

use App\Enums\StatoDocumento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoConsegna extends Model
{
    protected $table = 'documenti_consegna';

    protected $fillable = [
        'consegna_id', 'stato', 'nome_file', 'disco', 'percorso', 'template_versione',
        'hash_sha256', 'tentativi', 'ultimo_errore', 'generato_at',
    ];

    protected function casts(): array
    {
        return [
            'stato' => StatoDocumento::class,
            'tentativi' => 'integer',
            'generato_at' => 'datetime',
        ];
    }

    public function consegna(): BelongsTo
    {
        return $this->belongsTo(Consegna::class, 'consegna_id');
    }
}
