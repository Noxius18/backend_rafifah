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
        Schema::create('jadwal_seleksi', function (Blueprint $table) {
            $table->char('id_jadwal', 5)->primary();

            // Kolom baru sesuai restructure
            $table->char('id_mahasantri', 6);
            $table->date('tanggal');
            $table->time('jam')->nullable();
            $table->unsignedInteger('interval')->default(30)->comment('Interval waktu dalam menit antar mahasantri');
            $table->string('link_zoom')->nullable();
            // Reminder flag added from 2026_04_29_084833_add_zoom_reminder_sent_to_jadwal_tes_table.php
            $table->char('penanggung_jawab', 5)->nullable()->comment('Penanggung jawab (panitia)');

            // Foreign key ke tabel mahasantri
            $table->foreign('id_mahasantri')
                  ->references('id_mahasantri')
                  ->on('mahasantri')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            // Foreign key ke tabel panitia
            $table->foreign('penanggung_jawab')
                  ->references('id_panitia')
                  ->on('panitia')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            // Status jadwal tes: Aktif, Dibatalkan, Rescheduled
            $table->enum('status_jadwal', ['Aktif', 'Dibatalkan', 'Rescheduled', 'Menunggu', 'Disetujui', 'Revisi'])->default('Menunggu');

            // Digital Handshake: konfirmasi jadwal oleh Ketua Panitia
            $table->text('catatan_ketua')->nullable()->comment('Catatan dari Ketua Panitia saat approve/reject');
            $table->char('diproses_oleh', 5)->nullable();
            $table->foreign('diproses_oleh')
                  ->references('id_panitia')
                  ->on('panitia')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_seleksi');
    }
};