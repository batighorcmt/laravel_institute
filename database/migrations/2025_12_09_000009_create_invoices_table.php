<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('billing_period_id')->nullable();
            $table->decimal('total_due', 12, 2)->default(0);
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'billing_period_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
