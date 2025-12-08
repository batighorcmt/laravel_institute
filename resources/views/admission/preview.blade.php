<x-layout.public :school="$school" :title="'আবেদন সারসংক্ষেপ — ' . ($school->name ?? '')">
    @push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="icon" type="image/png" href="{{ asset('images/batighor-favicon.png') }}">
    <style>
        .preview-root { font-size: 1.06rem; }
        .hero-card { border-radius: 16px; overflow: hidden; box-shadow: 0 10px 24px rgba(0,0,0,.08); }
        .hero-banner { background: linear-gradient(135deg, #6f42c1 0%, #0d6efd 50%, #20c997 100%); color: #fff; }
        .app-badge { background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.25); }
        .avatar { width: 128px; height: 160px; object-fit: cover; border-radius: 10px; border: 3px solid rgba(255,255,255,.7); background: #fff; }
        .section-title { border-left: 4px solid #0d6efd; padding-left: .6rem; font-weight: 800; font-size: 1.1rem; }
        .list-icon { width: 30px; height: 30px; border-radius: 8px; display:inline-flex; align-items:center; justify-content:center; background:#eef4ff; color:#0d6efd; }
        .stat-badge { font-size:.95rem; }
        .kv { display:flex; align-items:center; gap:.5rem; }
        .kv .k { color:#6c757d; min-width: 8rem; }
        .kv .v { font-weight:600; }
        .applicant-name { font-weight: 800; font-size: 1.35rem; line-height: 1.25; }
        .apply-class { font-weight: 700; font-size: 1.1rem; color: #0d6efd; }
        @media (min-width:768px){ .applicant-name{ font-size:1.5rem; } .apply-class{ font-size:1.2rem; } }
        .fee-highlight { font-size:1.4rem; font-weight:800; color:#0d6efd; }
        .ssl-wide { width: 100vw; max-width: 100vw; height: auto; display:block; margin-left: 50%; transform: translateX(-50%); }
        .login-bar { border: 1px solid #e9ecef; border-radius: 12px; padding: 12px; background:#f8f9fa; }
        .login-bar .form-control { height: 40px; }
    </style>
    @endpush

    <div class="container my-4 preview-root">
        @php $applicantSession = session('admission_applicant'); @endphp
        <div class="d-flex justify-content-end mb-2">
            @if($applicantSession)
                <div class="d-inline-flex align-items-center gap-2">
                    <span class="badge bg-success px-3 py-2">লগইন: {{ data_get($applicantSession, 'app_id') }}</span>
                    <form action="{{ route('admission.logout', $school->code) }}" method="post" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-right-from-bracket me-1"></i> লগআউট</button>
                    </form>
                </div>
            @else
                <a href="{{ route('admission.login.page', $school->code) }}" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-right-to-bracket me-1"></i> লগইন</a>
            @endif
        </div>

        <div class="card hero-card border-0">
            <div class="hero-banner p-4 p-md-5">
                <div class="d-flex flex-column flex-md-row align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        @php $logo = $school->logo ?? null; @endphp
                        @if($logo)
                            <img src="{{ asset('storage/'.$logo) }}" alt="{{ $school->name }}" style="height:56px; object-fit:contain;" class="me-2" />
                        @endif
                        <div>
                            <div class="h4 mb-1 fw-bold">{{ $school->name }}</div>
                            <div class="small opacity-75">আবেদন সারসংক্ষেপ</div>
                        </div>
                    </div>
                    <div class="text-end mt-3 mt-md-0">
                        <div>
                            <span class="badge app-badge rounded-pill px-3 py-2 me-2 mb-2">Application ID: <strong class="ms-1">{{ $application->app_id }}</strong></span>
                            <span class="badge app-badge rounded-pill px-3 py-2 mb-2">Username: <strong class="ms-1">{{ $application->app_id }}</strong></span>
                        </div>
                        <div class="mt-1">
                            @php $paid = $application->payment_status==='Paid'; @endphp
                            <span class="badge {{ $paid ? 'bg-success' : 'bg-warning text-dark' }} rounded-pill stat-badge">
                                <i class="fa-solid {{ $paid ? 'fa-circle-check' : 'fa-hourglass-half' }} me-1"></i> {{ $application->payment_status }}
                            </span>
                            <span class="badge {{ $application->status==='cancelled' ? 'bg-danger' : 'bg-info' }} rounded-pill stat-badge ms-1">
                                <i class="fa-solid fa-circle-info me-1"></i> {{ ucfirst($application->status) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-4 p-md-5">
                <div class="row g-3">
                    <div class="col-md-4 text-center d-flex flex-column align-items-center justify-content-start">
                        <img class="avatar mb-3" src="{{ $application->photo ? asset('storage/admission/'.$application->photo) : asset('images/default-avatar.png') }}" alt="Applicant Photo">
                        <div class="applicant-name text-center">{{ $application->name_bn ?: $application->name_en }}</div>
                        <div class="text-muted">{{ $application->name_en }}</div>
                        <div class="apply-class mt-1 text-center">ভর্তিচ্ছুক শ্রেণি: {{ $application->class_name ?? '—' }}</div>
                    </div>
                    <div class="col-md-8">
                        <div class="mb-2 section-title"><i class="fa-solid fa-id-card-clip me-2 text-primary"></i>ব্যক্তিগত তথ্য</div>
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <div class="kv"><span class="list-icon"><i class="fa-regular fa-calendar"></i></span><div class="k">জন্ম তারিখ</div><div class="v">{{ optional($application->dob)->format('Y-m-d') }}</div></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="kv"><span class="list-icon"><i class="fa-solid fa-venus-mars"></i></span><div class="k">লিঙ্গ</div><div class="v">{{ $application->gender }}</div></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="kv"><span class="list-icon"><i class="fa-solid fa-hands-praying"></i></span><div class="k">ধর্ম</div><div class="v">{{ $application->religion ?? '—' }}</div></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="kv"><span class="list-icon"><i class="fa-solid fa-mobile-screen"></i></span><div class="k">মোবাইল</div><div class="v">{{ $application->mobile }}</div></div>
                            </div>
                        </div>

                        <div class="mt-3 mb-2 section-title"><i class="fa-solid fa-users me-2 text-primary"></i>অভিভাবক তথ্য</div>
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <div class="kv"><span class="list-icon"><i class="fa-solid fa-person me-1"></i></span><div class="k">পিতা</div><div class="v">{{ $application->father_name_en }} <span class="text-secondary">/ {{ $application->father_name_bn }}</span></div></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="kv"><span class="list-icon"><i class="fa-solid fa-person-dress me-1"></i></span><div class="k">মাতা</div><div class="v">{{ $application->mother_name_en }} <span class="text-secondary">/ {{ $application->mother_name_bn }}</span></div></div>
                            </div>
                            <div class="col-sm-6">
                                @php
                                    $relMap = [
                                        'father' => 'পিতা', 'mother' => 'মাতা', 'uncle' => 'চাচা/মামা', 'aunt' => 'চাচী/খালা',
                                        'brother' => 'ভাই', 'sister' => 'বোন', 'other' => 'অন্যান্য'
                                    ];
                                    $relKey = data_get($application->data, 'guardian_relation');
                                    $relBn = $relKey ? ($relMap[$relKey] ?? $relKey) : null;
                                @endphp
                                <div class="kv"><span class="list-icon"><i class="fa-solid fa-user-shield me-1"></i></span><div class="k">অভিভাবক</div><div class="v">{{ $application->guardian_name_en ?? '—' }} <span class="text-secondary">/ {{ $application->guardian_name_bn ?? '—' }}</span>@if($relBn) <span class="badge bg-secondary ms-2">{{ $relBn }}</span>@endif</div></div>
                            </div>
                        </div>

                        <div class="mt-3 mb-2 section-title"><i class="fa-solid fa-location-dot me-2 text-primary"></i>ঠিকানা</div>
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <div class="kv"><span class="list-icon"><i class="fa-solid fa-location-pin"></i></span><div class="k">বর্তমান ঠিকানা</div><div class="v">{{ $application->present_address }}</div></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="kv"><span class="list-icon"><i class="fa-solid fa-house"></i></span><div class="k">স্থায়ী ঠিকানা</div><div class="v">{{ $application->permanent_address }}</div></div>
                            </div>
                        </div>

                        <div class="mt-3 mb-2 section-title"><i class="fa-solid fa-graduation-cap me-2 text-primary"></i>পূর্ববর্তী শিক্ষা</div>
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <div class="kv"><span class="list-icon"><i class="fa-solid fa-school"></i></span><div class="k">সর্বশেষ বিদ্যালয়</div><div class="v">{{ $application->last_school ?? '—' }}</div></div>
                            </div>
                            <div class="col-sm-3">
                                <div class="kv"><span class="list-icon"><i class="fa-solid fa-chart-line"></i></span><div class="k">ফলাফল</div><div class="v">{{ $application->result ?? '—' }}</div></div>
                            </div>
                            <div class="col-sm-3">
                                <div class="kv"><span class="list-icon"><i class="fa-regular fa-calendar-days"></i></span><div class="k">পাশের বছর</div><div class="v">{{ $application->pass_year ?? '—' }}</div></div>
                            </div>
                        </div>

                        @if($application->status==='cancelled')
                            <div class="alert alert-danger mt-4">
                                <i class="fa-solid fa-triangle-exclamation me-2"></i> বাতিলের কারণ: {{ $application->cancellation_reason }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card-footer bg-light p-4">
                @if($application->payment_status!=='Paid')
                    <div class="d-flex flex-column flex-md-row align-items-center justify-content-between">
                        <div class="mb-3 mb-md-0">
                            @if(isset($fee))
                                <span class="fee-highlight">ফি: ৳ {{ number_format($fee, 0) }}</span>
                            @endif
                        </div>
                        <div>
                            <form action="{{ route('admission.payment', $school->code) }}" method="post" class="d-inline-block">
                                @csrf
                                <input type="hidden" name="app_id" value="{{ $application->app_id }}">
                                <button type="submit" class="btn btn-primary btn-lg px-5"><i class="fa-solid fa-wallet me-2"></i> পেমেন্ট করুন</button>
                            </form>
                        </div>
                    </div>
                    <div class="mt-3">
                        <img class="ssl-wide" src="{{ asset('images/sslcommerz.png') }}" alt="Pay with SSLCommerz" onerror="this.onerror=null;this.src='https://developer.sslcommerz.com/wp-content/uploads/2019/11/sslcommerz-banner.png';">
                    </div>
                @else
                    <div class="text-center">
                        <a href="{{ route('admission.copy', [$school->code, $application->app_id]) }}" class="btn btn-success btn-lg"><i class="fa-solid fa-file-lines me-2"></i> আবেদন কপি</a>
                        <a href="{{ route('admission.admit_card', [$school->code, $application->app_id]) }}" class="btn btn-primary btn-lg ms-2"><i class="fa-solid fa-id-card me-2"></i> এডমিট কার্ড</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layout.public>
