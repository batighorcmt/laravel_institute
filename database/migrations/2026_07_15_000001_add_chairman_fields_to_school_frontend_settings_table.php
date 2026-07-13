<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_frontend_settings', function (Blueprint $table) {
            $table->string('chairman_name')->nullable()->after('principal_image');
            $table->text('chairman_message')->nullable()->after('chairman_name');
            $table->string('chairman_image')->nullable()->after('chairman_message');
        });
    }

    public function down(): void
    {
        Schema::table('school_frontend_settings', function (Blueprint $table) {
            $table->dropColumn(['chairman_name', 'chairman_message', 'chairman_image']);
        });
    }
};
