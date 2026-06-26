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
        Schema::create('mahasantri', function (Blueprint $table) {
            $table->char("id_mahasantri", 6)->primary();
            $table->string("nama_lengkap", 35);
            $table->string("email", 100)->unique();

            // Data tambahan saat daftar ulang
            $table->string("nik", 16)->comment("Nomor Induk Kependudukan")->nullable();
            $table->string('nisn', 10)->comment('Nomor Induk Siswa Nasional')->nullable();
            $table->string("tempat_lahir", 50)->nullable();
            $table->string('alamat', 255)->nullable();
            $table->date("tanggal_lahir")->nullable();

            // Status pendaftaran
            $table->enum('status', ['Pendaftar Baru', 'Terverifikasi', 'Lulus', 'Tidak Lulus'])->default('Pendaftar Baru');
            $table->timestamp('tanggal_daftar')->nullable();
            // $table->string('gelombang', 20)->nullable();
            
            # TODO: Mungkin tambah Panitia yang mengelola mahasantri ini, jadi tau mahasantri ini dikelola oleh panitia siapa
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mahasantri');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
