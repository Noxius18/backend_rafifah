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
            $table->foreignId('jadwal_id')->unique();
            $table->boolean('zoom_reminder_sent')->default(false);
            $table->timestamp('sent_at')->nullable();

            $table->foreign('jadwal_id')
                ->references('id')
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
