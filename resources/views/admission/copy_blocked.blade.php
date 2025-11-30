<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>কপি দেখা যাবে না</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: radial-gradient(1200px circle at 10% 10%, #fff7ed 0, #fff1f2 40%, #fff 100%); min-height: 100vh; }
        .floating-card { position: relative; max-width: 560px; margin: 10vh auto; background: #fff; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.06); overflow: hidden; animation: float 6s ease-in-out infinite; }
        @keyframes float { 0%{transform:translateY(0)} 50%{transform:translateY(-10px)} 100%{transform:translateY(0)} }
        .content { position: relative; z-index: 1; }
        .badge-warn { background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; }
        .btn-outline { border-color: #93c5fd; color: #1d4ed8; }
        .btn-outline:hover { background: #eff6ff; }
        .btn-pay { background: #16a34a; border-color: #16a34a; }
        .btn-pay:hover { background: #15803d; border-color: #15803d; }
    </style>
</head>
<body>
    <div class="floating-card">
        <div class="content p-4 p-md-5">
            <div class="d-flex align-items-center mb-3">
                <span class="badge rounded-pill badge-warn me-2">403</span>
                <h5 class="m-0">ফিস পরিশোধ না হওয়ায় কপি দেখা যাবে না</h5>
            </div>
            <p class="text-secondary mb-4">
                ভর্তি আবেদন ফিস পরিশোধের পরেই আবেদন কপি দেখা ও প্রিন্ট করা যাবে।
            </p>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admission.preview', [$school->code, $application->app_id]) }}" class="btn btn-outline-primary btn-outline">প্রিভিউতে যান</a>
                <form method="POST" action="{{ route('admission.payment', $school->code) }}">
                    @csrf
                    <input type="hidden" name="app_id" value="{{ $application->app_id }}">
                    <button type="submit" class="btn btn-success btn-pay">ফিস পরিশোধ করুন</button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
