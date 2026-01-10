@extends('layouts.print')
@php
  $lang = $lang ?? request('lang','bn');
  $labels = $labels ?? [];
  $cols = is_array($cols ?? null) ? $cols : [];
  $yearLabel = $yearLabel ?? '';
  // Header titles
  $printTitle = $lang==='bn' ? 'শিক্ষার্থী তালিকা' : 'Student List';
  $filtersSummary = [];
  if (request('year_id')) { $filtersSummary[] = ($lang==='bn' ? 'বর্ষ: ' : 'Year: ') . ($yearLabel ?: '-'); }
  if (request('class_id')) {
    $class = optional($school->classes)->firstWhere('id', (int)request('class_id'));
    $filtersSummary[] = ($lang==='bn' ? 'শ্রেণি: ' : 'Class: ') . ($class->name ?? request('class_id'));
  }
  if (request('section_id')) {
    $section = optional($school->sections)->firstWhere('id', (int)request('section_id'));
    $filtersSummary[] = ($lang==='bn' ? 'শাখা: ' : 'Section: ') . ($section->name ?? request('section_id'));
  }
  if (request('group_id')) {
    $group = optional($school->groups)->firstWhere('id', (int)request('group_id'));
    $filtersSummary[] = ($lang==='bn' ? 'গ্রুপ: ' : 'Group: ') . ($group->name ?? request('group_id'));
  }
  if (request('status')) { $filtersSummary[] = ($lang==='bn' ? 'স্ট্যাটাস: ' : 'Status: ') . request('status'); }
  if (request('gender')) { $filtersSummary[] = ($lang==='bn' ? 'লিঙ্গ: ' : 'Gender: ') . request('gender'); }
  if (request('religion')) { $filtersSummary[] = ($lang==='bn' ? 'ধর্ম: ' : 'Religion: ') . request('religion'); }
  if (request('village')) { $filtersSummary[] = ($lang==='bn' ? 'গ্রাম: ' : 'Village: ') . request('village'); }
  $printSubtitle = implode(' | ', $filtersSummary);
@endphp

@push('print_head')
<style>
  table.print-table{width:100%;border-collapse:collapse;margin-top:8px;}
  .print-table th,.print-table td{border:1px solid #444;padding:6px 8px;font-size:13px;vertical-align:top}
  .print-table thead th{background:#f0f0f0;font-weight:700}
  .w-serial{width:50px}
  .w-id{width:110px}
  .w-roll{width:70px}
  .w-class,.w-sec,.w-group{width:90px}
  .w-status{width:90px}
  .photo-cell img{width:32px;height:32px;object-fit:cover;border-radius:6%}
  .small{font-size:12px}
  .muted{color:#555}
  .nowrap{white-space:nowrap}
</style>
@endpush

@section('title', $printTitle)

@section('content')
  <div>
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
              @case('mobile')
                <th>{{ $labels['mobile'] ?? 'Mobile' }}</th>
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
                  <td>{{ $idx + 1 }}</td>
                  @break
                @case('student_id')
                  <td class="nowrap">{{ $stu->student_id }}</td>
                  @break
                @case('name')
                  <td>{{ $lang==='bn' ? ($stu->student_name_bn ?: $stu->full_name ?? $stu->student_name_en) : ($stu->student_name_en ?: $stu->full_name ?? $stu->student_name_bn) }}</td>
                  @break
                @case('father')
                  <td>{{ $lang==='bn' ? ($stu->father_name_bn ?: $stu->father_name) : ($stu->father_name ?: $stu->father_name_bn) }}</td>
                  @break
                @case('class')
                  <td>{{ $en? $en->class?->name : '-' }}</td>
                  @break
                @case('section')
                  <td>{{ $en? $en->section?->name : '-' }}</td>
                  @break
                @case('roll')
                  <td class="nowrap">{{ $en? $en->roll_no : '-' }}</td>
                  @break
                @case('group')
                  <td>{{ $en? $en->group?->name : '-' }}</td>
                  @break
                @case('mobile')
                  <td class="nowrap">{{ $stu->guardian_phone }}</td>
                  @break
                @case('status')
                  <td>{{ $stu->status }}</td>
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
      {{ $lang==='bn' ? 'মোট শিক্ষার্থী: ' : 'Total Students: ' }} {{ $students->count() }}
    </div>
