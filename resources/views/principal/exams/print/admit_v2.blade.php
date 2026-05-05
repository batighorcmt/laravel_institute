@extends('layouts.print')
@section('title', 'Admit Card V2 - ' . $school->name)

@section('suppress_header')@endsection

@php
  $lang = request('lang','bn');
  $institute_name = $school->name ?: ($lang === 'bn' ? 'প্রতিষ্ঠান' : 'Institute');
  $institute_address = $school->address ?: '';
  $institute_phone = $school->phone ?? '';
  $institute_logo = $school->logo ? asset('storage/'.$school->logo) : '';

  $exam_name_en = $exam->name ?: 'Exam';
  $exam_name_bn = $exam->name_bn ?: ($exam->name ?: 'পরীক্ষা');
  $className = $lang==='bn' ? ($exam->class?->bangla_name ?: $exam->class?->name) : ($exam->class?->name ?: $exam->class?->bangla_name ?: '');

  function fmt_date($d){ return $d ? date('d/m/Y', strtotime($d)) : '-'; }
  function fmt_time($t){ return $t ? date('h:i A', strtotime($t)) : '-'; }
  if (!function_exists('bn_num')){
    function bn_num($v){ $en=['0','1','2','3','4','5','6','7','8','9']; $bn=['০','১','২','৩','৪','৫','৬','৭','৮','৯']; return str_replace($en,$bn,(string)$v); }
  }
  if (!function_exists('t')){
    function t($en, $bn){
      $lang = request('lang','bn');
      if ($lang === 'bn') return $bn ?: $en;
      return $en ?: $bn;
    }
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
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  @font-face {
    font-family: 'BengaliNumbers';
    src: url('/fonts/kalpurush/kalpurush.woff2') format('woff2');
    unicode-range: U+09E6-09EF;
  }
  body{ font-family: 'BengaliNumbers', 'Hind Siliguri', system-ui, Segoe UI, Roboto, Arial, sans-serif !important; }
  .num{ font-family: 'BengaliNumbers', 'Kalpurush', 'Noto Sans Bengali', serif; }
  .no-bn{ font-family: inherit; }
  :root { --primary:#0ea5e9; --primary-dark:#0284c7; --ink:#0f172a; --muted:#64748b; --border:#e2e8f0; --bg:#ffffff; }
    *{box-sizing:border-box}
    .actions{position:sticky;top:0;z-index:10;background:linear-gradient(180deg,#ffffff,rgba(255,255,255,.7));backdrop-filter:saturate(140%) blur(2px);padding:10px 0;text-align:center;border-bottom:1px solid var(--border)}
    .btn{background:var(--primary);border:none;color:#fff;padding:10px 16px;border-radius:8px;font-weight:700;cursor:pointer;box-shadow:0 2px 8px rgba(2,132,199,.2)}
    .btn:hover{background:var(--primary-dark)}
  .page{width:100%;max-width:210mm;margin:0 auto;padding:14mm 10mm}
  .card{position:relative;page-break-after:always;background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(15,23,42,.06)}
  .wm{position:absolute;inset:0;background-repeat:no-repeat;background-position:center;background-size:75% auto;opacity:.06;pointer-events:none;z-index:0}
    .card:last-child{page-break-after:auto}
  .hdr{display:grid;grid-template-columns:70px 1fr 120px;gap:12px;align-items:center;padding:10px 12px;background:linear-gradient(90deg,#e0f2fe,#ffffff)}
  .logo{width:90px;height:90px;border:none;border-radius:0;display:flex;align-items:center;justify-content:center;background:transparent;overflow:hidden}
    .logo img{max-width:100%;max-height:100%}
    .brand{text-align:center}
    .school-name{font-size:22px;font-weight:800;color:#0b3a67;margin:0}
    .school-meta{margin:2px 0;color:var(--muted);font-weight:600}
    .exam-pill{display:inline-block;margin-top:6px;padding:6px 12px;border-radius:999px;background:#0ea5e91a;color:#0369a1;font-weight:800;border:1px solid #7dd3fc}
  .photo{justify-self:end;width:105px;height:120px;border:2px solid #0ea5e9;border-radius:10px;display:flex;align-items:center;justify-content:center;background:#ffffff;color:#475569;font-weight:600;box-shadow:0 2px 10px rgba(2,132,199,.15);padding:4px}
    .photo img{width:100%;height:100%;object-fit:cover;border-radius:6px}
  .body{padding:10px 12px;position:relative;z-index:1}
  .info{display:grid;grid-template-columns:1fr;row-gap:8px}
    .panel{border:1px solid var(--border);border-radius:10px}
  .panel .ttl{padding:6px 10px;background:#f1f5f9;border-bottom:1px solid var(--border);font-weight:800;color:#0f172a}
  .dl{display:grid;grid-template-columns:100px 1fr 100px 1fr;row-gap:4px;column-gap:8px;padding:8px 10px;align-items:center}
  .dl .k{color:#475569;font-weight:700;line-height:1.2}
  .dl .v{color:#0f172a;line-height:1.2}
  table{width:100%;border-collapse:collapse}
  th,td{padding:4px 6px;border-bottom:1px solid var(--border);text-align:left;line-height:1.2}
  thead th{background:transparent;color:#0f172a;border-bottom:2px solid var(--border)}
    tbody tr:nth-child(odd){background:transparent}
  .foot{display:flex;flex-direction:column;gap:16px;margin-top:12px}
  .sign{border-top:1px solid #475569;margin-top:0;text-align:center;padding-top:0;padding-bottom:0.2in;font-weight:700;color:#0f172a}
    .note{font-size:13px;color:#475569}
  .note ul{list-style:disc;margin:6px 0 0;padding-left:18px;-webkit-column-count:1;column-count:1}
  .brandbar{padding:8px 12px;background:transparent;color:#0f172a;text-align:center;font-weight:800;letter-spacing:.3px;position:relative;z-index:1;border-top:1px solid #bbb}
  .hdr{position:relative;z-index:1}
  .sigs{display:flex;flex-direction:row;gap:16px;align-items:flex-end;justify-content:space-between}
  .sig{width:48%;display:flex;flex-direction:column;align-items:center}
  .sig-space{width:100%;height:0.5in;display:flex;align-items:flex-end;justify-content:center}
  .sig-space.hm img{max-height:0.48in;max-width:100%;object-fit:contain;opacity:.9}
  .bn{font-family:'Noto Sans Bengali','Hind Siliguri',system-ui,Segoe UI,Roboto,Arial,sans-serif}
    @media print{
      @page{size:A4 portrait;margin:10mm}
      .actions{display:none}
      .page{padding:0}
      .card{box-shadow:none;border:1px solid #bbb}
    }
</style>
@endpush

@section('content')
  <div class="page">
<?php foreach ($students as $stu):
    if (!function_exists('langField')){
      function langField($obj, $field, $lang='bn'){
        if (in_array($field, ['full_name','name'])){
          $bn = $obj->student_name_bn ?: '';
          $en = $obj->student_name_en ?: '';
          if ($lang === 'bn') return $bn ?: $en;
          return $en ?: $bn;
        }
        $bnField = $field . '_bn';
        if ($lang === 'bn') {
          return $obj->$bnField ?: $obj->$field ?: '';
        }
        return $obj->$field ?: $obj->$bnField ?: '';
      }
    }
    $full_name = langField($stu, 'full_name', $lang) ?: ($stu->full_name ?: ($stu->student_id ?? $stu->id ?? ''));
    $father = langField($stu, 'father_name', $lang) ?: ($stu->father_name ?? $stu->father_name_bn ?? '');
    $mother = langField($stu, 'mother_name', $lang) ?: ($stu->mother_name ?? $stu->mother_name_bn ?? '');

  $enrollment = $stu->enrollments->first();
  $roll = $enrollment ? $enrollment->roll_no : '';
  $section_name = '';
  $group = '';
  if ($enrollment) {
    $section_name = $lang==='bn' ? ($enrollment->section?->bangla_name ?: $enrollment->section?->name ?: '') : ($enrollment->section?->name ?: $enrollment->section?->bangla_name ?: '');
    $group = $lang==='bn' ? ($enrollment->group?->bangla_name ?: $enrollment->group?->name ?: '') : ($enrollment->group?->name ?: $enrollment->group?->bangla_name ?: '');
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
?>
    <div class="card">
      <div class="wm" style="background-image:url('{{ $institute_logo }}');"></div>
      <div class="hdr">
        <div class="logo">
          <?php if ($institute_logo): ?>
            <img src="{{ $institute_logo }}" alt="Logo" onerror="this.remove()">
          <?php else: ?>লোগো<?php endif; ?>
        </div>
        <div class="brand">
          <h1 class="school-name">{{ t($school->name ?? $institute_name, $school->name_bn ?? $institute_name) }}</h1>
          <div class="school-meta">{{ t($school->address ?? $institute_address, $school->address_bn ?? $institute_address) }}</div>
          <div class="exam-pill">{{ t($exam_name_en, $exam_name_bn) }} – {{ t('Admit Card','প্রবেশপত্র') }}</div>
        </div>
        <div class="photo">
          <?php if ($photoUrl): ?><img src="{{ $photoUrl }}" alt="Photo" onerror="this.parentNode.textContent='শিক্ষার্থীর ছবি';this.remove();"><?php else: ?>শিক্ষার্থীর ছবি<?php endif; ?>
        </div>
      </div>

      <div class="body">
        <div class="info">
          <div class="panel">
            <div class="ttl">{{ t('Student Information','শিক্ষার্থীর তথ্য') }}</div>
            <div class="dl">
              <div class="k">{{ t('Name','নাম') }}</div><div class="v">{{ $full_name }}</div>
              <div class="k">{{ t('Roll','রোল') }}</div><div class="v"><span class="num">{{ bnNum($roll) }}</span></div>
              <div class="k">{{ t("Father's Name",'পিতার নাম') }}</div><div class="v">{{ $father }}</div>
              <div class="k">{{ t('Class','শ্রেণি') }}</div><div class="v">{{ $className }}</div>
              <div class="k">{{ t("Mother's Name",'মাতার নাম') }}</div><div class="v">{{ $mother }}</div>
              <div class="k">{{ t('Section','শাখা') }}</div><div class="v">{{ $section_name ?: '' }}</div>
              <div class="k">{{ t('ID','আইডি') }}</div><div class="v no-bn">{{ $stu->student_id ?? $stu->id }}</div>
              <div class="k">{{ t('Group','গ্রুপ') }}</div><div class="v">{{ $group !== '' ? $group : '-' }}</div>
            </div>
          </div>
          <div class="panel">
            <div class="ttl">{{ t('Exam Schedule','পরীক্ষার সময়সূচী') }}</div>
            <table>
              <thead><tr><th>#</th><th>{{ t('Date','তারিখ') }}</th><th>{{ t('Day','বার') }}</th><th class="code">{{ t('Subject Code','বিষয় কোড') }}</th><th>{{ t('Subject','বিষয়') }}</th><th>{{ t('Time','সময়') }}</th></tr></thead>
              <tbody>
              <?php $sn=1; foreach ($sched_for_student as $row): ?>
                <tr>
                  <td><span class="num">{{ bnNum($sn++) }}</span></td>
                  <td><span class="num">{{ bnNum(fmt_date($row->exam_date ?? null)) }}</span></td>
                  <td><?php $d=$row->exam_date??null; echo $d? (request('lang','bn')==='bn' ? $bnDays[intval(date('w', strtotime($d)))] : date('l', strtotime($d))):'-'; ?></td>
                  <td class="code num">{{ bnNum($row->subject_code ?? '') }}</td>
                  <td>{{ t($row->subject_name ?? '', $row->subject_bangla_name ?? ($row->subject_name ?? '')) }}</td>
                  <td><span class="num">{{ bnNum(fmt_time($row->exam_time ?? null)) }}</span></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="foot">
            <div class="note">
            <strong>{{ t('Exam Instructions','পরীক্ষার নির্দেশাবলী') }}:</strong>
            <ul>
              <li>{{ t('Arrive at the exam hall 30 minutes before the start of the exam.','পরীক্ষা শুরুর ৩০ মিনিট আগে পরীক্ষা কক্ষে উপস্থিত হতে হবে।') }}</li>
              <li>{{ t('Entry to the exam centre without this admit card is prohibited.','প্রবেশপত্র ছাড়া পরীক্ষা কেন্দ্রে প্রবেশ করা যাবে না।') }}</li>
              <li>{{ t('Mobile phones or electronic devices are not allowed during the exam.','পরীক্ষার সময় মোবাইল ফোন বা ইলেকট্রনিক ডিভাইস ব্যবহার করা যাবে না।') }}</li>
              <li>{{ t('Cheating in the exam hall is strictly forbidden.','পরীক্ষার হলে নকল করা সম্পূর্ণ নিষিদ্ধ।') }}</li>
              <li>{{ t('Write name, roll number etc. correctly on the question and answer scripts.','প্রশ্নপত্র ও উত্তরপত্রে নাম, রোল নং ইত্যাদি সঠিকভাবে লিখতে হবে।') }}</li>
            </ul>
            <div class="sigs">
              <div class="sig sig-teacher">
                <div class="sig-space"></div>
                <div class="sign">{{ t('Class Teacher','শ্রেণি শিক্ষক') }}</div>
              </div>
              <div class="sig sig-hm">
                <div class="sig-space hm">
                  @if($principalTeacher && $principalTeacher->signature)
                    <img src="{{ asset('storage/' . $principalTeacher->signature) }}" alt="Signature">
                  @endif
                </div>
                <div class="sign">{{ t('Principal / Head Teacher','অধ্যক্ষ / প্রধান শিক্ষক') }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="brandbar">কারিগরি সহযোগীতায়ঃ বাতিঘর কম্পিউটার’স</div>
    </div>
<?php endforeach; ?>
  </div>
@endsection
