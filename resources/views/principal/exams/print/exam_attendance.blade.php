@extends('layouts.print')
@section('title', 'Attendance Sheet - ' . $school->name)

@section('suppress_header')@endsection

@php
  $lang = request('lang','bn');
  $institute_name = $school->name ?? 'Jorepukuria Secondary School';
  $institute_address = $school->address ?? 'Gangni, Meherpur';
  $institute_phone = $school->phone ?? '';
  $institute_logo = $school->logo ? asset('storage/'.$school->logo) : '';

  $exam_name = $exam->name ?? 'পরীক্ষা';
  $className = $lang==='bn' ? ($exam->class->bangla_name ?? $exam->class->name) : ($exam->class->name ?? $exam->class->bangla_name ?? '');

  if (!function_exists('bn_num')){
    function bn_num($v){ $en=['0','1','2','3','4','5','6','7','8','9']; $bn=['০','১','২','৩','৪','৫','৬','৭','৮','৯']; return str_replace($en,$bn,(string)$v); }
  }
  if (!function_exists('t')){
    function t($en, $bn){ return request('lang','bn') === 'bn' ? ($bn ?? $en) : ($en ?? $bn); }
  }
  if (!function_exists('bnNum')){
    function bnNum($v){ return request('lang','bn') === 'bn' ? bn_num($v) : $v; }
  }
  if (!function_exists('fmt_date')){
    function fmt_date($d){ return $d ? date('d/m/Y', strtotime($d)) : '-'; }
  }
  if (!function_exists('fmt_time')){
    function fmt_time($t){ return $t ? date('h:i A', strtotime($t)) : '-'; }
  }
  if (!function_exists('shortSubjectName')){
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
  }
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
  if (!function_exists('capitalizeEachWord')){
    function capitalizeEachWord($v){ return ucwords(strtolower($v)); }
  }
@endphp

@push('print_head')
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  /* Keep existing layout styles but ensure letters use Hind Siliguri and numbers use Kalpurush */
  body{ font-family:'Hind Siliguri', system-ui, Segoe UI, Roboto, Arial, sans-serif !important; }
  .num{ font-family: 'Kalpurush', 'Noto Sans Bengali', serif; font-size: 14pt; font-weight: bold; }
  .no-bn{ font-family: inherit; }
  /* original page styles preserved below */
  :root { --primary:#0ea5e9; --border:#1f2937; --ink:#0a0a0a; --muted:#111827; --bg:#ffffff; }
    *{box-sizing:border-box}
    .actions{position:sticky;top:0;z-index:10;background:#fff;padding:10px;text-align:center;border-bottom:1px solid var(--border)}
    .btn{background:var(--primary);border:none;color:#fff;padding:8px 14px;border-radius:8px;font-weight:700;cursor:pointer}
    .page{width:100%;max-width:210mm;margin:0 auto;padding:10mm}
    .sheet{background:#fff;border:1px solid var(--border);border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06);page-break-after:always}
    .sheet:last-child{page-break-after:auto}
  .hdr{display:grid;grid-template-columns:70px 1fr 85px;gap:12px;align-items:center;padding:4px 12px 4px;border-bottom:1px solid var(--border)}
    .logo{width:70px;height:70px;display:flex;align-items:center;justify-content:center}
    .logo img{max-width:100%;max-height:100%}
    .brand{text-align:center}
  .school-name{font-size:24px;font-weight:800;color:#000;margin:0;line-height:1.1}
  .school-meta{margin:0;color:#000;font-weight:700;line-height:1.1}
    .stud-photo{width:80px;height:95px;border:1px solid var(--border);border-radius:8px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden}
    .stud-photo img{width:100%;height:100%;object-fit:cover}
  .meta{padding:8px 12px;border-bottom:1px solid var(--border);display:flex;flex-direction:column;gap:6px}
    .meta .row{display:flex;flex-wrap:wrap;gap:18px;align-items:center}
    .meta .item{font-weight:800;color:#000}
    .meta .light{font-weight:700;color:#000}

  table{width:100%;border-collapse:collapse}
  th,td{border:1px solid var(--border);padding:8px 10px;text-align:center;font-size:12pt;color:#000}
  thead th{background:transparent;color:#000;font-weight:800}
    td.name, th.name{text-align:left}
    td.code, th.code{width:80px;white-space:nowrap}
    .bn{font-family:'Noto Sans Bengali','Hind Siliguri',system-ui,Segoe UI,Roboto,Arial,sans-serif}
    td.sigc{height:18mm}
  table.info-table{margin:4px 0;padding-top:0;padding-bottom:5px;}
  table.info-table td{border:none;padding:2px 8px;font-size:13pt;color:#000}

  .brandbar{padding:8px 12px;background:transparent;color:#0a0a0a;text-align:center;font-weight:800;letter-spacing:.3px;position:relative;z-index:1;border-top:1px solid var(--border)}

    @media print{
      @page{size:A4 portrait;margin:10mm}
      .actions{display:none}
      .page{padding:0}
      .sheet{border:none;box-shadow:none}
      thead{display:table-header-group}
    }
</style>
@endpush

@section('content')
  <div class="page">
@foreach ($students as $stu)
@php
  $stu_name = $lang === 'bn'
    ? ($stu->student_name_bn ?: ($stu->student_name_en ?: ($stu->full_name ?? '')))
    : capitalizeEachWord($stu->student_name_en ?: ($stu->full_name ?? ''));
  $enrollment = $stu->enrollments->first();
  $stu_roll = $enrollment ? str_pad((string)$enrollment->roll_no, 6, '0', STR_PAD_LEFT) : '';
  $stu_id_show = (string)($stu->student_id ?? $stu->id);

  $photoUrl = $stu->photo_url;

  $sec_name = '';
  $division = '';
  if ($enrollment) {
      $sec_name = $lang==='bn' ? ($enrollment->section->bangla_name ?? $enrollment->section->name ?? '') : ($enrollment->section->name ?? $enrollment->section->bangla_name ?? '');
      $division = $lang==='bn' ? ($enrollment->group->bangla_name ?? $enrollment->group->name ?? '') : ($enrollment->group->name ?? $enrollment->group->bangla_name ?? '');
  }

  $assigned_subs = [];
  $sidNum = $stu->id;
  if (!empty($assigned_by_student[$sidNum])) {
    $assigned_subs = array_keys($assigned_by_student[$sidNum]);
  }

  $sched_for_student = [];
  if (!empty($assigned_subs)) {
    foreach ($schedule as $row) {
        if (isset($row->subject_id) && in_array(intval($row->subject_id), $assigned_subs, true)) {
            $sched_for_student[] = $row;
        }
    }
  } else {
    $sched_for_student = is_object($schedule) ? $schedule->toArray() : $schedule;
  }

  usort($sched_for_student, function($a,$b){
    $ad = $a->exam_date ? strtotime($a->exam_date) : PHP_INT_MAX;
    $bd = $b->exam_date ? strtotime($b->exam_date) : PHP_INT_MAX;
    if ($ad === $bd) {
      $at = $a->exam_time ? strtotime($a->exam_time) : 0;
      $bt = $b->exam_time ? strtotime($b->exam_time) : 0;
      return $at <=> $bt;
    }
    return $ad <=> $bd;
  });
@endphp
    <div class="sheet">
      <div class="hdr">
        <div class="logo"><?php if ($institute_logo): ?><img src="{{ $institute_logo }}" alt="Logo" onerror="this.remove()"><?php endif; ?></div>
        <div class="brand">
          <h1 class="school-name">{{ t($school->name ?? $institute_name, $school->name_bn ?? $institute_name) }}</h1>
          <div class="school-meta"><small>{{ t($school->address ?? $institute_address, $school->address_bn ?? $institute_address) }}</small></div>
          <div class="school-meta" style="margin-top:4px;"><strong>{{ t($exam->name ?? $exam_name, $exam->name_bn ?? $exam_name) }}</strong></div>
          <div class="school-meta"><strong>{{ t('Attendance Sheet','হাজিরা শীট') }}</strong></div>
        </div>
        <div class="stud-photo">
          <?php if (!empty($photoUrl)): ?>
            <img src="{{ $photoUrl }}" alt="Photo" onerror="this.remove()">
          <?php endif; ?>
        </div>
      </div>
  <table class="info-table" cellpadding="0" cellspacing="0" style="margin:4px 0;width:100%">
          <tbody style="text-align: left;">
            <tr>
              <td>{{ t('Name of Student','শিক্ষার্থীর নাম') }}:</td>
              <td><strong>{{ $stu_name }}</strong></td>
              <td>{{ t('ID','আইডি') }}:</td>
              <td><strong class="no-bn">{{ $stu_id_show }}</strong></td>
              <td>{{ t('Roll Number','রোল নং') }}:</td>
              <td><strong class="num">{{ bnNum($stu_roll) }}</strong></td>
            </tr>
            <tr>
                <td>{{ t('Class','শ্রেণি') }}:</td>
                <td><strong>{{ $className }}</strong></td>
                <td>{{ t('Section','শাখা') }}:</td>
                <td><strong>{{ $sec_name }}</strong></td>
                <td>{{ t('Group','গ্রুপ') }}:</td>
                <td><strong>{{ $division }}</strong></td>
                <td>{{ t('Board Reg. No.','বোর্ড রেজি. নং') }}:</td>
                <td><strong class="no-bn">{{ $stu->board_registration_no ?? '' }}</strong></td>
            </tr>
          </tbody>
        </table>
      <div class="tbl-wrap">
        <table>
          <thead>
            <tr>
              <th style="width:50px">{{ t('#','ক্রম') }}</th>
              <th style="width:110px">{{ t('Date','তারিখ') }}</th>
              <th class="code">{{ t('Subject Code','বিষয় কোড') }}</th>
              <th class="name">{{ t('Subject','বিষয়ের নাম') }}</th>
              <th class="sigc" style="width:160px">{{ t("Student's signature","শিক্ষার্থীর স্বাক্ষর") }}</th>
              <th class="sigc" style="width:180px">{{ t('Room invigilator signature','কক্ষ পরিদর্শকের স্বাক্ষর') }}</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($sched_for_student)):
            $i=0; foreach ($sched_for_student as $row): $i++; ?>
            <tr>
              <td class="num">{{ $lang==='bn' ? bn_num($i) : $i }}</td>
              <td class="num">{{ $lang==='bn' ? bn_num(fmt_date($row->exam_date ?? null)) : fmt_date($row->exam_date ?? null) }}</td>
              <td class="code num">{{ $lang==='bn' ? bn_num($row->subject_code ?? '') : ($row->subject_code ?? '') }}</td>
              <td class="name">{{ $lang==='bn' ? ($row->subject_bangla_name ?? $row->subject_name ?? '') : ($row->subject_name ?? $row->subject_bangla_name ?? '') }}</td>
              <td class="sigc">&nbsp;</td>
              <td class="sigc">&nbsp;</td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="6" style="text-align:center;color:#111827">{{ t('Schedule not found','সময়সূচী পাওয়া যায়নি') }}</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div style="display:flex;justify-content:space-between;gap:16px;margin:16px 12px 12px">
        <div style="width:260px;text-align:center">
          <div style="border-top:1px solid #475569;height:0;margin-top:60px"></div>
          <div style="margin-top:6px;font-weight:700">{{ t('Verifier','যাচাইকারী') }}</div>
        </div>
        <div style="width:260px;text-align:center">
          <div style="border-top:1px solid #475569;height:0;margin-top:60px"></div>
          <div style="margin-top:6px;font-weight:700">{{ t('Principal / Head Teacher','অধ্যক্ষ / প্রধান শিক্ষক') }}</div>
        </div>
      </div>
      <div class="brandbar">{{ t('Technical support: Batighor Computers','কারিগরি সহযোগীতায়ঃ বাতিঘর কম্পিউটার’স') }}</div>
  </div>
@endforeach
  </div>
@endsection
