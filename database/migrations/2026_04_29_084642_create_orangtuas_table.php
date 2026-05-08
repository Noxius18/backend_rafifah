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
        Schema::create("orangtua", function (Blueprint $table) {
            $table->char('id_orangtua', 5)->primary();
            $table->char('id_mahasantri', 5);
            $table->enum('tipe_hubungan', ['Ayah', 'Ibu', 'Wali']);
            $table->string('nama_lengkap', 25);
            $table->string('pekerjaan', 20)->nullable();
            $table->string('no_wa', 13)->nullable()->unique();

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