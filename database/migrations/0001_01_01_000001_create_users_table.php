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
            $table->char("id_mahasantri", 5)->primary();
            $table->string("nama_lengkap", 35);
            $table->string("email", 50)->unique();
            $table->string("no_hp", 13)->unique();
            $table->char("password", 60)->comment("Hashing menggunakan Bcrypt");
            $table->text("alamat_lengkap");
            $table->char("nik", 16)->comment("Nomor Induk Keluarga");
            $table->enum("jenis_kelamin", ["Laki - laki", "Perempuan"]);
            $table->date("tanggal_lahir");
            $table->string("tempat_lahir", 50);
            # TODO: Mungkin tambah Panitia yang mengelola mahasantri ini, jadi tau mahasantri ini dikelola oleh panitia siapa
            $table->timestamps();
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
