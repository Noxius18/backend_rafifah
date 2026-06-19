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
        Schema::create('gelombang', function (Blueprint $table) {
            $table->id();
            // $table->tinyInteger('nomor')->unique();
            $table->string('nama', 20);
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('kuota')->default(0)->comment('Kuota maksimal mahasantri per gelombang');
            $table->char('updated_by', 5)->nullable();
            $table->foreign('updated_by')
                ->references('id_panitia')
                ->on('panitia')
                ->onDelete('set null')
                ->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gelombang');
    }
};