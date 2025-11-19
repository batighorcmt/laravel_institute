<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users','first_name_bn')) {
                $table->string('first_name_bn')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('users','last_name_bn')) {
                $table->string('last_name_bn')->nullable()->after('first_name_bn');
            }
            if (!Schema::hasColumn('users','father_name_bn')) {
                $table->string('father_name_bn')->nullable()->after('last_name_bn');
            }
            if (!Schema::hasColumn('users','father_name_en')) {
                $table->string('father_name_en')->nullable()->after('father_name_bn');
            }
            if (!Schema::hasColumn('users','mother_name_bn')) {
                $table->string('mother_name_bn')->nullable()->after('father_name_en');
            }
            if (!Schema::hasColumn('users','mother_name_en')) {
                $table->string('mother_name_en')->nullable()->after('mother_name_bn');
            }
            if (!Schema::hasColumn('users','joining_date')) {
                $table->date('joining_date')->nullable()->after('date_of_birth');
            }
            if (!Schema::hasColumn('users','qualification')) {
                $table->text('qualification')->nullable()->after('joining_date');
            }
            if (!Schema::hasColumn('users','academic_info')) {
                $table->text('academic_info')->nullable()->after('qualification');
            }
            if (!Schema::hasColumn('users','signature')) {
                $table->string('signature')->nullable()->after('photo');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = [
                'first_name_bn','last_name_bn','father_name_bn','father_name_en',
                'mother_name_bn','mother_name_en','joining_date','qualification','academic_info','signature'
            ];
            foreach ($cols as $c) {
                if (Schema::hasColumn('users',$c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
