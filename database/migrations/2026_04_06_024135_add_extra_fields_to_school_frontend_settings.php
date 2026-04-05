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
        Schema::table('school_frontend_settings', function (Blueprint $table) {
            $table->text('committee_text')->nullable()->after('principal_image');
            $table->string('meta_title')->nullable()->after('committee_text');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->string('meta_keywords')->nullable()->after('meta_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_frontend_settings', function (Blueprint $table) {
            $table->dropColumn(['committee_text', 'meta_title', 'meta_description', 'meta_keywords']);
        });
    }
};
