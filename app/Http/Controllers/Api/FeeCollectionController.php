<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Ledger;
use App\Models\SchoolPaymentSetting;
use App\Services\SSLCommerzClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FeeCollectionController extends Controller
{
    /**
     * Get due fees for a student
     */
    public function getDueFees(Request $request, $studentId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $student = Student::find((int)$studentId);

        if ($student) {
            $schoolId = $request->attributes->get('current_school_id') ?? $student->school_id;
            if ($user->isTeacher($schoolId) && !$user->isPrincipal($schoolId) && !$user->isSuperAdmin()) {
                $enrollment = $student->currentEnrollment()->with('section')->first();
                $teacher = \App\Models\Teacher::where('user_id', $user->id)->where('school_id', $schoolId)->first();
                $teacherId = $teacher ? $teacher->id : null;

                if (!$enrollment || !$teacherId || !$enrollment->section || $enrollment->section->class_teacher_id !== $teacherId) {
                    return response()->json(['message' => 'দুঃখিত, আপনি শুধুমাত্র আপনার নিজের শ্রেণির শিক্ষার্থীদের বকেয়া দেখতে পারবেন।'], 403);
                }
            }
        }

        if (!$student) {
            $fees = StudentFee::with(['feeStructure.category'])
                ->where('student_id', (int)$studentId)
                ->whereIn('status', ['unpaid', 'partial'])
                ->orderBy('due_date', 'asc')
                ->get()
                ->map(function ($fee) {
                    $fine = (float)$fee->calculateFine();
                    $fee->calculated_fine = $fine;
                    return $fee;
                });

            return response()->json([
                'student'  => ['id' => (int)$studentId, 'student_id' => null, 'name' => 'Unknown'],
                'due_fees' => $fees,
                'is_fine_enabled' => true, // default when unknown
                '_debug'   => ['warning' => 'Student model not found, fees fetched by raw id', 'fees_count' => $fees->count()]
            ]);
        }

        $fees = StudentFee::with(['feeStructure.category'])
            ->where('student_id', $student->id)
            ->whereIn('status', ['unpaid', 'partial'])
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($fee) {
                $fine = (float)$fee->calculateFine();
                $dueAmount = (float)$fee->amount - (float)$fee->paid_amount;
                
                if ($dueAmount <= 0.01 && $fine <= 0.01) {
                    return null;
                }

                $fee->calculated_fine = $fine;
                $fee->fine_reason = $fine > 0 ? "Late Fee Applied" : null;
                $fee->formatted_category_name = $fee->getFormattedName();
                $fee->effective_due_date = $fee->getEffectiveDueDate();
                return $fee;
            })->filter()->values();

        $allFees = StudentFee::where('student_id', $student->id)
            ->get(['id', 'school_id', 'status', 'amount', 'paid_amount', 'fee_structure_id']);

        $school = \App\Models\School::find($student->school_id);

        return response()->json([
            'student'  => [
                'id'         => $student->id,
                'student_id' => $student->student_id,
                'name'       => $student->full_name,
                'school_id'  => $student->school_id,
            ],
            'due_fees' => $fees,
            'is_fine_enabled' => $school ? (bool)$school->fine_enabled : false,
            '_debug'   => [
                'all_fees'  => $allFees,
                'due_count' => $fees->count(),
                'all_count' => $allFees->count(),
            ]
        ]);
    }

    /**
     * Collect fees (Cash / Bkash / Nagad — immediate settlement)
     */
    public function collectFees(Request $request)
    {
        $validated = $request->validate([
            'student_id'             => 'required|exists:students,id',
            'academic_year_id'       => 'required|exists:academic_years,id',
            'payment_method'         => 'required|in:cash,bkash,nagad,sslcommerz',
            'fees'                   => 'required|array|min:1',
            'fees.*.student_fee_id'  => 'required|exists:student_fees,id',
            'fees.*.amount'          => 'required|numeric|min:0.01',
            'fees.*.fine_amount'     => 'nullable|numeric|min:0',
            'received_at'            => 'nullable|date',
            'remarks'                => 'nullable|string',
        ]);

        $student     = Student::findOrFail($validated['student_id']);
        $totalAmount = collect($validated['fees'])->sum('amount');
        $schoolId    = $request->attributes->get('current_school_id') ?? $student->school_id;

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Enforce teacher restriction: Only class teachers can collect fees for their students
        if ($user->isTeacher($schoolId) && !$user->isPrincipal($schoolId) && !$user->isSuperAdmin()) {
            $enrollment = $student->currentEnrollment()->with('section')->first();
            $teacher = \App\Models\Teacher::where('user_id', $user->id)->where('school_id', $schoolId)->first();
            $teacherId = $teacher ? $teacher->id : null;
            
            if (!$enrollment || !$teacherId || !$enrollment->section || $enrollment->section->class_teacher_id !== $teacherId) {
                return response()->json(['message' => 'দুঃখিত, আপনি এই শিক্ষার্থীর ফি আদায় করার জন্য নির্ধারিত শ্রেণি শিক্ষক নন।'], 403);
            }
        }

        if (!$schoolId) {
            return response()->json(['message' => 'স্কুল আইডি পাওয়া যায়নি'], 400);
        }

        return DB::transaction(function () use ($validated, $student, $totalAmount, $request, $schoolId) {
            $payment = Payment::create([
                'school_id'            => $schoolId,
                'student_id'           => $student->id,
                'academic_year_id'     => $validated['academic_year_id'],
                'payment_number'       => $this->generatePaymentNumber($schoolId),
                'amount_paid'          => $totalAmount,
                'fine_applied'         => collect($validated['fees'])->sum('fine_amount'),
                'payment_method'       => $validated['payment_method'],
                'collected_by_user_id' => Auth::id(),
                'role'                 => $this->getCollectorRole(Auth::user(), $schoolId),
                'status'               => $validated['payment_method'] === 'cash' ? 'settled' : 'pending',
                'received_at'          => $validated['received_at'] 
                    ? (\Carbon\Carbon::parse($validated['received_at'])->isToday() 
                        ? \Carbon\Carbon::parse($validated['received_at'])->setTimeFrom(now()) 
                        : \Carbon\Carbon::parse($validated['received_at']))
                    : now(),
                'idempotency_key'      => $request->header('X-Idempotency-Key'),
            ]);

            foreach ($validated['fees'] as $feeData) {
                $studentFee = StudentFee::lockForUpdate()->findOrFail($feeData['student_fee_id']);
                
                // Track fine payment separately from base fee payment
                $finePayment = (float)($feeData['fine_amount'] ?? 0);
                $basePayment = (float)$feeData['amount'] - $finePayment;

                PaymentItem::create([
                    'school_id'      => $schoolId,
                    'payment_id'     => $payment->id,
                    'student_fee_id' => $studentFee->id,
                    'amount'         => $feeData['amount'], // Itemized total (base + fine)
                ]);

                $studentFee->paid_amount += $basePayment;
                $studentFee->fine_amount += $finePayment;
                
                // Status check: fully paid if base and fine are zero
                $remainingBasic = max(0, (float)$studentFee->amount - (float)$studentFee->paid_amount);
                $remainingFine = (float)$studentFee->calculateFine();
                
                $studentFee->status = ($remainingBasic + $remainingFine <= 0.01) ? 'paid' : 'partial';
                $studentFee->save();
            }

            // If fee_category_id is not set on payment, try to derive from the first student fee's fee structure
            if (! $payment->fee_category_id) {
                try {
                    $firstFeeId = $validated['fees'][0]['student_fee_id'] ?? null;
                    if ($firstFeeId) {
                        $firstFee = StudentFee::with('feeStructure')->find($firstFeeId);
                        if ($firstFee && $firstFee->feeStructure) {
                            $payment->fee_category_id = $firstFee->feeStructure->fee_category_id ?? null;
                            $payment->save();
                        }
                    }
                } catch (\Throwable $e) {
                    // swallow — not critical for payment success
                    \Log::warning('Could not derive fee_category_id for payment: '.$e->getMessage());
                }
            }

            // Auto-issue receipt for settled payments if not already set
            if ($payment->status === 'settled' && ! $payment->receipt_id) {
                try {
                    $receipt = (new \App\Services\ReceiptService())->issue($student->id, (float)$payment->amount_paid, Auth::id());
                    $payment->receipt_id = $receipt->id;
                    $payment->save();
                } catch (\Throwable $e) {
                    Log::error('Receipt issuance failed for payment '.$payment->id.': '.$e->getMessage());
                }
            }

            Ledger::create([
                'school_id'      => $schoolId,
                'type'           => 'income',
                'category'       => 'Fee Collection',
                'amount'         => $totalAmount,
                'entry_date'     => $payment->received_at->toDateString(),
                'reference_type' => Payment::class,
                'reference_id'   => $payment->id,
                'description'    => "Fee collection for student [{$student->student_id}] {$student->full_name}. " . ($validated['remarks'] ?? ''),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'পেমেন্ট সফলভাবে সম্পন্ন হয়েছে।',
                'data'    => [
                    'payment_id'     => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'receipt_url'    => url("/billing/receipts/{$payment->id}"),
                ]
            ]);
        });
    }

    /**
     * Initiate SSLCommerz payment — creates pending Payment and returns gateway URL.
     */
    public function initiateSSLPayment(Request $request)
    {
        $validated = $request->validate([
            'student_id'            => 'required|exists:students,id',
            'academic_year_id'      => 'required|exists:academic_years,id',
            'fees'                  => 'required|array|min:1',
            'fees.*.student_fee_id' => 'required|exists:student_fees,id',
            'fees.*.amount'         => 'required|numeric|min:0.01',
            'fees.*.fine_amount'    => 'nullable|numeric|min:0',
            'remarks'               => 'nullable|string',
        ]);

        $student  = Student::findOrFail($validated['student_id']);
        $schoolId = $request->attributes->get('current_school_id') ?? $student->school_id;
        $settings = SchoolPaymentSetting::where('school_id', $schoolId)->first();

        if (!$settings || !$settings->active) {
            return response()->json([
                'message' => 'পেমেন্ট গেটওয়ে সক্রিয় নেই। প্রিন্সিপাল প্যানেল থেকে SSLCommerz সেটিংস চালু করুন।'
            ], 422);
        }
        $totalAmount = collect($validated['fees'])->sum(function($f) {
            return (float)$f['amount'] + (float)($f['fine_amount'] ?? 0);
        });
        $tranId      = 'BILL' . strtoupper(Str::random(12));

        $payment = DB::transaction(function () use ($validated, $student, $totalAmount, $schoolId, $tranId, $request) {
            return Payment::create([
                'school_id'            => $schoolId,
                'student_id'           => $student->id,
                'academic_year_id'     => $validated['academic_year_id'],
                'payment_number'       => $this->generatePaymentNumber($schoolId),
                'amount_paid'          => $totalAmount,
                'payment_method'       => 'sslcommerz',
                'collected_by_user_id' => Auth::id(),
                'role'                 => $this->getCollectorRole(Auth::user(), $schoolId),
                'status'               => 'initiated',
                'received_at'          => $request->input('received_at')
                                            ? (\Carbon\Carbon::parse($request->input('received_at'))->isToday()
                                                ? \Carbon\Carbon::parse($request->input('received_at'))->setTimeFrom(now())
                                                : \Carbon\Carbon::parse($request->input('received_at')))
                                            : now(),
                'tran_id'              => $tranId,
                'meta'                 => [
                    'fees'    => $validated['fees'],
                    'remarks' => $validated['remarks'] ?? null,
                ],
            ]);
        });

        $client  = new SSLCommerzClient();
        $payload = [
            'store_id'         => $settings->store_id,
            'store_passwd'     => $settings->store_password,
            'total_amount'     => $totalAmount,
            'currency'         => 'BDT',
            'tran_id'          => $tranId,
            'success_url'      => route('api.billing.ssl.success'),
            'fail_url'         => route('api.billing.ssl.fail'),
            'cancel_url'       => route('api.billing.ssl.cancel'),
            'ipn_url'          => route('api.billing.ssl.ipn'),
            'emi_option'       => 0,
            'cus_name'         => $student->student_name_bn ?: $student->student_name_en,
            'cus_phone'        => $student->guardian_phone ?? '01700000000',
            'cus_email'        => 'student@school.bd',
            'cus_add1'         => $student->present_address ?? 'N/A',
            'cus_city'         => 'City',
            'cus_country'      => 'Bangladesh',
            'shipping_method'  => 'NO',
            'num_of_item'      => count($validated['fees']),
            'product_name'     => 'School Fee',
            'product_category' => 'Education',
            'product_profile'  => 'general',
        ];

        $gatewayUrl = $client->initiate($payload, (bool) $settings->sandbox);

        if (!$gatewayUrl) {
            $payment->update(['status' => 'failed']);
            return response()->json([
                'message' => 'SSLCommerz গেটওয়ে থেকে সংযোগ পাওয়া যায়নি। পরে আবার চেষ্টা করুন।'
            ], 502);
        }

        return response()->json([
            'success'     => true,
            'gateway_url' => $gatewayUrl,
            'tran_id'     => $tranId,
            'payment_id'  => $payment->id,
        ]);
    }

    /**
     * Generate unique payment number for receipt
     */
    private function generatePaymentNumber($schoolId): string
    {
        $prefix      = 'FEE-' . date('ym');
        $lastPayment = Payment::where('school_id', $schoolId)
            ->where('payment_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $serial = $lastPayment ? ((int) substr($lastPayment->payment_number, -6)) + 1 : 1;

        return $prefix . str_pad($serial, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Determine collector role
     */
    private function getCollectorRole($user, $schoolId): string
    {
        if ($user->isPrincipal($schoolId)) return 'headmaster';
        if ($user->isTeacher($schoolId))   return 'teacher';
        return 'online';
    }

    public function waiveFine(Request $request, $id)
    {
        try {
            $user = $request->user();
            $schoolId = $request->attributes->get('current_school_id') ?? 
                       $user?->primarySchool()?->id ??
                       $user?->activeSchoolRoles()?->first()?->school_id;
            
            $fee = StudentFee::where('school_id', $schoolId)->findOrFail($id);
            
            $validated = $request->validate([
                'waiver_amount' => 'required|numeric|min:0',
                'reason'        => 'nullable|string|max:255'
            ]);
            
            $fee->fine_waiver = $validated['waiver_amount'];
            $fee->fine_waiver_reason = $validated['reason'];
            
            // Recalculate status: if base is paid and fine is now 0 (due to waiver), it's 'paid'
            $remainingBasic = max(0, $fee->amount - $fee->paid_amount);
            $remainingFine = $fee->calculateFine();
            
            if ($remainingBasic + $remainingFine <= 0) {
                $fee->status = 'paid';
            }
            
            $fee->save();
            
            return response()->json([
                'message' => 'জরিমানা মওকুফ সফল হয়েছে',
                'fine_waiver' => $fee->fine_waiver,
                'calculated_fine' => $fee->calculateFine()
            ]);
        } catch (\Throwable $e) {
            Log::error('FeeCollectionController.waiveFine error', ['m' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
