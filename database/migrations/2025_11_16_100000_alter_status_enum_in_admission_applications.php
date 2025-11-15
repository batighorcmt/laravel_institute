<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Change enum to include cancelled; if column is already varchar this will be skipped.
        // Attempt ALTER with known previous enum values 'pending','accepted'
        try {
            DB::statement("ALTER TABLE admission_applications MODIFY COLUMN status ENUM('pending','accepted','cancelled') DEFAULT 'pending'");
        } catch (\Throwable $e) {
            // Fallback: ensure any existing records with invalid status get mapped
            // If modification failed (maybe already varchar), ignore.
        }
        // Normalize existing 'canceled' (US spelling) if any
        DB::table('admission_applications')->where('status','canceled')->update(['status'=>'cancelled']);
    }

    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE admission_applications MODIFY COLUMN status ENUM('pending','accepted') DEFAULT 'pending'");
            // Any 'cancelled' statuses revert to 'pending'
            DB::table('admission_applications')->where('status','cancelled')->update(['status'=>'pending']);
        } catch (\Throwable $e) {
            // Ignore
        }
    }
};
