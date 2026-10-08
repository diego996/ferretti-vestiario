<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contatore del progressivo annuale delle consegne.
     *
     * Una riga per anno, bloccata con SELECT ... FOR UPDATE durante la creazione
     * di una consegna: le conferme concorrenti si accodano su un lock di riga,
     * senza i gap lock (e i deadlock) di un MAX(progressivo) FOR UPDATE.
     */
    public function up(): void
    {
        Schema::create('numerazione_consegne', function (Blueprint $table) {
            $table->unsignedSmallInteger('anno')->primary();
            $table->unsignedInteger('ultimo_progressivo')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numerazione_consegne');
    }
};
