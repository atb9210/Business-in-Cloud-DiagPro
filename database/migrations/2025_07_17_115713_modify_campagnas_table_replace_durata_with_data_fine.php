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
        Schema::table('campagnas', function (Blueprint $table) {
            $table->dropColumn('durata');
            $table->date('data_fine')->nullable()->after('budget_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campagnas', function (Blueprint $table) {
            $table->dropColumn('data_fine');
            $table->integer('durata')->nullable()->after('budget_type');
        });
    }
};