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
        // No-op for fresh schema: kolom akhir langsung dibuat di migration create_berkas.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op.
    }
};
