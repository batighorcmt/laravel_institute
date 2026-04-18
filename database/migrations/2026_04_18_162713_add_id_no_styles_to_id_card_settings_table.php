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
        Schema::table('id_card_settings', function (Blueprint $table) {
            $table->integer('id_no_font_size')->default(10)->after('details_color');
            $table->string('id_no_color', 10)->default('#d32f2f')->after('id_no_font_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('id_card_settings', function (Blueprint $table) {
            $table->dropColumn(['id_no_font_size', 'id_no_color']);
        });
    }
};
