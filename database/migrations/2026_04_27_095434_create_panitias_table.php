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
        
        Schema::create('panitia', function (Blueprint $table) {
            $table->char('id_panitia', 5)->primary();
            $table->string('nama_lengkap', 30);
            $table->string('username', 10);
            # TODO: Mungkin tambah field no hp nanti
            $table->char('password', 60)->comment('Hashing menggunakan Bcrypt');
            $table->enum('jabatan', ['Pengawas', 'Panitia', 'Penguji']);
            $table->timestamps();

            /* TODO: Mungkin tambah panitia berelasi ke Mahasantri jadi tau mahasantri ini dikelola oleh panitia siapa
                 dan mungkin berelasi juga ke tabel jadwal tes jadi tau siapa penguji tes seleksi mahasantrinya
            */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('panitias');
    }
};
