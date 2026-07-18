<?php

namespace App\Http\Controllers\Principal\Institute;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;

/**
 * Teacher attendance settings (time windows, photo/location requirements)
 * used to live on this app-only page, backed by TeacherAttendanceSetting.
 * Now that attendance is channel-universal (app/web/biometric), all of that
 * lives on one unified page — Principal\AttendanceSettingsController,
 * backed by SchoolAttendanceSetting. This controller only exists so old
 * links/bookmarks to this route don't 404; it redirects to the real page.
 */
class TeacherAttendanceSettingsController extends Controller
{
    public function index(School $school)
    {
        return redirect()->route('principal.institute.attendance.settings', $school);
    }

    public function store(Request $request, School $school)
    {
        return redirect()->route('principal.institute.attendance.settings', $school);
    }
}
