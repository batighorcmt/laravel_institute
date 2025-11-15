<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('sent_by_user_id')->nullable();
            $table->string('recipient_type')->nullable(); // teacher|student|custom
            $table->string('recipient_category')->nullable(); // e.g., teacher_all, students_selected, custom_numbers
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_role')->nullable();
            $table->string('roll_number')->nullable();
            $table->string('class_name')->nullable();
            $table->string('section_name')->nullable();
            $table->string('recipient_number');
            $table->text('message');
            $table->string('status')->default('success'); // success|failed
            $table->text('response')->nullable();
            $table->timestamps();
            $table->index(['school_id','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
