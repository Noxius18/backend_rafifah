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
        // Tambah kolom penguji ke tabel jadwal_tes
        Schema::table('jadwal_tes', function (Blueprint $table) {
            $table->char('penguji_bacaan_al_quran', 5)->nullable()->after('penanggung_jawab');
            $table->char('penguji_tajwid_tahsin', 5)->nullable()->after('penguji_bacaan_al_quran');
            $table->char('penguji_hafalan', 5)->nullable()->after('penguji_tajwid_tahsin');
            $table->char('penguji_wawancara', 5)->nullable()->after('penguji_hafalan');

            $table->foreign('penguji_bacaan_al_quran')
                  ->references('id_panitia')
                  ->on('panitia')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->foreign('penguji_tajwid_tahsin')
                  ->references('id_panitia')
                  ->on('panitia')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->foreign('penguji_hafalan')
                  ->references('id_panitia')
                  ->on('panitia')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->foreign('penguji_wawancara')
                  ->references('id_panitia')
                  ->on('panitia')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
        });

        // Drop tabel penguji
        Schema::dropIfExists('penguji');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Buat ulang tabel penguji
        Schema::create('penguji', function (Blueprint $table) {
            $table->char('id_penguji', 5)->primary();
            $table->char('id_jadwal', 5);
            $table->char('id_panitia', 5);
            $table->enum('aspek', ['Bacaan Al Qur\'an', 'Tajwid dan Tahsin', 'Hafalan', 'Wawancara']);

            $table->foreign('id_jadwal')
                  ->references('id_jadwal')
                  ->on('jadwal_tes')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('id_panitia')
                  ->references('id_panitia')
                  ->on('panitia')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });

        // Hapus kolom penguji dari jadwal_tes
        Schema::table('jadwal_tes', function (Blueprint $table) {
            $table->dropForeign(['penguji_bacaan_al_quran']);
            $table->dropForeign(['penguji_tajwid_tahsin']);
            $table->dropForeign(['penguji_hafalan']);
            $table->dropForeign(['penguji_wawancara']);
            $table->dropColumn(['penguji_bacaan_al_quran', 'penguji_tajwid_tahsin', 'penguji_hafalan', 'penguji_wawancara']);
        });
    }
};