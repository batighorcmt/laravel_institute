<!DOCTYPE html>
@php
  $institute_name = $school->name ?? 'Jorepukuria Secondary School';
  $institute_logo = $school->logo ? asset('storage/'.$school->logo) : '';
@endphp
<html lang="bn">
<head>
<meta charset="UTF-8">
<title>প্রবেশপত্র - {{ $institute_name }}</title>
<link rel="icon" href="{{ $institute_logo }}" type="image/x-icon">
<style>
  @font-face{font-family:'Kalpurush';src:url('https://fonts.maateen.me/kalpurush/font.ttf') format('truetype');font-display:swap}
  body{margin:0;padding:0;font-family:'Kalpurush', Arial, Helvetica, sans-serif}
  .num{font-family:'Kalpurush', 'Noto Sans Bengali', serif}
  .no-bn{font-family:inherit}
  .sheet{padding:8mm 8mm; break-inside: avoid; page-break-inside: avoid}
  .school-header{display:grid;grid-template-columns:70px 1fr 80px;gap:8px;align-items:center;margin-bottom:8px}
  .logo{width:70px;height:70px;display:flex;align-items:center;justify-content:center}
  .logo img{max-width:100%;max-height:100%;object-fit:contain}
  .school-text{text-align:center}
  .school-name{font-weight:800;font-size:22px;line-height:1.1;margin:0}
  .school-meta{color:#444;line-height:1.2;margin:2px 0 0 0}
  .exam-title{font-weight:800;margin:2px 0 0 0}
  .admit-text{display:inline-block;font-weight:800;margin-top:4px;padding:3px 8px;border:2px solid #222;border-radius:4px;font-size:18px;letter-spacing:0.3px}
  .photo{width:80px;height:100px;border:1px solid #ddd;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:4px;background:#fafafa;font-size:12px;color:#777}
  .photo img{width:100%;height:100%;object-fit:cover;object-position:top center}
  .student-info{margin:6px 0}
  .info-row{display:flex;align-items:baseline;justify-content:space-between;gap:10px;white-space:nowrap}
  .kv .k{font-weight:700;color:#222}
  .kv .v{color:#111}
  @extends('layouts.print')

  @section('suppress_header')@endsection

  @php
    $lang = request('lang','bn');
    $institute_name = $school->name ?? 'Jorepukuria Secondary School';
    $institute_address = $school->address ?? 'Gangni, Meherpur';
    $institute_phone = $school->phone ?? '';
    $institute_logo = $school->logo ? asset('storage/'.$school->logo) : '';

    $exam_name = $exam->name ?? 'পরীক্ষা';
    $className = $lang==='bn' ? ($exam->class->bangla_name ?? $exam->class->name) : ($exam->class->name ?? $exam->class->bangla_name ?? '');

      function fmt_date($d){ return $d ? date('d/m/Y', strtotime($d)) : '-'; }
      function fmt_time($t){ return $t ? date('h:i A', strtotime($t)) : '-'; }
      if (!function_exists('bn_num')){
        function bn_num($v){ $en=['0','1','2','3','4','5','6','7','8','9']; $bn=['০','১','২','৩','৪','৫','৬','৭','৮','৯']; return str_replace($en,$bn,(string)$v); }
      }
      if (!function_exists('t')){
        function t($en, $bn){ return request('lang','bn') === 'bn' ? ($bn ?? $en) : ($en ?? $bn); }
      }
      if (!function_exists('bnNum')){
        function bnNum($v){ return request('lang','bn') === 'bn' ? bn_num($v) : $v; }
      }
    $bnDays=['রবিবার','সোমবার','মঙ্গলবার','বুধবার','বৃহস্পতিবার','শুক্রবার','শনিবার'];
    function shortSubjectName($name){
      $n = trim((string)$name);
      if ($n === '') return $n;
      $normalized = strtolower(preg_replace('/\s+/', ' ', $n));
      if ($normalized === 'information and communication technology') return 'ICT';
      if ($normalized === 'history of bangladesh and world civilization') return 'History';
      if (strpos($normalized, 'information') !== false && strpos($normalized, 'communication') !== false && strpos($normalized, 'technology') !== false) return 'ICT';
      if (strpos($normalized, 'history of bangladesh') !== false && strpos($normalized, 'world civilization') !== false) return 'History';
      return $n;
    }
  @endphp

  @push('print_head')
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body{ font-family:'Hind Siliguri', system-ui, Segoe UI, Roboto, Arial, sans-serif !important; }
    .num{ font-family: 'Kalpurush', 'Noto Sans Bengali', serif; }
    .no-bn{ font-family: inherit; }
    @font-face{font-family:'Kalpurush';src:url('https://fonts.maateen.me/kalpurush/font.ttf') format('truetype');font-display:swap}
    .sheet{padding:8mm 8mm; break-inside: avoid; page-break-inside: avoid}
    .school-header{display:grid;grid-template-columns:70px 1fr 80px;gap:8px;align-items:center;margin-bottom:8px}
    .logo{width:70px;height:70px;display:flex;align-items:center;justify-content:center}
    .logo img{max-width:100%;max-height:100%;object-fit:contain}
    .school-text{text-align:center}
    .school-name{font-weight:800;font-size:22px;line-height:1.1;margin:0}
    .school-meta{color:#444;line-height:1.2;margin:2px 0 0 0}
    .exam-title{font-weight:800;margin:2px 0 0 0}
    .admit-text{display:inline-block;font-weight:800;margin-top:4px;padding:3px 8px;border:2px solid #222;border-radius:4px;font-size:18px;letter-spacing:0.3px}
    .photo{width:80px;height:100px;border:1px solid #ddd;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:4px;background:#fafafa;font-size:12px;color:#777}
    .photo img{width:100%;height:100%;object-fit:cover;object-position:top center}
    .student-info{margin:6px 0}
    .info-row{display:flex;align-items:baseline;justify-content:space-between;gap:10px;white-space:nowrap}
    .kv .k{font-weight:700;color:#222}
    .kv .v{color:#111}
    table{width:100%;border-collapse:collapse;font-family:'Kalpurush', Arial, sans-serif}
    th,td{border:1px solid #ddd;padding:2px 3px;text-align:center;line-height:1.1;font-size:13px}
    th{background:#f2f2f2}
    .signature-area{margin-top:12px;text-align:center}
    .signature-area table{width:90%;margin:0 auto;border:none;table-layout:fixed}
    .signature-area td{border:none;padding-top:0;vertical-align:bottom}
    .sig-space{height:12mm}
    .sig-line{width:55%;margin:0 auto 3mm;border-top:1px solid #333}
    .sig-label{font-weight:600}
    .sig-gap{width:20%}
    .actions{ position:fixed; top:10px; left:50%; transform:translateX(-50%); z-index:99999; text-align:center; background:rgba(255,255,255,0.95); border:1px solid #ddd; border-radius:8px; padding:6px 10px; box-shadow:0 2px 8px rgba(0,0,0,0.08) }
    .print-btn{ background:#1e3a8a; color:#fff; border:none; border-radius:6px; padding:8px 14px; cursor:pointer }
    .print-btn:hover{ background:#172554 }
    .cutline{display:none}
    @media print{
      @page{size:A4 portrait;margin:10mm}
      .sheet{height:122mm;padding:4mm 8mm}
      .sheet:nth-child(odd){ margin-bottom:18mm }
      .sheet:nth-child(2n){page-break-after:always}
      .sheet:last-child{page-break-after:auto}
      .cutline{display:block;position:fixed;left:0;right:0;top:50%;border-top:1px dashed #999;z-index:9999}
      .actions{ display:none }
    }
  </style>
  @endpush

  @section('content')
  <div class="cutline" aria-hidden="true"></div>
  @foreach($students as $stu)
    @php
        if (!function_exists('langField')){
          function langField($obj, $field, $lang='bn'){
            if (in_array($field, ['full_name','name'])){
              if ($lang === 'bn'){
                return $obj->student_name_bn ?? $obj->student_name_en ?? $obj->full_name ?? null;
              }
              return $obj->student_name_en ?? $obj->student_name_bn ?? $obj->full_name ?? null;
            }
            $bn = $field . '_bn';
            if ($lang === 'bn') {
              return $obj->$bn ?? $obj->$field ?? null;
            }
            return $obj->$field ?? $obj->$bn ?? null;
          }
        }
        $full_name = langField($stu, 'full_name', $lang) ?: langField($stu, 'name', $lang) ?: ($stu->student_id ?? $stu->id ?? '');
        $enrollment = $stu->enrollments->first();
        $roll = $enrollment ? $enrollment->roll_no : '';
        $section_name = '';
        $group = '';
        if ($enrollment) {
          $section_name = $lang==='bn' ? ($enrollment->section->bangla_name ?? $enrollment->section->name ?? '') : ($enrollment->section->name ?? $enrollment->section->bangla_name ?? '');
          $group = $lang==='bn' ? ($enrollment->group->bangla_name ?? $enrollment->group->name ?? '') : ($enrollment->group->name ?? $enrollment->group->bangla_name ?? '');
        }
      $photoUrl = $stu->photo_url;
      $stuIdNum = $stu->id;

      $assigned = [];
      if (!empty($assigned_by_student[$stuIdNum])) {
        $assigned = array_keys($assigned_by_student[$stuIdNum]);
      }

      $sched_for_student = [];
      if (!empty($assigned)) {
        foreach ($schedule as $row) {
          if (isset($row->subject_id) && in_array(intval($row->subject_id), $assigned, true)) { $sched_for_student[] = $row; }
        }
      } else {
        $sched_for_student = $schedule;
      }
    @endphp

    <div class="sheet">
      <div class="school-header">
        <div class="logo">
          @if ($institute_logo)
            <img src="{{ $institute_logo }}" alt="Logo" onerror="this.style.display='none'">
          @endif
        </div>
        <div class="school-text">
            <h1 class="school-name">{{ t($school->name ?? $institute_name, $school->name_bn ?? $institute_name) }}</h1>
            <div class="school-meta">{{ t($school->address ?? $institute_address, $school->address_bn ?? $institute_address) }}</div>
            <div class="exam-title">{{ t($exam->name ?? $exam_name, $exam->name_bn ?? $exam_name) }}</div>
            <div class="admit-text">{{ t('Admit Card','প্রবেশপত্র') }}</div>
          </div>
        <div class="photo">
          @if ($photoUrl)
            <img src="{{ $photoUrl }}" alt="Photo" onerror="this.parentNode.textContent='ছবি';this.remove();">
          @else
            ছবি
          @endif
        </div>
      </div>

      <div class="student-info">
        <div class="info-row">
          <div class="kv"><span class="k">{{ t('Name','নাম') }}</span> <span class="v">{{ $full_name }}</span></div>
          <div class="kv"><span class="k">{{ t('Class','শ্রেণি') }}</span> <span class="v">{{ $className }}</span></div>
          <div class="kv"><span class="k">{{ t('Section','শাখা') }}</span> <span class="v">{{ $section_name !== '' ? $section_name : '—' }}</span></div>
          <div class="kv"><span class="k">{{ t('Roll','রোল') }}</span> <span class="v num">{{ $lang==='bn' ? bn_num($roll) : $roll }}</span></div>
          <div class="kv"><span class="k">{{ t('Group','গ্রুপ') }}</span> <span class="v">{{ $group !== '' ? $group : '—' }}</span></div>
        </div>
      </div>

      <table>
        <thead>
          <tr>
            <th>{{ t('#','ক্রমিক নং') }}</th>
            <th>{{ t('Date','তারিখ') }}</th>
            <th>{{ t('Day','বার') }}</th>
            <th>{{ t('Subject','বিষয়') }}</th>
            <th>{{ t('Subject Code','বিষয় কোড') }}</th>
            <th>{{ t('Time','সময়') }}</th>
          </tr>
        </thead>
        <tbody>
          @php $i = 0; @endphp
          @foreach($sched_for_student as $row)
            @php $i++; $d = $row->exam_date ?? null; @endphp
            <tr>
              <td class="num">{{ $lang==='bn' ? bn_num($i) : $i }}</td>
              <td class="num">{{ $lang==='bn' ? bn_num(fmt_date($d)) : fmt_date($d) }}</td>
              <td>{{ $d ? $bnDays[intval(date('w', strtotime($d)))] : '-' }}</td>
              <td>{{ $row->subject_bangla_name ?? shortSubjectName($row->subject_name ?? '') }}</td>
              <td class="num">{{ $lang==='bn' ? bn_num($row->subject_code ?? '') : ($row->subject_code ?? '') }}</td>
              <td class="num">{{ $lang==='bn' ? bn_num(fmt_time($row->exam_time ?? null)) : fmt_time($row->exam_time ?? null) }}</td>
            </tr>
          @endforeach
          @if($i === 0)
            <tr><td colspan="6">এই শিক্ষার্থীর জন্য কোনো সময়সূচী পাওয়া যায়নি।</td></tr>
          @endif
        </tbody>
      </table>

      <div class="signature-area">
        <table>
          <tr>
            <td>
              <div class="sig-space"></div>
              <div class="sig-line"></div>
              <div class="sig-label">{{ t('Class Teacher','শ্রেণি শিক্ষক') }}</div>
            </td>
            <td class="sig-gap"></td>
            <td>
              <div class="sig-space"></div>
              <div class="sig-line"></div>
              <div class="sig-label">{{ t('Principal / Head Teacher','অধ্যক্ষ / প্রধান শিক্ষক') }}</div>
            </td>
          </tr>
        </table>
      </div>
    </div>
  @endforeach

  @endsection
