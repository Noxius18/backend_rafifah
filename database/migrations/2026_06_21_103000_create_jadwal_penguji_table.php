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
            $table->id('id_jadwal_penguji');

            // Relasi ke jadwal_tes
            $table->foreignId('jadwal_id');
            $table->foreign('jadwal_id')
                  ->references('id')
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
            $table->unique(['jadwal_id', 'id_panitia', 'aspek_penguji']);
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
