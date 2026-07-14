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
            $table->dropColumn([
                'contact_address',
                'contact_phone',
                'contact_mobile',
                'contact_email',
                'contact_website',
            ]);
        });

        Schema::table('school_frontend_settings', function (Blueprint $table) {
            $table->string('contact_email_secondary')->nullable()->after('office_hours');
            $table->foreignId('gro_teacher_id')->nullable()->after('contact_email_secondary')->constrained('teachers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_frontend_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gro_teacher_id');
            $table->dropColumn(['contact_email_secondary']);
        });

        Schema::table('school_frontend_settings', function (Blueprint $table) {
            $table->string('contact_address')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_mobile')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_website')->nullable();
        });
    }
};
