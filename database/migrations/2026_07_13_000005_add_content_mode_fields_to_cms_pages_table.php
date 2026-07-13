<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->string('content_mode')->default('static')->after('content');
            $table->string('data_source')->nullable()->after('content_mode');
            $table->foreignId('page_template_id')->nullable()->after('data_source')->constrained('website_page_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cms_pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('page_template_id');
            $table->dropColumn(['content_mode', 'data_source']);
        });
    }
};
