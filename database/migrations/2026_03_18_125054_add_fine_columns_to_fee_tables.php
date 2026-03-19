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
            $table->boolean('fine_enabled')->default(true);
        });

        Schema::table('fee_categories', function (Blueprint $table) {
            $table->boolean('has_fine')->default(false)->after('frequency');
            $table->decimal('fine_amount', 10, 2)->default(0)->after('has_fine');
            $table->enum('fine_type', ['fixed', 'percentage'])->default('fixed')->after('fine_amount');
            $table->integer('late_fee_day')->nullable()->after('fine_type'); // Day of month for Monthly fees
        });

        Schema::table('student_fees', function (Blueprint $table) {
            $table->decimal('fine_amount', 12, 2)->default(0)->after('paid_amount');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('fine_enabled');
        });

        Schema::table('fee_categories', function (Blueprint $table) {
            $table->dropColumn(['has_fine', 'fine_amount', 'fine_type', 'late_fee_day']);
        });

        Schema::table('student_fees', function (Blueprint $table) {
            $table->dropColumn('fine_amount');
        });
    }
};
