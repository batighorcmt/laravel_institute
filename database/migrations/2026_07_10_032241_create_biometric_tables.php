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
        Schema::create('biometric_device_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('biometric_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_group_id')->nullable()->constrained('biometric_device_groups')->nullOnDelete();
            $table->string('device_name');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable()->unique();
            $table->string('ip_address')->nullable();
            $table->string('port')->nullable();
            $table->string('location')->nullable();
            $table->string('agent_id')->nullable();
            $table->string('status')->default('offline');
            $table->timestamp('last_seen')->nullable();
            $table->timestamp('last_sync_time')->nullable();
            $table->timestamps();
        });

        Schema::create('biometric_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->enum('user_type', ['student', 'teacher']);
            $table->foreignId('student_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('biometric_id');
            $table->integer('finger_count')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('fingerprint_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biometric_profile_id')->constrained()->cascadeOnDelete();
            $table->string('finger_name')->nullable();
            $table->text('template_data');
            $table->string('algorithm')->nullable();
            $table->string('device_source')->nullable();
            $table->boolean('encrypted')->default(false);
            $table->timestamps();
        });

        Schema::create('biometric_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('biometric_devices')->nullOnDelete();
            $table->string('action');
            $table->string('record_type')->nullable();
            $table->string('status');
            $table->text('message')->nullable();
            $table->timestamps();
        });

        Schema::create('biometric_attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('biometric_devices')->nullOnDelete();
            $table->string('biometric_id');
            $table->dateTime('punch_time');
            $table->string('punch_type')->nullable();
            $table->string('sync_status')->default('pending');
            $table->timestamps();
        });

        Schema::create('device_heartbeats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('biometric_devices')->cascadeOnDelete();
            $table->string('status');
            $table->string('ip_address')->nullable();
            $table->timestamp('last_check');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_heartbeats');
        Schema::dropIfExists('biometric_attendance_logs');
        Schema::dropIfExists('biometric_sync_logs');
        Schema::dropIfExists('fingerprint_templates');
        Schema::dropIfExists('biometric_profiles');
        Schema::dropIfExists('biometric_devices');
        Schema::dropIfExists('biometric_device_groups');
    }
};
