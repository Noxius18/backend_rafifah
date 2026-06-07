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
        Schema::table('jadwal_tes', function (Blueprint $table) {
            $table->unsignedInteger('interval_minutes')->default(30)->after('jam')
                  ->comment('Interval waktu dalam menit antar mahasantri');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal_tes', function (Blueprint $table) {
            $table->dropColumn('interval_minutes');
        });
    }
};