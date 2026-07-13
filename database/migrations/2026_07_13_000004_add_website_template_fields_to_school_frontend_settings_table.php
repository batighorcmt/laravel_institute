<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_frontend_settings', function (Blueprint $table) {
            $table->foreignId('theme_id')->nullable()->after('school_id')->constrained('website_themes')->nullOnDelete();
            $table->json('theme_overrides')->nullable()->after('theme_id');
            $table->foreignId('applied_menu_template_id')->nullable()->after('theme_overrides')->constrained('website_menu_templates')->nullOnDelete();
            $table->timestamp('applied_at')->nullable()->after('applied_menu_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('school_frontend_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('theme_id');
            $table->dropConstrainedForeignId('applied_menu_template_id');
            $table->dropColumn(['theme_overrides', 'applied_at']);
        });
    }
};
