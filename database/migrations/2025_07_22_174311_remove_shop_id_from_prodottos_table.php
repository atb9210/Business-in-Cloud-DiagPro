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
        Schema::table('prodottos', function (Blueprint $table) {
            // Rimuovi la foreign key constraint prima di eliminare la colonna
            $table->dropForeign(['shop_id']);
            $table->dropColumn('shop_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prodottos', function (Blueprint $table) {
            // Ripristina la colonna shop_id
            $table->foreignId('shop_id')->nullable()->constrained('shops')->onDelete('set null');
        });
    }
};