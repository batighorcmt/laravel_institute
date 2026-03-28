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
        Schema::table('schools', function (Blueprint $table) {
            $table->string('mobile')->nullable()->after('phone');
            $table->string('eiin')->nullable()->after('code');
            $table->string('mpo_code')->nullable()->after('eiin');
            $table->string('short_address_bn')->nullable()->after('address_bn');
            $table->string('short_address_en')->nullable()->after('short_address_bn');
            $table->string('founding_year')->nullable()->after('short_address_en');
            $table->string('school_code')->nullable()->after('founding_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['mobile', 'eiin', 'mpo_code', 'short_address_bn', 'short_address_en', 'founding_year', 'school_code']);
        });
    }
};
