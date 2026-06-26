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
        Schema::create('schedule_statuses', function (Blueprint $table) {
            $table->id();
            $table->char('id_jadwal', 5)->unique();
            $table->boolean('zoom_reminder_sent')->default(false);
            $table->timestamp('sent_at')->nullable();

            $table->foreign('id_jadwal')
                ->references('id_jadwal')
                ->on('jadwal_seleksi')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_statuses');
    }
};
