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
            $table->char('id_berkas', 5)->primary();
            $table->char('id_mahasantri', 6);
            $table->enum('tipe_dokumen', ['KTP', 'KK', 'Ijazah', 'Surat Izin Orangtua', 'Pas Foto']);
            $table->text('original_url')->nullable();
            $table->string('file_path')->nullable();
            $table->enum('download_status', ['pending', 'processing', 'success', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->boolean('is_valid')->default(false);
            $table->timestamp('tanggal_upload')->useCurrent();

            $table->foreign('id_mahasantri')
                  ->references('id_mahasantri')
                  ->on('mahasantri')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
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