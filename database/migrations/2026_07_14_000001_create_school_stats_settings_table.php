<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_stats_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('mode')->default('dynamic');
            $table->unsignedInteger('static_students_count')->nullable();
            $table->unsignedInteger('static_teachers_count')->nullable();
            $table->unsignedInteger('static_staff_count')->nullable();
            $table->unsignedInteger('static_classes_count')->nullable();
            $table->unsignedInteger('static_founding_year')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_stats_settings');
    }
};
