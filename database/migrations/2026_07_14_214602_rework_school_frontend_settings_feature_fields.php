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
            $table->renameColumn('feature_image', 'principal_feature_image');
        });

        Schema::table('school_frontend_settings', function (Blueprint $table) {
            $table->string('chairman_feature_image')->nullable();
            $table->string('principal_title')->nullable();
            $table->string('principal_designation')->nullable();
            $table->string('chairman_title')->nullable();
            $table->string('chairman_designation')->nullable();
            $table->json('about_images')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_frontend_settings', function (Blueprint $table) {
            $table->dropColumn([
                'chairman_feature_image',
                'principal_title',
                'principal_designation',
                'chairman_title',
                'chairman_designation',
                'about_images',
            ]);
        });

        Schema::table('school_frontend_settings', function (Blueprint $table) {
            $table->renameColumn('principal_feature_image', 'feature_image');
        });
    }
};
