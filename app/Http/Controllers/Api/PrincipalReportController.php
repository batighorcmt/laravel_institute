<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PrincipalReportController extends Controller
{
    public function attendanceSummary(Request $request)
    {
        return response()->json([
            'data' => [
                'present_percentage' => null,
                'absent_percentage' => null,
            ],
            'meta' => ['message' => 'attendance summary placeholder']
        ]);
    }

    public function examResultsSummary(Request $request)
    {
        return response()->json([
            'data' => [
                'average_score' => null,
                'top_students' => [],
            ],
            'meta' => ['message' => 'exam results summary placeholder']
        ]);
    }
}
