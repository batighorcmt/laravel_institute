<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>অ্যাকশন প্রয়োজন</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-start:#0f172a; --bg-end:#1e293b; --accent:#6366f1; --accent-soft:#818cf8; --danger:#dc2626;
        }
        body { min-height:100vh; margin:0; font-family:'Segoe UI',system-ui,-apple-system,BlinkMacSystemFont,'Helvetica Neue',Arial,sans-serif; background:linear-gradient(135deg,var(--bg-start),var(--bg-end)); display:flex; align-items:center; justify-content:center; padding:40px 24px; color:#e2e8f0; font-size:14px; }
        .glass-card { position:relative; width:100%; max-width:600px; padding:42px 40px 36px; border-radius:28px; backdrop-filter: blur(18px) saturate(160%); background:linear-gradient(165deg,rgba(255,255,255,0.18),rgba(255,255,255,0.06)); box-shadow:0 18px 60px -10px rgba(0,0,0,.55), 0 2px 4px rgba(255,255,255,0.08) inset; border:1px solid rgba(255,255,255,0.22); animation:cardIn .7s cubic-bezier(.16,.8,.33,1); }
        @keyframes cardIn { from { opacity:0; transform:translateY(25px) scale(.96); } to { opacity:1; transform:translateY(0) scale(1); } }
        .halo::before { content:""; position:absolute; inset:-120px; background:radial-gradient(circle at 30% 30%,rgba(99,102,241,.35),transparent 60%), radial-gradient(circle at 70% 70%,rgba(14,165,233,.30),transparent 55%); filter:blur(65px); z-index:-1; }
        h1.block-title { margin:0 0 14px; font-size:1.35rem; font-weight:600; letter-spacing:.5px; display:flex; align-items:center; gap:10px; }
        .lock-icon { width:44px; height:44px; border-radius:14px; background:linear-gradient(135deg,var(--accent),var(--accent-soft)); display:flex; align-items:center; justify-content:center; box-shadow:0 6px 16px -4px rgba(99,102,241,.55); }
        .lock-icon svg { width:24px; height:24px; stroke:#fff; }
        p.message { margin:0 0 26px; line-height:1.68; font-size:1rem; font-weight:600; color:#cbd5e1; }
        .actions { display:flex; flex-wrap:wrap; gap:12px; }
        .btn-modern { position:relative; border:none; padding:14px 22px; font-size:.9rem; font-weight:700; letter-spacing:.7px; text-transform:uppercase; border-radius:16px; background:linear-gradient(135deg,#1e3a8a,#1d4ed8); color:#fff; cursor:pointer; overflow:hidden; transition:.35s; }
        .btn-modern:hover { box-shadow:0 10px 28px -6px rgba(29,78,216,.55); transform:translateY(-3px); }
        .btn-outline-lite { background:rgba(255,255,255,0.10); color:#e2e8f0; border:1px solid rgba(255,255,255,.25); }
        .btn-outline-lite:hover { background:rgba(255,255,255,0.18); }
        .btn-danger-lite { background:linear-gradient(135deg,#dc2626,#ef4444); }
        .btn-danger-lite:hover { box-shadow:0 10px 28px -6px rgba(220,38,38,.55); }
        .meta-bar { display:flex; align-items:center; gap:8px; margin-bottom:22px; flex-wrap:wrap; }
        .tag { padding:6px 14px; background:rgba(255,255,255,0.14); border:1px solid rgba(255,255,255,0.28); font-size:.9rem; font-weight:700; letter-spacing:.6px; border-radius:40px; backdrop-filter:blur(6px); }
        .divider { height:1px; background:linear-gradient(90deg,transparent,rgba(255,255,255,0.35),transparent); margin:22px 0 28px; }
        .fade-pop { animation:fadePop .6s ease; }
        @keyframes fadePop { from { opacity:0; transform:scale(.94); } to { opacity:1; transform:scale(1); } }
        @media (max-width:640px){ .glass-card { padding:34px 26px 32px; } h1.block-title { font-size:1.15rem; } .lock-icon { width:40px; height:40px; } }
    </style>
</head>
<body>
    <div class="glass-card halo fade-pop">
        <div class="meta-bar">
            <span class="tag">403 FORBIDDEN</span>
            @isset($schoolCode)
                <span class="tag">SCHOOL: {{ strtoupper($schoolCode) }}</span>
            @endisset
            @if(($showLogout ?? false) === true)
                <span class="tag">SESSION ACTIVE</span>
            @endif
        </div>
        <h1 class="block-title">
            <span class="lock-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V7.875A4.125 4.125 0 0 0 12.375 3.75h-.75A4.125 4.125 0 0 0 7.5 7.875V10.5m-.75 0h10.5m-10.5 0A2.25 2.25 0 0 0 5.25 12.75v6A2.25 2.25 0 0 0 7.5 21h9a2.25 2.25 0 0 0 2.25-2.25v-6a2.25 2.25 0 0 0-2.25-2.25m-10.5 0h10.5" />
                </svg>
            </span>
            {{ $title ?? 'অনুমতি সীমাবদ্ধ' }}
        </h1>
        <div class="divider"></div>
        <p class="message">{{ $message ?? 'দেখার অনুমতি নেই।' }}</p>
        <div class="actions">
            <button onclick="history.back()" class="btn-modern btn-outline-lite">পেছনে যান</button>
            @isset($schoolCode)
                @if(($showLogout ?? false) === true)
                    <form method="POST" action="{{ route('admission.logout', $schoolCode) }}" style="display:inline-block;">
                        @csrf
                        <button type="submit" class="btn-modern btn-danger-lite">লগআউট করুন</button>
                    </form>
                @endif
            @endisset
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
