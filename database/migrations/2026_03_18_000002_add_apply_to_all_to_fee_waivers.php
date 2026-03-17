<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('fee_waivers', function (Blueprint $table) {
            $table->boolean('apply_to_all')->default(false)->after('fee_structure_id');
        });
    }

    public function down()
    {
        Schema::table('fee_waivers', function (Blueprint $table) {
            $table->dropColumn('apply_to_all');
        });
    }
};
