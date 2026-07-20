@extends('layouts.admin')
@section('title','প্রতিষ্ঠান প্রধান ড্যাশবোর্ড')
@section('content')
@php
  $user = auth()->user();
  $school = $user->primarySchool();

  $hasModule = fn($slug) => $school && $user->hasModule($slug);

  $routeOrNull = function($name, ...$params) {
    return \Illuminate\Support\Facades\Route::has($name) ? route($name, $params) : null;
  };

  $links = [
    'attendanceDashboard' => $school && $hasModule('attendance') ? $routeOrNull('principal.institute.attendance.dashboard', $school->id) : null,
    'lessonEvaluations'   => $school && $hasModule('lesson_evaluation') ? $routeOrNull('principal.institute.lesson-evaluations.index', $school->id) : null,
    'teacherAttendance'   => $school ? $routeOrNull('principal.institute.teacher-attendance.reports.daily', $school->id) : null,
  ];

  $quickLinkDefs = [
    ['label' => 'উপস্থিতি ড্যাশবোর্ড', 'icon' => 'fas fa-calendar-check', 'module' => 'attendance', 'route' => 'principal.institute.attendance.dashboard'],
    ['label' => 'দৈনিক উপস্থিতি রিপোর্ট', 'icon' => 'fas fa-file-alt', 'module' => 'attendance', 'route' => 'principal.institute.attendance.daily_report'],
    ['label' => 'মাসিক উপস্থিতি রিপোর্ট', 'icon' => 'fas fa-calendar-alt', 'module' => 'attendance', 'route' => 'principal.institute.attendance.monthly_report'],
    ['label' => 'শিক্ষক উপস্থিতি রিপোর্ট', 'icon' => 'fas fa-user-clock', 'module' => null, 'route' => 'principal.institute.teacher-attendance.reports.daily'],
    ['label' => 'কর্মচারী তালিকা', 'icon' => 'fas fa-id-badge', 'module' => null, 'route' => 'principal.institute.staff.index'],
    ['label' => 'ক্লাস রুটিন', 'icon' => 'fas fa-clock', 'module' => 'routine', 'route' => 'principal.institute.routine.panel'],
    ['label' => 'পাঠ মূল্যায়ন রিপোর্ট', 'icon' => 'fas fa-clipboard-check', 'module' => 'lesson_evaluation', 'route' => 'principal.institute.lesson-evaluations.index'],
    ['label' => 'পাঠ মূল্যায়ন এন্ট্রি রিপোর্ট', 'icon' => 'fas fa-list-check', 'module' => 'lesson_evaluation', 'route' => 'principal.institute.lesson-evaluations.entry-report'],
    ['label' => 'পরীক্ষা তালিকা', 'icon' => 'fas fa-file-signature', 'module' => 'exams', 'route' => 'principal.institute.exams.index'],
    ['label' => 'মার্ক এন্ট্রি', 'icon' => 'fas fa-pen', 'module' => 'exams', 'route' => 'principal.institute.marks.index'],
    ['label' => 'ফলাফল তালিকা', 'icon' => 'fas fa-poll', 'module' => 'results', 'route' => 'principal.institute.results.exams'],
    ['label' => 'মার্কশিট', 'icon' => 'fas fa-id-card', 'module' => 'results', 'route' => 'principal.institute.results.marksheet'],
    ['label' => 'ফি সংগ্রহ', 'icon' => 'fas fa-money-bill-wave', 'module' => 'accounts', 'route' => 'billing.collect'],
    ['label' => 'ফি রিপোর্ট', 'icon' => 'fas fa-chart-pie', 'module' => 'accounts', 'route' => 'billing.reports'],
    ['label' => 'নোটিশ বোর্ড', 'icon' => 'fas fa-bullhorn', 'module' => 'notices', 'route' => 'principal.institute.notices'],
    ['label' => 'শিক্ষক তালিকা', 'icon' => 'fas fa-user-tie', 'module' => null, 'route' => 'principal.institute.teachers.index'],
    ['label' => 'শিক্ষার্থী তালিকা', 'icon' => 'fas fa-user-graduate', 'module' => null, 'route' => 'principal.institute.students.index'],
  ];

  $quickLinks = collect($quickLinkDefs)
    ->filter(fn($l) => $school && (!$l['module'] || $hasModule($l['module'])))
    ->map(function($l) use ($routeOrNull, $school) {
      $needsSchool = !str_starts_with($l['route'], 'billing.');
      $href = $needsSchool ? $routeOrNull($l['route'], $school->id) : $routeOrNull($l['route']);
      return $href ? ['label' => $l['label'], 'icon' => $l['icon'], 'href' => $href] : null;
    })
    ->filter()
    ->values();
@endphp

<div id="app">
  <principal-dashboard
    :quick-links='@json($quickLinks)'
    :links='@json($links)'
  ></principal-dashboard>
</div>
@endsection
