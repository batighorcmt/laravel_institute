@extends('layouts.admin')
@section('content')
@push('styles')
<style>
    .profile-wrapper { display:grid; grid-template-columns: 300px 1fr; gap:28px; }
    @media (max-width: 992px) { .profile-wrapper { grid-template-columns:1fr; } }
    .sidebar-card { background:#ffffff; border:1px solid #e0e7ff; border-radius:22px; padding:22px 22px 28px; box-shadow:0 6px 28px -8px rgba(30,58,138,.25); position:sticky; top:90px; }
    .photo-large { width:100%; aspect-ratio:4/5; border-radius:18px; overflow:hidden; background:linear-gradient(135deg,#f1f5f9,#e2e8f0); border:2px solid #e2e8f0; }
    .photo-large img { width:100%; height:100%; object-fit:cover; }
    .badge-stack { display:flex; flex-wrap:wrap; gap:6px; margin-top:16px; }
    .badge-chip { padding:8px 16px; border-radius:40px; font-size:.9rem; font-weight:700; letter-spacing:.6px; background:#f1f5f9; border:1px solid #e2e8f0; color:#1e293b; }
    .badge-chip.paid { background:#ecfdf5; border-color:#34d399; color:#065f46; }
    .badge-chip.unpaid { background:#fef9c3; border-color:#fde047; color:#92400e; }
    .badge-chip.accepted { background:#ccfbf1; border-color:#5eead4; color:#0f766e; }
    .badge-chip.cancelled { background:#fee2e2; border-color:#fca5a5; color:#b91c1c; }
    .badge-chip.pending { background:#f1f5f9; border-color:#e2e8f0; color:#475569; }
    .action-bar { display:flex; flex-wrap:wrap; gap:10px; margin-top:22px; }
    .action-bar .btn { font-size:.95rem; letter-spacing:.7px; font-weight:700; padding:12px 22px; border-radius:14px; }
    .section-cluster { display:grid; gap:26px; }
    .cat-panel { background:#ffffff; border:1px solid #e0e7ff; border-radius:22px; padding:26px 26px 30px; box-shadow:0 6px 28px -10px rgba(31,41,55,.12); }
    .panel-title { margin:0 0 18px; font-size:.95rem; font-weight:800; letter-spacing:.9px; text-transform:uppercase; color:#1e293b; display:flex; align-items:center; gap:10px; }
    .fields-table { width:100%; border-collapse:separate; border-spacing:0 10px; }
    .fields-table tr { background:#f8fafc; box-shadow:0 2px 6px rgba(0,0,0,.04); }
    .fields-table td { padding:12px 18px; font-size:.95rem; vertical-align:top; }
    .fields-table td.label { width:200px; font-weight:700; letter-spacing:.6px; color:#1e293b; text-transform:uppercase; font-size:.9rem; }
    .fields-table td.value { font-weight:600; color:#0f172a; font-size:.95rem; }
    .fields-table tr:hover td { background:#eef2ff; }
    .address-box { background:#f1f5f9; border:1px dashed #cbd5e1; padding:18px 20px; border-radius:18px; font-size:.95rem; line-height:1.7; font-weight:600; color:#334155; }
    .alert-note { background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:16px 20px; border-radius:16px; font-size:.95rem; font-weight:700; }
    .inline-tag { padding:6px 12px; background:#eef2ff; color:#4338ca; border-radius:10px; font-size:.9rem; font-weight:700; letter-spacing:.5px; }
    @media (max-width:640px){ .fields-table td.label { width:120px; } }
    /* Mobile adjustments: avoid sticky and constrain photo height */
    @media (max-width: 768px) {
        .sidebar-card { position: static; top: auto; }
        .photo-large { aspect-ratio: 1 / 1; max-height: 280px; }
        .photo-large img { object-fit: contain; }
    }
</style>
@endpush

<div class="container-fluid py-4">
    <div class="profile-wrapper">
        <!-- Sidebar Profile Summary -->
        <aside class="sidebar-card">
            <div class="photo-large mb-3">
                <img src="{{ $application->photo ? asset('storage/admission/'.$application->photo) : asset('images/default-avatar.png') }}" alt="Photo">
            </div>
            <h2 class="h6 mb-1 fw-bold">{{ $application->name_bn ?? $application->name_en }}</h2>
            <div class="text-muted mb-3" style="font-size:.75rem;">Application ID: <span class="inline-tag">{{ $application->app_id }}</span></div>
            <div class="badge-stack">
                @if($application->payment_status==='Paid')
                    <span class="badge-chip paid">Paid</span>
                @else
                    <span class="badge-chip unpaid">Unpaid</span>
                @endif
                @if($application->accepted_at)
                    <span class="badge-chip accepted">Accepted</span>
                @elseif($application->status==='cancelled')
                    <span class="badge-chip cancelled">Cancelled</span>
                @else
                    <span class="badge-chip pending">Pending</span>
                @endif
                <span class="badge-chip">Class: {{ $application->class_name ?? '—' }}</span>
            </div>
            <div class="action-bar">
                <a href="{{ route('principal.institute.admissions.applications', $school->id) }}" class="btn btn-outline-secondary">তালিকা</a>
                @if($application->payment_status==='Paid')
                    <a href="{{ route('principal.institute.admissions.applications.copy', [$school->id, $application->id]) }}" target="_blank" class="btn btn-outline-info">কপি</a>
                @endif
                <a href="{{ route('principal.institute.admissions.applications.payments.details', [$school->id, $application->id]) }}" class="btn btn-outline-dark">পেমেন্ট</a>
                @if(!$application->accepted_at && $application->payment_status==='Paid' && $application->status!=='cancelled')
                    <form action="{{ route('principal.institute.admissions.applications.accept', [$school->id, $application->id]) }}" method="post" onsubmit="return confirm('গ্রহণ নিশ্চিত?')">
                        @csrf
                        <button class="btn btn-success">গ্রহণ</button>
                    </form>
                @endif
                @if($application->accepted_at)
                    <a href="{{ route('principal.institute.admissions.applications.admit_card', [$school->id, $application->id]) }}" class="btn btn-primary">অ্যাডমিট</a>
                @endif
            </div>
            @if($application->status==='cancelled')
                <div class="divider-soft"></div>
                <div class="alert-note">বাতিলের কারণ: {{ $application->cancellation_reason }}</div>
            @endif
        </aside>

        <!-- Main Category Sections -->
        <div class="section-cluster">
            <div class="cat-panel">
                <h3 class="panel-title">Basic Identity</h3>
                <table class="fields-table">
                    <tr><td class="label">নাম (EN)</td><td class="value">{{ $application->name_en }}</td></tr>
                    <tr><td class="label">নাম (BN)</td><td class="value">{{ $application->name_bn }}</td></tr>
                    <tr><td class="label">লিঙ্গ</td><td class="value">{{ $application->gender }}</td></tr>
                    <tr><td class="label">ধর্ম</td><td class="value">{{ $application->religion ?? '—' }}</td></tr>
                    <tr><td class="label">জন্ম তারিখ</td><td class="value">{{ \Carbon\Carbon::parse($application->dob)->format('d/m/Y') }}</td></tr>
                    <tr><td class="label">মোবাইল</td><td class="value">{{ $application->mobile }}</td></tr>
                </table>
            </div>
            <div class="cat-panel">
                <h3 class="panel-title">Family</h3>
                <table class="fields-table">
                    <tr><td class="label">পিতা (EN)</td><td class="value">{{ $application->father_name_en }}</td></tr>
                    <tr><td class="label">পিতা (BN)</td><td class="value">{{ $application->father_name_bn ?? '—' }}</td></tr>
                    <tr><td class="label">মাতা (EN)</td><td class="value">{{ $application->mother_name_en }}</td></tr>
                    <tr><td class="label">মাতা (BN)</td><td class="value">{{ $application->mother_name_bn ?? '—' }}</td></tr>
                    <tr><td class="label">অভিভাবক (EN)</td><td class="value">{{ $application->guardian_name_en ?? '—' }}</td></tr>
                    <tr><td class="label">অভিভাবক (BN)</td><td class="value">{{ $application->guardian_name_bn ?? '—' }}</td></tr>
                    <tr><td class="label">সম্পর্ক</td><td class="value">{{ $application->guardian_relation ?? '—' }}</td></tr>
                </table>
            </div>
            <div class="cat-panel">
                <h3 class="panel-title">Academic</h3>
                <table class="fields-table">
                    <tr><td class="label">আবেদিত শিক্ষাবর্ষ</td><td class="value">{{ $academicYear ? ($academicYear->title ?? $academicYear->name ?? '—') : '—' }}</td></tr>
                    <tr><td class="label">ক্লাস</td><td class="value">{{ $application->class_name ?? '—' }}</td></tr>
                    <tr><td class="label">পূর্ববর্তী স্কুল</td><td class="value">{{ $application->last_school ?? '—' }}</td></tr>
                    <tr><td class="label">ফলাফল</td><td class="value">{{ $application->result ?? '—' }}</td></tr>
                    <tr><td class="label">পাসের বছর</td><td class="value">{{ $application->pass_year ?? '—' }}</td></tr>
                </table>
            </div>
            <div class="cat-panel">
                <h3 class="panel-title">Payment & Status</h3>
                <table class="fields-table">
                    <tr><td class="label">পেমেন্ট</td><td class="value">{{ $application->payment_status }}</td></tr>
                    <tr><td class="label">স্ট্যাটাস</td><td class="value">{{ $application->status }} @if($application->accepted_at)<span class="text-success">(গৃহীত)</span>@endif</td></tr>
                    <tr><td class="label">Application ID</td><td class="value">{{ $application->app_id }}</td></tr>
                    @if($application->admission_roll_no)
                        <tr><td class="label">ভর্তি রোল</td><td class="value">{{ str_pad($application->admission_roll_no,4,'0',STR_PAD_LEFT) }}</td></tr>
                    @endif
                </table>
            </div>
            <div class="cat-panel">
                <h3 class="panel-title">Address (Present)</h3>
                <div class="address-box">
                    <div>গ্রামঃ {{ $application->present_village ? $application->present_village : '—' }}@if($application->present_para_moholla) ({{ $application->present_para_moholla }})@endif</div>
                    <div>ডাকঘরঃ {{ $application->present_post_office ?? '—' }}</div>
                    <div>উপজেলা {{ $application->present_upazilla ?? '—' }}</div>
                    <div>জেলাঃ {{ $application->present_district ?? '—' }}</div>
                </div>
            </div>
            <div class="cat-panel">
                <h3 class="panel-title">Address (Permanent)</h3>
                <div class="address-box">
                    <div>গ্রামঃ {{ $application->permanent_village ? $application->permanent_village : '—' }}@if($application->permanent_para_moholla) ({{ $application->permanent_para_moholla }})@endif</div>
                    <div>ডাকঘরঃ {{ $application->permanent_post_office ?? '—' }}</div>
                    <div>উপজেলা {{ $application->permanent_upazilla ?? '—' }}</div>
                    <div>জেলাঃ {{ $application->permanent_district ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
