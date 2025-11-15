<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ParentController extends Controller
{
    /**
     * Show parent dashboard.
     */
    public function dashboard(Request $request)
    {
        // Later: child progress, attendance summary, notices.
        return view('parent.dashboard');
    }
}
