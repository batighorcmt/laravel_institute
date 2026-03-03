<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\NotificationLog;
use App\Models\School;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class FcmDiagnosticsController extends Controller
{
    public function index(School $school, Request $request)
    {
        // 1. Health Check
        $saPath = config('fcm.service_account_path');
        $saExists = File::exists($saPath);
        $saDetails = null;
        if ($saExists) {
            $saDetails = json_decode(File::get($saPath), true);
        }

        // 2. Active Tokens for this school's users
        // Since DeviceToken is linked to User, we filter by users belonging to this school
        $tokens = DeviceToken::whereHas('user.schoolRoles', function($q) use ($school) {
            $q->where('school_id', $school->id);
        })
        ->with('user')
        ->latest('updated_at')
        ->paginate(50, ['*'], 'tokens_page');

        // 3. Notification Logs (Latest)
        // We filter logs for users in this school
        $logsQuery = NotificationLog::whereHas('user.schoolRoles', function($q) use ($school) {
            $q->where('school_id', $school->id);
        })
        ->with('user')
        ->latest();

        if ($request->status) {
            $logsQuery->where('status', $request->status);
        }

        $logs = $logsQuery->paginate(50, ['*'], 'logs_page');

        return view('principal.institute.fcm.diagnostics', compact('school', 'tokens', 'logs', 'saExists', 'saDetails'));
    }

    public function testSend(School $school, Request $request)
    {
        $request->validate([
            'token' => 'required',
            'title' => 'required',
            'body' => 'required',
            'user_id' => 'required|exists:users,id'
        ]);

        try {
            $fcm = new FcmService();
            $result = $fcm->sendToToken(
                $request->token,
                $request->title,
                $request->body,
                ['type' => 'test'],
                $request->user_id
            );

            if ($result['success']) {
                return back()->with('success', 'Test notification sent successfully!');
            } else {
                return back()->with('error', 'Failed to send: ' . ($result['body']['error']['message'] ?? 'Unknown error'));
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function deleteToken(School $school, DeviceToken $token)
    {
        $token->delete();
        return back()->with('success', 'Token removed successfully.');
    }

    public function clearLogs(School $school, Request $request)
    {
        $days = (int)$request->input('days', 30);
        $count = NotificationLog::where('created_at', '<', now()->subDays($days))
            ->whereHas('user.schoolRoles', function($q) use ($school) {
                $q->where('school_id', $school->id);
            })
            ->delete();

        return back()->with('success', "Cleared $count logs older than $days days.");
    }

    public function purgeStale(School $school, Request $request)
    {
        $days = (int)$request->input('days', 90);
        $count = DeviceToken::where('updated_at', '<', now()->subDays($days))
            ->whereHas('user.schoolRoles', function($q) use ($school) {
                $q->where('school_id', $school->id);
            })
            ->delete();

        return back()->with('success', "Purged $count stale tokens older than $days days.");
    }
}
