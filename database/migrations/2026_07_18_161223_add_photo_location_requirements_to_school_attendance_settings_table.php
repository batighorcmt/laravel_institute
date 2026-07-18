<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('school_attendance_settings', function (Blueprint $table) {
            $table->boolean('require_photo')->default(true)->after('teacher_check_out_end');
            $table->boolean('require_location')->default(true)->after('require_photo');
        });

        // Teacher attendance used to be configured on a separate, app-only
        // settings page/table (teacher_attendance_settings). Now that
        // attendance is channel-universal (app/web/biometric), that page's
        // require_photo/require_location choices are consolidated here so
        // existing schools keep whatever they'd already configured instead
        // of silently resetting to the column defaults above.
        if (Schema::hasTable('teacher_attendance_settings')) {
            $legacy = DB::table('teacher_attendance_settings')->get(['school_id', 'require_photo', 'require_location']);
            foreach ($legacy as $row) {
                // updateOrInsert (not update): a school may not have a
                // school_attendance_settings row yet (it's created lazily on
                // first save of the unified page), but could already have a
                // legacy teacher_attendance_settings row whose choice must
                // still carry forward. Other columns fall back to their own
                // DB-level defaults on insert.
                DB::table('school_attendance_settings')->updateOrInsert(
                    ['school_id' => $row->school_id],
                    [
                        'require_photo' => $row->require_photo,
                        'require_location' => $row->require_location,
                    ]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_attendance_settings', function (Blueprint $table) {
            $table->dropColumn(['require_photo', 'require_location']);
        });
    }
};
