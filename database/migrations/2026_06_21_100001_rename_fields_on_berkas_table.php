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
        Schema::table('berkas', function (Blueprint $table) {
            $table->renameColumn('tipe_dokumen', 'tipe_berkas');
            $table->renameColumn('original_url', 'link_sumber');
            $table->renameColumn('is_valid', 'status_verifikasi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('berkas', function (Blueprint $table) {
            $table->renameColumn('tipe_berkas', 'tipe_dokumen');
            $table->renameColumn('link_sumber', 'original_url');
            $table->renameColumn('status_verifikasi', 'is_valid');
        });
    }
};
