<x-layout.public :school="$school" :title="'ভর্তি তথ্য — ' . $school->name">
    @push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/png" href="{{ asset('images/batighor-favicon.png') }}">
    <style>
        .hero {
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
            color: #fff;
            border-radius: 16px;
        }
        .feature-card {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.06);
            height: 100%;
        }
        .feature-icon {
            width: 48px; height: 48px; display:flex; align-items:center; justify-content:center;
            border-radius: 10px; background: rgba(13,110,253,0.1); color:#0d6efd;
        }
        .cta-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(0,0,0,0.15); }
    </style>
    @endpush

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                @php $applicantSession = session('admission_applicant'); @endphp
                <div class="d-flex justify-content-end mb-2">
                    @if($applicantSession)
                        <div class="d-inline-flex align-items-center gap-2">
                            <span class="badge bg-success px-3 py-2">লগইন: {{ data_get($applicantSession, 'app_id') }}</span>
                            <form action="{{ route('admission.logout', $school->code) }}" method="post" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-right-from-bracket me-1"></i> লগআউট</button>
                            </form>
                        </div>
                    @else
                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#applicantLoginModal"><i class="fas fa-right-to-bracket me-1"></i> আবেদনকারী লগইন</button>
                    @endif
                </div>
                <div class="p-4 p-md-5 hero text-center mb-4">
                    <div class="mb-3">
                        @php $logo = $school->logo ?? null; @endphp
                        @if($logo)
                            <img src="{{ asset('storage/'.$logo) }}" alt="{{ $school->name }}" style="height:72px;object-fit:contain;" />
                        @endif
                    </div>
                    <h1 class="fw-bold mb-2">{{ $school->name }}</h1>
                    <p class="lead mb-4">শিক্ষার মান, শৃঙ্খলা ও মূল্যবোধে আমরা অঙ্গীকারবদ্ধ। আপনার সন্তানের উজ্জ্বল ভবিষ্যতের জন্য সঠিক সিদ্ধান্ত নিন আজই।</p>
                    <a class="btn btn-light btn-lg px-4 cta-btn" href="{{ route('admission.instruction', $school->code) }}">
                        <i class="fas fa-clipboard-check me-2"></i> নির্দেশনা পড়ে আবেদন শুরু করুন
                    </a>
                </div>

                <div class="row g-3 g-md-4">
                    <div class="col-md-4">
                        <div class="card feature-card p-3 p-md-4">
                            <div class="feature-icon mb-3"><i class="fas fa-chalkboard-teacher"></i></div>
                            <h5 class="fw-bold">অভিজ্ঞ শিক্ষক মণ্ডলী</h5>
                            <p class="text-muted mb-0">বিষয়ভিত্তিক দক্ষ ও প্রশিক্ষিত শিক্ষকদের তত্ত্বাবধানে মানসম্মত পাঠদান।</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card feature-card p-3 p-md-4">
                            <div class="feature-icon mb-3"><i class="fas fa-book-open"></i></div>
                            <h5 class="fw-bold">আধুনিক পাঠক্রম</h5>
                            <p class="text-muted mb-0">সহজবোধ্য ও সমৃদ্ধ কারিকুলাম, কো-কারিকুলার কার্যক্রমে বিশেষ গুরুত্ব।</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card feature-card p-3 p-md-4">
                            <div class="feature-icon mb-3"><i class="fas fa-shield-alt"></i></div>
                            <h5 class="fw-bold">নিরাপদ পরিবেশ</h5>
                            <p class="text-muted mb-0">সিসিটিভি পর্যবেক্ষণ, স্বাস্থ্যবিধি মেনে পরিচ্ছন্ন ও নিরাপদ ক্যাম্পাস।</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card feature-card p-3 p-md-4">
                            <div class="feature-icon mb-3"><i class="fas fa-laptop-code"></i></div>
                            <h5 class="fw-bold">ডিজিটাল সুবিধা</h5>
                            <p class="text-muted mb-0">কম্পিউটার ল্যাব, অনলাইন রেজাল্ট ও উপস্থিতি—অভিভাবকদের জন্য সহজ ট্র্যাকিং।</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card feature-card p-3 p-md-4">
                            <div class="feature-icon mb-3"><i class="fas fa-futbol"></i></div>
                            <h5 class="fw-bold">সহশিক্ষা কার্যক্রম</h5>
                            <p class="text-muted mb-0">খেলাধুলা, সাংস্কৃতিক প্রতিযোগিতা ও নেতৃত্ব বিকাশে নানা আয়োজন।</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card feature-card p-3 p-md-4">
                            <div class="feature-icon mb-3"><i class="fas fa-award"></i></div>
                            <h5 class="fw-bold">ধারাবাহিক সাফল্য</h5>
                            <p class="text-muted mb-0">প্রতিবছর পাবলিক পরীক্ষায় উৎকৃষ্ট ফলাফল ও পুরস্কার প্রাপ্তি।</p>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a class="btn btn-primary btn-lg px-5 cta-btn" href="{{ route('admission.instruction', $school->code) }}">
                        <i class="fas fa-arrow-right me-2"></i> ভর্তি নির্দেশনায় যান
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Applicant Login Modal -->
    <div class="modal fade" id="applicantLoginModal" tabindex="-1" aria-labelledby="applicantLoginLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="applicantLoginLabel">আবেদনকারী লগইন</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('admission.login', ['schoolCode' => $school->code]) }}" method="post" id="applicantLoginForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">ইউজারনেম (Application ID)</label>
                            <input type="text" name="username" class="form-control" placeholder="যেমন: JSS_ADD0001" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">পাসওয়ার্ড</label>
                            <input type="password" name="password" class="form-control" placeholder="পাসওয়ার্ড লিখুন" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-right-to-bracket me-1"></i> লগইন</button>
                    </form>
                    @if(session('admission_login_error'))
                        <div class="alert alert-danger mt-2 mb-0">{{ session('admission_login_error') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Placeholder hook if needed later
    </script>
    <!-- Bootstrap JS for modal functionality -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    @endpush
</x-layout.public>
