<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_applications','cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('accepted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            if (Schema::hasColumn('admission_applications','cancellation_reason')) {
                $table->dropColumn('cancellation_reason');
            }
        });
    }
};
