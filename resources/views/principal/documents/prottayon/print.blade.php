<?php
  // Determine language from template
  $lang = $lang ?? 'bn';
  $isEn = $lang === 'en';

  // Bangla digit converter
  if (!function_exists('bn_digits')) {
    function bn_digits($str) {
      $en = ['0','1','2','3','4','5','6','7','8','9'];
      $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
      return str_replace($en, $bn, (string)$str);
    }
  }
  // Date formatter
  $months_bn = ['জানুয়ারি','ফেব্রুয়ারি','মার্চ','এপ্রিল','মে','জুন','জুলাই','আগস্ট','সেপ্টেম্বর','অক্টোবর','নভেম্বর','ডিসেম্বর'];
  $months_en = ['January','February','March','April','May','June','July','August','September','October','November','December'];

  if (!function_exists('format_date_localized')) {
    function format_date_localized($dt, $months, $isEn) {
      if (empty($dt)) return '';
      try { $ts = \Carbon\Carbon::parse($dt); } catch (\Exception $e) { return ''; }
      $day   = $isEn ? $ts->format('d') : bn_digits($ts->format('d'));
      $month = $months[(int)$ts->format('n') - 1] ?? '';
      $year  = $isEn ? $ts->format('Y') : bn_digits($ts->format('Y'));
      return $day . ' ' . $month . ' ' . $year;
    }
  }

  $layout = $document->data['layout'] ?? 'standard';

  // Language strings
  $lbl = $isEn ? [
    'title'         => 'To Whom It May Concern',
    'memo'          => 'Memo No',
    'date'          => 'Date',
    'headmaster'    => 'Headmaster',
    'class_teacher' => 'Class Teacher',
    'print_btn'     => '🖨️ Print',
    'back'          => 'Back to List',
    'office'        => 'Office of the Headmaster',
    'established'   => 'Established',
  ] : [
    'title'         => 'প্রত্যয়নপত্র',
    'memo'          => 'স্মারক নং',
    'date'          => 'তারিখ',
    'headmaster'    => 'প্রধান শিক্ষক',
    'class_teacher' => 'শ্রেণি শিক্ষক',
    'print_btn'     => '🖨️ প্রিন্ট করুন',
    'back'          => 'তালিকায় ফিরে যান',
    'office'        => 'প্রধান শিক্ষকের কার্যালয়',
    'established'   => 'স্থাপিত',
  ];

  // Format memo and date by language
  $rawMemo = $document->memo_no;
  if ($isEn && isset($setting)) {
      $customBn = $setting->custom_text ? implode('-', $setting->custom_text) : 'CUSTOM';
      $customEn = $setting->custom_text_en ? implode('-', $setting->custom_text_en) : 'CUSTOM';
      if ($customBn !== $customEn) {
          $rawMemo = str_replace($customBn, $customEn, $rawMemo);
      }
  }
  $memoFormatted = $isEn ? $rawMemo : bn_digits($rawMemo);
  $dateFormatted = format_date_localized($document->issued_at, $isEn ? $months_en : $months_bn, $isEn);
  $schoolName    = $isEn ? $school->name : ($school->name_bn ?: $school->name);
?>
<!DOCTYPE html>
<html lang="{{ $isEn ? 'en' : 'bn' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $lbl['title'] }} - {{ $isEn ? $student->student_name_en : $student->student_name_bn }}</title>
<?php
  $fontFamily = $isEn
    ? "'Arial', 'Times New Roman', serif"
    : "'SolaimanLipi', 'Siyam Rupali', 'Kalpurush', Arial, sans-serif";
?>
<style>
  @page { size: A4; margin: 0; }
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: <?= $fontFamily ?>;
    background: #f5f5f5; color: #000; line-height: 1.6;
  }

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

  .watermark {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    opacity: 0.1; width: 70%; z-index: 0; pointer-events: none;
  }
  .content-wrapper { position: relative; z-index: 2; flex-grow: 1; }

  /* PAD LAYOUT */
  .pad-header {
    display: flex; align-items: center;
    border-bottom: 2px double #008000;
    padding-bottom: 15px; margin-bottom: 10px; color: #008000;
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
    display: flex; justify-content: space-between;
    margin-top: 5px; font-weight: bold; font-size: 16px; color: #008000;
  }

  .pad-title {
    text-align: center; font-size: 24px; font-weight: bold;
    margin: 60px 0 20px; text-decoration: underline;
  }

  .pad-body {
    font-size: 18px; text-align: justify; line-height: 1.8; margin-top: 30px;
  }

  .pad-signature-area {
    margin-top: 120px; display: flex; justify-content: space-between;
    padding: 0 20mm;
  }
  .pad-signature-box { text-align: center; min-width: 150px; }
  .pad-signature-name { font-weight: bold; font-size: 18px; margin-top: 5px; }

  .pad-footer {
    border-top: 2px solid #008000; padding-top: 5px;
    display: flex; justify-content: space-between;
    font-size: 14px; color: #008000; font-weight: bold; margin-top: auto;
  }

  /* Settings Overlay */
  .settings-btn {
    background: #555; color: white; border: none; border-radius: 50%;
    width: 38px; height: 38px; font-size: 18px; cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center;
    margin-left: 10px; vertical-align: middle;
  }
  .settings-overlay {
    display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;
  }
  .settings-overlay.open { display: flex; }
  .settings-panel {
    background: white; border-radius: 10px; padding: 25px 30px;
    min-width: 320px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);
  }
  .settings-panel h5 { margin-bottom: 15px; font-size: 18px; font-weight: bold; }
  .settings-item { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; font-size: 16px; cursor: pointer; }
  .settings-item input[type=checkbox] { width: 18px; height: 18px; cursor: pointer; }
  .settings-close { margin-top: 15px; background: #333; color: white; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer; font-size: 15px; }

  /* STANDARD LAYOUT */
  .standard-header {
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px;
  }
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
  .btn-print {
    background: #008000; color: white; padding: 10px 25px;
    border: none; border-radius: 5px; cursor: pointer;
    font-family: inherit; font-size: 16px;
  }
</style>
</head>
<body>

  <div class="no-print">
    <button class="btn-print" onclick="window.print()">{{ $lbl['print_btn'] }}</button>
    <a href="{{ route('principal.institute.documents.prottayon.history', $school) }}" style="margin-left:15px;">{{ $lbl['back'] }}</a>
    <a href="{{ route('principal.institute.documents.prottayon.edit', [$school, $document->id]) }}" style="margin-left:15px;">✏️ {{ $isEn ? 'Edit' : 'সম্পাদনা' }}</a>
    <button class="settings-btn" onclick="document.getElementById('printSettings').classList.toggle('open')" title="{{ $isEn ? 'Print Settings' : 'প্রিন্ট সেটিংস' }}">⚙️</button>
  </div>

  {{-- Settings Overlay --}}
  <div class="settings-overlay" id="printSettings">
    <div class="settings-panel">
      <h5>⚙️ {{ $isEn ? 'Print Settings' : 'প্রিন্ট সেটিংস' }}</h5>
      <label class="settings-item">
        <input type="checkbox" id="showClassTeacher" onchange="toggleClassTeacher(this.checked)">
        {{ $isEn ? 'Show Class Teacher Signature' : 'শ্রেণি শিক্ষকের স্বাক্ষর প্রয়োজন' }}
      </label>
      <div>
        <button class="settings-close" onclick="document.getElementById('printSettings').classList.remove('open')">{{ $isEn ? 'Close' : 'বন্ধ করুন' }}</button>
      </div>
    </div>
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
                <div class="pad-office-title">{{ $lbl['office'] }}</div>
                <div class="pad-school-name">{{ $schoolName }}</div>
                <div class="pad-address">{{ $school->address }}</div>
                @if($school->established_year)
                <div class="pad-established">{{ $lbl['established'] }}-{{ $isEn ? $school->established_year : bn_digits($school->established_year) }}</div>
                @endif
            </div>
            <div class="pad-contacts">
                @if($school->phone)
                    {{ $isEn ? 'Mobile' : 'মোবাইল' }}: {{ $school->phone }}<br>
                @endif
                @if($school->mpo_code)
                    {{ $isEn ? 'MPO Code' : 'এমপিও কোড' }}: {{ $isEn ? $school->mpo_code : bn_digits($school->mpo_code) }}<br>
                @endif
                @if($school->school_code)
                    {{ $isEn ? 'School Code' : 'স্কুল কোড' }}: {{ $isEn ? $school->school_code : bn_digits($school->school_code) }}
                @endif
            </div>
        </div>

        <div class="pad-details-row">
            <div>{{ $lbl['memo'] }}: {{ $memoFormatted }}</div>
            <div>{{ $lbl['date'] }}: {{ $dateFormatted }}</div>
        </div>

        <div class="pad-title">{{ $lbl['title'] }}</div>

        <div class="pad-body">
            {!! nl2br(e($document->data['custom_content'] ?? '')) !!}
        </div>

        <div class="pad-signature-area">
            <div class="pad-signature-box">
                <div class="pad-signature-name">{{ $lbl['headmaster'] }}</div>
            </div>
        </div>

      @else
        {{-- STANDARD LAYOUT --}}
        <div class="standard-header">
            <img src="{{ $logoUrl }}" style="height:80px;" alt="Logo">
            <div class="standard-school-info">
                <h1 style="font-size: 28px;">{{ $schoolName }}</h1>
                <p>{{ $school->address }}</p>
                <p>{{ $isEn ? 'Mobile' : 'মোবাইল' }}: {{ $school->phone }}
                   @if($school->email) | {{ $isEn ? 'Email' : 'ইমেইল' }}: {{ $school->email }} @endif
                </p>
            </div>
            <div style="width: 80px;">
                {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(80)->generate(route('documents.verify', $document->code)) !!}
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; margin-bottom: 20px; font-weight: bold;">
            <div>{{ $lbl['memo'] }}: {{ $memoFormatted }}</div>
            <div>{{ $lbl['date'] }}: {{ $dateFormatted }}</div>
        </div>

        <h2 style="text-align:center; margin: 50px 0 20px; text-decoration: underline;">{{ $lbl['title'] }}</h2>

        @if(empty($document->data['custom_content']) || !($document->data['is_final'] ?? false))
          <table class="student-data-table">
            <tr>
              <td class="info-label">{{ $isEn ? 'Student Name' : 'শিক্ষার্থীর নাম' }}:</td>
              <td>{{ $isEn ? $student->student_name_en : $student->student_name_bn }}</td>
            </tr>
            <tr>
              <td class="info-label">{{ $isEn ? "Father's Name" : 'পিতার নাম' }}:</td>
              <td>{{ $isEn ? $student->father_name : $student->father_name_bn }}</td>
            </tr>
            <tr>
              <td class="info-label">{{ $isEn ? "Mother's Name" : 'মাতার নাম' }}:</td>
              <td>{{ $isEn ? $student->mother_name : $student->mother_name_bn }}</td>
            </tr>
            <tr>
              <td class="info-label">{{ $isEn ? 'Class' : 'শ্রেণি' }}:</td>
              <td>{{ $student->class?->name ?? '' }}</td>
            </tr>
            <tr>
              <td class="info-label">{{ $isEn ? 'Roll No' : 'রোল নম্বর' }}:</td>
              <td>{{ $isEn ? $student->roll : ($student->roll ? bn_digits($student->roll) : '') }}</td>
            </tr>
            <tr>
              <td class="info-label">{{ $isEn ? 'Student ID' : 'আইডি নং' }}:</td>
              <td>{{ $student->student_id }}</td>
            </tr>
            <tr>
              <td class="info-label">{{ $isEn ? 'Present Address' : 'বর্তমান ঠিকানা' }}:</td>
              <td>{{ $student->present_village }}, {{ $student->present_post_office }}</td>
            </tr>
          </table>
        @endif

        <div style="font-size: 16px; text-align: justify; line-height: 1.8;">
            {!! nl2br(e($document->data['custom_content'] ?? '')) !!}
        </div>

        <div style="margin-top: 120px; display: flex; justify-content: space-between; padding: 0 20mm;">
            <div id="classTeacherSig" style="text-align:center; width: 150px; display:none;">
                <div style="border-top: 1px solid #000; padding-top: 5px;">{{ $lbl['class_teacher'] }}</div>
            </div>
            <div style="margin-left:auto; text-align:center; width: 150px;">
                <div style="border-top: 1px solid #000; padding-top: 5px;">{{ $lbl['headmaster'] }}</div>
            </div>
        </div>
      @endif

    </div>

    @if($layout === 'pad')
    <div class="pad-footer">
        <div>{{ $isEn ? 'Email' : 'E-mail' }}: {{ $school->email }}</div>
        @if($school->website)<div>{{ $isEn ? 'Website' : 'ওয়েবসাইট' }}: {{ $school->website }}</div>@endif
    </div>
    @endif
  </div>

<script>
function toggleClassTeacher(show) {
    var el = document.getElementById('classTeacherSig');
    if (el) {
        el.style.display = show ? 'block' : 'none';
        // Adjust headmaster position when class teacher shown
        el.parentElement.style.justifyContent = show ? 'space-between' : 'flex-end';
        if (show) {
            el.parentElement.querySelector('[style*="margin-left:auto"]')?.style.setProperty('margin-left', '0');
        } else {
            el.parentElement.querySelector('div:last-child')?.style.setProperty('margin-left', 'auto');
        }
    }
}
// Default: class teacher hidden
document.addEventListener('DOMContentLoaded', function() {
    toggleClassTeacher(false);
});
</script>
</body>
</html>
