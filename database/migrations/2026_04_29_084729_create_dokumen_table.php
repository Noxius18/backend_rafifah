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
        Schema::create('berkas', function (Blueprint $table) {
            $table->id();
            $table->char('id_mahasantri', 6);
            $table->enum('tipe_berkas', ['KTP', 'KK', 'Ijazah', 'Surat Izin Orangtua', 'Pas Foto']);
            $table->text('link_sumber')->nullable();
            $table->string('file_path')->nullable();
            $table->boolean('status_verifikasi')->default(false);
            $table->timestamp('tanggal_upload')->useCurrent();

            $table->foreign('id_mahasantri')
                  ->references('id_mahasantri')
                  ->on('mahasantri')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->unique(['id_mahasantri', 'tipe_berkas']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('berkas');
    }
};
