@extends('layouts.admin')
@section('content')
@php
    $logo = $school->logo ? asset('storage/'.$school->logo) : null;
    $photo = $application->photo ? asset('storage/admission/'.$application->photo) : asset('images/default-avatar.png');
    $session = $application->academicYear->name ?? ($school->admissionAcademicYear->name ?? '');
    $roll = $application->admission_roll_no ? str_pad($application->admission_roll_no, 3, '0', STR_PAD_LEFT) : '—';
    $examDt = $application->exam_datetime ? \Carbon\Carbon::parse($application->exam_datetime)->format('d-m-Y h:i A') : '—';
@endphp

<style>
/* Screen + Print base (scoped to avoid leaking into layout) */
.admit-scope .admit-wrapper { display:flex; justify-content:center; padding:24px; }
.admit-scope .admit-card {
        position: relative;
        width: 210mm; /* A4 width */
        min-height: 297mm; /* A4 height */
        background: #fff;
        color: #111;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 10px 20px rgba(0,0,0,.08);
        overflow: hidden;
}
.admit-scope .admit-body { padding: 22mm 18mm; position: relative; z-index: 1; }
.admit-scope .admit-watermark { position:absolute; inset:0; opacity:0.06; background-position:center; background-repeat:no-repeat; background-size: 60% auto; }
.admit-scope .admit-header { display:flex; align-items:center; gap:16px; border-bottom:2px solid #111; padding-bottom:12px; }
.admit-scope .school-logo { width:72px; height:72px; object-fit:contain; }
.admit-scope .school-name { font-size: 22px; font-weight: 800; line-height:1.2; }
.admit-scope .school-meta { color:#4b5563; font-size: 12px; }
.admit-scope .title-row { display:flex; align-items:center; justify-content:space-between; margin-top:16px; margin-bottom:8px; }
.admit-scope .admit-title { font-size: 18px; font-weight: 700; letter-spacing:.5px; }
.admit-scope .ac-badge { background:#111; color:#fff; padding:6px 10px; border-radius:6px; font-size:12px; }
.admit-scope .ac-grid { display:grid; gap:10px; grid-template-columns: 1.4fr 1fr; }
.admit-scope .ac-left, .admit-scope .ac-right { background:#fafafa; border:1px solid #eee; border-radius:8px; padding:14px; }
.admit-scope .field { display:flex; gap:8px; font-size:13px; }
.admit-scope .label { width:140px; color:#374151; font-weight:600; }
.admit-scope .value { color:#111; }
.admit-scope .photo-box { width: 120px; height: 150px; border:1px dashed #cbd5e1; display:flex; align-items:center; justify-content:center; background:#fff; border-radius:6px; overflow:hidden; }
.admit-scope .photo-box img { width:100%; height:100%; object-fit:cover; }
.admit-scope .info-table { width:100%; border-collapse:separate; border-spacing:0 8px; font-size:13px; }
.admit-scope .info-table td:first-child { color:#374151; font-weight:600; width: 160px; }
.admit-scope .info-table td:last-child { color:#111; }
.admit-scope .sign-row { display:flex; justify-content:space-between; gap:24px; margin-top:32px; }
.admit-scope .sign { width:48%; text-align:center; }
.admit-scope .sign .line { margin-top:48px; border-top:1px solid #111; padding-top:6px; font-size:12px; }
.admit-scope .print-actions { margin-top:18px; text-align:center; }
.admit-scope .print-btn { background:#111; color:#fff; padding:10px 16px; border:none; border-radius:6px; cursor:pointer; }
.admit-scope .back-link { margin-left:10px; color:#2563eb; text-decoration:none; }
/* exam info block */
.admit-scope .exam-block { margin-top:14px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px; padding:12px 14px; }
.admit-scope .exam-block-title { font-weight:700; margin-bottom:6px; }
.admit-scope .exam-row { display:flex; gap:12px; font-size:13px; margin:6px 0; }
.admit-scope .exam-row .label { width:160px; }

/* Print */
@media print {
    @page { size: A4; margin: 10mm; }
    .admit-scope .no-print { display:none !important; }
    .admit-scope .admit-wrapper { padding:0; }
    .admit-scope .admit-card { box-shadow:none; border:none; }
}
</style>

<div class="admit-scope">
<div class="admit-wrapper">
    <div class="admit-card">
        @if($logo)
            <div class="admit-watermark" style="background-image:url('{{ $logo }}')"></div>
        @endif
        <div class="admit-body">
            <div class="admit-header">
                <div>
                    @if($logo)
                        <img src="{{ $logo }}" alt="{{ $school->name }}" class="school-logo">
                    @endif
                </div>
                <div>
                    <div class="school-name">{{ $school->name_bn ?: $school->name }}</div>
                    <div class="school-meta">
                        @if($school->address_bn || $school->address)
                            {{ $school->address_bn ?: $school->address }}
                        @endif
                        @if($school->phone) • ফোন: {{ $school->phone }} @endif
                        @if($school->email) • ইমেইল: {{ $school->email }} @endif
                    </div>
                </div>
            </div>

            <div class="title-row">
                <div class="admit-title">ভর্তি পরীক্ষার প্রবেশপত্র</div>
                <div class="ac-badge">শিক্ষাবর্ষ: {{ $session }}</div>
            </div>

            <div class="ac-grid" style="margin-top:12px;">
                <div class="ac-left">
                    <table class="info-table">
                        <tr><td>রোল নম্বর</td><td>: <strong style="font-size:16px">{{ $roll }}</strong></td></tr>
                        <tr><td>আবেদন আইডি</td><td>: {{ $application->app_id }}</td></tr>
                        <tr><td>নাম (বাংলা)</td><td>: {{ $application->name_bn }}</td></tr>
                        <tr><td>নাম (ইংরেজি)</td><td>: {{ $application->name_en }}</td></tr>
                        <tr><td>পিতা</td><td>: {{ $application->father_name_bn ?: $application->father_name_en }}</td></tr>
                        <tr><td>মাতা</td><td>: {{ $application->mother_name_bn ?: $application->mother_name_en }}</td></tr>
                        <tr><td>জন্ম তারিখ</td><td>: {{ optional($application->dob)->format('d-m-Y') }}</td></tr>
                        <tr><td>শ্রেণি</td><td>: {{ $application->class_name }}</td></tr>
                        <tr><td>লিঙ্গ</td><td>: {{ $application->gender }}</td></tr>
                    </table>
                </div>
                <div class="ac-right">
                    <div class="photo-box" style="margin-left:auto;">
                        <img src="{{ $photo }}" alt="Applicant Photo">
                    </div>
                </div>
            </div>

            <div class="exam-block">
                <div class="exam-block-title">পরীক্ষার তথ্য</div>
                <div class="exam-row"><div class="label">পরীক্ষার তারিখ-সময়</div><div class="value">: {{ $examDt }}</div></div>
                <div class="exam-row"><div class="label">পরীক্ষার কেন্দ্র</div><div class="value">: {{ $school->name_bn ?: $school->name }}</div></div>
                @if($school->address_bn || $school->address)
                    <div class="exam-row"><div class="label">ঠিকানা</div><div class="value">: {{ $school->address_bn ?: $school->address }}</div></div>
                @endif
            </div>

            <div style="margin-top:18px;">
                <div style="font-weight:700; margin-bottom:6px;">নির্দেশনা:</div>
                <ol style="padding-left:18px; line-height:1.6; font-size:12.5px; color:#374151;">
                    <li>এই প্রবেশপত্র এবং বৈধ কাগজপত্র সঙ্গে আনতে হবে। মোবাইল ফোন, স্মার্টওয়াচ ইত্যাদি নিষিদ্ধ।</li>
                    <li>পরীক্ষা শুরুর কমপক্ষে ৩০ মিনিট পূর্বে কেন্দ্রে উপস্থিত হোন।</li>
                    <li>প্রবেশপত্র ভাঁজ বা ক্ষতিগ্রস্ত করবেন না।</li>
                    <li>প্রশ্নপত্র/উত্তরপত্রে কোনো চিহ্ন/লিখন করবেন না যা পরিচয় প্রকাশ করে।</li>
                    <li>পরীক্ষা চলাকালীন হলে কোনো প্রকার ইলেকট্রনিক ডিভাইস ব্যবহার করা যাবে না।</li>
                </ol>
            </div>

            <div class="sign-row">
                <div class="sign">
                    <div class="line">প্রধান শিক্ষক/অধ্যক্ষের স্বাক্ষর</div>
                </div>
                <div class="sign">
                    <div class="line">পরীক্ষার্থীর স্বাক্ষর</div>
                </div>
            </div>

            <div class="print-actions no-print">
                <button class="print-btn" onclick="window.print()">প্রিন্ট করুন</button>
                <a class="back-link" href="{{ route('principal.institute.admissions.applications.show', [$school->id, $application->id]) }}">ফিরে যান</a>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
