<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\School;
use App\Models\User;
use App\Models\Student;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        $data = [
            'total_schools' => School::count(),
            'total_users' => User::count(),
            'total_students' => Student::count(),
            'active_schools' => School::where('status', 'active')->count(),
        ];

        return view('superadmin.dashboard', compact('data'));
    }
}
