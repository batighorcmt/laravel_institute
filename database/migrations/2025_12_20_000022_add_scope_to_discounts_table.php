<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            if (!Schema::hasColumn('discounts', 'scope')) {
                $table->enum('scope', ['fee', 'fine'])->default('fee')->after('value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            if (Schema::hasColumn('discounts', 'scope')) {
                $table->dropColumn('scope');
            }
        });
    }
};
