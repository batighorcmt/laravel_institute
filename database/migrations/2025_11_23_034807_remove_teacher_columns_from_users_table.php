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
        Schema::table('users', function (Blueprint $table) {
            // Remove teacher-specific columns (now in teachers table)
            $table->dropColumn([
                'first_name',
                'last_name',
                'first_name_bn',
                'last_name_bn',
                'father_name_bn',
                'father_name_en',
                'mother_name_bn',
                'mother_name_en',
                'phone',
                'address',
                'date_of_birth',
                'joining_date',
                'qualification',
                'academic_info',
                'gender',
                'photo',
                'signature',
                'status',
                'plain_password',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Restore columns if needed
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('first_name_bn', 191)->nullable();
            $table->string('last_name_bn', 191)->nullable();
            $table->string('father_name_bn', 191)->nullable();
            $table->string('father_name_en', 191)->nullable();
            $table->string('mother_name_bn', 191)->nullable();
            $table->string('mother_name_en', 191)->nullable();
            $table->string('phone', 32)->nullable();
            $table->text('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('joining_date')->nullable();
            $table->text('qualification')->nullable();
            $table->text('academic_info')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('photo')->nullable();
            $table->string('signature')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->string('plain_password')->nullable();
        });
    }
};
