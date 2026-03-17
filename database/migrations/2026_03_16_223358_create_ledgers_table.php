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
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->enum('type', ['income', 'expense'])->default('income');
            $table->string('category'); // e.g., 'Tuition Fee', 'Electricity Bill', 'Salary'
            $table->decimal('amount', 12, 2);
            $table->date('entry_date');
            $table->nullableMorphs('reference'); // Link to payment or expense record
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'type', 'entry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};
