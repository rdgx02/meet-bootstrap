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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_id')->constrained()->cascadeOnDelete();

            // Quem criou o agendamento (rastreabilidade)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->date('date');           // dia do agendamento
            $table->time('start_time');     // hora início
            $table->time('end_time');       // hora fim

            $table->string('title');        // motivo / título
            $table->string('requester');    // quem solicitou (nome)
            $table->string('contact')->nullable(); // opcional: ramal/email

            $table->timestamps();

            $table->index(['room_id', 'date']);
            $table->index(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
