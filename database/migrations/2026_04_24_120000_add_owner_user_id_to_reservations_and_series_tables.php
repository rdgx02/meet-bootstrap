<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->foreignId('owner_user_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('reservation_series', function (Blueprint $table): void {
            $table->foreignId('owner_user_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('reservations')
            ->whereNull('owner_user_id')
            ->update(['owner_user_id' => DB::raw('user_id')]);

        DB::table('reservation_series')
            ->whereNull('owner_user_id')
            ->update(['owner_user_id' => DB::raw('user_id')]);
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('owner_user_id');
        });

        Schema::table('reservation_series', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('owner_user_id');
        });
    }
};
