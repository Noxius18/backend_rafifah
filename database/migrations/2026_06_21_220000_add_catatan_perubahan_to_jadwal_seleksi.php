<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_seleksi', function (Blueprint $table) {
            $table->text('catatan_perubahan')->nullable()->after('catatan_ketua')->comment('Catatan perubahan/penolakan jadwal dari Ketua Panitia');
        });
    }

    public function down(): void
    {
        Schema::table('jadwal_seleksi', function (Blueprint $table) {
            $table->dropColumn('catatan_perubahan');
        });
    }
};
