<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('fee_category_id')->nullable();
            $table->enum('type', ['percent', 'fixed']);
            $table->decimal('value', 12, 2);
            $table->string('start_month', 7)->nullable();
            $table->string('end_month', 7)->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'fee_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
