<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('richiesta_ordine_prodotto', function (Blueprint $table) {
            $table->id();
            
            // Riferimenti
            $table->foreignId('richiesta_ordine_id')->constrained('richieste_ordine')->onDelete('cascade');
            $table->foreignId('prodotto_id')->constrained('prodottos')->onDelete('cascade');
            
            // Dati prodotto (snapshot al momento della richiesta)
            $table->string('nome_prodotto');
            $table->string('sku')->nullable();
            $table->decimal('prezzo_unitario', 8, 2);
            $table->decimal('prezzo_personalizzato', 8, 2)->nullable();
            $table->integer('quantita');
            $table->decimal('subtotale', 10, 2);
            
            // Configurazione specifica per questo shop
            $table->json('configurazione')->nullable();
            
            $table->timestamps();
            
            // Indici
            $table->unique(['richiesta_ordine_id', 'prodotto_id']);
            $table->index('richiesta_ordine_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('richiesta_ordine_prodotto');
    }
};