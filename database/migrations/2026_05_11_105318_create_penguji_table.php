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
        Schema::create('penguji', function (Blueprint $table) {
            $table->char('id_penguji', 5)->primary();
            $table->char('id_jadwal', 5);
            $table->char('id_panitia', 5);
            $table->enum('aspek', ['Tajwid', 'Tahsin', 'Kelancaran', 'Wawancara']);

            $table->foreign('id_jadwal')
                  ->references('id_jadwal')
                  ->on('jadwal_tes')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('id_panitia')
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
        Schema::dropIfExists('penguji');
    }
};
