<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\BillingCalculationService;

class DueController extends Controller
{
    public function show(Request $request, int $studentId)
    {
        $data = $request->validate([
            'class_id' => 'required|integer',
            'month' => 'required|string', // YYYY-MM
        ]);

        $svc = new BillingCalculationService();
        $result = $svc->calculateMonthlyDue($studentId, (int)$data['class_id'], $data['month']);
        return response()->json($result);
    }
}
