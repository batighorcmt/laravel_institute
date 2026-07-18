<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\User;

class CashierManagementController extends Controller
{
    public function getCashierSetup(Request $request)
    {
        try {
            $schoolId = $request->attributes->get('current_school_id') ?? $request->user()?->primarySchool()?->id;

            // Get active cashier
            $activeAssignment = DB::table('cashier_assignments')
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->join('users', 'cashier_assignments.user_id', '=', 'users.id')
                ->select('cashier_assignments.*', 'users.name as cashier_name', 'users.username as cashier_username')
                ->first();

            // Get active teachers (for the dropdown)
            $activeTeachers = User::whereHas('schoolRoles', function($q) use ($schoolId) {
                    $q->where('school_id', $schoolId)
                      ->whereHas('role', fn($r) => $r->where('name', 'teacher'))
                      ->where('status', 'active');
                })
                ->select('id', 'name', 'username')
                ->get();

            // Get assignment history
            $history = DB::table('cashier_assignments')
                ->where('cashier_assignments.school_id', $schoolId)
                ->join('users as cashier', 'cashier_assignments.user_id', '=', 'cashier.id')
                ->leftJoin('users as assigner', 'cashier_assignments.assigned_by', '=', 'assigner.id')
                ->select(
                    'cashier_assignments.*', 
                    'cashier.name as cashier_name', 
                    'assigner.name as assigned_by_name'
                )
                ->orderBy('id', 'desc')
                ->get();

            return response()->json([
                'active_assignment' => $activeAssignment,
                'teachers' => $activeTeachers,
                'history' => $history
            ]);
        } catch (\Exception $e) {
            Log::error('getCashierSetup Error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    public function assignCashier(Request $request)
    {
        try {
            $request->validate([
                'teacher_id' => 'required|exists:users,id'
            ]);

            $schoolId = $request->attributes->get('current_school_id') ?? $request->user()?->primarySchool()?->id;
            
            DB::beginTransaction();
            // Mark existing active cashier as inactive
            DB::table('cashier_assignments')
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'ended_at' => now()->toDateString(),
                    'updated_at' => now()
                ]);

            // Create new assignment
            DB::table('cashier_assignments')->insert([
                'school_id' => $schoolId,
                'user_id' => $request->teacher_id,
                'assigned_by' => $request->user()->id,
                'started_at' => now()->toDateString(),
                'ended_at' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            DB::commit();

            return response()->json(['message' => 'Cashier assigned successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('assignCashier Error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function getCashierStatement(Request $request, $assignmentId)
    {
        try {
            $schoolId = $request->attributes->get('current_school_id') ?? $request->user()?->primarySchool()?->id;

            $assignment = DB::table('cashier_assignments')
                ->where('id', $assignmentId)
                ->where('school_id', $schoolId)
                ->first();

            if (!$assignment) {
                return response()->json(['error' => 'Assignment not found'], 404);
            }
            $assignment = (object) $assignment;

            $startDate = Carbon::parse($assignment->started_at)->startOfDay();
            $endDate = $assignment->ended_at ? Carbon::parse($assignment->ended_at)->endOfDay() : now()->endOfDay();

            // Total Received (either from teacher deposits OR directly collected by cashier on billing portal)
            // Wait, "teacher_deposits" table tells us what the cashier received.
            $receivedDeposits = DB::table('teacher_deposits')
                ->where('cashier_id', $assignment->user_id)
                ->where('status', 'received')
                ->whereBetween('deposit_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

            // What if cashier collects money directly from students? 
            // In payments table, collected_by_user_id = $assignment->user_id.
            $directCollections = DB::table('payments')
                ->where('school_id', $schoolId)
                ->where('collected_by_user_id', $assignment->user_id)
                ->where('status', 'settled')
                ->whereBetween('received_at', [$startDate, $endDate]);

            $totalReceivedDeposits = $receivedDeposits->sum('amount');
            $totalDirectCollections = $directCollections->sum('amount_paid');
            $totalReceived = $totalReceivedDeposits + $totalDirectCollections;

            // Total Spent (from cashier_expenses)
            $expenses = DB::table('cashier_expenses')
                ->where('school_id', $schoolId)
                ->where('cashier_id', $assignment->user_id)
                ->whereBetween('expense_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

            $totalSpent = $expenses->sum('amount');
            $balance = $totalReceived - $totalSpent;

            // Fetch list of deposits
            $depositList = DB::table('teacher_deposits')
                ->where('cashier_id', $assignment->user_id)
                ->where('status', 'received')
                ->join('users', 'teacher_deposits.teacher_id', '=', 'users.id')
                ->whereBetween('deposit_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->select('teacher_deposits.*', 'users.name as teacher_name')
                ->orderBy('deposit_date', 'desc')
                ->get();

            // Fetch list of expenses
            $expenseList = $expenses->orderBy('expense_date', 'desc')->get();

            return response()->json([
                'assignment' => $assignment,
                'summary' => [
                    'total_received_deposits' => round($totalReceivedDeposits, 2),
                    'total_direct_collections' => round($totalDirectCollections, 2),
                    'total_received' => round($totalReceived, 2),
                    'total_spent' => round($totalSpent, 2),
                    'balance' => round($balance, 2)
                ],
                'deposits' => $depositList,
                'expenses' => $expenseList
            ]);
        } catch (\Exception $e) {
            Log::error('getCashierStatement Error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    public function getPendingDeposits(Request $request)
    {
        try {
            $user = $request->user();
            $schoolId = $request->attributes->get('current_school_id') ?? $user->primarySchool()?->id;

            // Only fetch for active assigned school
            if (!$user->isCashier($schoolId)) {
                return response()->json(['error' => 'You are not the active cashier for this school'], 403);
            }

            $deposits = DB::table('teacher_deposits')
                ->where('teacher_deposits.status', 'pending')
                ->whereExists(function ($query) use ($schoolId) {
                    $query->select(DB::raw(1))
                          ->from('users')
                          ->join('user_school_roles', 'users.id', '=', 'user_school_roles.user_id')
                          ->whereColumn('teacher_deposits.teacher_id', 'users.id')
                          ->where('user_school_roles.school_id', $schoolId);
                })
                ->join('users', 'teacher_deposits.teacher_id', '=', 'users.id')
                ->leftJoin('fee_categories', 'teacher_deposits.fee_category_id', '=', 'fee_categories.id')
                ->select(
                    'teacher_deposits.*', 
                    'users.name as teacher_name',
                    'fee_categories.name as fee_category_name'
                )
                ->orderBy('teacher_deposits.created_at', 'desc')
                ->get();

            return response()->json($deposits);
        } catch (\Exception $e) {
            Log::error('getPendingDeposits Error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function acceptDeposit(Request $request, $id)
    {
        try {
            $user = $request->user();
            $schoolId = $request->attributes->get('current_school_id') ?? $user->primarySchool()?->id;

            if (!$user->isCashier($schoolId)) {
                return response()->json(['error' => 'You are not the active cashier'], 403);
            }

            // Scope the deposit to teachers who actually belong to this cashier's
            // school — without this, a valid cashier at School A could accept a
            // deposit that belongs to a teacher at School B by guessing its id.
            $deposit = DB::table('teacher_deposits')
                ->where('teacher_deposits.id', $id)
                ->whereExists(function ($query) use ($schoolId) {
                    $query->select(DB::raw(1))
                          ->from('users')
                          ->join('user_school_roles', 'users.id', '=', 'user_school_roles.user_id')
                          ->whereColumn('teacher_deposits.teacher_id', 'users.id')
                          ->where('user_school_roles.school_id', $schoolId);
                })
                ->first();
            if (!$deposit) {
                return response()->json(['error' => 'Deposit not found'], 404);
            }
            $deposit = (object) $deposit;
            if ($deposit->status !== 'pending') {
                return response()->json(['error' => 'Deposit is already ' . $deposit->status], 400);
            }

            DB::table('teacher_deposits')->where('id', $id)->update([
                'status' => 'received',
                'cashier_id' => $user->id,
                'updated_at' => now()
            ]);

            return response()->json(['message' => 'Deposit accepted successfully.']);
        } catch (\Exception $e) {
            Log::error('acceptDeposit Error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function getCashierDashboardData(Request $request)
    {
        try {
            $user = $request->user();
            $schoolId = $request->attributes->get('current_school_id') ?? $user->primarySchool()?->id;

            if (!$user->isCashier($schoolId)) {
                return response()->json(['error' => 'You are not the active cashier for this school'], 403);
            }

            // Get Current Active Assignment
            $assignment = DB::table('cashier_assignments')
                ->where('school_id', $schoolId)
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            if (!$assignment) {
                return response()->json(['error' => 'Active cashier assignment not found'], 404);
            }
            $assignment = (object) $assignment;

            $startDate = Carbon::parse($assignment->started_at)->startOfDay();
            $endDate = now()->endOfDay();

            // Total Received (either from teacher deposits OR directly collected by cashier on billing portal)
            $receivedDepositsQuery = DB::table('teacher_deposits')
                ->where('cashier_id', $user->id)
                ->where('status', 'received')
                ->whereBetween('deposit_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

            $directCollectionsQuery = DB::table('payments')
                ->where('school_id', $schoolId)
                ->where('collected_by_user_id', $user->id)
                ->where('status', 'settled')
                ->whereBetween('received_at', [$startDate, $endDate]);

            $totalReceivedDeposits = $receivedDepositsQuery->sum('amount');
            $totalDirectCollections = $directCollectionsQuery->sum('amount_paid');
            $totalReceived = $totalReceivedDeposits + $totalDirectCollections;

            // Total Spent (from cashier_expenses)
            $expensesQuery = DB::table('cashier_expenses')
                ->where('school_id', $schoolId)
                ->where('cashier_id', $user->id)
                ->whereBetween('expense_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

            $totalSpent = $expensesQuery->sum('amount');
            $balance = $totalReceived - $totalSpent;

            // Generate Deposit List Query
            $query = DB::table('teacher_deposits')
                ->whereExists(function ($q) use ($schoolId) {
                    $q->select(DB::raw(1))
                      ->from('users')
                      ->join('user_school_roles', 'users.id', '=', 'user_school_roles.user_id')
                      ->whereColumn('teacher_deposits.teacher_id', 'users.id')
                      ->where('user_school_roles.school_id', $schoolId);
                })
                ->where(function($q) use ($user, $startDate, $endDate) {
                    // Include pending ones (not yet accepted by anyone) 
                    $q->where('teacher_deposits.status', 'pending')
                      // or those accepted by this cashier during this tenure
                      ->orWhere(function($subQ) use ($user, $startDate, $endDate) {
                          $subQ->where('teacher_deposits.cashier_id', $user->id)
                               ->where('teacher_deposits.status', 'received')
                               ->whereBetween('teacher_deposits.deposit_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
                      });
                })
                ->join('users', 'teacher_deposits.teacher_id', '=', 'users.id')
                ->leftJoin('fee_categories', 'teacher_deposits.fee_category_id', '=', 'fee_categories.id')
                ->leftJoin('sections', 'teacher_deposits.teacher_id', '=', 'sections.class_teacher_id')
                ->leftJoin('classes', 'sections.class_id', '=', 'classes.id')
                ->select(
                    'teacher_deposits.*', 
                    'users.name as teacher_name',
                    'users.id as teacher_user_id',
                    'fee_categories.name as fee_category_name',
                    'sections.name as class_teacher_section',
                    'classes.name as class_teacher_class'
                );

            if ($request->filled('from_date')) {
                $query->whereDate('teacher_deposits.deposit_date', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('teacher_deposits.deposit_date', '<=', $request->to_date);
            }
            if ($request->filled('teacher_id')) {
                $query->where('teacher_deposits.teacher_id', $request->teacher_id);
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

            $deposits = $query->orderBy('teacher_deposits.created_at', 'desc')->get();
            
            // Get dropdown data for filters
            $categories = DB::table('fee_categories')->where('school_id', $schoolId)->where('active', true)->select('id', 'name')->get();
            
            // Get unique teachers who have made deposits
            $activeTeachers = DB::table('users')
                ->whereIn('id', DB::table('teacher_deposits')->pluck('teacher_id'))
                ->select('id', 'name')
                ->get();

            return response()->json([
                'summary' => [
                    'total_received' => round($totalReceived, 2),
                    'total_spent' => round($totalSpent, 2),
                    'balance' => round($balance, 2)
                ],
                'deposits' => $deposits,
                'categories' => $categories,
                'teachers' => $activeTeachers
            ]);
        } catch (\Exception $e) {
            Log::error('getCashierDashboardData Error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function addExpense(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:1',
                'category' => 'required|string|max:255',
                'description' => 'nullable|string',
                'expense_date' => 'required|date'
            ]);

            $user = $request->user();
            $schoolId = $request->attributes->get('current_school_id') ?? $user->primarySchool()?->id;

            if (!$user->isCashier($schoolId)) {
                return response()->json(['error' => 'You are not the active cashier'], 403);
            }

            DB::table('cashier_expenses')->insert([
                'school_id' => $schoolId,
                'cashier_id' => $user->id,
                'amount' => $request->amount,
                'category' => $request->category,
                'description' => $request->description,
                'expense_date' => $request->expense_date,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json(['message' => 'Expense added successfully']);
        } catch (\Exception $e) {
            Log::error('addExpense Error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}
