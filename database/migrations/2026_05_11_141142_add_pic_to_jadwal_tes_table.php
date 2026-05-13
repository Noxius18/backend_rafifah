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
        Schema::table('jadwal_tes', function (Blueprint $table) {
            $table->char('pic', 5)->nullable()->after('link_zoom')->comment('Penanggung jawab (panitia)');

            $table->foreign('pic')
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
        Schema::table('jadwal_tes', function (Blueprint $table) {
            $table->dropForeign(['pic']);
            $table->dropColumn('pic');
        });
    }
};
