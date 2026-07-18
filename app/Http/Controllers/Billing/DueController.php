<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Services\BillingCalculationService;

class DueController extends Controller
{
    public function show(Request $request, int $studentId)
    {
        $data = $request->validate([
            'class_id' => 'required|integer',
            'month' => 'required|string', // YYYY-MM
        ]);

        $student = Student::findOrFail($studentId);
        $schoolId = $request->attributes->get('current_school_id') ?? $request->user()?->primarySchool()?->id;

        if ($student->school_id !== $schoolId) {
            abort(404);
        }

        $user = $request->user();
        if (! $user->isSuperAdmin() && ! $user->isPrincipal($schoolId) && ! $user->isCashier($schoolId)) {
            abort(403, 'অননুমোদিত');
        }

        $svc = new BillingCalculationService();
        $result = $svc->calculateMonthlyDue($studentId, (int)$data['class_id'], $data['month']);
        return response()->json($result);
    }
}
