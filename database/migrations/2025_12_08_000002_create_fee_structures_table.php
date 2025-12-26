<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('fee_category_id');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('BDT');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['class_id', 'fee_category_id']);
            $table->foreign('fee_category_id')->references('id')->on('fee_categories')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structures');
    }
};
