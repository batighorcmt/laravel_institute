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
            $table->string('contact_mobile')->nullable()->after('contact_phone');
            $table->string('contact_website')->nullable()->after('contact_mobile');
            $table->string('dshe_info_center')->nullable()->after('contact_website');
            $table->string('dshe_info_mobile')->nullable()->after('dshe_info_center');
            $table->string('gro_name')->nullable()->after('dshe_info_mobile');
            $table->string('gro_designation')->nullable()->after('gro_name');
            $table->string('gro_mobile')->nullable()->after('gro_designation');
            $table->string('office_hours')->nullable()->after('gro_mobile');
            $table->text('map_embed_url')->nullable()->after('office_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_frontend_settings', function (Blueprint $table) {
            $table->dropColumn([
                'contact_mobile',
                'contact_website',
                'dshe_info_center',
                'dshe_info_mobile',
                'gro_name',
                'gro_designation',
                'gro_mobile',
                'office_hours',
                'map_embed_url',
            ]);
        });
    }
};
