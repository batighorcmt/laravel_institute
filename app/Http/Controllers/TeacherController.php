<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TeacherController extends Controller
{
    /**
     * Show teacher dashboard.
     */
     public function dashboard(Request $request)
     {
         // Later: show timetable, assigned subjects, pending tasks.
         return view('teacher.dashboard');
     }
}
