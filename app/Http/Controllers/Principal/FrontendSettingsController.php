<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\School;

class FrontendSettingsController extends Controller
{
    public function index(School $school)
    {
        return view('principal.frontend.settings', compact('school'));
    }
}
