<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('fee_waivers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('fee_category_id')->nullable();
            $table->unsignedBigInteger('fee_structure_id')->nullable();
            $table->enum('waiver_type', ['full','amount','percent'])->default('full');
            $table->decimal('waiver_value', 10, 2)->nullable();
            $table->boolean('is_recurring')->default(true);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'student_id']);
            $table->index(['fee_category_id']);
            $table->index(['fee_structure_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('fee_waivers');
    }
};
