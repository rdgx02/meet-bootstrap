<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('title');
            $table->string('requester');
            $table->string('contact')->nullable();
            $table->string('frequency', 20);
            $table->unsignedInteger('interval')->default(1);
            $table->json('weekdays')->nullable();
            $table->string('conflict_mode', 20)->default('strict');
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['room_id', 'starts_on', 'ends_on']);
            $table->index(['user_id', 'starts_on']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('series_id')
                ->nullable()
                ->after('room_id')
                ->constrained('reservation_series')
                ->nullOnDelete();

            $table->date('original_date')
                ->nullable()
                ->after('date');

            $table->boolean('is_exception')
                ->default(false)
                ->after('original_date');

            $table->index(['series_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['series_id']);
            $table->dropIndex(['series_id', 'date']);
            $table->dropColumn(['series_id', 'original_date', 'is_exception']);
        });

        Schema::dropIfExists('reservation_series');
    }
};
