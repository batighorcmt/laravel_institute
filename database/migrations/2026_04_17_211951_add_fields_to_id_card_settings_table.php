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
            // JSON column — stores array of field keys to display on ID card
            $table->json('fields')->nullable()->after('show_principal_signature');
            // School header on card (portrait cards can optionally show school name/logo)
            $table->boolean('show_school_header')->default(false)->after('fields');
            // Custom label overrides (JSON)
            $table->json('custom_labels')->nullable()->after('show_school_header');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('id_card_settings', function (Blueprint $table) {
            $table->dropColumn(['fields', 'show_school_header', 'custom_labels']);
        });
    }
};
