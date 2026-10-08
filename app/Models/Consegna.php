<?php

namespace App\Models;

use App\Enums\StatoConsegna;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Consegna extends Model
{
    use HasFactory;

    protected $table = 'consegne';

    /**
     * Numero, stato e campi di annullamento sono gestiti esclusivamente da
     * ConsegnaService e non sono assegnabili in massa.
     */
    protected $fillable = ['collega_id', 'commessa_id', 'data_consegna', 'user_id', 'note'];

    protected $attributes = [
        'stato' => 'confermata',
    ];

    protected function casts(): array
    {
        return [
            'anno' => 'integer',
            'progressivo' => 'integer',
            'data_consegna' => 'datetime',
            'annullata_at' => 'datetime',
            'stato' => StatoConsegna::class,
        ];
    }

    public function collega(): BelongsTo
    {
        return $this->belongsTo(Collega::class, 'collega_id');
    }

    public function commessa(): BelongsTo
    {
        return $this->belongsTo(Commessa::class, 'commessa_id');
    }

    public function operatore(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function annullataDa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'annullata_da');
    }

    public function righe(): HasMany
    {
        return $this->hasMany(ConsegnaRiga::class, 'consegna_id');
    }

    public function documento(): HasOne
    {
        return $this->hasOne(DocumentoConsegna::class, 'consegna_id');
    }

    public function movimenti(): MorphMany
    {
        return $this->morphMany(MovimentoMagazzino::class, 'origine');
    }

    public function isAnnullata(): bool
    {
        return $this->stato === StatoConsegna::Annullata;
    }

    public function scopeConfermate(Builder $query): void
    {
        $query->where('stato', StatoConsegna::Confermata);
    }

    /** Formato del numero consegna: "2026-00042". */
    public static function formattaNumero(int $anno, int $progressivo): string
    {
        return sprintf('%04d-%05d', $anno, $progressivo);
    }
}
