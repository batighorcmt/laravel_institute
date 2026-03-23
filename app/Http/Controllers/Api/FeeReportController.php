<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeCategory;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\StudentFee;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FeeReportController extends Controller
{
    /**
     * Get All Fee Categories
     */
    public function getCategories(Request $request)
    {
        try {
            $user = $request->user();
            $schoolId = $request->attributes->get('current_school_id') ?? $user?->primarySchool()?->id;

            $categories = FeeCategory::where('school_id', $schoolId)
                ->orderBy('name', 'asc')
                ->get();

            return response()->json($categories);
        } catch (\Exception $e) {
            Log::error('Get Categories Error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Teacher Collections & Summary
     */
    public function teacherCollections(Request $request)
    {
        try {
            $user = $request->user();
            $schoolId = $request->attributes->get('current_school_id') ?? $user?->primarySchool()?->id;

            // 1. Identify teacher and assigned sections
            $teacher = $user->teacher; 
            if (!$teacher) {
                return response()->json(['error' => 'Teacher profile not found'], 404);
            }

            $sections = Section::where('class_teacher_id', $teacher->id)
                ->where('school_id', $schoolId)
                ->with('class')
                ->get();

            $sectionIds = $sections->pluck('id')->toArray();

            // 2. Summary remains similar but we ensure we use provided filters
            $summary = [];
            if (!empty($sectionIds)) {
                $summaryQuery = StudentFee::join('students', 'student_fees.student_id', '=', 'students.id')
                    ->join('student_enrollments', function($join) use ($sectionIds) {
                        $join->on('students.id', '=', 'student_enrollments.student_id')
                            ->whereIn('student_enrollments.section_id', $sectionIds)
                            ->where('student_enrollments.status', 'active');
                    })
                    ->join('fee_structures', 'student_fees.fee_structure_id', '=', 'fee_structures.id')
                    ->join('fee_categories', 'fee_structures.fee_category_id', '=', 'fee_categories.id')
                    ->where('student_fees.school_id', $schoolId);

                if ($request->filled('month')) {
                    $summaryQuery->where('student_fees.month', $request->month);
                }
                if ($request->filled('fee_category_id')) {
                    $summaryQuery->where('fee_structures.fee_category_id', $request->fee_category_id);
                }

                $summary = $summaryQuery->select(
                        'fee_categories.name as category_name',
                        DB::raw('COUNT(DISTINCT student_fees.student_id) as student_count'),
                        DB::raw('SUM(student_fees.amount + student_fees.fine_amount - student_fees.fine_waiver) as total_payable'),
                        DB::raw('SUM(student_fees.paid_amount) as total_paid')
                    )
                    ->groupBy('fee_categories.id', 'fee_categories.name')
                    ->get()
                    ->map(function($item) {
                        return [
                            'category' => $item->category_name,
                            'student_count' => $item->student_count,
                            'payable' => round($item->total_payable, 2),
                            'paid' => round($item->total_paid, 2),
                            'due' => round($item->total_payable - $item->total_paid, 2)
                        ];
                    });
            }

            // 3. Detailed Collections: Paginated and Grouped by Receipt (Payment)
            $paymentsQuery = \App\Models\Payment::query()
                ->where('collected_by_user_id', $user->id)
                ->where('school_id', $schoolId)
                ->where('status', 'settled')
                ->with([
                    'student.currentEnrollment.class', 
                    'student.currentEnrollment.section',
                    'paymentItems.studentFee.feeStructure.category'
                ]);

            if ($request->filled('from_date')) {
                $paymentsQuery->whereDate('received_at', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $paymentsQuery->whereDate('received_at', '<=', $request->to_date);
            }
            if ($request->filled('fee_category_id')) {
                $paymentsQuery->whereHas('paymentItems.studentFee.feeStructure', function($q) use ($request) {
                    $q->where('fee_category_id', $request->fee_category_id);
                });
            }
            if ($request->filled('month')) {
                $paymentsQuery->whereHas('paymentItems.studentFee', function($q) use ($request) {
                    $q->where('month', $request->month);
                });
            }
            if ($request->filled('student_id')) {
                $paymentsQuery->whereHas('student', function($q) use ($request) {
                    $q->where('student_id', 'like', '%' . $request->student_id . '%');
                });
            }

            $paginatedPayments = $paymentsQuery->orderBy('received_at', 'desc')->paginate(10);

            $collections = collect($paginatedPayments->items())->map(function($p) {
                $enrollment = $p->student->currentEnrollment ?? null;
                
                return [
                    'id' => $p->id,
                    'receipt_no' => $p->payment_number ?? ($p->trx_id ?? 'N/A'),
                    'received_at' => $p->received_at ? $p->received_at->format('Y-m-d H:i:s') : null,
                    'amount_paid' => $p->amount_paid,
                    'payment_method' => $p->payment_method,
                    'student' => [
                        'id' => $p->student->id ?? null,
                        'student_id' => $p->student->student_id ?? null,
                        'name' => $p->student->student_name_bn ?: ($p->student->student_name_en ?? 'Unknown'),
                        'photo_url' => $p->student->photo_url ?? null,
                        'class_name' => $enrollment->class->name ?? ($enrollment->class->bangla_name ?? 'N/A'),
                        'section_name' => $enrollment->section->name ?? ($enrollment->section->bangla_name ?? 'N/A'),
                        'roll' => $enrollment->roll_no ?? 'N/A',
                    ],
                    'items' => $p->paymentItems->map(function($item) {
                        return [
                            'category' => $item->studentFee->feeStructure->category->name ?? 'Fee',
                            'month' => $item->studentFee->month ?? null,
                            'amount' => $item->amount,
                            'fine' => $item->studentFee->fine_amount ?? 0,
                            'waiver' => $item->studentFee->fine_waiver ?? 0,
                        ];
                    })
                ];
            });

            return response()->json([
                'teacher_name' => $user->name,
                'sections' => $sections->map(fn($s) => ($s->class->name ?? '') . ' (' . $s->name . ')')->implode(', '),
                'summary' => $summary,
                'collections' => $collections,
                'meta' => [
                    'current_page' => $paginatedPayments->currentPage(),
                    'last_page' => $paginatedPayments->lastPage(),
                    'total' => $paginatedPayments->total(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Teacher Collections Error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Teacher Cash Transfer (calculated)
     */
    public function teacherCashTransfer(Request $request)
    {
        try {
            $request->validate([
                'from_date' => 'required|date',
                'to_date' => 'required|date'
            ]);

            $user = $request->user();
            $schoolId = $request->attributes->get('current_school_id') ?? $user?->primarySchool()?->id;

            $fromDate = Carbon::parse($request->from_date)->startOfDay();
            $toDate = Carbon::parse($request->to_date)->endOfDay();

            // Total Collected by teacher in date range (across ALL methods for summary)
            $total_collected = \App\Models\Payment::where('school_id', $schoolId)
                ->where('collected_by_user_id', $user->id)
                ->where('status', 'settled')
                ->whereBetween('received_at', [$fromDate, $toDate])
                ->sum('amount_paid');

            // Total Actually Received by cashier
            $total_received = \DB::table('teacher_deposits')
                ->where('teacher_id', $user->id)
                ->where('status', 'received')
                ->whereBetween('deposit_date', [$fromDate->format('Y-m-d'), $toDate->format('Y-m-d')])
                ->sum('amount');
            
            // Total Pending (Requested and not yet received/rejected)
            $total_pending = \DB::table('teacher_deposits')
                ->where('teacher_id', $user->id)
                ->where('status', 'pending')
                ->whereBetween('deposit_date', [$fromDate->format('Y-m-d'), $toDate->format('Y-m-d')])
                ->sum('amount');

            // Remaining Hand Cash = Total Collected - Total Received (Pending does NOT subtract yet)
            $total_remaining = $total_collected - $total_received;

            // Breakdown of CASH collections only
            $breakdown = \App\Models\PaymentItem::join('payments', 'payment_items.payment_id', '=', 'payments.id')
                ->join('student_fees', 'payment_items.student_fee_id', '=', 'student_fees.id')
                ->join('fee_structures', 'student_fees.fee_structure_id', '=', 'fee_structures.id')
                ->join('fee_categories', 'fee_structures.fee_category_id', '=', 'fee_categories.id')
                ->where('payments.school_id', $schoolId)
                ->where('payments.collected_by_user_id', $user->id)
                ->where('payments.payment_method', 'cash')
                ->where('payments.status', 'settled')
                ->whereBetween('payments.received_at', [$fromDate, $toDate])
                ->select(
                    'fee_categories.id as fee_category_id',
                    'fee_categories.name as category_name',
                    'student_fees.month',
                    \DB::raw('SUM(payment_items.amount) as collected_amount')
                )
                ->groupBy('fee_categories.id', 'fee_categories.name', 'student_fees.month')
                ->get();

            // Calculate remaining available to request per sector
            foreach ($breakdown as $item) {
                // Already processed (pending OR received)
                $processed = (float) \DB::table('teacher_deposits')
                    ->where('teacher_id', $user->id)
                    ->where('fee_category_id', $item->fee_category_id)
                    ->where('month', $item->month)
                    ->whereIn('status', ['pending', 'received'])
                    ->sum('amount');
                
                $item->remaining_to_request = max(0, (float)$item->collected_amount - $processed);
            }

            return response()->json([
                'total_collected' => round($total_collected, 2),
                'total_received' => round($total_received, 2),
                'total_pending' => round($total_pending, 2),
                'total_remaining' => round($total_remaining, 2),
                'breakdown' => $breakdown
            ]);
        } catch (\Exception $e) {
            Log::error('Teacher Cash Transfer Error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Teacher Deposit Cash
     */
    public function teacherDepositCash(Request $request)
    {
        try {
            $request->validate([
                'items' => 'required|array',
                'items.*.amount' => 'required|numeric|min:0.01',
                'items.*.fee_category_id' => 'required|exists:fee_categories,id',
                'items.*.month' => 'nullable|string'
            ]);

            $user = $request->user();
            
            \DB::transaction(function() use ($request, $user) {
                foreach ($request->items as $item) {
                    \DB::table('teacher_deposits')->insert([
                        'teacher_id' => $user->id,
                        'amount' => $item['amount'],
                        'deposit_date' => now()->toDateString(),
                        'status' => 'pending',
                        'fee_category_id' => $item['fee_category_id'],
                        'month' => $item['month'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            });

            return response()->json(['message' => 'Cash deposit requests submitted successfully']);
        } catch (\Exception $e) {
            Log::error('Teacher Deposit Cash Error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Teacher Deposit History
     */
    public function teacherDepositHistory(Request $request)
    {
        try {
            $user = $request->user();

            $query = \DB::table('teacher_deposits')
                ->leftJoin('users as cashier', 'teacher_deposits.cashier_id', '=', 'cashier.id')
                ->leftJoin('fee_categories', 'teacher_deposits.fee_category_id', '=', 'fee_categories.id')
                ->where('teacher_deposits.teacher_id', $user->id)
                ->select(
                    'teacher_deposits.*', 
                    'cashier.name as cashier_name', 
                    'fee_categories.name as category_name'
                );

            if ($request->filled('from_date')) {
                $query->whereDate('teacher_deposits.deposit_date', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('teacher_deposits.deposit_date', '<=', $request->to_date);
            }
            if ($request->filled('fee_category_id')) {
                $query->where('teacher_deposits.fee_category_id', $request->fee_category_id);
            }
            if ($request->filled('month')) {
                $query->where('teacher_deposits.month', $request->month);
            }
            if ($request->filled('status')) {
                $query->where('teacher_deposits.status', $request->status);
            }

            $deposits = $query->orderBy('teacher_deposits.id', 'desc')
                ->paginate($request->per_page ?? 10);

            return response()->json($deposits);
        } catch (\Exception $e) {
            Log::error('Teacher Deposit History Error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error', 'message' => $e->getMessage()], 500);
        }
    }
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

            // Normalize date range to include whole "to" day (front-end sends YYYY-MM-DD)
            $fromDate = Carbon::parse($request->from_date)->startOfDay();
            $toDate = Carbon::parse($request->to_date)->endOfDay();

            // Compute totals from payment_items first
            $itemsQuery = \App\Models\PaymentItem::join('payments', 'payment_items.payment_id', '=', 'payments.id')
                ->where('payments.school_id', $schoolId)
                ->whereBetween('payments.received_at', [$fromDate, $toDate])
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
                ->whereBetween('payments.received_at', [$fromDate, $toDate])
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
                ->whereBetween('received_at', [$fromDate, $toDate])
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
            Log::error('FeeReportController.collectionByDate error', [
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

        // include entire to-day
        $fromDate = Carbon::parse($request->from_date)->startOfDay();
        $toDate = Carbon::parse($request->to_date)->endOfDay();

        $report = Payment::where('payments.school_id', $schoolId)
            ->whereBetween('received_at', [$fromDate, $toDate])
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
                    'classes.numeric_value as class_numeric',
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

            // Also include payments that don't have payment_items (fallback),
            // so the paid-students list matches the summary which merges both.
            $paymentsOnlyQuery = DB::table('payments')
                ->leftJoin('payment_items', 'payments.id', '=', 'payment_items.payment_id')
                ->leftJoin('students', 'payments.student_id', '=', 'students.id')
                ->leftJoin('student_enrollments as se', function($j) use ($schoolId) {
                    $j->on('payments.student_id', '=', 'se.student_id')
                      ->where('se.school_id', '=', $schoolId)
                      ->where('se.status', '=', 'active');
                })
                ->leftJoin('classes', 'se.class_id', '=', 'classes.id')
                ->leftJoin('sections', 'se.section_id', '=', 'sections.id')
                ->where('payments.school_id', $schoolId)
                ->where('payments.status', 'settled')
                ->whereNull('payment_items.id')
                ->select(
                    'payments.id as payment_id',
                    'payments.received_at as paid_at',
                    DB::raw('COALESCE(payments.amount_paid, 0) as amount'),
                    'students.student_name_bn',
                    'students.student_name_en',
                    'payments.student_id as student_id',
                    'classes.name as class_name_en',
                    'classes.bangla_name as class_name_bn',
                    'classes.numeric_value as class_numeric',
                    'sections.name as section_name_en',
                    'sections.bangla_name as section_name_bn',
                    'se.roll_no as roll_no',
                    DB::raw('NULL as fee_month'),
                    DB::raw('COALESCE(NULL, "General") as category_name_bn'),
                    DB::raw('COALESCE(NULL, "General") as category_name_en')
                );

            if ($request->filled('from_date') && $request->filled('to_date')) {
                $paymentsOnlyQuery->whereDate('payments.received_at', '>=', $request->from_date)
                                  ->whereDate('payments.received_at', '<=', $request->to_date);
            }
            if ($request->filled('class_id')) {
                $paymentsOnlyQuery->where('se.class_id', $request->class_id);
            }
            if ($request->filled('section_id')) {
                $paymentsOnlyQuery->where('se.section_id', $request->section_id);
            }

            $paymentsOnlyRows = $paymentsOnlyQuery->orderBy('payments.received_at', 'desc')->get();

            // Merge itemized rows with payments-only rows and sort by paid_at desc (requested for this report)
            $all = $rows->merge($paymentsOnlyRows)->sortByDesc('paid_at')->values();

            return response()->json($all);
        } catch (\Throwable $e) {
            Log::error('FeeReportController.collectionPaidStudents error', [
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

        // Group by category AND month for detailed statistics
        $rawRecords = StudentFee::from('student_fees')
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
                'fee_categories.frequency',
                'student_fees.month',
                DB::raw('SUM(student_fees.amount) as total_amount'),
                DB::raw('COALESCE(SUM(payment_items.amount), 0) as total_paid'),
                DB::raw('COUNT(DISTINCT student_fees.id) as record_count')
            )
            ->groupBy('fee_categories.name', 'fee_categories.frequency', 'student_fees.month')
            ->get();

        // Group the results by category
        $report = $rawRecords->groupBy('category_name')->map(function ($items, $categoryName) {
            $first = $items->first();
            $summary = [
                'category_name' => $categoryName,
                'frequency' => $first->frequency,
                'total_amount' => $items->sum('total_amount'),
                'total_paid' => $items->sum('total_paid'),
                'total_due' => $items->sum('total_amount') - $items->sum('total_paid'),
                'record_count' => $items->sum('record_count'),
                'monthly_stats' => []
            ];

            if ($first->frequency === 'monthly') {
                $summary['monthly_stats'] = $items->map(function($m) {
                    return [
                        'month' => $m->month,
                        'total_amount' => (float)$m->total_amount,
                        'total_paid' => (float)$m->total_paid,
                        'total_due' => (float)$m->total_amount - (float)$m->total_paid,
                        'record_count' => $m->record_count
                    ];
                })->sortByDesc('month')->values();
            }

            return $summary;
        })->values();

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
                ->with(['student.fees' => function($q) use ($schoolId, $request) {
                    $q->where('school_id', $schoolId)->whereIn('status', ['unpaid', 'partial']);
                    if ($request->filled('fee_category_id')) {
                        $q->whereHas('feeStructure', function($fs) use ($request) {
                            $fs->where('fee_category_id', $request->fee_category_id);
                        });
                    }
                    if ($request->filled('month')) {
                        $q->where('month', $request->month);
                    }
                }]);

            if ($classId) {
                $query->where('student_enrollments.class_id', $classId);
            }
            if ($sectionId) {
                $query->where('student_enrollments.section_id', $sectionId);
            }
            if ($request->filled('student_id')) {
                $query->where('students.student_id', 'like', '%' . $request->student_id . '%');
            }

            $students = $query->get()->map(function($enrollment) {
                $student = $enrollment->student ?? null;
                $basicDue = 0;
                $fineDue = 0;
                
                if ($student && isset($student->fees)) {
                    foreach ($student->fees as $f) {
                        $basicDue += max(0, (float)$f->amount - (float)$f->paid_amount);
                        $fineDue += (float)$f->calculateFine();
                    }
                }

                $totalDue = $basicDue + $fineDue;

                // fallback values using selected columns
                $studentIdStr = $enrollment->student_code ?? ($student->student_id ?? null);
                $name = $enrollment->student_name_bn ?: ($enrollment->student_name_en ?? ($student->student_name_en ?? null));

                return [
                    'id' => $enrollment->student_real_id ?? ($student->id ?? null),
                    'student_id' => $studentIdStr,
                    'name' => $name,
                    'roll' => $enrollment->roll_no ?? null,
                    'basic_due' => round($basicDue, 2),
                    'fine_due' => round($fineDue, 2),
                    'total_due' => round($totalDue, 2),
                ];
            })->filter(fn($s) => $s['total_due'] > 0)
            ->sort(function($a, $b) {
                return (int)$a['roll'] <=> (int)$b['roll'];
            })
            ->values();

            return response()->json($students);
        } catch (\Throwable $e) {
            Log::error('FeeReportController.studentDues error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => 'internal_exception',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function detailedDues(Request $request)
    {
        try {
            $schoolId = $request->attributes->get('current_school_id') ?? $request->user()?->primarySchool()?->id;

            $query = StudentFee::with(['student', 'feeStructure.category'])
                ->where('school_id', $schoolId);

            // Filters
            if ($request->academic_year_id) {
                $query->whereHas('student.enrollments', function($q) use ($request) {
                    $q->where('academic_year_id', $request->academic_year_id);
                });
            }

            if ($request->class_id) {
                $query->whereHas('student.enrollments', function($q) use ($request) {
                    $q->where('class_id', $request->class_id);
                });
            }

            if ($request->section_id) {
                $query->whereHas('student.enrollments', function($q) use ($request) {
                    $q->where('section_id', $request->section_id);
                });
            }

            if ($request->student_id) {
                $query->whereHas('student', function($q) use ($request) {
                    $q->where('student_id', 'like', '%' . $request->student_id . '%');
                });
            }

            if ($request->fee_category_id) {
                $query->whereHas('feeStructure', function($q) use ($request) {
                    $q->where('fee_category_id', $request->fee_category_id);
                });
            }

            if ($request->month) {
                $query->where('month', $request->month);
            }

            if ($request->status && $request->status !== 'all') {
                if ($request->status === 'due') {
                    $query->where('status', '!=', 'paid');
                } else {
                    $query->where('status', $request->status);
                }
            }

            $fees = $query->get()->map(function($f) use ($schoolId) {
                $enrollment = $f->student->enrollments()
                    ->where('school_id', $schoolId)
                    ->latest('id')
                    ->first();

                return [
                    'student_name_bn' => $f->student->student_name_bn,
                    'student_name_en' => $f->student->student_name_en,
                    'student_code' => $f->student->student_id,
                    'roll_no' => $enrollment->roll_no ?? null,
                    'class_bangla_name' => $enrollment->class->bangla_name ?? null,
                    'class_name' => $enrollment->class->name ?? null,
                    'class_numeric' => $enrollment->class->numeric_value ?? 0,
                    'section_bangla_name' => $enrollment->section->bangla_name ?? null,
                    'section_name' => $enrollment->section->name ?? null,
                    'category_name' => $f->getFormattedName(),
                    'month' => $f->month,
                    'amount' => $f->original_amount ?: $f->amount, // Show full amount before waiver
                    'paid_amount' => $f->paid_amount + $f->fine_amount, // Total of base paid + fine paid
                    'fine_amount' => $f->calculateOriginalFine(), // Original potential fine charge
                    'fine_waiver' => ( ($f->original_amount ?: $f->amount) - $f->amount) + ($f->fine_waiver ?? 0), // Total of fee waiver + fine waiver
                    'status' => $f->status,
                    'due_date' => $f->getEffectiveDueDate(),
                ];
            })
            ->sort(function($a, $b) {
                $cA = $a['class_numeric'] ?? 0;
                $cB = $b['class_numeric'] ?? 0;
                if ($cA !== $cB) return $cA <=> $cB;
                
                if ($a['section_name'] !== $b['section_name']) return $a['section_name'] <=> $b['section_name'];
                
                return (int)$a['roll_no'] <=> (int)$b['roll_no'];
            })
            ->values();

            return response()->json($fees);
        } catch (\Throwable $e) {
            Log::error('Detailed Dues Report Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
