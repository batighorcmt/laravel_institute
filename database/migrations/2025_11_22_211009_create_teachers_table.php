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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            
            // Personal Information
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('first_name_bn', 191)->nullable();
            $table->string('last_name_bn', 191)->nullable();
            $table->string('father_name_bn', 191)->nullable();
            $table->string('father_name_en', 191)->nullable();
            $table->string('mother_name_bn', 191)->nullable();
            $table->string('mother_name_en', 191)->nullable();
            $table->string('phone', 32)->nullable();
            
            // Login Credentials (for display to principal)
            $table->string('plain_password')->nullable();
            
            // Professional Information
            $table->string('designation', 100)->nullable();
            $table->integer('serial_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('joining_date')->nullable();
            $table->text('academic_info')->nullable();
            $table->text('qualification')->nullable();
            
            // Files
            $table->string('photo')->nullable();
            $table->string('signature')->nullable();
            
            // Status
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['school_id', 'status']);
            $table->index('serial_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
