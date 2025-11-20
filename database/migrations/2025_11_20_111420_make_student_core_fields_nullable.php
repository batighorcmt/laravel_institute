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
        Schema::table('students', function (Blueprint $table) {
            // Make previously required fields nullable to allow minimal bulk import
            $table->date('date_of_birth')->nullable()->change();
            $table->enum('gender', ['male','female'])->nullable()->change();
            $table->string('father_name')->nullable()->change();
            $table->string('mother_name')->nullable()->change();
            $table->string('guardian_phone')->nullable()->change();
            $table->text('address')->nullable()->change();
            $table->date('admission_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Revert to NOT NULL with sensible defaults (may fail if nulls exist)
            $table->date('date_of_birth')->nullable(false)->change();
            $table->enum('gender', ['male','female'])->nullable(false)->change();
            $table->string('father_name')->nullable(false)->change();
            $table->string('mother_name')->nullable(false)->change();
            $table->string('guardian_phone')->nullable(false)->change();
            $table->text('address')->nullable(false)->change();
            $table->date('admission_date')->nullable(false)->change();
        });
    }
};
