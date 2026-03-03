<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\School;
use App\Models\DeviceToken;
use App\Models\NotificationLog;
use App\Services\FcmService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class FcmDiagnosticsController extends Controller
{
    public function index(School $school, Request $request)
    {
        $current = Auth::user();
        abort_unless($current->isPrincipal($school->id) || $current->isSuperAdmin(), 403);

        // Health Check
        $saPath = storage_path('app/firebase-service-account.json');
        $saExists = File::exists($saPath);
        $saDetails = null;
        if ($saExists) {
            $saDetails = json_decode(File::get($saPath), true);
        }

        // Tokens List
        $tokens = DeviceToken::whereHas('user.schoolRoles', function($q) use ($school) {
            $q->where('school_id', $school->id);
        })->with('user')->orderBy('updated_at', 'desc')->paginate(100, ['*'], 'tokens_page');

        // Notification Logs
        $logsQuery = NotificationLog::whereHas('user.schoolRoles', function($q) use ($school) {
            $q->where('school_id', $school->id);
        })->with('user')->orderBy('created_at', 'desc');

        if ($request->has('status') && $request->status != '') {
            $logsQuery->where('status', $request->status);
        }

        $logs = $logsQuery->paginate(100, ['*'], 'logs_page');

        return view('principal.institute.fcm.diagnostics', compact('school', 'tokens', 'logs', 'saExists', 'saDetails'));
    }

    public function testSend(School $school, Request $request, FcmService $fcm)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'token' => 'required|string',
            'title' => 'required|string|max:100',
            'body' => 'required|string|max:255',
        ]);

        $current = Auth::user();
        abort_unless($current->isPrincipal($school->id) || $current->isSuperAdmin(), 403);

        $res = $fcm->sendToToken(
            $request->token,
            $request->title . " (Web Test)",
            $request->body,
            ['type' => 'test'],
            $request->user_id
        );

        if ($res['success']) {
            return back()->with('success', 'Test notification sent successfully!');
        } else {
            return back()->with('error', 'Failed to send test: ' . ($res['body']['error']['message'] ?? 'Unknown Error'));
        }
    }

    public function deleteToken(School $school, DeviceToken $token)
    {
        $current = Auth::user();
        abort_unless($current->isPrincipal($school->id) || $current->isSuperAdmin(), 403);

        // Ensure token belongs to this school's user
        if ($token->user->schoolRoles()->where('school_id', $school->id)->exists()) {
            $token->delete();
            return back()->with('success', 'Device token deleted successfully.');
        }

        return back()->with('error', 'Unauthorized action.');
    }

    public function clearLogs(School $school, Request $request)
    {
        $current = Auth::user();
        abort_unless($current->isPrincipal($school->id) || $current->isSuperAdmin(), 403);

        $days = (int)$request->input('days', 30);
        $count = NotificationLog::whereHas('user.schoolRoles', function($q) use ($school) {
            $q->where('school_id', $school->id);
        })->where('created_at', '<', now()->subDays($days))->delete();

        return back()->with('success', "$count logs older than $days days cleared successfully.");
    }
}
