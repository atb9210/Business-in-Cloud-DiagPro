<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Integrazione;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Aggiungi l'integrazione per la chiave API OpenAI
        Integrazione::create([
            'chiave' => 'openai_api_key',
            'valore' => '',
            'tipo' => 'password',
            'descrizione' => 'API Key per OpenAI (richiesta per utilizzare le funzionalità AI)',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovi l'integrazione per la chiave API OpenAI
        Integrazione::where('chiave', 'openai_api_key')->delete();
    }
};