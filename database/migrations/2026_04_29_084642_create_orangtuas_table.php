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
        // Schema::create('orangtua', function (Blueprint $table) {
        //     $table->char('id_orangtua', 5)->primary();
        //     $table->string('nama_lengkap', 30);
        //     $table->string('pekerjaan', 20);
        //     $table->string('no_hp', 13)->unique();
        //     $table->enum('tipe_hubungan', ['Ayah', 'Ibu', 'Wali']);
        //     $table->char('id_mahasantri', 5);
        //     // $table->timestamps();

        //     $table->foreign('id_mahasantri')
        //           ->references('id_mahasantri')
        //           ->on('mahasantri')
        //           ->onDelete('cascade')
        //           ->onUpdate('cascade');
        // });
        Schema::create("orangtua", function (Blueprint $table) {
            $table->char('id_orangtua', 5)->primary();
            $table->char('id_mahasantri', 5);

            // Data Ayah
            $table->string('nama_ayah', 25)->nullable();
            $table->string('pekerjaan_ayah', 20)->nullable();
            $table->string('no_wa_ayah', 13)->unique()->nullable();
        

            // Data Ibu
            $table->string('nama_ibu', 25)->nullable();
            $table->string('pekerjaan_ibu', 20)->nullable();
            $table->string('no_wa_ibu', 13)->unique()->nullable();

            // Data Wali
            $table->string('nama_wali', 25)->nullable();
            $table->string('pekerjaan_wali', 20)->nullable();
            $table->string('no_wa_wali', 13)->unique()->nullable();

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
        Schema::dropIfExists('orangtua');
    }
};
