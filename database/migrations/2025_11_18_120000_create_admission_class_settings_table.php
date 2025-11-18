<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admission_class_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->string('class_code', 32); // e.g. 6, 7, 8, "9", "10"
            $table->decimal('fee_amount', 10, 2)->default(0);
            $table->date('deadline')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
            $table->unique(['school_id','academic_year_id','class_code'],'uniq_school_year_class');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_class_settings');
    }
};
