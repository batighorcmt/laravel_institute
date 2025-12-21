<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('type'); // prottayon|certificate|testimonial
            $table->string('memo_no');
            $table->timestamp('issued_at');
            $table->string('code')->unique(); // verification code
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['school_id','type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_records');
    }
};
