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
        Schema::create('prodotto_shop', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodotto_id')->constrained('prodottos')->onDelete('cascade');
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->decimal('prezzo_personalizzato', 10, 2)->nullable()->comment('Prezzo specifico per questo shop (se diverso dal prezzo base)');
            $table->boolean('attivo')->default(true)->comment('Se il prodotto è attivo in questo shop');
            $table->integer('ordine')->default(0)->comment('Ordine di visualizzazione nel shop');
            $table->json('configurazione')->nullable()->comment('Configurazioni specifiche per questo shop (es. descrizione personalizzata)');
            $table->timestamps();
            
            // Indice unico per evitare duplicati
            $table->unique(['prodotto_id', 'shop_id']);
            
            // Indici per performance
            $table->index(['shop_id', 'attivo']);
            $table->index(['prodotto_id', 'attivo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prodotto_shop');
    }
};