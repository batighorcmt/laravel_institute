<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admission_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_application_id')->constrained('admission_applications')->onDelete('cascade');
            $table->decimal('amount',10,2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('tran_id')->nullable();
            $table->string('invoice_no')->nullable();
            $table->enum('status',['Initiated','Completed','Failed'])->default('Initiated');
            $table->timestamps();
            $table->index(['admission_application_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_payments');
    }
};