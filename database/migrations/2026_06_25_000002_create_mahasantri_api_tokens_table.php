<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mahasantri_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->char('id_mahasantri', 6);
            $table->string('name')->default('mobile');
            $table->char('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('id_mahasantri')
                ->references('id_mahasantri')
                ->on('mahasantri')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->index('id_mahasantri');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mahasantri_api_tokens');
    }
};
