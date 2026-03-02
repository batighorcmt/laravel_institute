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
  $layout = $document->data['layout'] ?? 'standard';
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>প্রত্যয়নপত্র - {{ $student->student_name_bn }}</title>
<style>
  @page { size: A4; margin: 0; }
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'SolaimanLipi', 'Siyam Rupali', Arial, sans-serif; background: #f5f5f5; color: #000; line-height: 1.6; }
  
  .certificate-container { 
    width: 210mm; 
    min-height: 297mm; 
    margin: 10px auto; 
    background: white; 
    box-shadow: 0 0 20px rgba(0,0,0,0.1); 
    position: relative; 
    padding: 15mm 15mm 20mm 15mm; 
    display: flex;
    flex-direction: column;
  }

  /* Watermark */
  .watermark {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    opacity: 0.1;
    width: 70%;
    z-index: 0;
    pointer-events: none;
  }

  .content-wrapper { position: relative; z-index: 2; flex-grow: 1; }

  /* PAD LAYOUT SPECIFIC (Based on Image) */
  .pad-header {
    display: flex;
    align-items: center;
    border-bottom: 2px double #008000;
    padding-bottom: 15px;
    margin-bottom: 10px;
    color: #008000;
  }
  .pad-logo { width: 100px; height: 100px; margin-right: 15px; }
  .pad-logo img { width: 100%; height: auto; }
  .pad-school-info { flex: 1; text-align: center; }
  .pad-office-title { font-size: 20px; margin-bottom: 5px; font-weight: bold; }
  .pad-school-name { font-size: 36px; font-weight: bold; line-height: 1; margin-bottom: 10px; }
  .pad-address { font-size: 16px; color: #333; }
  .pad-established { font-size: 16px; margin-top: 5px; font-weight: bold; }
  .pad-contacts { text-align: right; font-size: 13px; min-width: 200px; line-height: 1.4; color: #008000; }

  .pad-details-row {
    display: flex;
    justify-content: space-between;
    margin-top: 5px;
    font-weight: bold;
    font-size: 16px;
    color: #008000;
  }

  .pad-title {
    text-align: center;
    font-size: 24px;
    font-weight: bold;
    margin: 40px 0 20px;
    text-decoration: underline;
  }

  .pad-body {
    font-size: 18px;
    text-align: justify;
    line-height: 1.8;
    margin-top: 30px;
  }

  .pad-signature-area {
    margin-top: 80px;
    display: flex;
    justify-content: flex-end;
  }
  .pad-signature-box {
    text-align: center;
    min-width: 150px;
  }
  .pad-signature-name { font-weight: bold; font-size: 18px; margin-top: 5px; }

  .pad-footer {
    border-top: 2px solid #008000;
    padding-top: 5px;
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    color: #008000;
    font-weight: bold;
    margin-top: auto;
  }

  /* STANDARD LAYOUT (Original) */
  .standard-header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
  .standard-school-info { text-align: center; flex: 1; }
  .student-data-table { width: 100%; border-collapse: collapse; margin: 20px 0; border: 1px solid #ddd; }
  .student-data-table td { padding: 8px; border: 1px solid #ddd; }
  .info-label { width: 180px; font-weight: bold; background: #f9f9f9; }

  @media print {
    body { background: none; }
    .certificate-container { margin: 0; box-shadow: none; }
    .no-print { display: none !important; }
  }

  .no-print { text-align: center; margin: 20px; }
  .btn-print { background: #008000; color: white; padding: 10px 25px; border: none; border-radius: 5px; cursor: pointer; font-family: inherit; font-size: 16px; }
</style>
</head>
<body>

  <div class="no-print">
    <button class="btn-print" onclick="window.print()">🖨️ প্রিন্ট করুন</button>
    <a href="{{ route('principal.institute.documents.prottayon.history', $school) }}" class="btn btn-link">তালিকায় ফিরে যান</a>
  </div>

  <div class="certificate-container">
    @php($logoUrl = $school->logo ? asset('storage/'.$school->logo) : asset('images/logo.png'))
    <img src="{{ $logoUrl }}" class="watermark" alt="Watermark">

    <div class="content-wrapper">
      
      @if($layout === 'pad')
        {{-- PAD LAYOUT --}}
        <div class="pad-header">
            <div class="pad-logo">
                <img src="{{ $logoUrl }}" alt="Logo">
            </div>
            <div class="pad-school-info">
                <div class="pad-office-title">প্রধান শিক্ষকের কার্যালয়</div>
                <div class="pad-school-name">{{ $school->name ?: 'জোড়পুকুরিয়া মাধ্যমিক বিদ্যালয়' }}</div>
                <div class="pad-address">{{ $school->address ?: 'ডাকঘর : জোড়পুকুরিয়া, উপজেলা : গাংনী, জেলা : মেহেরপুর।' }}</div>
                <div class="pad-established">স্থাপিত-১৯৬৭ইং।</div>
            </div>
            <div class="pad-contacts">
                মোবাইলঃ ০১৭১৩-৯১১৩৭৬<br>
                মোবাইলঃ ০১৩০৯-১১৮২১৩<br><br>
                এমপিও কোডঃ ৬৬০১02১৩0১<br>
                স্কুল কোডঃ ৫৬৭৭৮<br>
                ই.আই.আই.এনঃ ১১৮২১৩
            </div>
        </div>

        <div class="pad-details-row">
            <div>স্মারক নং : {{ bn_digits($document->memo_no) }}</div>
            <div>তারিখঃ {{ format_bangla_datetime($document->issued_at, $months_bn) }}</div>
        </div>

        <div class="pad-title">প্রত্যয়ন পত্র</div>

        <div class="pad-body">
            {!! nl2br(e($document->data['custom_content'] ?? '')) !!}
        </div>

        <div class="pad-signature-area">
            <div class="pad-signature-box">
                <div class="pad-signature-name">প্রধান শিক্ষক</div>
            </div>
        </div>
      @else
        {{-- STANDARD LAYOUT --}}
        <div class="standard-header">
            <img src="{{ $logoUrl }}" style="height:80px;" alt="Logo">
            <div class="standard-school-info">
                <h1 style="font-size: 28px;">{{ $school->name }}</h1>
                <p>{{ $school->address }}</p>
                <p>মোবাইল: {{ $school->phone }} | ইমেইল: {{ $school->email }}</p>
            </div>
            <div style="width: 80px;">
                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(80)->generate(route('documents.verify', $document->code)) !!}
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; margin-bottom: 20px; font-weight: bold;">
            <div>স্মারক নং: {{ $document->memo_no }}</div>
            <div>তারিখ: {{ $document->issued_at->format('d/m/Y') }}</div>
        </div>

        <h2 style="text-align:center; margin-bottom: 20px; text-decoration: underline;">প্রত্যয়নপত্র</h2>

        @if(empty($document->data['custom_content']) || !($document->data['is_final'] ?? false))
             <table class="student-data-table">
                <tr><td class="info-label">শিক্ষার্থীর নাম:</td><td>{{ $student->student_name_bn ?: $student->name }}</td></tr>
                <tr><td class="info-label">পিতার নাম:</td><td>{{ $student->father_name_bn ?: $student->father_name }}</td></tr>
                <tr><td class="info-label">মাতার নাম:</td><td>{{ $student->mother_name_bn ?: $student->mother_name }}</td></tr>
                <tr><td class="info-label">শ্রেণি:</td><td>{{ $student->class->name ?? '' }}</td></tr>
                <tr><td class="info-label">রোল নম্বর:</td><td>{{ $student->roll }}</td></tr>
                <tr><td class="info-label">আইডি নং:</td><td>{{ $student->student_id }}</td></tr>
                <tr><td class="info-label">বর্তমান ঠিকানা:</td><td>{{ $student->present_village }}, {{ $student->present_post_office }}</td></tr>
             </table>
        @endif

        <div style="font-size: 16px; text-align: justify; line-height: 1.8;">
            {!! nl2br(e($document->data['custom_content'] ?? '')) !!}
        </div>

        <div style="margin-top: 50px; display: flex; justify-content: space-between;">
            <div style="text-align:center; width: 150px;"><div style="border-top: 1px solid #000; padding-top: 5px;">শ্রেণি শিক্ষক</div></div>
            <div style="text-align:center; width: 150px;"><div style="border-top: 1px solid #000; padding-top: 5px;">প্রধান শিক্ষক</div></div>
        </div>
      @endif

    </div>

    @if($layout === 'pad')
    <div class="pad-footer">
        <div>E-mail: {{ $school->email ?: 'jorepukuria1967@gmail.com' }}</div>
        <div>Website: {{ $school->website ?: 'http://jorepukuriasecodaryschool.jessoreboard.gov.bd' }}</div>
    </div>
    @endif
  </div>

</body>
</html>
