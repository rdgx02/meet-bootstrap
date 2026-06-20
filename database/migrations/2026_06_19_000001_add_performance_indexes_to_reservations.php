<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índices de performance:
     * - reservations_conflict_idx: serve a query quente de detecção de conflito
     *   (room_id + date + faixa de horário), que roda em transação com lockForUpdate
     *   em cada criação/edição e em cada ocorrência de série.
     * - owner_user_id: usado por Reservation::scopeVisibleTo em toda listagem de
     *   usuário não-gestor.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->index(['room_id', 'date', 'start_time', 'end_time'], 'reservations_conflict_idx');
            $table->index('owner_user_id', 'reservations_owner_user_id_idx');
        });

        Schema::table('reservation_series', function (Blueprint $table) {
            $table->index('owner_user_id', 'reservation_series_owner_user_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_conflict_idx');
            $table->dropIndex('reservations_owner_user_id_idx');
        });

        Schema::table('reservation_series', function (Blueprint $table) {
            $table->dropIndex('reservation_series_owner_user_id_idx');
        });
    }
};
