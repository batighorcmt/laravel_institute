<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeCategory;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\StudentFee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeReportController extends Controller
{
    /**
     * Date Range Collection Report
     */
    public function collectionByDate(Request $request)
    {
        try {
            $request->validate([
                'from_date' => 'required|date',
                'to_date' => 'required|date',
                'payment_method' => 'nullable|string'
            ]);

            $schoolId = $request->attributes->get('current_school_id') ?? $request->user()?->primarySchool()?->id;
            // Compute totals from payment_items first
            $itemsQuery = \App\Models\PaymentItem::join('payments', 'payment_items.payment_id', '=', 'payments.id')
                ->where('payments.school_id', $schoolId)
                ->whereBetween('payments.received_at', [$request->from_date, $request->to_date])
                ->where('payments.status', 'settled');

            if ($request->payment_method) {
                $itemsQuery->where('payments.payment_method', $request->payment_method);
            }

            $itemsSummary = (clone $itemsQuery)
                ->select('payments.payment_method', DB::raw('SUM(payment_items.amount) as total'))
                ->groupBy('payments.payment_method')
                ->get()->keyBy('payment_method');

            $itemsDaily = (clone $itemsQuery)
                ->select(DB::raw('DATE(payments.received_at) as date'), DB::raw('SUM(payment_items.amount) as total'))
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get()->keyBy('date');

            // Compute totals for payments that don't have payment_items (fallback)
            $paymentsOnlyQuery = \App\Models\Payment::where('payments.school_id', $schoolId)
                ->whereBetween('payments.received_at', [$request->from_date, $request->to_date])
                ->where('payments.status', 'settled')
                ->leftJoin('payment_items', 'payments.id', '=', 'payment_items.payment_id')
                ->whereNull('payment_items.id');

            if ($request->payment_method) {
                $paymentsOnlyQuery->where('payments.payment_method', $request->payment_method);
            }

            $paymentsOnlySummary = (clone $paymentsOnlyQuery)
                ->select('payments.payment_method', DB::raw('COALESCE(SUM(payments.amount_paid),0) as total'))
                ->groupBy('payments.payment_method')
                ->get()->keyBy('payment_method');

            $paymentsOnlyDaily = (clone $paymentsOnlyQuery)
                ->select(DB::raw('DATE(payments.received_at) as date'), DB::raw('COALESCE(SUM(payments.amount_paid),0) as total'))
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get()->keyBy('date');

            // Merge summaries so both itemized and non-itemized payments count
            $methodKeys = collect(array_unique(array_merge($itemsSummary->keys()->toArray(), $paymentsOnlySummary->keys()->toArray())));
            $summary = $methodKeys->map(function($m) use ($itemsSummary, $paymentsOnlySummary) {
                $a = $itemsSummary->has($m) ? (float)$itemsSummary->get($m)->total : 0.0;
                $b = $paymentsOnlySummary->has($m) ? (float)$paymentsOnlySummary->get($m)->total : 0.0;
                return ['payment_method' => $m, 'total' => $a + $b];
            })->values();

            // Merge daily totals by date
            $dateKeys = collect(array_unique(array_merge($itemsDaily->keys()->toArray(), $paymentsOnlyDaily->keys()->toArray())))->sort()->reverse();
            $daily = $dateKeys->map(function($d) use ($itemsDaily, $paymentsOnlyDaily) {
                $a = $itemsDaily->has($d) ? (float)$itemsDaily->get($d)->total : 0.0;
                $b = $paymentsOnlyDaily->has($d) ? (float)$paymentsOnlyDaily->get($d)->total : 0.0;
                return (object)['date' => $d, 'total' => $a + $b];
            })->values();

            // For details keep returning payments (with their items) so UI can show receipts;
            $payments = \App\Models\Payment::where('payments.school_id', $schoolId)
                ->whereBetween('received_at', [$request->from_date, $request->to_date])
                ->where('payments.status', 'settled')
                ->with('paymentItems')
                ->latest('received_at')
                ->paginate(50);

            return response()->json([
                'summary' => $summary,
                'daily_stats' => $daily,
                'details' => $payments
            ]);
        } catch (\Throwable $e) {
            \Log::error('FeeReportController.collectionByDate error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'internal_exception',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Teacher Wise Collection Report
     */
    public function collectionByTeacher(Request $request)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date'
        ]);
        $schoolId = $request->attributes->get('current_school_id') ?? $request->user()->primarySchool()?->id;
        $report = Payment::where('payments.school_id', $schoolId)
            ->whereBetween('received_at', [$request->from_date, $request->to_date])
            ->where('payments.status', 'settled')
            ->join('users', 'payments.collected_by_user_id', '=', 'users.id')
            ->select(
                'users.name as teacher_name',
                'payments.payment_method',
                DB::raw('COUNT(payments.id) as tx_count'),
                DB::raw('SUM(payments.amount_paid) as total_amount')
            )
            ->groupBy('users.name', 'payments.payment_method')
            ->get()
            ->groupBy('teacher_name');

        return response()->json($report);
    }

    /**
     * Return list of paid students matching filters (for collection reports page)
     */
    public function collectionPaidStudents(Request $request)
    {
        try {
            $schoolId = $request->attributes->get('current_school_id') ?? $request->user()?->primarySchool()?->id;

            $query = DB::table('payment_items')
                ->join('payments', 'payment_items.payment_id', '=', 'payments.id')
                ->join('students', 'payments.student_id', '=', 'students.id')
                ->leftJoin('student_fees', 'payment_items.student_fee_id', '=', 'student_fees.id')
                ->leftJoin('student_enrollments as se', function($j) use ($schoolId) {
                    $j->on('payments.student_id', '=', 'se.student_id')
                      ->where('se.school_id', '=', $schoolId)
                      ->where('se.status', '=', 'active');
                })
                ->leftJoin('fee_structures', 'student_fees.fee_structure_id', '=', 'fee_structures.id')
                ->leftJoin('fee_categories', function($j) {
                    $j->on('fee_structures.fee_category_id', '=', 'fee_categories.id');
                })
                ->leftJoin('classes', 'se.class_id', '=', 'classes.id')
                ->leftJoin('sections', 'se.section_id', '=', 'sections.id')
                ->where('payments.school_id', $schoolId)
                ->where('payments.status', 'settled')
                ->select(
                    'payments.id as payment_id',
                    'payments.received_at as paid_at',
                    'payment_items.amount as amount',
                    'students.student_name_bn',
                    'students.student_name_en',
                    'students.student_id as student_id',
                    'classes.name as class_name_en',
                    'classes.bangla_name as class_name_bn',
                    'sections.name as section_name_en',
                    'sections.bangla_name as section_name_bn',
                    'se.roll_no as roll_no',
                    'student_fees.month as fee_month',
                    DB::raw('COALESCE(fee_categories.name, "General") as category_name_bn'),
                    DB::raw('COALESCE(fee_categories.name, "General") as category_name_en')
                );

            if ($request->filled('from_date') && $request->filled('to_date')) {
                $query->whereDate('payments.received_at', '>=', $request->from_date)
                      ->whereDate('payments.received_at', '<=', $request->to_date);
            }
            if ($request->filled('class_id')) {
                $query->where('se.class_id', $request->class_id);
            }
            if ($request->filled('section_id')) {
                $query->where('se.section_id', $request->section_id);
            }
            if ($request->filled('fee_category_id')) {
                $query->where('fee_structures.fee_category_id', $request->fee_category_id);
            }
            if ($request->filled('month')) {
                // month in YYYY-MM
                $month = $request->month;
                $query->whereRaw("DATE_FORMAT(payments.received_at, '%Y-%m') = ?", [$month]);
            }

            $rows = $query->orderBy('payments.received_at', 'desc')->get();

            return response()->json($rows);
        } catch (\Throwable $e) {
            \Log::error('FeeReportController.collectionPaidStudents error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'error' => 'internal_exception',
                'message' => 'রিপোর্ট লোডে সমস্যা হয়েছে, সার্ভার লগ চেক করুন.'
            ], 500);
        }
    }

    /**
     * Fee Wise Due Report
     */
    public function dueReport(Request $request)
    {
        $schoolId = $request->attributes->get('current_school_id') ??
               $request->user()?->primarySchool()?->id ??
               $request->user()?->activeSchoolRoles()?->first()?->school_id;

        // Compute totals using student_fees for total assigned amounts and payment_items/payments
        // for actual collected amounts so the summary reflects real collections.
        $report = StudentFee::from('student_fees')
            ->where('student_fees.school_id', $schoolId)
            ->join('fee_structures', 'student_fees.fee_structure_id', '=', 'fee_structures.id')
            ->join('fee_categories', 'fee_structures.fee_category_id', '=', 'fee_categories.id')
            ->leftJoin('payment_items', 'payment_items.student_fee_id', '=', 'student_fees.id')
            ->leftJoin('payments', function($join) use ($schoolId) {
                $join->on('payment_items.payment_id', '=', 'payments.id')
                     ->where('payments.school_id', $schoolId)
                     ->where('payments.status', 'settled');
            })
            ->select(
                'fee_categories.name as category_name',
                DB::raw('SUM(student_fees.amount) as total_amount'),
                DB::raw('COALESCE(SUM(payment_items.amount), 0) as total_paid'),
                DB::raw('SUM(student_fees.amount) - COALESCE(SUM(payment_items.amount), 0) as total_due'),
                DB::raw('COUNT(DISTINCT student_fees.id) as record_count')
            )
            // include all student_fees so collected (payment_items) is counted even when
            // a fee is fully paid (status = 'paid') — otherwise collected appears as 0
            // for categories where fees were already settled.
            ->groupBy('fee_categories.name')
            ->get();

        return response()->json($report);
    }

    /**
     * Get student-wise due listing for a class/section
     */
    public function studentDues(Request $request)
    {
        try {
            $user = $request->user();
            $schoolId = $request->attributes->get('current_school_id') ??
                       $user?->primarySchool()?->id ??
                       $user?->activeSchoolRoles()?->first()?->school_id;

            $classId = $request->query('class_id');
            $sectionId = $request->query('section_id');

            $query = \App\Models\StudentEnrollment::where('student_enrollments.school_id', $schoolId)
                ->where('student_enrollments.status', 'active')
                ->join('students', 'student_enrollments.student_id', '=', 'students.id')
                ->select(
                    'student_enrollments.*',
                    'students.id as student_real_id',
                    'students.student_id as student_code',
                    'students.student_name_bn',
                    'students.student_name_en'
                )
                ->with(['student.fees' => function($q) use ($schoolId) {
                    $q->where('school_id', $schoolId)->whereIn('status', ['unpaid', 'partial']);
                }]);

            if ($classId) {
                $query->where('student_enrollments.class_id', $classId);
            }
            if ($sectionId) {
                $query->where('student_enrollments.section_id', $sectionId);
            }

            $students = $query->get()->map(function($enrollment) {
                $student = $enrollment->student ?? null;
                $totalDue = 0;
                if ($student && isset($student->fees)) {
                    $totalDue = $student->fees->sum(function($f) {
                        return (float)$f->amount - (float)$f->paid_amount;
                    });
                }

                // fallback values using selected columns
                $studentId = $enrollment->student_code ?? ($student->student_id ?? null);
                $name = $enrollment->student_name_bn ?: ($enrollment->student_name_en ?? ($student->student_name_en ?? null));

                return [
                    'id' => $enrollment->student_real_id ?? ($student->id ?? null),
                    'student_id' => $studentId,
                    'name' => $name,
                    'roll' => $enrollment->roll_no ?? null,
                    'total_due' => $totalDue,
                ];
            })->filter(fn($s) => $s['total_due'] > 0)->values();

            return response()->json($students);
        } catch (\Throwable $e) {
            \Log::error('FeeReportController.studentDues error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'internal_exception',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
