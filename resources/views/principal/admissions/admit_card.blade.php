@extends('layouts.admin')
@section('content')
@php
    $logo = $school->logo ? asset('storage/'.$school->logo) : null;
    $photo = $application->photo ? asset('storage/admission/'.$application->photo) : asset('images/default-avatar.png');
    $session = $application->academicYear->name ?? ($school->admissionAcademicYear->name ?? '');
    // Display roll: prefer class-prefixed 4 digits (classNumber + 3-digit sequence)
    $rawSeq = (int)($application->admission_roll_no ?? 0);
    $classRaw = (string)($application->class_name ?? '');
    $classNum = (int)preg_replace('/[^0-9]/','', $classRaw);
    if ($rawSeq > 0 && $classNum > 0) {
        $calc = ($classNum * 1000) + max(min($rawSeq, 999), 1);
        $roll = str_pad((string)$calc, 4, '0', STR_PAD_LEFT);
    } else {
        $roll = $application->admission_roll_no ? str_pad($application->admission_roll_no, 3, '0', STR_PAD_LEFT) : '—';
    }
    // Prefer settings-provided exam datetime
    $examFromSettings = isset($examDatetime) && $examDatetime ? \Carbon\Carbon::parse($examDatetime)->format('d-m-Y h:i A') : null;
    $examDt = $examFromSettings ?: ($application->exam_datetime ? \Carbon\Carbon::parse($application->exam_datetime)->format('d-m-Y h:i A') : '—');
    $gender = strtolower((string)($application->gender ?? ''));
    $genderBn = $gender === 'male' ? 'ছেলে' : ($gender === 'female' ? 'মেয়ে' : ($application->gender ?? ''));
    // QR text in English (offline server-side QR)
    $qrText = 'Admission Admit Card | Name: '.($application->name_en ?: $application->name_bn).
              ' | Class: '.($application->class_name ?: '').
              ' | Roll No: '.$roll.
              ' | Year: '.$session;
    // Bengali digits mapping
    $bnDigits = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];
    $sessionBn = strtr((string)$session, $bnDigits);
    $examDtBn = strtr((string)$examDt, $bnDigits);
    $rollBn = strtr((string)$roll, $bnDigits);
    // Class label mapping
    $classLabelBn = $classRaw;
    if ($classNum === 6) $classLabelBn = 'ষষ্ঠ';
    elseif ($classNum === 7) $classLabelBn = 'সপ্তম';
    elseif ($classNum === 8) $classLabelBn = 'অষ্টম';
    elseif ($classNum === 9) $classLabelBn = 'নবম';
    elseif ($classNum === 10) $classLabelBn = 'দশম';
    // Venues array for optional selection (screen-only)
    $venuesArr = (isset($venues) && is_array($venues)) ? $venues : [];
    // Principal (institution head) info from role assignment
    $principalRole = \App\Models\UserSchoolRole::active()->forSchool($school->id)->withRole(\App\Models\Role::PRINCIPAL)->with(['user'])->first();
    $principalUser = $principalRole?->user;
    $principalPhoto = $principalUser && $principalUser->photo ? asset('storage/'.ltrim($principalUser->photo,'/')) : null;
    $principalSignature = $principalUser && ($principalUser->signature ?? null) ? asset('storage/'.ltrim($principalUser->signature,'/')) : null;
    $principalDesignation = $principalUser && (!empty($principalUser->designation) ? $principalUser->designation : null) ?: ($principalRole?->designation ?: 'প্রতিষ্ঠান প্রধান');
@endphp

<style>
html, body {
    background: #fff !important;
}
/* hide any global footers on this page */
body footer, body .footer, body .main-footer, body .app-footer { display:none !important; }
.content-wrapper, .content, .main-content, .page-content, .container, .container-fluid { background:#fff !important; }
.admit-scope .admit-wrapper { display:flex; flex-direction:column; justify-content:flex-start; padding:24px; background: none !important; min-height:calc(297mm - 20mm); }
.admit-scope .admit-card {
    position: relative;
    width: 210mm;
    min-height: calc(297mm - 20mm); /* page height minus @page margins */
    display: flex;
    flex-direction: column;
    background: #fff;
    color: #111;
    border: 2px solid #bbb;
    border-radius: 8px;
    box-shadow: none;
    font-size: 22px;
}
.admit-scope .admit-body { flex: 1 1 auto; padding: 22mm 18mm 10mm 18mm; position: relative; z-index: 1; }
.admit-scope .admit-watermark { position:absolute; inset:0; opacity:0.06; background-position:center; background-repeat:no-repeat; background-size: 60% auto; }
.admit-scope .admit-header { display:flex; align-items:center; gap:16px; border-bottom:2px solid #111; padding-bottom:12px; }
.admit-scope .school-logo { width:82px; height:82px; object-fit:contain; }
.admit-scope .school-name { font-size: 32px; font-weight: 800; line-height:1.2; }
.admit-scope .school-meta { color:#4b5563; font-size: 18px; }
.admit-scope .title-row { display:flex; align-items:center; justify-content:space-between; margin-top:16px; margin-bottom:8px; }
.admit-scope .admit-title { font-size: 28px; font-weight: 700; letter-spacing:.5px; }
.admit-scope .ac-badge { background:#111; color:#fff; padding:9px 16px; border-radius:6px; font-size:18px; }
.admit-scope .class-badge { background:#2563eb; color:#fff; padding:9px 16px; border-radius:6px; font-size:18px; margin-right:8px; }
.admit-scope .ac-grid { display:grid; gap:10px; grid-template-columns: 2fr 180px; }
.admit-scope .ac-left { background:none; border:1.5px solid #bbb; border-radius:8px; padding:18px; }
.admit-scope .ac-right { background:none; border:none; display:flex; align-items:flex-start; justify-content:center; padding:0; }
.admit-scope .field { display:flex; gap:8px; font-size:20px; }
.admit-scope .label { width:200px; color:#111; font-weight:600; }
.admit-scope .value { color:#111; }
.admit-scope .photo-box { display:none; }
.admit-scope .ac-right img { width: 150px; height: 200px; object-fit: cover; border-radius: 8px; border: 1.5px solid #bbb; background: #fff; }
.admit-scope .info-table { width:100%; border-collapse:separate; border-spacing:0 12px; font-size:20px; }
.admit-scope .info-table td:first-child { color:#111; font-weight:600; width: 180px; }
.admit-scope .info-table td:last-child { color:#111; }
.admit-scope .roll-digits { display:inline-flex; gap:0; vertical-align:middle; margin-left:8px; }
.admit-scope .roll-digit { border: 2px solid #bbb; color:#111; font-size: 30px; font-weight:700; width: 42px; height: 48px; line-height: 48px; text-align:center; background:none; }
.admit-scope .sign-row { display:flex; justify-content:flex-end; gap:24px; margin-top:12px; }
.admit-scope .sign { width:300px; text-align:center; margin-left:auto; }
.admit-scope .sign .line { margin-top:6px; border-top:1.5px solid #111; padding-top:6px; font-size:18px; }
.admit-scope .signature-img { max-height:80px; width:auto; display:block; margin:0 auto 2px auto; border:1px solid #bbb; padding:2px; background:#fff; border-radius:4px; }
.admit-scope .print-actions { position: absolute; top: 18px; right: 32px; z-index: 10; }
.admit-scope .print-btn { background:#111; color:#fff; padding:12px 20px; border:none; border-radius:6px; cursor:pointer; font-size:18px; }
.admit-scope .back-link { margin-left:10px; color:#2563eb; text-decoration:none; font-size:18px; }
.admit-scope .exam-block { margin-top:14px; background:none; border:1.5px solid #bbb; border-radius:8px; padding:16px 18px; }
.admit-scope .exam-block-title { font-weight:700; margin-bottom:10px; font-size:22px; }
.admit-scope .exam-row { display:flex; gap:12px; font-size:20px; margin:10px 0; align-items:flex-start; }
.admit-scope .exam-row .label { width:180px; }
.admit-scope .admit-footer {
    margin-top:auto;
    padding:4px 0 0;
    text-align:center;
    font-size:14px;
    color:#222;
    border-top:1px solid #111;
    letter-spacing:.5px;
}
.admit-scope .venue-select { font-size:16px; padding:6px 8px; border:1px solid #bbb; border-radius:6px; }
@media print {
    @page { size: A4; margin: 0.5in; }
    .admit-scope .no-print { display:none !important; }
    .admit-scope .admit-wrapper { padding:0; min-height:auto; width:100%; }
    .admit-scope .admit-card { width:100%; min-height: calc(297mm - 1in); box-shadow:none; border:2px solid #bbb; border-radius: 8px; display:flex; flex-direction:column; }
    /* Slightly tighter top padding to balance enlarged font */
    .admit-scope .admit-body { padding: 12mm 11mm 10mm 11mm; }
    .admit-scope .print-actions { display:none !important; }
    /* Footer stays at bottom via flex auto margin */
    .admit-scope .admit-footer { position: relative; bottom: 0; margin-top:auto; font-weight: 600; font-size:15px; }
    /* Widen first column to reduce wrapping in print */
    .admit-scope .info-table td:first-child { width:200px; }
    .admit-scope .exam-row .label { width:200px; }
    /* Ensure logo prints clearly */
    .admit-scope .school-logo { width:78px; height:78px; }
    /* Prevent page breaks inside card */
    .admit-scope .admit-card, .admit-scope .admit-body { page-break-inside: avoid; }
}
</style>

<div class="admit-scope">
<div class="admit-wrapper">
    <div class="admit-card">
        @if($logo)
            <div class="admit-watermark" style="background-image:url('{{ $logo }}')"></div>
        @endif
        <div class="admit-body">
            <div class="print-actions no-print">
                <button class="print-btn" onclick="window.print()">প্রিন্ট করুন</button>
                <a class="back-link" href="{{ route('principal.institute.admissions.applications.show', [$school->id, $application->id]) }}">ফিরে যান</a>
                @if(!empty($venuesArr) && count($venuesArr) > 1)
                    <label style="margin-left:12px; font-size:16px;">ভেন্যু নির্ধারণ করুন:</label>
                    <select id="venueSelect" class="venue-select">
                        @foreach($venuesArr as $idx => $vn)
                            <option value="{{ $idx }}">{{ $vn['name'] ?? '' }}@if(!empty($vn['address'])) — {{ $vn['address'] }}@endif</option>
                        @endforeach
                    </select>
                @endif
            </div>
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
                    </br>
                        @if($school->phone) ফোন: {{ $school->phone }} @endif
                        @if($school->email) • ইমেইল: {{ $school->email }} @endif
                    </div>
                </div>
            </div>
            <div class="title-row">
                <div class="admit-title">ভর্তি পরীক্ষার প্রবেশপত্র</div>
                <div>
                    <span class="class-badge">ভর্তিচ্ছুক শ্রেণিঃ {{ $classLabelBn }}</span>
                    <span class="ac-badge">শিক্ষাবর্ষঃ {{ $sessionBn }}</span>
                </div>
            </div>

            <div class="ac-grid" style="margin-top:12px;">
                <div class="ac-left">
                    <table class="info-table">
                        <tr>
                            <td>রোল নম্বর</td>
                            <td>:
                                <span class="roll-digits">
                                    @foreach(mb_str_split($rollBn) as $digit)
                                        <span class="roll-digit">{{ $digit }}</span>
                                    @endforeach
                                </span>
                            </td>
                        </tr>
                        <tr><td>আবেদন আইডি</td><td>: {{ $application->app_id }}</td></tr>
                        <tr><td>নাম (বাংলা)</td><td>: {{ $application->name_bn }}</td></tr>
                        <tr><td>নাম (ইংরেজি)</td><td>: {{ $application->name_en }}</td></tr>
                        <tr><td>পিতা</td><td>: {{ $application->father_name_bn ?: $application->father_name_en }}</td></tr>
                        <tr><td>মাতা</td><td>: {{ $application->mother_name_bn ?: $application->mother_name_en }}</td></tr>
                        <tr><td>জন্ম তারিখ</td><td>: {{ optional($application->dob)->format('d-m-Y') }}</td></tr>
                        <tr><td>লিঙ্গ</td><td>: {{ $genderBn }}</td></tr>
                    </table>
                </div>
                <div class="ac-right">
                    <div>
                        <img src="{{ $photo }}" alt="Applicant Photo">
                        <div style="margin-top:12px; display:flex; justify-content:center;">
                            <div style="width:150px; height:150px; border:2px solid #000; display:flex; align-items:center; justify-content:center; padding:6px; box-sizing:border-box;">
                                @if(app()->bound('qrcode'))
                                    {!! app('qrcode')->size(140)->generate($qrText) !!}
                                @else
                                    <div style="width:140px; height:140px;"></div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="exam-block">
                <div class="exam-block-title">পরীক্ষার তথ্য</div>
                <div class="exam-row"><div class="label">পরীক্ষার তারিখ-সময়</div><div class="value">: {{ $examDtBn }}</div></div>
                @if(!empty($venuesArr))
                    @php($firstV = $venuesArr[0])
                    <div class="exam-row">
                        <div class="label">পরীক্ষার কেন্দ্র/ভেন্যু</div>
                        <div class="value">:
                            <strong id="venueName">{{ $firstV['name'] ?? '' }}</strong>
                            @if(!empty($firstV['address'])) — <span id="venueAddr">{{ $firstV['address'] }}</span>@else <span id="venueAddr"></span>@endif
                        </div>
                    </div>
                @else
                    <div class="exam-row"><div class="label">পরীক্ষার কেন্দ্র</div><div class="value">: {{ $school->name_bn ?: $school->name }}</div></div>
                    @if($school->address_bn || $school->address)
                        <div class="exam-row"><div class="label">ঠিকানা</div><div class="value">: {{ $school->address_bn ?: $school->address }}</div></div>
                    @endif
                @endif
            </div>

            <div style="margin-top:18px;">
                <div style="font-weight:700; margin-bottom:6px;">নির্দেশনা:</div>
                <ol style="padding-left:18px; line-height:1.6; font-size:18px; color:#374151;">
                    <li>এই প্রবেশপত্র এবং বৈধ কাগজপত্র সঙ্গে আনতে হবে। মোবাইল ফোন, স্মার্টওয়াচ ইত্যাদি নিষিদ্ধ।</li>
                    <li>পরীক্ষা শুরুর কমপক্ষে ৩০ মিনিট পূর্বে কেন্দ্রে উপস্থিত হোন।</li>
                    <li>প্রবেশপত্র ভাঁজ বা ক্ষতিগ্রস্ত করবেন না।</li>
                    <li>প্রশ্নপত্র/উত্তরপত্রে কোনো চিহ্ন/লিখন করবেন না যা পরিচয় প্রকাশ করে।</li>
                    <li>পরীক্ষা চলাকালীন হলে কোনো প্রকার ইলেকট্রনিক ডিভাইস ব্যবহার করা যাবে না।</li>
                </ol>
            </div>

            <div class="sign-row">
                <div class="sign">
                    @if(!empty($principalSignature))
                        <img class="signature-img" src="{{ $principalSignature }}" alt="Principal Signature" style="width:300px;height:80px;object-fit:contain;border:none;padding:0;margin-bottom:2px;" />
                    @elseif($principalPhoto)
                        <img class="signature-img" src="{{ $principalPhoto }}" alt="Principal Photo" style="width:300px;height:80px;object-fit:cover;padding:2px;" />
                    @endif
                    <div class="line">{{ $principalDesignation }}</div>
                </div>
            </div>
            <div style="height:100px; width:100%;"></div>
            <div class="admit-footer">কারিগরী সহযোগিতায়ঃ বাতিঘর কম্পিউটার’স - ০১৭৬২৩৯৬৭১৩</div>
        </div>
    </div>
</div>
    
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var venues = @json($venuesArr);
    var select = document.getElementById('venueSelect');
    if (select && Array.isArray(venues) && venues.length > 1) {
        var nameEl = document.getElementById('venueName');
        var addrEl = document.getElementById('venueAddr');
        select.addEventListener('change', function(){
            var idx = parseInt(this.value, 10);
            var v = venues[idx] || {};
            if (nameEl) nameEl.textContent = v.name || '';
            if (addrEl) addrEl.textContent = v.address || '';
        });
    }
});
</script>
@endsection
