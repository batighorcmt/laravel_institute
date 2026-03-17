<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Update payment_method enum to include sslcommerz
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash', 'bkash', 'nagad', 'bank', 'sslcommerz') DEFAULT 'cash'");
            
            // Update status enum to include initiated
            DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending', 'settled', 'reversed', 'initiated') DEFAULT 'settled'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash', 'bkash', 'nagad', 'bank') DEFAULT 'cash'");
            DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending', 'settled', 'reversed') DEFAULT 'settled'");
        });
    }
};
