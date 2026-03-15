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
        Schema::create('campagnas', function (Blueprint $table) {
            $table->id();
            $table->date('data_inizio');
            $table->string('nome_campagna');
            $table->enum('stato', ['attiva', 'pausa', 'terminata', 'in_review'])->default('attiva');
            $table->foreignId('traffic_source_id')->constrained('traffic_sources')->onDelete('cascade');
            $table->decimal('spesa', 10, 2)->default(0);
            $table->enum('budget_type', ['giornaliero', 'settimanale', 'mensile', 'annuale'])->default('mensile');
            $table->integer('durata')->nullable()->comment('Durata in giorni');
            $table->decimal('costo_conversione', 10, 2)->nullable();
            $table->decimal('margine', 10, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['stato', 'data_inizio']);
            $table->index('traffic_source_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campagnas');
    }
};