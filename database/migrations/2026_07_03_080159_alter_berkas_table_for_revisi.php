<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('berkas', function (Blueprint $table) {
            // 1. Tambah kolom catatan_revisi (nullable karena diisi kalau ditolak aja)
            $table->string('catatan_revisi')->nullable()->after('file_path');
            
            // 2. Ubah tipe data status_verifikasi dari boolean ke string/enum agar mendukung 3 kondisi
            // Kita pakai string biasa agar aman saat proses rollback/alter di beberapa jenis database
            $table->string('status_verifikasi')->default('menunggu')->change();
        });
    }

    public function down(): void
    {
        Schema::table('berkas', function (Blueprint $table) {
            $table->dropColumn('catatan_revisi');
            // Kembalikan ke boolean jika di-rollback
            $table->boolean('status_verifikasi')->default(false)->change();
        });
    }
};