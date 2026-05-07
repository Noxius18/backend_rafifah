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
        // Schema::create('dokumen', function (Blueprint $table) {
        //     $table->char('id_dokumen', 5)->primary();
        //     $table->enum('jenis_dokumen', ['KTP', 'KK', 'Ijazah', 'Surat Izin Orangtua']);
        //     $table->string('path_file');
        //     $table->date('tanggal_upload');
        //     $table->char('id_mahasantri',5);

        //     $table->foreign('id_mahasantri')
        //           ->references('id_mahasantri')
        //           ->on('mahasantri')
        //           ->onDelete('cascade')
        //           ->onUpdate('cascade');
        // });

        Schema::create('dokumen', function (Blueprint $table) {
            $table->char('id_dokumen', 5)->primary();
            $table->char('id_mahasantri', 5);

            // Field URL langsung dari kolom CSV
            $table->text('url_kk')->nullable();
            $table->text('url_ktp')->nullable();
            $table->text('url_ijazah')->nullable();
            $table->text('url_surat_izin')->nullable();

            // Field status verifikasi per dokumen (opsional, sangat bagus untuk nilai plus)
            $table->boolean('is_kk_valid')->default(false);
            $table->boolean('is_ktp_valid')->default(false);

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
        Schema::dropIfExists('dokumen');
    }
};
