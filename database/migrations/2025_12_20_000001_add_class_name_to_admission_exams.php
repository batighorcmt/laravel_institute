<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('admission_exams', function (Blueprint $table) {
            $table->string('class_name', 50)->after('school_id');
            $table->index(['school_id','class_name']);
        });
    }

    public function down(): void
    {
        Schema::table('admission_exams', function (Blueprint $table) {
            $table->dropIndex(['school_id','class_name']);
            $table->dropColumn('class_name');
        });
    }
};
