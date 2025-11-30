<x-layout.public :school="$school" :title="'আবেদনকারী লগইন — ' . ($school->name ?? '')">
    @push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        .login-card { max-width: 520px; margin: 24px auto; border-radius: 16px; box-shadow: 0 10px 24px rgba(0,0,0,.08); }
        .login-hero { background: linear-gradient(135deg, #6f42c1 0%, #0d6efd 50%, #20c997 100%); color: #fff; }
        .login-hero .logo { height: 56px; object-fit: contain; }
        .form-control { height: 44px; }
    </style>
    @endpush

    <div class="container my-4">
        <div class="card login-card border-0">
            <div class="login-hero p-4 text-center">
                @php $logo = $school?->logo ?? null; @endphp
                @if($logo)
                    <img src="{{ asset('storage/'.$logo) }}" alt="{{ $school?->name }}" class="logo mb-2" />
                @endif
                <div class="h5 fw-bold mb-0">{{ $school?->name ?? 'প্রতিষ্ঠান' }}</div>
                <div class="small opacity-75">আবেদনকারী লগইন</div>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('admission.login', ['schoolCode' => $school?->code]) }}" method="post" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <label class="form-label">ইউজারনেম (Application ID)</label>
                        <input type="text" name="username" class="form-control" placeholder="যেমন: JSS_ADD0001" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">পাসওয়ার্ড</label>
                        <input type="password" name="password" class="form-control" placeholder="পাসওয়ার্ড লিখুন" required>
                    </div>
                    <div class="col-12 d-grid">
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fa-solid fa-right-to-bracket me-1"></i> লগইন</button>
                    </div>
                    @if(session('admission_login_error'))
                        <div class="col-12">
                            <div class="alert alert-danger mb-0">{{ session('admission_login_error') }}</div>
                        </div>
                    @endif
                </form>
                <div class="text-center mt-3">
                    <a href="{{ route('admission.preview', [$school?->code, request()->query('app')]) }}" class="small">প্রিভিউতে ফিরে যান</a>
                </div>
            </div>
        </div>
    </div>
</x-layout.public>
