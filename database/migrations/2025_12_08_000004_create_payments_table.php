<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('fee_category_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->decimal('amount_paid', 12, 2);
            $table->decimal('discount_applied', 12, 2)->default(0);
            $table->decimal('fine_applied', 12, 2)->default(0);
            $table->enum('payment_method', ['cash', 'bkash', 'nagad', 'bank'])->default('cash');
            $table->unsignedBigInteger('collected_by_user_id')->nullable();
            $table->enum('role', ['teacher', 'cashier', 'headmaster', 'online'])->default('teacher');
            $table->enum('status', ['pending', 'settled', 'reversed'])->default('settled');
            $table->timestamp('received_at')->nullable();
            $table->unsignedBigInteger('receipt_id')->nullable();
            $table->string('external_txn_id')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'fee_category_id']);
            $table->foreign('fee_category_id')->references('id')->on('fee_categories')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
