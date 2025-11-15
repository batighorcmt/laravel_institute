<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('admission_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_payments','gateway_response')) {
                $table->json('gateway_response')->nullable()->after('status');
            }
            // Expand status enum if not already supporting more states (Cannot alter enum easily; add new column alternative?)
            // We'll add a generic text column for gateway_status to track raw status.
            if (!Schema::hasColumn('admission_payments','gateway_status')) {
                $table->string('gateway_status')->nullable()->after('gateway_response');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admission_payments', function (Blueprint $table) {
            if (Schema::hasColumn('admission_payments','gateway_status')) {
                $table->dropColumn('gateway_status');
            }
            if (Schema::hasColumn('admission_payments','gateway_response')) {
                $table->dropColumn('gateway_response');
            }
        });
    }
};
