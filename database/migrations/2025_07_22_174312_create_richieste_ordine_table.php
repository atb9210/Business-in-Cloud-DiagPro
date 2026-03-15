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
        Schema::create('richieste_ordine', function (Blueprint $table) {
            $table->id();
            
            // Riferimenti
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('ordine_id')->nullable()->constrained('ordinis')->onDelete('set null');
            
            // Dati ordine
            $table->string('numero_richiesta')->unique();
            $table->decimal('totale', 10, 2);
            $table->decimal('totale_spedizione', 8, 2)->default(0);
            $table->text('note')->nullable();
            
            // Dati cliente (snapshot al momento della richiesta)
            $table->json('dati_cliente');
            
            // Dati spedizione
            $table->json('dati_spedizione')->nullable();
            
            // Stati possibili: in_attesa_validazione, in_validazione, approvato, rifiutato, convertito
            $table->enum('stato', [
                'in_attesa_validazione',
                'in_validazione', 
                'approvato',
                'rifiutato',
                'convertito'
            ])->default('in_attesa_validazione');
            
            // Metadati validazione
            $table->foreignId('validato_da')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('validato_il')->nullable();
            $table->text('note_validazione')->nullable();
            $table->text('motivo_rifiuto')->nullable();
            
            // Metadati richiesta
            $table->string('ip_origine')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadati_aggiuntivi')->nullable();
            
            $table->timestamps();
            
            // Indici
            $table->index(['shop_id', 'stato']);
            $table->index(['stato', 'created_at']);
            $table->index('numero_richiesta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('richieste_ordine');
    }
};