<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove a coluna `contact`, que nunca foi usada (não é fillable nem lida
     * em lugar nenhum do código), de reservations e reservation_series.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn('contact');
        });

        Schema::table('reservation_series', function (Blueprint $table): void {
            $table->dropColumn('contact');
        });
    }

    /**
     * Recria a coluna exatamente como era: string nullable.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->string('contact')->nullable();
        });

        Schema::table('reservation_series', function (Blueprint $table): void {
            $table->string('contact')->nullable();
        });
    }
};
