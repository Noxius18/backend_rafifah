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
        Schema::create('jadwal_penguji', function (Blueprint $table) {
            $table->id('id_jadwal_penguji')->primary();

            // Relasi ke jadwal_tes
            $table->char('id_jadwal', 5);
            $table->foreign('id_jadwal')
                  ->references('id_jadwal')
                  ->on('jadwal_seleksi')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            // Relasi ke panitia (penguji)
            $table->char('id_panitia', 5);
            $table->foreign('id_panitia')
                  ->references('id_panitia')
                  ->on('panitia')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            // Aspek yang dinilai
            $table->enum('aspek_penguji', [
                'Bacaan Al-Quran',
                'Tajwid/Tahsin',
                'Hafalan',
                'Wawancara',
            ]);

            // Catatan dan nilai dari penguji
            $table->text('catatan_penguji')->nullable();
            $table->integer('nilai')->nullable();

            // Unique constraint agar tidak duplikasi (1 penguji hanya 1 aspek per jadwal)
            $table->unique(['id_jadwal', 'id_panitia', 'aspek_penguji']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_penguji');
    }
};