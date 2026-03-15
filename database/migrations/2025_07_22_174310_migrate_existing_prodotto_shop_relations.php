<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migra i dati esistenti dalla colonna shop_id alla tabella pivot
        DB::statement('
            INSERT INTO prodotto_shop (prodotto_id, shop_id, attivo, created_at, updated_at)
            SELECT id, shop_id, true, NOW(), NOW()
            FROM prodottos 
            WHERE shop_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ripristina i dati nella colonna shop_id (solo il primo shop per prodotto)
        DB::statement('
            UPDATE prodottos p
            SET shop_id = (
                SELECT ps.shop_id 
                FROM prodotto_shop ps 
                WHERE ps.prodotto_id = p.id 
                ORDER BY ps.created_at ASC 
                LIMIT 1
            )
            WHERE EXISTS (
                SELECT 1 FROM prodotto_shop ps WHERE ps.prodotto_id = p.id
            )
        ');
        
        // Svuota la tabella pivot
        DB::table('prodotto_shop')->truncate();
    }
};