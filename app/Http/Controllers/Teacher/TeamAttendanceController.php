<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\School;

class TeamAttendanceController extends Controller
{
    public function index(School $school)
    {
        return view('teacher.attendance.team.index', compact('school'));
    }
}
