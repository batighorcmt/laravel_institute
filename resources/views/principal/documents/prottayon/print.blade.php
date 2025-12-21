@extends('layouts.print')
@section('title','শিক্ষার্থী প্রত্যয়নপত্র')

@section('content')
<?php
  // Bangla digit converter
  if (!function_exists('bn_digits')) {
    function bn_digits($str) {
      $en = ['0','1','2','3','4','5','6','7','8','9'];
      $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
      return str_replace($en, $bn, (string)$str);
    }
  }
  // Bangla month names
  $months_bn = ['জানুয়ারি','ফেব্রুয়ারি','মার্চ','এপ্রিল','মে','জুন','জুলাই','আগস্ট','সেপ্টেম্বর','অক্টোবর','নভেম্বর','ডিসেম্বর'];
  // Format date to Bangla string
  if (!function_exists('format_bangla_datetime')) {
    function format_bangla_datetime($dt, $months_bn) {
      if (empty($dt)) return '';
      try { $ts = \Carbon\Carbon::parse($dt); } catch (\Exception $e) { return ''; }
      $day = bn_digits($ts->format('d'));
      $month = $months_bn[(int)$ts->format('n')-1] ?? '';
      $year = bn_digits($ts->format('Y'));
      return $day.' '.$month.' '.$year;
    }
  }
?>
<!DOCTYPE html>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'SolaimanLipi', 'Siyam Rupali', Arial, sans-serif; background: #f5f5f5; color: #000; line-height: 1.6; }
  .certificate-container { display: flex; flex-direction: column; max-width: 210mm; min-height: 297mm; margin: 10px auto; background: white; box-shadow: 0 0 20px rgba(0,0,0,0.1); position: relative; padding: 12mm 10mm 30mm 10mm; page-break-after: avoid; }
  .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px double #000; padding-bottom: 3px; margin-bottom: 12px; position: relative; z-index: 2; }
  .school-logo { display: flex; align-items: center; }
  .school-logo img { max-height: 60px; width: auto; vertical-align: middle; }
  .school-info { flex: 1; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; }
  .school-name { font-size: 28px; font-weight: bold; color: #006400; }
  .school-address { font-size: 16px; color: #333; }
  .school-contact { font-size: 14px; color: #666; }
  .certificate-title { font-size: 20px; font-weight: bold; text-align: center; margin: 18px 0 6px; color: #000; position: relative; z-index: 2; }
  .certificate-title .title-text { display: inline-block; padding-bottom: 6px; border-bottom: 2px solid #000; }
  .content { position: relative; z-index: 2; font-size: 15px; text-align: justify; }
  .student-info { margin: 12px 0; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 5px; }
  .info-row { display: flex; margin-bottom: 5px; padding: 2px 0; }
  .info-label { width: 200px; font-weight: bold; color: #333; }
  .info-value { flex: 1; color: #000; }
  .declaration { margin: 10px 0; font-size: 14px; line-height: 1.5; }
  .signature-area { margin-top: 30px; display: flex; justify-content: space-between; align-items: flex-end; }
  .signature-box { text-align: center; flex: 1; }
  .signature-line { width: 120px; height: 1px; background: #000; margin: 20px auto 8px; }
  .signature-name { font-weight: bold; margin-bottom: 3px; }
  .footer { text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd; padding-top: 6px; margin-top: auto; position: sticky; bottom: 0; background: #fff; }
  @media print {
    .certificate-container { box-shadow: none; margin: 0; padding: 10mm 8mm 30mm 8mm; page-break-after: avoid !important; page-break-inside: avoid !important; }
    .footer { position: fixed; left: 0; right: 0; bottom: 0; background: #fff; page-break-before: avoid !important; z-index: 999; }
    .print-button, .no-print { display: none !important; }
  }
  .print-button { text-align: center; margin: 20px auto; max-width: 210mm; }
  .btn-print { background: #006400; color: white; border: none; padding: 12px 30px; font-size: 16px; border-radius: 5px; cursor: pointer; font-family: 'SolaimanLipi', sans-serif; }
  .btn-print:hover { background: #004d00; }
  html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
</style>

  <div class="print-button no-print">
    <button class="btn-print" onclick="window.print()">🖨️ প্রত্যয়নপত্র প্রিন্ট করুন</button>
    @if(\Illuminate\Support\Facades\Route::has('principal.documents.prottayon.history'))
      <a href="{{ route('principal.documents.prottayon.history', [$school->id]) }}" style="margin-left: 15px; color: #006400;">← প্রত্যয়নপত্র তালিকায় ফিরে যান</a>
    @endif
  </div>

<div class="certificate-container">
  @php($logoPath = isset($school->logo) ? asset('storage/'.$school->logo) : null)
  @if(!empty($logoPath))
    <div style="position:absolute;left:0;top:0;width:100%;height:100%;z-index:0;display:flex;justify-content:center;align-items:center;pointer-events:none;">
      <img src="{{ $logoPath }}" alt="Watermark Logo" style="opacity:0.13;max-width:70%;max-height:80%;margin:auto;">
    </div>
  @endif

  <div class="header">
    <div class="school-logo">
      @if(!empty($logoPath))
        <img src="{{ $logoPath }}" alt="School Logo" style="vertical-align:middle; max-height:100px; width:auto;">
      @endif
    </div>
    <div class="school-info">
      <div class="school-name">{{ $school->name ?? 'আমাদের স্কুল' }}</div>
      <div class="school-address">{{ $school->address ?? '' }}</div>
      <div class="school-contact">মোবাইল: {{ bn_digits($school->phone ?? '০১XXXXXXXXX') }} | ইমেইল: {{ $school->email ?? 'school@example.com' }}</div>
    </div>
    <div style="display: flex; align-items: center;">
      <a href="{{ route('documents.verify', $document->code) }}" target="_blank" title="ভেরিফাই করুন">
        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(90)->generate(route('documents.verify', $document->code)) !!}
      </a>
    </div>
  </div>

  <div class="certificate-details" style="display:flex;justify-content:space-between;align-items:center;margin-top:2px;margin-bottom:6px;">
    <div class="certificate-id" style="font-weight:700;">স্মারক নং: <span id="certNumberPrint">{{ bn_digits($document->memo_no) }}</span></div>
    <div class="issue-date" style="font-weight:700;">তারিখ: <span id="certDatePrint">{{ format_bangla_datetime($document->issued_at, $months_bn) }}</span></div>
  </div>

  <div class="certificate-title"><span class="title-text">প্রত্যয়নপত্র</span></div>

  <div class="content">
    <div class="student-info" style="background:none !important;border:1px solid #ddd;border-radius:5px;">
      <div class="info-row"><div class="info-label">শিক্ষার্থীর নাম:</div><div class="info-value">{{ $student->full_name ?? ($student->first_name.' '.$student->last_name ?? '') }}</div></div>
      <div class="info-row"><div class="info-label">পিতার নাম:</div><div class="info-value">{{ $student->father_name ?? 'প্রদান করা হয়নি' }}</div></div>
      <div class="info-row"><div class="info-label">মাতার নাম:</div><div class="info-value">{{ $student->mother_name ?? 'প্রদান করা হয়নি' }}</div></div>
      <div class="info-row"><div class="info-label">ঠিকানা:</div><div class="info-value">{{ $student->present_address ?? 'প্রদান করা হয়নি' }}</div></div>
      <div class="info-row"><div class="info-label">শ্রেণি ও শাখা:</div>
        <div class="info-value">
          @php($className = $document->data['class_name'] ?? ($student->class_name ?? ''))
          @php($sectionName = $document->data['section_name'] ?? ($student->section_name ?? ''))
          {{ bn_digits($className ?: 'প্রদান করা হয়নি') }}@if(!empty($sectionName)) ({{ bn_digits($sectionName) }}) @endif
        </div>
      </div>
      <div class="info-row"><div class="info-label">রোল নম্বর:</div><div class="info-value">{{ bn_digits($document->data['roll_number'] ?? ($student->roll_number ?? 'প্রদান করা হয়নি')) }}</div></div>
      <div class="info-row"><div class="info-label">স্টুডেন্ট আইডি:</div><div class="info-value">{{ $student->student_id ?? '' }}</div></div>
      <div class="info-row"><div class="info-label">জন্ম তারিখ:</div>
        <div class="info-value">
          @php($dob = $student->date_of_birth ?? null)
          @if(!empty($dob)) {{ bn_digits(\Carbon\Carbon::parse($dob)->format('d/m/Y')) }} @else প্রদান করা হয়নি @endif
        </div>
      </div>
      <div class="info-row"><div class="info-label">লিঙ্গ:</div>
        <div class="info-value">
          @if(($student->gender ?? null) === 'male') পুরুষ
          @elseif(($student->gender ?? null) === 'female') মহিলা
          @else প্রদান করা হয়নি
          @endif
        </div>
      </div>
    </div>

    <div class="declaration">
      <p>এই মর্মে প্রত্যয়ন করা যাচ্ছে যে, <strong>{{ $student->full_name ?? ($student->first_name.' '.$student->last_name ?? '') }}</strong> বর্তমানে {{ $school->name ?? 'বিদ্যালয়' }} এর {{ $className }} শ্রেণির একজন নিয়মিত শিক্ষার্থী হিসেবে অধ্যয়নরত আছে।</p>
      <p style="margin-top: 8px;">সে একজন মেধাবী ও শৃংখলাবদ্ধ শিক্ষার্থী হিসেবে বিদ্যালয়ের সকলের নিকট পরিচিত। তার বিদ্যালয়ে উপস্থিতি ও আচরণ সন্তোষজনক। কোনো প্রকার শাস্তিমূলক ব্যবস্থার আওতাভুক্ত নয়।</p>
      <p style="margin-top: 8px;">সে বিদ্যালয়ের সকল নিয়ম-কানুন মেনে চলে এবং নিয়মিতভাবে ক্লাসে উপস্থিত থাকে। প্রয়োজনে যেকোনো সময় এই প্রত্যয়নপত্র যাচাই করা যাবে।</p>
    </div>

    <div style="height: 40px;"></div>
    <div class="signature-area">
      <div class="signature-box"><div class="signature-line"></div><div class="signature-name">শ্রেণি শিক্ষক</div></div>
      <div class="signature-box"><div class="signature-line"></div><div class="signature-name">প্রধান শিক্ষক/অধ্যক্ষ</div></div>
    </div>
  </div>

  <div class="footer" style="margin-top:8px;padding:8px;background:#e9f2ff;color:#000;font-size:0.95rem;text-align:center;">কারিগরি সহযোগীতায়ঃ <strong>বাতিঘর কম্পিউটার'স</strong>, মোবাইলঃ <span style="font-weight:700">01762-396713</span></div>
  </div>
@endsection
