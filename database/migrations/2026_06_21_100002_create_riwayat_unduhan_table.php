<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('downloads_log', function (Blueprint $table) {
            $table->id();
            $table->char('id_berkas', 5);
            $table->enum('download_status', ['pending', 'processing', 'success', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('attempted_at')->useCurrent();

            $table->foreign('id_berkas')
                  ->references('id_berkas')
                  ->on('berkas')
                  ->onDelete('cascade');

            $table->index('id_berkas');
        });

        // Migrate existing data from berkas table
        DB::statement('
            INSERT INTO downloads_log (id_berkas, download_status, error_message, attempted_at)
            SELECT id_berkas, download_status, error_message, COALESCE(tanggal_upload, CURRENT_TIMESTAMP) FROM berkas
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('downloads_log');
    }
};
