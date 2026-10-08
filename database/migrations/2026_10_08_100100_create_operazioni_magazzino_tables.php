<?php

use App\Support\CheckConstraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carichi', function (Blueprint $table) {
            $table->id();
            // Data operativa: può differire dalla data di registrazione (created_at).
            $table->dateTime('data_carico');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('data_carico');
        });

        Schema::create('carico_righe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carico_id')->constrained('carichi')->restrictOnDelete();
            $table->foreignId('variante_id')->constrained('indumento_varianti')->restrictOnDelete();
            $table->unsignedInteger('quantita');
            $table->timestamps();

            // Le righe duplicate vengono aggregate prima del salvataggio.
            $table->unique(['carico_id', 'variante_id']);
        });

        CheckConstraint::add('carico_righe', 'chk_carico_righe_quantita', 'quantita > 0');

        Schema::create('consegne', function (Blueprint $table) {
            $table->id();
            // Numerazione progressiva annuale: numero = "AAAA-NNNNN".
            $table->unsignedSmallInteger('anno');
            $table->unsignedInteger('progressivo');
            $table->string('numero', 20)->unique();
            $table->foreignId('collega_id')->constrained('colleghi')->restrictOnDelete();
            $table->foreignId('commessa_id')->constrained('commesse')->restrictOnDelete();
            $table->dateTime('data_consegna');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('stato', 20)->default('confermata');
            $table->text('note')->nullable();
            // Chiave di idempotenza generata dal form: impedisce il doppio salvataggio.
            $table->uuid('idempotency_key')->nullable()->unique();
            $table->dateTime('annullata_at')->nullable();
            $table->foreignId('annullata_da')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('motivo_annullamento')->nullable();
            $table->timestamps();

            $table->unique(['anno', 'progressivo']);
            $table->index(['stato', 'data_consegna']);
            $table->index(['collega_id', 'data_consegna']);
            $table->index(['commessa_id', 'data_consegna']);
        });

        CheckConstraint::add('consegne', 'chk_consegne_stato', "stato IN ('confermata', 'annullata')");

        Schema::create('consegna_righe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consegna_id')->constrained('consegne')->restrictOnDelete();
            $table->foreignId('variante_id')->constrained('indumento_varianti')->restrictOnDelete();
            $table->unsignedInteger('quantita');
            // Snapshot descrittivo: lo storico e il Word restano fedeli anche se il catalogo cambia.
            $table->string('indumento_codice', 50)->nullable();
            $table->string('indumento_nome', 150);
            $table->string('taglia', 20);
            $table->timestamps();

            $table->unique(['consegna_id', 'variante_id']);
            $table->index('variante_id');
        });

        CheckConstraint::add('consegna_righe', 'chk_consegna_righe_quantita', 'quantita > 0');

        Schema::create('movimenti_magazzino', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variante_id')->constrained('indumento_varianti')->restrictOnDelete();
            $table->string('tipo', 40);
            // Quantità con segno: positiva aumenta la giacenza, negativa la riduce.
            $table->integer('quantita');
            // Saldo della variante dopo il movimento (audit e ricostruzione rapida).
            $table->unsignedInteger('giacenza_risultante');
            // Operazione origine (carico / consegna). NULL per le rettifiche manuali.
            // Riferimento polimorfico: nessuna FK a livello DB, vedi docs/DATABASE.md.
            $table->nullableMorphs('origine');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('data_movimento');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['variante_id', 'data_movimento']);
            $table->index(['tipo', 'data_movimento']);
            $table->index('data_movimento');
        });

        CheckConstraint::add('movimenti_magazzino', 'chk_movimenti_quantita', 'quantita <> 0');
        CheckConstraint::add('movimenti_magazzino', 'chk_movimenti_tipo', "tipo IN ('carico', 'scarico_consegna', 'rettifica_positiva', 'rettifica_negativa', 'compensazione_annullamento')");

        Schema::create('documenti_consegna', function (Blueprint $table) {
            $table->id();
            // Un documento archiviato per consegna; i tentativi falliti aggiornano la stessa riga.
            $table->foreignId('consegna_id')->unique()->constrained('consegne')->restrictOnDelete();
            $table->string('stato', 20)->default('in_attesa');
            $table->string('nome_file')->nullable();
            $table->string('disco', 30)->default('local');
            $table->string('percorso')->nullable();
            $table->string('template_versione', 50)->nullable();
            $table->string('hash_sha256', 64)->nullable();
            $table->unsignedSmallInteger('tentativi')->default(0);
            $table->text('ultimo_errore')->nullable();
            $table->dateTime('generato_at')->nullable();
            $table->timestamps();

            $table->index('stato');
        });

        CheckConstraint::add('documenti_consegna', 'chk_documenti_stato', "stato IN ('in_attesa', 'generato', 'fallito')");
    }

    public function down(): void
    {
        Schema::dropIfExists('documenti_consegna');
        Schema::dropIfExists('movimenti_magazzino');
        Schema::dropIfExists('consegna_righe');
        Schema::dropIfExists('consegne');
        Schema::dropIfExists('carico_righe');
        Schema::dropIfExists('carichi');
    }
};
