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
        Schema::table('student_fees', function (Blueprint $table) {
            $table->decimal('fine_waiver', 12, 2)->default(0)->after('fine_amount');
            $table->text('fine_waiver_reason')->nullable()->after('fine_waiver');
        });
    }

    public function down(): void
    {
        Schema::table('student_fees', function (Blueprint $table) {
            $table->dropColumn(['fine_waiver', 'fine_waiver_reason']);
        });
    }
};
