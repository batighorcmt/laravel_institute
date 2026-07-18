<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Laravel's SESSION_LIFETIME is normally only enforced by probabilistic
 * garbage collection (session.lottery) — a stale session row/file that GC
 * hasn't swept yet stays valid indefinitely, so a session can outlive its
 * configured lifetime if the user's cookie happens to survive (e.g. a
 * computer restart while expire_on_close's cookie hadn't actually been
 * cleared by the browser). This middleware enforces the same lifetime
 * deterministically: it stamps the session with a last-activity timestamp
 * on every request and force-logs-out once that gap exceeds the configured
 * lifetime, regardless of whether GC has run.
 */
class ExpireSessionOnIdle
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $lifetimeSeconds = ((int) config('session.lifetime', 60)) * 60;
            $lastActivity = $request->session()->get('_last_activity_at');

            if ($lastActivity !== null && (time() - $lastActivity) > $lifetimeSeconds) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $message = 'নিরাপত্তার জন্য আপনার সেশনের মেয়াদ শেষ হয়েছে। আবার লগইন করুন।';

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json(['message' => $message], 401);
                }

                return redirect()->route('login')->with('status', $message);
            }

            $request->session()->put('_last_activity_at', time());
        }

        return $next($request);
    }
}
