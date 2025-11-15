<x-layout.public :school="$school" :title="'ভর্তি তথ্য — ' . $school->name">
    @push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

    @push('scripts')
    <script>
        // No scripts needed for static page, keep hook for consistency
    </script>
    @endpush
</x-layout.public>
