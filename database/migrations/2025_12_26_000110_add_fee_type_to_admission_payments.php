<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('admission_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_payments','fee_type')) {
                $table->string('fee_type', 50)->nullable()->after('gateway_status');
                $table->index('fee_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admission_payments', function (Blueprint $table) {
            if (Schema::hasColumn('admission_payments','fee_type')) {
                $table->dropIndex(['fee_type']);
                $table->dropColumn('fee_type');
            }
        });
    }
};
