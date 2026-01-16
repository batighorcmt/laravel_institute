@extends('layouts.print')
@php
  $lang = $lang ?? request('lang','bn');
  $labels = $labels ?? [];
  $cols = is_array($cols ?? null) ? $cols : [];
  $yearLabel = $yearLabel ?? '';
  
  // Function to convert English numbers to Bengali
  function toBengaliNumber($number) {
    $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $bengaliDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    return str_replace($englishDigits, $bengaliDigits, (string)$number);
  }
  
  // Header titles
  $printTitle = $lang==='bn' ? 'শিক্ষার্থী তালিকা' : 'Student List';
  $filtersSummary = [];
  if (request('year_id')) { 
    $yearText = $yearLabel ?: '-';
    $filtersSummary[] = ($lang==='bn' ? 'বর্ষ: ' : 'Year: ') . ($lang==='bn' ? toBengaliNumber($yearText) : $yearText); 
  }
  if (request('class_id')) {
    $class = optional($school->classes)->firstWhere('id', (int)request('class_id'));
    $className = $class->name ?? request('class_id');
    $filtersSummary[] = ($lang==='bn' ? 'শ্রেণি: ' : 'Class: ') . $className;
  }
  if (request('section_id')) {
    $section = optional($school->sections)->firstWhere('id', (int)request('section_id'));
    $sectionName = $section->name ?? request('section_id');
    $filtersSummary[] = ($lang==='bn' ? 'শাখা: ' : 'Section: ') . $sectionName;
  }
  if (request('group_id')) {
    $group = optional($school->groups)->firstWhere('id', (int)request('group_id'));
    $groupName = $group->name ?? request('group_id');
    $filtersSummary[] = ($lang==='bn' ? 'গ্রুপ: ' : 'Group: ') . $groupName;
  }
  if (request('status')) { 
    $statusValue = request('status');
    if ($lang==='bn') {
      $statusValue = $statusValue === 'active' ? 'সক্রিয়' : ($statusValue === 'inactive' ? 'নিষ্ক্রিয়' : ($statusValue === 'graduated' ? 'গ্র্যাজুয়েট' : ($statusValue === 'transferred' ? 'ট্রান্সফার্ড' : $statusValue)));
    }
    $filtersSummary[] = ($lang==='bn' ? 'স্ট্যাটাস: ' : 'Status: ') . $statusValue; 
  }
  if (request('gender')) { 
    $genderValue = request('gender');
    if ($lang==='bn') {
      $genderValue = $genderValue === 'male' ? 'পুরুষ' : ($genderValue === 'female' ? 'মহিলা' : $genderValue);
    }
    $filtersSummary[] = ($lang==='bn' ? 'লিঙ্গ: ' : 'Gender: ') . $genderValue; 
  }
  if (request('religion')) { 
    $religionValue = request('religion');
    if ($lang==='bn') {
      $religionValue = $religionValue === 'Islam' || $religionValue === 'islam' ? 'ইসলাম' : ($religionValue === 'Hindu' || $religionValue === 'hindu' ? 'হিন্দু' : ($religionValue === 'Buddhist' || $religionValue === 'buddhist' ? 'বৌদ্ধ' : ($religionValue === 'Christian' || $religionValue === 'christian' ? 'খ্রিস্টান' : 'অন্যান্য')));
    }
    $filtersSummary[] = ($lang==='bn' ? 'ধর্ম: ' : 'Religion: ') . $religionValue; 
  }
  if (request('village')) { $filtersSummary[] = ($lang==='bn' ? 'গ্রাম: ' : 'Village: ') . request('village'); }
  $printSubtitle = implode(' | ', $filtersSummary);
@endphp

@push('print_head')
<style>
  @media print {
    @page {
      size: {{ request('orientation', 'portrait') === 'landscape' ? 'landscape' : 'portrait' }};
      margin: 10mm;
    }
    body {
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
      color-adjust: exact !important;
    }
  }
  
  table.print-table{width:100%;border-collapse:collapse;margin-top:8px;}
  .print-table th,.print-table td{border:1px solid #000 !important;padding:6px 8px;font-size:13px;vertical-align:top}
  .print-table thead th{background:#f0f0f0 !important;font-weight:700;color:#000 !important}
  .w-serial{width:50px}
  .w-id{width:110px}
  .w-roll{width:70px}
  .w-class,.w-sec,.w-group{width:90px}
  .w-status{width:90px}
  .w-date{width:100px}
  .w-gender{width:70px}
  .w-blood{width:80px}
  .photo-cell img{width:60px;height:60px;object-fit:cover;border-radius:6%}
  .small{font-size:12px}
  .muted{color:#555 !important}
  .nowrap{white-space:nowrap}
  
  /* Print controls */
  .print-controls {
    margin-bottom: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
  }
  
  @media print {
    .print-controls {
      display: none;
    }
  }
</style>
@endpush

@section('title', $printTitle)

@section('content')
  <div>
    <div class="print-controls">
      <button onclick="window.print()" class="btn btn-primary">
        <i class="fas fa-print"></i> প্রিন্ট করুন
      </button>
      <button onclick="setOrientation('portrait')" class="btn btn-secondary">
        <i class="fas fa-file"></i> Portrait
      </button>
      <button onclick="setOrientation('landscape')" class="btn btn-secondary">
        <i class="fas fa-file-alt"></i> Landscape
      </button>
    </div>

    <table class="print-table">
      <thead>
        <tr>
          @foreach($cols as $key)
            @switch($key)
              @case('serial')
                <th class="w-serial">{{ $labels['serial'] ?? 'Serial' }}</th>
                @break
              @case('student_id')
                <th class="w-id">{{ $labels['student_id'] ?? 'Student ID' }}</th>
                @break
              @case('name')
                <th>{{ $labels['name'] ?? 'Name' }}</th>
                @break
              @case('father')
                <th>{{ $labels['father'] ?? "Father's Name" }}</th>
                @break
              @case('mother')
                <th>{{ $labels['mother'] ?? "Mother's Name" }}</th>
                @break
              @case('date_of_birth')
                <th class="w-date">{{ $labels['date_of_birth'] ?? 'Date of Birth' }}</th>
                @break
              @case('gender')
                <th class="w-gender">{{ $labels['gender'] ?? 'Gender' }}</th>
                @break
              @case('religion')
                <th>{{ $labels['religion'] ?? 'Religion' }}</th>
                @break
              @case('blood_group')
                <th class="w-blood">{{ $labels['blood_group'] ?? 'Blood Group' }}</th>
                @break
              @case('class')
                <th class="w-class">{{ $labels['class'] ?? 'Class' }}</th>
                @break
              @case('section')
                <th class="w-sec">{{ $labels['section'] ?? 'Section' }}</th>
                @break
              @case('roll')
                <th class="w-roll">{{ $labels['roll'] ?? 'Roll' }}</th>
                @break
              @case('group')
                <th class="w-group">{{ $labels['group'] ?? 'Group' }}</th>
                @break
              @case('guardian_name')
                <th>{{ $labels['guardian_name'] ?? 'Guardian Name' }}</th>
                @break
              @case('guardian_relation')
                <th>{{ $labels['guardian_relation'] ?? 'Guardian Relation' }}</th>
                @break
              @case('mobile')
                <th>{{ $labels['mobile'] ?? 'Mobile' }}</th>
                @break
              @case('present_village')
                <th>{{ $labels['present_village'] ?? 'Present Village' }}</th>
                @break
              @case('present_para_moholla')
                <th>{{ $labels['present_para_moholla'] ?? 'Present Para/Moholla' }}</th>
                @break
              @case('present_post_office')
                <th>{{ $labels['present_post_office'] ?? 'Present Post Office' }}</th>
                @break
              @case('present_upazilla')
                <th>{{ $labels['present_upazilla'] ?? 'Present Upazilla' }}</th>
                @break
              @case('present_district')
                <th>{{ $labels['present_district'] ?? 'Present District' }}</th>
                @break
              @case('permanent_village')
                <th>{{ $labels['permanent_village'] ?? 'Permanent Village' }}</th>
                @break
              @case('permanent_para_moholla')
                <th>{{ $labels['permanent_para_moholla'] ?? 'Permanent Para/Moholla' }}</th>
                @break
              @case('permanent_post_office')
                <th>{{ $labels['permanent_post_office'] ?? 'Permanent Post Office' }}</th>
                @break
              @case('permanent_upazilla')
                <th>{{ $labels['permanent_upazilla'] ?? 'Permanent Upazilla' }}</th>
                @break
              @case('permanent_district')
                <th>{{ $labels['permanent_district'] ?? 'Permanent District' }}</th>
                @break
              @case('admission_date')
                <th class="w-date">{{ $labels['admission_date'] ?? 'Admission Date' }}</th>
                @break
              @case('previous_school')
                <th>{{ $labels['previous_school'] ?? 'Previous School' }}</th>
                @break
              @case('pass_year')
                <th>{{ $labels['pass_year'] ?? 'Pass Year' }}</th>
                @break
              @case('previous_result')
                <th>{{ $labels['previous_result'] ?? 'Previous Result' }}</th>
                @break
              @case('status')
                <th class="w-status">{{ $labels['status'] ?? 'Status' }}</th>
                @break
              @case('photo')
                <th>{{ $labels['photo'] ?? 'Photo' }}</th>
                @break
              @case('subjects')
                <th>{{ ($labels['subjects'] ?? 'Subjects') . ($yearLabel ? " (" . $yearLabel . ")" : "") }}</th>
                @break
            @endswitch
          @endforeach
        </tr>
      </thead>
      <tbody>
        @foreach($students as $idx=>$stu)
          @php
            $en = $stu->enrollments->first();
            // Build subjects HTML similar to index page
            $subsHtml = '';
            if ($en) {
              $subs = collect($en->subjects);
              $subsSorted = $subs->sortBy(function($ss){
                $code = optional($ss->subject)->code;
                $num  = $code ? intval(preg_replace('/\D+/', '', $code)) : PHP_INT_MAX;
                return [ $ss->is_optional ? 1 : 0, $num, $code ];
              })->values();
              $parts = [];
              foreach ($subsSorted as $ss) {
                $code = optional($ss->subject)->code;
                if (!$code) { continue; }
                if ($ss->is_optional) {
                  $parts[] = '<span class="muted">'.e($code).'</span>';
                } else {
                  $parts[] = e($code);
                }
              }
              $subsHtml = implode(', ', $parts);
            }
          @endphp
          <tr>
            @foreach($cols as $key)
              @switch($key)
                @case('serial')
                  <td>{{ $lang==='bn' ? toBengaliNumber($idx + 1) : ($idx + 1) }}</td>
                  @break
                @case('student_id')
                  <td class="nowrap">{{ $stu->student_id }}</td>
                  @break
                @case('name')
                  <td>{{ $lang==='bn' ? ($stu->student_name_bn ?: ($stu->full_name ?? $stu->student_name_en)) : ($stu->student_name_en ?: ($stu->full_name ?? $stu->student_name_bn)) }}</td>
                  @break
                @case('father')
                  <td>{{ $lang==='bn' ? ($stu->father_name_bn ?: $stu->father_name) : ($stu->father_name ?: $stu->father_name_bn) }}</td>
                  @break
                @case('mother')
                  <td>{{ $lang==='bn' ? ($stu->mother_name_bn ?: $stu->mother_name) : ($stu->mother_name ?: $stu->mother_name_bn) }}</td>
                  @break
                @case('date_of_birth')
                  <td class="nowrap">{{ $stu->date_of_birth ? ($lang==='bn' ? toBengaliNumber($stu->date_of_birth->format('d-m-Y')) : $stu->date_of_birth->format('d-m-Y')) : '-' }}</td>
                  @break
                @case('gender')
                  <td>{{ $stu->gender ? ($lang==='bn' ? ($stu->gender === 'male' ? 'পুরুষ' : 'মহিলা') : ucfirst($stu->gender)) : '-' }}</td>
                  @break
                @case('religion')
                  <td>{{ $stu->religion ? ($lang==='bn' ? ($stu->religion === 'Islam' || $stu->religion === 'islam' ? 'ইসলাম' : ($stu->religion === 'Hindu' || $stu->religion === 'hindu' ? 'হিন্দু' : ($stu->religion === 'Buddhist' || $stu->religion === 'buddhist' ? 'বৌদ্ধ' : ($stu->religion === 'Christian' || $stu->religion === 'christian' ? 'খ্রিস্টান' : 'অন্যান্য')))) : $stu->religion) : '-' }}</td>
                  @break
                @case('blood_group')
                  <td>{{ $stu->blood_group ?: '-' }}</td>
                  @break
                @case('class')
                  <td>{{ $en? $en->class?->name : '-' }}</td>
                  @break
                @case('section')
                  <td>{{ $en? $en->section?->name : '-' }}</td>
                  @break
                @case('roll')
                  <td class="nowrap">{{ $en ? ($lang==='bn' ? toBengaliNumber($en->roll_no) : $en->roll_no) : '-' }}</td>
                  @break
                @case('group')
                  <td>{{ $en? $en->group?->name : '-' }}</td>
                  @break
                @case('guardian_name')
                  <td>{{ ($lang==='bn' ? ($stu->guardian_name_bn ?: $stu->guardian_name_en) : ($stu->guardian_name_en ?: $stu->guardian_name_bn)) ?: '-' }}</td>
                  @break
                @case('guardian_relation')
                  <td>{{ $stu->guardian_relation ? ($lang==='bn' ? ($stu->guardian_relation === 'father' ? 'পিতা' : ($stu->guardian_relation === 'mother' ? 'মাতা' : 'অন্যান্য')) : ucfirst($stu->guardian_relation)) : '-' }}</td>
                  @break
                @case('mobile')
                  <td class="nowrap">{{ $stu->guardian_phone ? ($lang==='bn' ? toBengaliNumber($stu->guardian_phone) : $stu->guardian_phone) : '-' }}</td>
                  @break
                @case('present_village')
                  <td>{{ $stu->present_village ?: '-' }}</td>
                  @break
                @case('present_para_moholla')
                  <td>{{ $stu->present_para_moholla ?: '-' }}</td>
                  @break
                @case('present_post_office')
                  <td>{{ $stu->present_post_office ?: '-' }}</td>
                  @break
                @case('present_upazilla')
                  <td>{{ $stu->present_upazilla ?: '-' }}</td>
                  @break
                @case('present_district')
                  <td>{{ $stu->present_district ?: '-' }}</td>
                  @break
                @case('permanent_village')
                  <td>{{ $stu->permanent_village ?: '-' }}</td>
                  @break
                @case('permanent_para_moholla')
                  <td>{{ $stu->permanent_para_moholla ?: '-' }}</td>
                  @break
                @case('permanent_post_office')
                  <td>{{ $stu->permanent_post_office ?: '-' }}</td>
                  @break
                @case('permanent_upazilla')
                  <td>{{ $stu->permanent_upazilla ?: '-' }}</td>
                  @break
                @case('permanent_district')
                  <td>{{ $stu->permanent_district ?: '-' }}</td>
                  @break
                @case('admission_date')
                  <td class="nowrap">{{ $stu->admission_date ? ($lang==='bn' ? toBengaliNumber($stu->admission_date->format('d-m-Y')) : $stu->admission_date->format('d-m-Y')) : '-' }}</td>
                  @break
                @case('previous_school')
                  <td>{{ $stu->previous_school ?: '-' }}</td>
                  @break
                @case('pass_year')
                  <td>{{ $stu->pass_year ?: '-' }}</td>
                  @break
                @case('previous_result')
                  <td>{{ $stu->previous_result ?: '-' }}</td>
                  @break
                @case('status')
                  <td>{{ $lang==='bn' ? ($stu->status === 'active' ? 'সক্রিয়' : ($stu->status === 'inactive' ? 'নিষ্ক্রিয়' : ($stu->status === 'graduated' ? 'গ্র্যাজুয়েট' : ($stu->status === 'transferred' ? 'ট্রান্সফার্ড' : $stu->status)))) : ucfirst($stu->status) }}</td>
                  @break
                @case('photo')
                  <td class="photo-cell">
                    @if(!empty($stu->photo_url))
                      <img src="{{ $stu->photo_url }}" alt="photo">
                    @endif
                  </td>
                  @break
                @case('subjects')
                  <td class="small">{!! $subsHtml ?: '-' !!}</td>
                  @break
              @endswitch
            @endforeach
          </tr>
        @endforeach
      </tbody>
    </table>
    <div class="small muted" style="margin-top:6px;">
      {{ $lang==='bn' ? 'মোট শিক্ষার্থী: ' : 'Total Students: ' }} {{ $lang==='bn' ? toBengaliNumber($students->count()) : $students->count() }}
    </div>
    
    <script>
      function setOrientation(orientation) {
        const url = new URL(window.location.href);
        url.searchParams.set('orientation', orientation);
        window.location.href = url.toString();
      }
    </script>
  </div>
@endsection
