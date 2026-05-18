<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('sms_logs')) {
            return;
        }

        $column = collect(DB::select("SHOW COLUMNS FROM `sms_logs` WHERE Field = 'id'"))->first();
        if ($column === null) {
            return;
        }

        $extra = strtolower((string) ($column->Extra ?? ''));
        if (str_contains($extra, 'auto_increment')) {
            return;
        }

        $maxId = (int) DB::table('sms_logs')->max('id');

        DB::statement('ALTER TABLE `sms_logs` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

        if ($maxId > 0) {
            DB::statement('ALTER TABLE `sms_logs` AUTO_INCREMENT = '.($maxId + 1));
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('sms_logs')) {
            return;
        }

        $column = collect(DB::select("SHOW COLUMNS FROM `sms_logs` WHERE Field = 'id'"))->first();
        if ($column === null) {
            return;
        }

        $extra = strtolower((string) ($column->Extra ?? ''));
        if (! str_contains($extra, 'auto_increment')) {
            return;
        }

        DB::statement('ALTER TABLE `sms_logs` MODIFY `id` BIGINT UNSIGNED NOT NULL');
    }
};
