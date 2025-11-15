<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('class_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id');
            $table->unsignedInteger('period_count')->default(8);
            $table->timestamps();

            $table->unique(['school_id','class_id','section_id'], 'uniq_school_class_section');
            $table->index(['class_id','section_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_periods');
    }
};
