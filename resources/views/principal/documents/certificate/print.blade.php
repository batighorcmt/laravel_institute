@extends('layouts.print')
@section('title','প্রশংসা পত্র')

@section('content')
<?php
  // Bangla number converter
  if (!function_exists('convert_to_bangla_number')) {
    function convert_to_bangla_number($str) {
      $en = ['0','1','2','3','4','5','6','7','8','9'];
      $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
      return str_replace($en, $bn, (string)$str);
    }
  }
  if (!function_exists('format_bangla_date_simple')) {
    function format_bangla_date_simple($date) {
      if (empty($date)) return '';
      try { $dt = \Carbon\Carbon::parse($date); } catch (\Exception $e) { return ''; }
      $months = ['01'=>'জানুয়ারি','02'=>'ফেব্রুয়ারি','03'=>'মার্চ','04'=>'এপ্রিল','05'=>'মে','06'=>'জুন','07'=>'জুলাই','08'=>'আগস্ট','09'=>'সেপ্টেম্বর','10'=>'অক্টোবর','11'=>'নভেম্বর','12'=>'ডিসেম্বর'];
      $d = convert_to_bangla_number($dt->format('d'));
      $m = $months[$dt->format('m')] ?? '';
      $y = convert_to_bangla_number($dt->format('Y'));
      return $d.' '.$m.', '.$y;
    }
  }
?>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap');
  @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&display=swap');
  * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Hind Siliguri', sans-serif; }
  .bangla-number { font-family: 'Noto Sans Bengali', sans-serif !important; }
  body { background-color: #f5f5f5; display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
  .certificate-container { width: 794px; height: 1123px; background-color: #fff; border: 15px double #1e5799; box-shadow: 0 5px 15px rgba(0,0,0,0.1); position: relative; margin-bottom: 20px; overflow: hidden; display: flex; flex-direction: column; }
  .certificate-watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 400px; height: 400px; opacity: 0.08; z-index: 0; pointer-events: none; user-select: none; }
  .certificate-header { display: flex; align-items: center; padding: 15px 30px; border-bottom: 2px solid #d4af37; margin-bottom: 10px; }
  .school-logo { width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; margin-right: 20px; }
  .school-logo img { max-width: 90px; max-height: 90px; }
  .school-info { flex: 1; }
  .school-name { font-size: 26px; font-weight: 700; color: #1e5799; margin-bottom: 5px; }
  .school-address { font-size: 16px; margin-bottom: 5px; }
  .contact-info { font-size: 14px; display: flex; gap: 20px; flex-wrap: wrap; }
  .certificate-details { display: flex; justify-content: space-between; padding: 5px 30px; font-size: 14px; border-bottom: 1px solid #ddd; margin-bottom: 10px; }
  .certificate-id { font-weight: 600; }
  .issue-date { font-weight: 600; }
  .certificate-body { padding: 0 40px; text-align: center; }
  .certificate-title { font-size: 32px; font-weight: 700; color: #1e5799; margin-bottom: 15px; text-decoration: underline; }
  .certificate-text { font-size: 18px; line-height: 1.6; margin-bottom: 15px; text-align: justify; }
  .student-details { display: flex; margin: 20px 0; gap: 30px; flex-wrap: wrap; }
  .student-info { flex: 1; text-align: left; min-width: 220px; }
  .student-photo { width: 150px; height: 180px; background-color: #f0f0f0; border: 1px solid #ddd; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #666; overflow: hidden; }
  .student-photo img { max-width: 140px; max-height: 170px; }
  .info-row { display: flex; margin-bottom: 8px; border-bottom: 1px dashed #ddd; padding-bottom: 6px; }
  .info-label { width: 160px; font-weight: 600; }
  .info-value { flex: 1; }
  .signature-section { display: flex; justify-content: space-between; margin-top: 30px; padding: 0 20px; flex-wrap: wrap; }
  .principal-signature { text-align: center; min-width: 220px; }
  .signature-line { width: 200px; height: 1px; background-color: #000; margin: 40px auto 10px; }
  .qr-section { text-align: center; min-width: 140px; }
  .qr-code { width: 120px; height: 120px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd; margin: 0 auto 5px; }
  .qr-caption { font-size: 12px; color: #666; }
  .certificate-footer { background-color: #f9f9f9; padding: 12px; text-align: center; font-size: 14px; color: #666; border-top: 1px solid #ddd; margin-top: 25px; position: relative; bottom: 0; left: 0; right: 0; }
  .technical-support { font-weight: bold; color: #1e5799; }
  .stamp { position: absolute; bottom: 140px; right: 80px; width: 100px; height: 100px; border: 2px solid #d4af37; border-radius: 50%; display: flex; align-items: center; justify-content: center; transform: rotate(-15deg); opacity: 0.9; background-color: rgba(255,255,255,0.8); }
  .stamp-text { font-size: 14px; font-weight: bold; color: #d4af37; text-align: center; }
  .print-btn { background-color: #1e5799; color: white; border: none; padding: 10px 20px; font-size: 16px; border-radius: 4px; cursor: pointer; margin-top: 20px; transition: background-color 0.3s; position: fixed; bottom: 20px; z-index: 1000; }
  .print-btn:hover { background-color: #154274; }
  @media print {
    body { background-color: white; padding: 0; margin: 0; display: block; height: 100%; }
    .certificate-container { box-shadow: none; margin: 0; border: 15px double #1e5799; width: 100%; height: 100%; position: relative; display: flex; flex-direction: column; }
    .certificate-footer { margin-top: auto !important; position: relative !important; bottom: 0 !important; left: 0 !important; right: 0 !important; }
    .print-btn { display: none; }
    .certificate-watermark { opacity: 0.12; }
    .student-details { flex-direction: row !important; gap: 30px !important; align-items: flex-start !important; page-break-inside: avoid !important; }
    .student-photo { align-self: flex-start !important; }
    .signature-section { flex-direction: row !important; justify-content: space-between !important; align-items: flex-start !important; gap: 0 !important; page-break-inside: avoid !important; }
    .principal-signature, .qr-section { min-width: 220px; max-width: 50%; }
    @page { size: A4; margin: 0.5in; }
  }
  html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
</style>

@php($logoPath = isset($school->logo) ? asset('storage/'.$school->logo) : null)
<div class="certificate-container">
  @if(!empty($logoPath))
    <img src="{{ $logoPath }}" alt="Watermark Logo" class="certificate-watermark" />
  @endif

  <div class="certificate-header">
    <div class="school-logo">
      @if(!empty($logoPath))
        <img src="{{ $logoPath }}" alt="বিদ্যালয় লোগো">
      @else
        <span style="font-size: 14px; font-weight: bold; color: #1e5799;">বিদ্যালয় লোগো</span>
      @endif
    </div>
    <div class="school-info">
      <h1 class="school-name">{{ $school->name ?? 'বিদ্যালয়' }}</h1>
      <p class="school-address">{{ $school->address ?? '' }}</p>
      <div class="contact-info"><span>মোবাইল: {{ $school->phone ?? '' }}</span><span>ই-মেইল: {{ $school->email ?? '' }}</span></div>
    </div>
  </div>

  <div class="certificate-details">
    <div class="certificate-id">সার্টিফিকেট আইডি: {{ $document->memo_no }}</div>
    <div class="issue-date">ইস্যুর তারিখ: {!! format_bangla_date_simple($document->issued_at) !!}</div>
  </div>

  <div class="certificate-body">
    <h2 class="certificate-title">প্রশংসা পত্র</h2>
    <p class="certificate-text">এই মর্মে প্রত্যয়ন করা যাচ্ছে যে, নিম্নলিখিত শিক্ষার্থী আমাদের বিদ্যালয়ের {{ $document->data['class_name'] ?? 'পঞ্চম' }} শ্রেণিতে অধ্যয়ন করেছে এবং {{ convert_to_bangla_number($document->data['exam_year'] ?? ($document->data['year'] ?? '')) }} শিক্ষাবর্ষের বার্ষিক পরীক্ষায় সাফল্যের সাথে উত্তীর্ণ হয়েছে। শিক্ষার্থী বিদ্যালয়ের নিয়ম-কানুন মেনে চলেছে এবং তার আচরণ সন্তোষজনক ছিল।</p>

    <div class="student-details">
      <div class="student-info">
        <div class="info-row"><div class="info-label">শিক্ষার্থীর নাম:</div><div class="info-value">{{ $student->full_name ?? ($student->first_name.' '.$student->last_name ?? '') }}</div></div>
        <div class="info-row"><div class="info-label">পিতার নাম:</div><div class="info-value">{{ $student->father_name ?? '' }}</div></div>
        <div class="info-row"><div class="info-label">মাতার নাম:</div><div class="info-value">{{ $student->mother_name ?? '' }}</div></div>
        <div class="info-row"><div class="info-label">জন্ম তারিখ:</div><div class="info-value">{{ format_bangla_date_simple($student->date_of_birth ?? null) }}</div></div>
        <div class="info-row"><div class="info-label">শ্রেণি:</div><div class="info-value">{{ $document->data['class_name'] ?? 'পঞ্চম' }}</div></div>
        <div class="info-row"><div class="info-label">রোল নং:</div><div class="info-value">{{ convert_to_bangla_number($document->data['roll_number'] ?? ($student->roll_number ?? '')) }}</div></div>
        <div class="info-row"><div class="info-label">শিক্ষার্থী আইডি:</div><div class="info-value">{{ $student->student_id ?? '' }}</div></div>
        <div class="info-row"><div class="info-label">পরীক্ষার বছর:</div><div class="info-value">{{ convert_to_bangla_number($document->data['exam_year'] ?? ($document->data['year'] ?? '')) }}</div></div>
        <div class="info-row"><div class="info-label">ফলাফল:</div><div class="info-value">{{ convert_to_bangla_number($document->data['gpa'] ?? '') }}</div></div>
      </div>
      <div class="student-photo">
        @php($photoPath = isset($student->photo) ? asset('storage/students/'.$student->photo) : null)
        @if(!empty($photoPath))
          <img src="{{ $photoPath }}" alt="ছবি">
        @else
          শিক্ষার্থীর ছবি
        @endif
      </div>
    </div>

    <p class="certificate-text">শিক্ষার্থীকে ভবিষ্যত জীবনে সাফল্য ও সমৃদ্ধি কামনা করা হল। এই সার্টিফিকেটটি তার শিক্ষাগত যোগ্যতার প্রমাণ হিসেবে গণ্য হবে।</p>

    <div class="signature-section">
      <div class="principal-signature">
        <div class="signature-line"></div>
        <p>{{ $school->principal_name ?? 'প্রধান শিক্ষক' }}</p>
        <p>{{ $school->principal_designation ?? 'প্রধান শিক্ষক' }}</p>
        <p>{{ $school->name ?? '' }}</p>
      </div>
      <div class="qr-section">
        <div class="qr-code">{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(100)->generate(route('documents.verify', $document->code)) !!}</div>
        <p class="qr-caption">যাচাই করতে স্ক্যান করুন</p>
      </div>
    </div>
  </div>

  <div class="certificate-footer"><p>কারিগরি সহযোগীতায়ঃ <span class="technical-support">বাতিঘর কম্পিউটার’স</span>, মোবাইলঃ 01762-396713</p></div>
  </div>

  <button class="print-btn" onclick="window.print()">সার্টিফিকেট প্রিন্ট করুন</button>
@endsection
