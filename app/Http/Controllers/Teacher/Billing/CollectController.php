<?php

namespace App\Http\Controllers\Teacher\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CollectController extends Controller
{
    public function create(Request $request, \App\Models\School $school)
    {
        $user = $request->user();
        // TODO: Fetch classes assigned to this teacher for the given school.
        // Fallback: show collect view with school context; UI should filter by class.
        return view('billing.collect', [
            'school' => $school,
            'teacher' => $user,
        ]);
    }
}
