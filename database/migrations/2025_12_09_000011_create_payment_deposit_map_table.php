<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_deposit_map', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_id');
            $table->unsignedBigInteger('cashier_deposit_id');
            $table->primary(['payment_id', 'cashier_deposit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_deposit_map');
    }
};
