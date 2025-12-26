<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_applications','admission_permission')) {
                $table->boolean('admission_permission')->default(false)->after('accepted_at');
            }
            if (!Schema::hasColumn('admission_applications','admission_fee')) {
                $table->decimal('admission_fee', 10, 2)->nullable()->after('admission_permission');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            if (Schema::hasColumn('admission_applications','admission_fee')) {
                $table->dropColumn('admission_fee');
            }
            if (Schema::hasColumn('admission_applications','admission_permission')) {
                $table->dropColumn('admission_permission');
            }
        });
    }
};
