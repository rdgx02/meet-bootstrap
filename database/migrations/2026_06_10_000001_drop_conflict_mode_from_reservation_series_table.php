<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove a coluna `conflict_mode`, que é código morto: o comportamento das
     * séries é sempre "strict" (proibir double-booking) e o modo alternativo
     * nunca foi exposto nem executado. A coluna só guardava 'strict'.
     */
    public function up(): void
    {
        Schema::table('reservation_series', function (Blueprint $table): void {
            $table->dropColumn('conflict_mode');
        });
    }

    /**
     * Recria a coluna exatamente como era: string(20) com default 'strict'.
     */
    public function down(): void
    {
        Schema::table('reservation_series', function (Blueprint $table): void {
            $table->string('conflict_mode', 20)->default('strict');
        });
    }
};
