@extends('layouts.admin')
@section('title', 'বায়োমেট্রিক রিপোর্ট')
@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold"><i class="fas fa-chart-bar text-info me-2"></i>বায়োমেট্রিক রিপোর্ট</h4>
            <small class="text-muted">{{ $school->name }}</small>
        </div>
        <a href="{{ route('principal.institute.biometric.dashboard', $school) }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> ড্যাশবোর্ড
        </a>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <a href="{{ route('principal.institute.biometric.reports.daily', $school) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 text-center py-4 hover-lift">
                    <div class="fs-1 mb-2">📋</div>
                    <h5 class="fw-bold">দৈনিক পাঞ্চ রিপোর্ট</h5>
                    <p class="text-muted small">আজ বা যেকোনো দিনের বায়োমেট্রিক পাঞ্চ লগ দেখুন।</p>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('principal.institute.biometric.reports.sync-history', $school) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 text-center py-4 hover-lift">
                    <div class="fs-1 mb-2">🔄</div>
                    <h5 class="fw-bold">সিঙ্ক ইতিহাস</h5>
                    <p class="text-muted small">Local Agent এবং ডিভাইসের সিঙ্ক লগ ইতিহাস দেখুন।</p>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('principal.institute.biometric.enrollment.index', $school) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 text-center py-4 hover-lift">
                    <div class="fs-1 mb-2">👆</div>
                    <h5 class="fw-bold">এনরোলমেন্ট স্ট্যাটাস</h5>
                    <p class="text-muted small">কতজন শিক্ষার্থী এনরোলড আছে তা দেখুন।</p>
                </div>
            </a>
        </div>
    </div>
</div>
<style>.hover-lift:hover{transform:translateY(-4px);transition:.2s;box-shadow:0 8px 30px rgba(0,0,0,.12)!important}</style>
@endsection
