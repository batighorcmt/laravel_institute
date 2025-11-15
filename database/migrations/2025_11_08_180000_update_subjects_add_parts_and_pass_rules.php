<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects','has_creative')) {
                $table->boolean('has_creative')->default(false)->after('status');
                $table->unsignedSmallInteger('creative_full_mark')->nullable()->after('has_creative');
                $table->unsignedSmallInteger('creative_pass_mark')->nullable()->after('creative_full_mark');
            }
            if (!Schema::hasColumn('subjects','has_mcq')) {
                $table->boolean('has_mcq')->default(false)->after('creative_pass_mark');
                $table->unsignedSmallInteger('mcq_full_mark')->nullable()->after('has_mcq');
                $table->unsignedSmallInteger('mcq_pass_mark')->nullable()->after('mcq_full_mark');
            }
            if (!Schema::hasColumn('subjects','has_practical')) {
                $table->boolean('has_practical')->default(false)->after('mcq_pass_mark');
                $table->unsignedSmallInteger('practical_full_mark')->nullable()->after('has_practical');
                $table->unsignedSmallInteger('practical_pass_mark')->nullable()->after('practical_full_mark');
            }
            if (!Schema::hasColumn('subjects','pass_type')) {
                $table->enum('pass_type',[ 'overall','per-part' ])->default('overall')->after('practical_pass_mark');
            }
            if (!Schema::hasColumn('subjects','overall_full_mark')) {
                $table->unsignedSmallInteger('overall_full_mark')->nullable()->after('pass_type');
                $table->unsignedSmallInteger('overall_pass_mark')->nullable()->after('overall_full_mark');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            foreach ([
                'has_creative','creative_full_mark','creative_pass_mark',
                'has_mcq','mcq_full_mark','mcq_pass_mark',
                'has_practical','practical_full_mark','practical_pass_mark',
                'pass_type','overall_full_mark','overall_pass_mark'
            ] as $col) {
                if (Schema::hasColumn('subjects',$col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
