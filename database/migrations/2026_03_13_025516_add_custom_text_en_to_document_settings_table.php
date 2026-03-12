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
        Schema::table('document_settings', function (Blueprint $table) {
            $table->json('custom_text_en')->nullable()->after('custom_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_settings', function (Blueprint $table) {
            $table->dropColumn('custom_text_en');
        });
    }
};
