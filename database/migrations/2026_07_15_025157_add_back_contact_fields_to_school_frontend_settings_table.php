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
            $table->string('contact_address')->nullable()->after('marquee_text');
            $table->string('contact_phone')->nullable()->after('contact_address');
            $table->string('contact_mobile')->nullable()->after('contact_phone');
            $table->string('contact_email')->nullable()->after('contact_mobile');
            $table->string('contact_website')->nullable()->after('contact_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_frontend_settings', function (Blueprint $table) {
            $table->dropColumn([
                'contact_address',
                'contact_phone',
                'contact_mobile',
                'contact_email',
                'contact_website',
            ]);
        });
    }
};
