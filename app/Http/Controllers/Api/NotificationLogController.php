<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = \App\Models\NotificationLog::with(['user', 'notice'])
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

    /**
     * Return notifications for authenticated user.
     */
    public function userIndex(Request $request)
    {
        $user = $request->user();
        $logs = \App\Models\NotificationLog::with(['notice'])
            ->where('user_id', $user->id)
            ->when($request->status, function($q) use ($request) {
                return $q->where('status', $request->status);
            })
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json($logs);
    }

    /**
     * Mark given notification(s) as read for authenticated user.
     * Accepts POST body: { ids: [1,2,3] } or { all: true } or URL param id
     */
    public function markAsRead(Request $request, $id = null)
    {
        $user = $request->user();

        if ($request->input('all') === true || $request->input('all') === 'true' || $request->input('all') === '1') {
            \App\Models\NotificationLog::where('user_id', $user->id)->whereNull('read_at')->update(['read_at' => now()]);
            return response()->json(['message' => 'Marked all as read']);
        }

        $ids = $request->input('ids');
        if ($id !== null) $ids = array_merge((array)$ids ?? [], [(int)$id]);
        if (empty($ids)) {
            return response()->json(['message' => 'No ids provided'], 400);
        }

        \App\Models\NotificationLog::whereIn('id', (array)$ids)->where('user_id', $user->id)->update(['read_at' => now()]);

        return response()->json(['message' => 'Marked as read']);
    }

    public function stats()
    {
        $stats = [
            'total' => \App\Models\NotificationLog::count(),
            'sent' => \App\Models\NotificationLog::where('status', 'sent')->count(),
            'failed' => \App\Models\NotificationLog::where('status', 'failed')->count(),
            'latest_logs' => \App\Models\NotificationLog::with('user')->latest()->limit(5)->get()
        ];

        return response()->json($stats);
    }
}
