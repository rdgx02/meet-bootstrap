<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('requester');
        });

        Schema::table('reservation_series', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('requester');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('phone');
        });

        Schema::table('reservation_series', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
