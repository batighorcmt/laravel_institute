<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ParentController extends Controller
{
    public function children(Request $request)
    {
        return response()->json([
            'data' => [],
            'meta' => ['message' => 'children list placeholder']
        ]);
    }

    public function homework(Request $request)
    {
        return response()->json([
            'data' => [],
            'meta' => ['message' => 'parent homework list placeholder']
        ]);
    }

    public function attendance(Request $request)
    {
        return response()->json([
            'data' => [],
            'meta' => ['message' => 'student attendance placeholder']
        ]);
    }

    public function examResults(Request $request)
    {
        return response()->json([
            'data' => [],
            'meta' => ['message' => 'exam results placeholder']
        ]);
    }

    public function leavesIndex(Request $request)
    {
        return response()->json([
            'data' => [],
            'meta' => ['message' => 'leave applications placeholder']
        ]);
    }

    public function leavesStore(Request $request)
    {
        $validated = $request->validate([
            'reason' => ['required','string'],
            'from_date' => ['required','date'],
            'to_date' => ['required','date','after_or_equal:from_date'],
        ]);

        return response()->json([
            'saved' => true,
            'leave' => $validated,
            'meta' => ['message' => 'leave create placeholder']
        ], 201);
    }
}
