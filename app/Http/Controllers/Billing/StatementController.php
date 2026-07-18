<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Services\BillingCalculationService;
use App\Models\Payment;

class StatementController extends Controller
{
    public function monthly(Request $request, int $studentId)
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
        $summary = $svc->calculateMonthlyDue($studentId, (int)$data['class_id'], $data['month']);
        $paid = Payment::where('student_id', $studentId)
            ->where('status', 'settled')
            ->whereDate('created_at', '>=', $data['month'].'-01')
            ->whereDate('created_at', '<=', $data['month'].'-31')
            ->sum('amount_paid');

        return response()->json([
            'month' => $data['month'],
            'total_due' => $summary['total_due'],
            'paid' => (float)$paid,
            'outstanding' => max(0, $summary['total_due'] - (float)$paid),
            'lines' => $summary['lines'],
        ]);
    }
}
