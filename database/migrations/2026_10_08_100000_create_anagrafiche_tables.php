<?php

use App\Support\CheckConstraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorie_indumenti', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->unique();
            $table->string('descrizione')->nullable();
            $table->unsignedSmallInteger('ordinamento')->default(0);
            $table->boolean('attiva')->default(true);
            $table->timestamps();
        });

        Schema::create('indumenti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorie_indumenti')->restrictOnDelete();
            // Codice interno opzionale: univoco quando presente (più NULL ammessi).
            $table->string('codice', 50)->nullable()->unique();
            $table->string('nome', 150);
            $table->text('descrizione')->nullable();
            $table->boolean('attivo')->default(true);
            $table->timestamps();

            $table->index('nome');
            $table->index(['attivo', 'categoria_id']);
        });

        Schema::create('indumento_varianti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indumento_id')->constrained('indumenti')->restrictOnDelete();
            // Taglia libera (S, M, 42, 44/46, Unica...): nessun enum rigido.
            $table->string('taglia', 20);
            // Ordine di visualizzazione delle taglie (S < M < L...).
            $table->unsignedSmallInteger('ordinamento')->default(0);
            // Saldo corrente: aggiornato SOLO da MagazzinoService insieme ai movimenti.
            $table->unsignedInteger('giacenza')->default(0);
            $table->unsignedInteger('scorta_minima')->default(0);
            $table->boolean('attiva')->default(true);
            $table->timestamps();

            $table->unique(['indumento_id', 'taglia']);
            $table->index('giacenza');
        });

        CheckConstraint::add('indumento_varianti', 'chk_varianti_giacenza', 'giacenza >= 0');

        Schema::create('colleghi', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->string('cognome', 100);
            // Omonimi ammessi: nessun vincolo su nome/cognome. Matricola univoca se presente.
            $table->string('matricola', 50)->nullable()->unique();
            $table->boolean('attivo')->default(true);
            $table->timestamps();

            $table->index(['cognome', 'nome']);
        });

        Schema::create('commesse', function (Blueprint $table) {
            $table->id();
            $table->string('codice', 50)->nullable()->unique();
            $table->string('nome', 150);
            $table->text('descrizione')->nullable();
            $table->boolean('attiva')->default(true);
            $table->timestamps();

            $table->index('nome');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commesse');
        Schema::dropIfExists('colleghi');
        Schema::dropIfExists('indumento_varianti');
        Schema::dropIfExists('indumenti');
        Schema::dropIfExists('categorie_indumenti');
    }
};
