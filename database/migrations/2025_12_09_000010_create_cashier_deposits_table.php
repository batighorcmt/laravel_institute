<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cashier_deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cashier_user_id');
            $table->date('date');
            $table->decimal('total_amount', 12, 2);
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashier_deposits');
    }
};
