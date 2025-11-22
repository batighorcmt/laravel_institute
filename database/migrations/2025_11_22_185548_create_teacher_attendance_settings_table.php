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
        Schema::create('teacher_attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->unique();
            $table->time('check_in_start')->default('08:00:00'); // Check-in can start from
            $table->time('check_in_end')->default('09:00:00'); // On-time check-in deadline
            $table->time('late_threshold')->default('09:30:00'); // After this is considered late
            $table->time('check_out_start')->default('14:00:00'); // Check-out can start from
            $table->time('check_out_end')->default('17:00:00'); // Normal check-out time
            $table->boolean('require_photo')->default(true);
            $table->boolean('require_location')->default(true);
            $table->timestamps();
            
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_attendance_settings');
    }
};
