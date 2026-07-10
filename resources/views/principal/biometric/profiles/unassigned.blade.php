@extends('layouts.admin')
@section('title', 'অজানা ফিঙ্গারপ্রিন্ট প্রোফাইল')

@push('styles')
<style>
.profile-card {
    background: linear-gradient(135deg,#1e1e2e 0%,#2a2a3e 100%);
    border: 1px solid rgba(125,211,252,.15);
    border-radius: 14px;
    color: #fff;
    transition: transform .2s, box-shadow .2s;
}
.profile-card:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,.3); }
.bio-id-badge {
    font-family: monospace;
    font-size: 1.15rem;
    font-weight: 700;
    color: #7dd3fc;
    background: rgba(125,211,252,.1);
    border: 1px solid rgba(125,211,252,.25);
    border-radius: 8px;
    padding: .3rem .8rem;
    display: inline-block;
}
.finger-dot {
    width: 12px; height: 12px; border-radius: 50%;
    background: #34d399;
    display: inline-block;
    margin-right: 3px;
    box-shadow: 0 0 6px #34d399;
}
.alert-unassigned {
    background: linear-gradient(90deg,rgba(245,158,11,.15),transparent);
    border-left: 4px solid #f59e0b;
    border-radius: 8px;
}
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold">
                <i class="fas fa-question-circle text-warning me-2"></i>অজানা ফিঙ্গারপ্রিন্ট প্রোফাইল
            </h4>
            <small class="text-muted">{{ $school->name }} — ডিভাইস থেকে আপলোড হওয়া টেমপ্লেট যা এখনো কোনো ব্যক্তির সাথে লিংক করা হয়নি</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('principal.institute.biometric.monitor', $school) }}" class="btn btn-sm btn-outline-info">
                <i class="fas fa-tv me-1"></i>লাইভ মনিটর
            </a>
            <a href="{{ route('principal.institute.biometric.dashboard', $school) }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> ড্যাশবোর্ড
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">{{ $errors->first() }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Info alert --}}
    @if($totalUnassigned > 0)
    <div class="alert-unassigned p-3 mb-4 d-flex align-items-center gap-3">
        <span class="fs-2">⚠️</span>
        <div>
            <div class="fw-bold text-warning">{{ $totalUnassigned }}টি ফিঙ্গারপ্রিন্ট প্রোফাইল লিংক করা হয়নি।</div>
            <div class="small text-muted">এই প্রোফাইলগুলো ডিভাইস থেকে আপলোড হয়েছে কিন্তু কোনো শিক্ষার্থী বা শিক্ষকের সাথে যুক্ত করা হয়নি। নিচে থেকে প্রতিটি প্রোফাইল ম্যানুয়ালি লিংক করুন।</div>
        </div>
    </div>
    @endif

    {{-- Search --}}
    <form method="GET" class="mb-4">
        <div class="input-group" style="max-width:400px">
            <span class="input-group-text bg-dark text-muted border-secondary"><i class="fas fa-search"></i></span>
            <input type="text" name="search" class="form-control bg-dark text-white border-secondary"
                   placeholder="বায়োমেট্রিক আইডি দিয়ে খুঁজুন..." value="{{ $search }}">
            <button type="submit" class="btn btn-outline-primary">খুঁজুন</button>
        </div>
    </form>

    {{-- Profile Cards --}}
    @if($profiles->isEmpty())
    <div class="text-center py-5 text-muted">
        <i class="fas fa-check-circle fa-4x text-success mb-3 d-block"></i>
        <h5>সব প্রোফাইল লিংক করা আছে!</h5>
        <p>কোনো অজানা ফিঙ্গারপ্রিন্ট প্রোফাইল নেই।</p>
    </div>
    @else
    <div class="row g-3">
        @foreach($profiles as $profile)
        <div class="col-lg-6">
            <div class="profile-card p-3">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-muted small mb-1">বায়োমেট্রিক আইডি</div>
                        <span class="bio-id-badge">#{{ $profile->biometric_id }}</span>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted mb-1">টেমপ্লেট</div>
                        <div>
                            @for($i = 0; $i < $profile->finger_count; $i++)
                                <span class="finger-dot"></span>
                            @endfor
                            <span class="small text-light ms-1">{{ $profile->finger_count }} টি আঙুল</span>
                        </div>
                    </div>
                </div>

                <div class="row g-2 align-items-center">
                    <div class="col-md-7">
                        <form action="{{ route('principal.institute.biometric.profiles.link', [$school, $profile]) }}" method="POST">
                            @csrf
                            <div class="mb-2">
                                <select name="user_type" class="form-select form-select-sm bg-dark text-white border-secondary"
                                        onchange="updateUserList(this, {{ $profile->id }})">
                                    <option value="student">শিক্ষার্থী</option>
                                    <option value="teacher">শিক্ষক</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <select name="user_id" id="user-select-{{ $profile->id }}" class="form-select form-select-sm bg-dark text-white border-secondary">
                                    <option value="">— শিক্ষার্থী নির্বাচন করুন —</option>
                                    @foreach($students as $student)
                                    <option value="{{ $student->id }}" data-type="student">
                                        {{ $student->student_name_en ?? $student->student_name_bn }} ({{ $student->student_id }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="fas fa-link me-1"></i>লিংক করুন
                            </button>
                        </form>
                    </div>
                    <div class="col-md-5 text-center">
                        <div class="small text-muted mb-2">তৈরির তারিখ</div>
                        <div class="small">{{ $profile->created_at->format('d M Y') }}</div>
                        <form action="{{ route('principal.institute.biometric.profiles.delete', [$school, $profile]) }}"
                              method="POST" class="mt-2"
                              onsubmit="return confirm('এই অজানা প্রোফাইল ও সকল টেমপ্লেট মুছে ফেলবেন?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                <i class="fas fa-trash me-1"></i>মুছুন
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    @if($profiles->hasPages())
    <div class="mt-4">{{ $profiles->withQueryString()->links() }}</div>
    @endif
    @endif

</div>

{{-- Hidden data for JS --}}
<script id="students-data" type="application/json">{!! json_encode($students->map(fn($s) => ['id'=>$s->id,'name'=>($s->student_name_en ?? $s->student_name_bn).' ('.$s->student_id.')','type'=>'student'])) !!}</script>
<script id="teachers-data" type="application/json">{!! json_encode($teachers->map(fn($t) => ['id'=>$t->id,'name'=>trim($t->first_name.' '.$t->last_name).' ('.($t->phone ?? 'N/A').')','type'=>'teacher'])) !!}</script>

@push('scripts')
<script>
const studentsData = JSON.parse(document.getElementById('students-data').textContent);
const teachersData = JSON.parse(document.getElementById('teachers-data').textContent);

function updateUserList(select, profileId) {
    const userSelect = document.getElementById('user-select-' + profileId);
    const type = select.value;
    const data = type === 'student' ? studentsData : teachersData;
    const placeholder = type === 'student' ? '— শিক্ষার্থী নির্বাচন করুন —' : '— শিক্ষক নির্বাচন করুন —';

    userSelect.innerHTML = `<option value="">${placeholder}</option>`;
    data.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.id;
        opt.textContent = item.name;
        userSelect.appendChild(opt);
    });
}
</script>
@endpush
@endsection
