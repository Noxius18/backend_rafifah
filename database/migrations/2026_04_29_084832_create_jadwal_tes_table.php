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
        Schema::create('jadwal_tes', function (Blueprint $table) {
            $table->char('id_jadwal', 5)->primary();

            // Kolom baru sesuai restructure
            $table->char('id_mahasantri', 6);
            $table->date('tanggal');
            $table->time('jam')->nullable();
            $table->unsignedInteger('interval_minutes')->default(30)->comment('Interval waktu dalam menit antar mahasantri');
            $table->string('link_zoom')->nullable();
            // Reminder flag added from 2026_04_29_084833_add_zoom_reminder_sent_to_jadwal_tes_table.php
            $table->boolean('zoom_reminder_sent')->default(false);
            $table->char('penanggung_jawab', 5)->nullable()->comment('Penanggung jawab (panitia)');
            // Penguji columns added from 2026_06_03_121210_add_penguji_columns_to_jadwal_tes_and_drop_penguji_table.php
            $table->char('penguji_bacaan_al_quran', 5)->nullable();
            $table->char('penguji_tajwid_tahsin', 5)->nullable();
            $table->char('penguji_hafalan', 5)->nullable();
            $table->char('penguji_wawancara', 5)->nullable();

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

            // Foreign key penguji columns ke tabel panitia
            $table->foreign('penguji_bacaan_al_quran')
                  ->references('id_panitia')
                  ->on('panitia')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
            $table->foreign('penguji_tajwid_tahsin')
                  ->references('id_panitia')
                  ->on('panitia')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
            $table->foreign('penguji_hafalan')
                  ->references('id_panitia')
                  ->on('panitia')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
            $table->foreign('penguji_wawancara')
                  ->references('id_panitia')
                  ->on('panitia')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            // Status jadwal tes: Aktif, Dibatalkan, Rescheduled
            $table->enum('status', ['Aktif', 'Dibatalkan', 'Rescheduled'])->default('Aktif');

            // Digital Handshake: konfirmasi jadwal oleh Ketua Panitia
            $table->enum('status_konfirmasi', ['Menunggu', 'Disetujui', 'Perlu Revisi'])->default('Menunggu');
            $table->text('catatan_ketua')->nullable()->comment('Catatan dari Ketua Panitia saat approve/reject');
            $table->char('dikonfirmasi_oleh', 5)->nullable();
            $table->timestamp('dikonfirmasi_pada')->nullable();
            $table->foreign('dikonfirmasi_oleh')
                  ->references('id_panitia')
                  ->on('panitia')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            // Audit trail untuk pembatalan
            $table->text('alasan_pembatalan')->nullable();
            $table->char('dibatalkan_oleh', 5)->nullable();
            $table->timestamp('dibatalkan_pada')->nullable();
            $table->foreign('dibatalkan_oleh')
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
        Schema::dropIfExists('jadwal_tes');
    }
};