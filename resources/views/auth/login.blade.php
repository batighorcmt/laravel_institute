<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('images/batighor-favicon.png') }}">
    <title>Batighor EIMS | Login</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>
        body, .form-control, .btn { font-family: 'Kalpurush', system-ui, -apple-system, Segoe UI, Roboto, 'Helvetica Neue', Arial, 'Noto Sans', 'Liberation Sans', sans-serif; }
        body { min-height:100vh; overflow-x:hidden; }
        .bg-animated {
            position:fixed; inset:0; z-index:-1;
            background:linear-gradient(120deg,#0d6efd,#6610f2,#6f42c1,#20c997,#0dcaf0);
            background-size:400% 400%; animation:gradientMove 18s ease infinite;
        }
        .bg-mark {
            position:fixed; inset:0; z-index:-1; display:flex; align-items:center; justify-content:center; opacity:.08;
            background-repeat:no-repeat; background-position:center; background-size:min(520px, 60vw) auto;
            background-image:url('{{ asset('images/logo.svg') }}');
        }
        @keyframes gradientMove { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
        .glass-card { background:rgba(255,255,255,.75); backdrop-filter:blur(16px) saturate(140%); -webkit-backdrop-filter:blur(16px) saturate(140%); border:1px solid rgba(255,255,255,.4); }
        .brand-mark { font-weight:700; letter-spacing:.5px; display:inline-flex; align-items:center; }
        .brand-mark i { margin-right:.4rem; }
        .floating-group { position:relative; margin-bottom:1.35rem; }
    .floating-group input { width:100%; height:54px; padding:26px 16px 8px; border-radius:10px; border:1px solid #ced4da; background:#fff; transition:.25s; }
    /* Ensure password field has space for eye toggle */
    #password { padding-right:44px; }
        .floating-group input:focus { box-shadow:0 0 0 0.25rem rgba(13,110,253,.25); border-color:#0d6efd; }
        .floating-group label { position:absolute; top:14px; left:16px; font-size:.95rem; color:#6c757d; transition:.25s; pointer-events:none; }
        .floating-group input:not(:placeholder-shown) + label,
        .floating-group input:focus + label { top:6px; font-size:.7rem; letter-spacing:.5px; color:#0d6efd; }
        .toggle-pass { position:absolute; top:50%; right:14px; transform:translateY(-50%); background:transparent; border:none; color:#6c757d; }
        .toggle-pass:focus { outline:none; color:#0d6efd; }
        .login-wrapper { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:40px 18px; }
        .brand-wrap { display:flex; flex-direction:column; align-items:center; }
        .brand-logo { width:200px; height:200px; object-fit:contain; filter:drop-shadow(0 8px 18px rgba(13,110,253,.35)); }
        .brand-title { font-weight:800; font-size:1.25rem; letter-spacing:.5px; }
        .caps-indicator { display:none; font-size:.72rem; color:#dc3545; margin-left:4px; }
        .action-row { display:flex; justify-content:space-between; align-items:center; margin:-6px 0 14px; }
        .btn-gradient { background:linear-gradient(135deg,#0d6efd,#6610f2); border:none; color:#fff; font-weight:600; letter-spacing:.5px; box-shadow:0 12px 24px -10px rgba(13,110,253,.55); }
        .btn-gradient:hover { filter:brightness(1.08); }
        .meta-links a { font-size:.75rem; text-decoration:none; color:#495057; }
        .meta-links a:hover { color:#0d6efd; }
    /* .demo-pill removed per request */
        .fade-in { animation:fadeIn .9s ease; }
        @keyframes fadeIn { from{opacity:0; transform:translateY(12px);} to{opacity:1; transform:translateY(0);} }
        .error-list { font-size:.75rem; margin-top:-4px; }
        .dark-toggle { position:absolute; top:14px; right:14px; background:rgba(255,255,255,.55); border:none; padding:8px 12px; border-radius:8px; font-size:.8rem; font-weight:600; display:flex; align-items:center; gap:6px; }
        .dark-toggle i { font-size:.9rem; }
        body.dark-mode .glass-card { background:rgba(25,25,28,.78); color:#e9ecef; border-color:rgba(255,255,255,.1); }
        body.dark-mode .floating-group input { background:#1f1f23; color:#e9ecef; border-color:#343a40; }
        body.dark-mode .floating-group label { color:#adb5bd; }
        body.dark-mode .floating-group input:not(:placeholder-shown)+label, body.dark-mode .floating-group input:focus+label{ color:#66b2ff; }
        body.dark-mode .btn-gradient { background:linear-gradient(135deg,#6610f2,#0d6efd); }
        body.dark-mode .demo-pill { border-color:#6610f2; }
        body.dark-mode .demo-pill:hover { background:#6610f2; }
        body.dark-mode .meta-links a { color:#adb5bd; }
        body.dark-mode .meta-links a:hover { color:#66b2ff; }
    </style>
</head>
<body>
<div class="bg-animated"></div>
<div class="bg-mark"></div>
<div class="login-wrapper">
    <div class="glass-card shadow-lg rounded-4 p-4 p-md-5 fade-in" style="width:100%; max-width:600px; position:relative;">
        <button id="themeToggleLogin" class="dark-toggle"><i class="fas fa-adjust"></i><span>Mode</span></button>
        <div class="text-center mb-4 brand-wrap">
            <img src="{{ asset('images/batighor_eims.png') }}" alt="Batighor" class="brand-logo mb-2">
            <div class="brand-title">Batighor Educational Institute Management System</div>
        </div>

        @if ($errors->any())
            <div class="mb-3">
                <div class="alert alert-danger py-2 px-3 mb-0" style="font-size:.8rem;">
                    <ul class="mb-0 error-list list-unstyled">
                        @foreach ($errors->all() as $error)
                            <li><i class="fas fa-exclamation-circle"></i> {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form action="{{ route('login') }}" method="post" novalidate id="loginForm">
            @csrf
            <div class="floating-group">
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder=" " required autocomplete="username" class="@error('email') is-invalid @enderror">
                <label for="email">Email / Username</label>
                @error('email')<small class="text-danger" style="position:absolute; bottom:-16px; left:4px;">{{ $message }}</small>@enderror
            </div>
            <div class="floating-group">
                <input type="password" id="password" name="password" placeholder=" " required autocomplete="current-password" class="@error('password') is-invalid @enderror">
                <label for="password">Password <span id="capsIndicator" class="caps-indicator">(Caps Lock)</span></label>
                <button type="button" class="toggle-pass" id="togglePassword" aria-label="Show Password"><i class="fas fa-eye"></i></button>
                @error('password')<small class="text-danger" style="position:absolute; bottom:-16px; left:4px;">{{ $message }}</small>@enderror
            </div>
            <div class="action-row" style="justify-content:space-between;">
                <div class="form-check" style="margin:0;">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember" style="font-size:.75rem;">Remember Me</label>
                </div>
                @if (Route::has('password.request'))
                    <div class="meta-links"><a href="{{ route('password.request') }}">Password Reset</a></div>
                @endif
            </div>
            
            <button type="submit" class="btn btn-gradient btn-block py-3 rounded-3" id="submitBtn" style="font-size:.95rem;">
                <span class="spinner-border spinner-border-sm d-none" id="submitSpinner" role="status" aria-hidden="true"></span>
                <span class="btn-text">Login</span>
            </button>
        </form>
        <div class="mt-4 text-center meta-links" style="font-size:.7rem;">
            <span>© {{ date('Y') }} Batighor Software Systems Limited</span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const pass = document.getElementById('password');
    const toggle = document.getElementById('togglePassword');
    const caps = document.getElementById('capsIndicator');
    const form = document.getElementById('loginForm');
    const btn = document.getElementById('submitBtn');
    const spin = document.getElementById('submitSpinner');
    const themeBtn = document.getElementById('themeToggleLogin');
    // Demo autofill removed per request

    function applyTheme(mode){
        if(mode==='dark'){ document.body.classList.add('dark-mode'); } else { document.body.classList.remove('dark-mode'); }
    }
    const saved = localStorage.getItem('loginTheme'); if(saved) applyTheme(saved);
    if(themeBtn){ themeBtn.addEventListener('click',()=>{ const next = localStorage.getItem('loginTheme')==='dark' ? 'light':'dark'; localStorage.setItem('loginTheme', next); applyTheme(next); }); }

    if (toggle && pass) {
        toggle.addEventListener('click', function(){
            const type = pass.getAttribute('type') === 'password' ? 'text' : 'password';
            pass.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }
    if (pass && caps) {
        const syncCaps = e => { caps.style.display = e.getModifierState && e.getModifierState('CapsLock') ? 'inline' : 'none'; };
        pass.addEventListener('keydown', syncCaps); pass.addEventListener('keyup', syncCaps);
    }
    if (form && btn && spin) {
        form.addEventListener('submit', function(){ btn.setAttribute('disabled','disabled'); spin.classList.remove('d-none'); });
    }
    @if (session('status')) if (window.toastr) toastr.success(@json(session('status'))); @endif
    @if (session('error')) if (window.toastr) toastr.error(@json(session('error'))); @endif
});
</script>
</body>
</html>
