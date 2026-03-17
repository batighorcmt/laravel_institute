<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('student_fees', function (Blueprint $table) {
            $table->decimal('original_amount', 10, 2)->nullable()->after('amount');
            $table->index('original_amount');
        });
    }

    public function down()
    {
        Schema::table('student_fees', function (Blueprint $table) {
            $table->dropIndex(['original_amount']);
            $table->dropColumn('original_amount');
        });
    }
};
