<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('berkas')
            ->where(function ($query) {
                $query->whereNull('status_verifikasi')
                    ->orWhereNotIn('status_verifikasi', ['menunggu', 'disetujui', 'ditolak']);
            })
            ->update(['status_verifikasi' => 'menunggu']);

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE berkas
            MODIFY status_verifikasi
            ENUM('menunggu', 'disetujui', 'ditolak')
            NOT NULL DEFAULT 'menunggu'
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE berkas
            MODIFY status_verifikasi VARCHAR(255)
            NOT NULL DEFAULT 'menunggu'
        ");
    }
};
