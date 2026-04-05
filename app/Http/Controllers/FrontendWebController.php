<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\Request;

class FrontendWebController extends Controller
{
    public function index(Request $request)
    {
        $domain = $request->getHost();
        $superAdminDomain = 'institute.batighorbd.com';

        // Superadmin domain doesn't have a frontend, redirect to login
        if ($domain === $superAdminDomain) {
            return redirect('/login');
        }

        // Get the active school id from config setup by identify school middleware
        $schoolId = config('school.id');
        
        // For local testing if domain is localhost and no school is identified, redirect to login
        if (!$schoolId && app()->environment('local') && ($domain === 'localhost' || $domain === '127.0.0.1')) {
            return redirect('/login');
        }

        $school = School::find($schoolId);

        if (!$school) {
            return redirect('/login');
        }

        // Check if frontend_website module is enabled
        if (!$school->hasModule('frontend_website')) {
            return redirect('/login');
        }

        // Render the Vue application view
        return view('frontend.index', [
            'school' => $school
        ]);
    }
}
