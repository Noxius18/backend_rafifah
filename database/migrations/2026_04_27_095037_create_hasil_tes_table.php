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
        Schema::create('hasil_tes', function (Blueprint $table) {
            $table->char('id_hasil', 5)->primary();
            $table->enum('status', ['Lulus', 'Tidak Lulus', 'Belum Tes'])->default('Belum Tes');
            $table->text('catatan_penguji')->nullable();
            $table->char('id_mahasantri', 5);
            $table->char('id_jadwal', 5);

            $table->foreign('id_mahasantri')
                  ->references('id_mahasantri')
                  ->on('mahasantri')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('id_jadwal')
                  ->references('id_jadwal')
                  ->on('jadwal')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_tes');
    }
};
