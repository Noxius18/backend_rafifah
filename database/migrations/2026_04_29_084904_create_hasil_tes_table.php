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
        Schema::create('hasil_seleksi', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['Belum Tes', 'Pertimbangan', 'Lulus', 'Tidak Lulus'])->default('Belum Tes');
            $table->integer('total_nilai')->nullable();
            $table->foreignId('jadwal_id')->unique();

            $table->foreign('jadwal_id')
                  ->references('id')
                  ->on('jadwal_seleksi')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_seleksi');
    }
};
