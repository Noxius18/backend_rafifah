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
        Schema::create('jadwal_tes', function (Blueprint $table) {
            $table->char('id_jadwal', 5)->primary();
            $table->string('nama_tes', 20);
            $table->string('keterangan', 50);
            $table->char('penguji', 5);
            $table->date('tanggal');
            $table->string('link_zoom');

            $table->foreign('penguji')
                  ->references('id_panitia')
                  ->on('panitia')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_tes');
    }
};
