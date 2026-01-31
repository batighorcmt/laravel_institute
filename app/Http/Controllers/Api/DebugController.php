<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolClass;

class DebugController extends Controller
{
    /**
     * Return active classes for a given school_id. Query param: school_id
     */
    public function classes(Request $request)
    {
        $schoolId = $request->query('school_id') ?? $request->input('school_id');
        if (! $schoolId) {
            return response()->json(['error' => 'school_id is required'], 400);
        }

        $classes = SchoolClass::forSchool($schoolId)
            ->active()
            ->ordered()
            ->get(['id', 'name']);

        return response()->json($classes);
    }
}
