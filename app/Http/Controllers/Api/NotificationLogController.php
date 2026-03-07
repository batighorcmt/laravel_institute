<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class NotificationLogController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $request->attributes->get('current_school_id') ?? $request->school_id;

        $logs = \App\Models\NotificationLog::with(['user', 'notice'])
            ->when($schoolId, function($q) use ($schoolId) {
                return $q->whereHas('user.schoolRoles', function($sq) use ($schoolId) {
                    $sq->where('school_id', $schoolId);
                });
            })
            ->when($request->user_id, function($q) use ($request) {
                return $q->where('user_id', $request->user_id);
            })
            ->when($request->status, function($q) use ($request) {
                return $q->where('status', $request->status);
            })
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json($logs);
    }

<<<<<<< Updated upstream
    /**
     * Return notifications for authenticated user.
     */
    public function userIndex(Request $request)
    {
        try {
            $user = $request->user();
            if (! $user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $logs = \App\Models\NotificationLog::with(['notice'])
                ->where('user_id', $user->id)
                ->when($request->status, function($q) use ($request) {
                    return $q->where('status', $request->status);
                })
                ->latest()
                ->paginate($request->per_page ?? 20);

            return response()->json($logs);
        } catch (\Throwable $e) {
            \Log::error('Notification userIndex error: ' . $e->getMessage());
            return response()->json(['message' => 'Server error while loading notifications'], 500);
        }
    }

    /**
     * Mark given notification(s) as read for authenticated user.
     * Accepts POST body: { ids: [1,2,3] } or { all: true } or URL param id
     */
    public function markAsRead(Request $request, $id = null)
    {
        try {
            $user = $request->user();
            if (! $user) return response()->json(['message' => 'Unauthenticated'], 401);

            if ($request->input('all') === true || $request->input('all') === 'true' || $request->input('all') === '1') {
                if (! Schema::hasColumn('notification_logs', 'read_at')) {
                    return response()->json(['message' => 'Database missing column read_at. Run migrations: php artisan migrate'], 500);
                }

                \App\Models\NotificationLog::where('user_id', $user->id)->whereNull('read_at')->update(['read_at' => now()]);
                return response()->json(['message' => 'Marked all as read']);
            }

            $ids = $request->input('ids');
            if ($id !== null) $ids = array_merge((array)$ids ?? [], [(int)$id]);

            if (empty($ids)) {
                return response()->json(['message' => 'No ids provided'], 400);
            }

            if (! Schema::hasColumn('notification_logs', 'read_at')) {
                return response()->json(['message' => 'Database missing column read_at. Run migrations: php artisan migrate'], 500);
            }

            \App\Models\NotificationLog::whereIn('id', (array)$ids)->where('user_id', $user->id)->update(['read_at' => now()]);

            return response()->json(['message' => 'Marked as read']);
        } catch (\Throwable $e) {
            \Log::error('Notification markAsRead error: ' . $e->getMessage());
            return response()->json(['message' => 'Server error while marking read'], 500);
        }
    }

    public function stats(Request $request)
    {
=======
    public function stats(Request $request)
    {
>>>>>>> Stashed changes
        $schoolId = $request->attributes->get('current_school_id') ?? $request->school_id;

        $query = \App\Models\NotificationLog::query();
        if ($schoolId) {
            $query->whereHas('user.schoolRoles', function($sq) use ($schoolId) {
                $sq->where('school_id', $schoolId);
            });
        }

        $stats = [
            'total' => (clone $query)->count(),
            'sent' => (clone $query)->where('status', 'sent')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'latest_logs' => (clone $query)->with('user')->latest()->limit(5)->get()
        ];

        return response()->json($stats);
    }
}
