<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('first_name_bn')->nullable();
            $table->string('last_name_bn')->nullable();
            $table->foreignId('designation_id')->nullable()->constrained('designations')->nullOnDelete();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('joining_date')->nullable();
            $table->string('photo')->nullable();
            $table->unsignedInteger('serial_number')->nullable();
            $table->string('status')->default('active');
            $table->boolean('show_on_website')->default(true);
            $table->timestamps();

            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_members');
    }
};
