<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_applications','app_id')) {
                $table->string('app_id')->unique()->after('id');
            }
            $table->string('name_en')->nullable();
            $table->string('name_bn')->nullable();
            $table->string('father_name_en')->nullable();
            $table->string('father_name_bn')->nullable();
            $table->string('mother_name_en')->nullable();
            $table->string('mother_name_bn')->nullable();
            $table->string('guardian_name_en')->nullable();
            $table->string('guardian_name_bn')->nullable();
            $table->string('gender')->nullable();
            $table->string('religion')->nullable();
            $table->string('blood_group')->nullable();
            $table->date('dob')->nullable();
            $table->string('birth_reg_no')->nullable();
            $table->string('photo')->nullable();
            $table->string('mobile')->nullable();
            $table->string('present_address')->nullable();
            $table->string('permanent_address')->nullable();
            $table->string('last_school')->nullable();
            $table->string('result')->nullable();
            $table->string('pass_year')->nullable();
            $table->string('achievement')->nullable();
            if (!Schema::hasColumn('admission_applications','payment_status')) {
                $table->enum('payment_status',['Unpaid','Paid'])->default('Unpaid');
            }
                if (!Schema::hasColumn('admission_applications','accepted_at')) {
                    $table->timestamp('accepted_at')->nullable()->after('status');
                }
        });
    }

    public function down(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            // Only drop columns we added (guard existing minimal ones)
            foreach ([
                'app_id','name_en','name_bn','father_name_en','father_name_bn','mother_name_en','mother_name_bn',
                'guardian_name_en','guardian_name_bn','gender','religion','blood_group','dob','birth_reg_no','photo',
                'mobile','present_address','permanent_address','last_school','result','pass_year','achievement','payment_status'
            ] as $col) {
                if (Schema::hasColumn('admission_applications',$col)) {
                    $table->dropColumn($col);
                }
            }
                if (Schema::hasColumn('admission_applications','accepted_at')) {
                    $table->dropColumn('accepted_at');
                }
        });
    }
};