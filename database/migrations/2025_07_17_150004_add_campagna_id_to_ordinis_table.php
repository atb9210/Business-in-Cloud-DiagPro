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
        Schema::table('ordinis', function (Blueprint $table) {
            $table->foreignId('campagna_id')->nullable()->after('traffic_source_id')->constrained('campagnas')->onDelete('set null');
            $table->index('campagna_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordinis', function (Blueprint $table) {
            $table->dropForeign(['campagna_id']);
            $table->dropIndex(['campagna_id']);
            $table->dropColumn('campagna_id');
        });
    }
};