<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number');
            $table->unsignedBigInteger('student_id');
            $table->decimal('total_amount', 12, 2);
            $table->timestamp('printed_at')->nullable();
            $table->unsignedBigInteger('issued_by_user_id')->nullable();
            $table->timestamps();

            $table->unique(['receipt_number']);
            $table->index(['student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
