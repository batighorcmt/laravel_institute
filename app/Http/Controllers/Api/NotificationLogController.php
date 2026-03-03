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
