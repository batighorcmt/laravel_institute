@extends('layouts.admin')
@section('title','প্রতিষ্ঠান প্রধান ড্যাশবোর্ড')
@section('content')
@php
  $user = Auth::user();
  $schools = $user->getSchoolsForRole(\App\Models\Role::PRINCIPAL);
  $school = $schools->first();
  // Academic Year selection
  $academicYears = $school ? \App\Models\AcademicYear::where('school_id',$school->id)->orderByDesc('id')->get() : collect();
  $currentAy = $school ? \App\Models\AcademicYear::forSchool($school->id)->current()->first() : null;
  $ayId = $currentAy?->id;
  // Core counts (guard models that may not exist)
  $teacherCount = $school ? \App\Models\Teacher::where('school_id',$school->id)->where('status','active')->count() : 0;
  $classCount = $school ? (class_exists(\App\Models\ClassSubject::class) ? \App\Models\ClassSubject::where('school_id',$school->id)->distinct('class_id')->count('class_id') : 0) : 0;
  $studentCount = 0;
  if ($school && class_exists(\App\Models\StudentEnrollment::class)) {
    // count distinct students with an active enrollment for the selected academic year
    $studentCount = \App\Models\StudentEnrollment::where('school_id',$school->id)
      ->when($ayId, fn($q)=>$q->where('academic_year_id',$ayId))
      ->where('status','active')
      ->distinct('student_id')
      ->count('student_id');
  } elseif ($school && class_exists(\App\Models\Student::class)) {
    $studentCount = \App\Models\Student::where('school_id',$school->id)->where('status','active')->where('academic_year_id',$ayId)->distinct('student_id')->count('student_id');
  }
  // Today attendance (students)
  $today = now()->toDateString();
  $attPresent = 0; $attAbsent = 0;
  if ($school && class_exists(\App\Models\Attendance::class)) {
    // attendance table has no school_id; filter via enrolled students belonging to this school
    if (class_exists(\App\Models\StudentEnrollment::class) && class_exists(\App\Models\Student::class)) {
      $enrolledStudentIds = \App\Models\StudentEnrollment::where('school_id',$school->id)
        ->when($ayId, fn($q)=>$q->where('academic_year_id',$ayId))
        ->where('status','active')
        ->pluck('student_id');
      $attPresent = \App\Models\Attendance::whereIn('student_id',$enrolledStudentIds)->whereDate('date',$today)->where('status','present')->count();
      $attAbsent  = \App\Models\Attendance::whereIn('student_id',$enrolledStudentIds)->whereDate('date',$today)->where('status','absent')->count();
    } elseif (class_exists(\App\Models\Student::class)) {
      $studentIds = \App\Models\Student::where('school_id',$school->id)->where('status','active')->pluck('id');
      $attPresent = \App\Models\Attendance::whereIn('student_id',$studentIds)->whereDate('date',$today)->where('status','present')->count();
      $attAbsent  = \App\Models\Attendance::whereIn('student_id',$studentIds)->whereDate('date',$today)->where('status','absent')->count();
    } else {
      // Fallback: count global attendance for today
      $attPresent = \App\Models\Attendance::whereDate('date',$today)->where('status','present')->count();
      $attAbsent  = \App\Models\Attendance::whereDate('date',$today)->where('status','absent')->count();
    }
  }
  $attRate = $studentCount > 0 ? round(($attPresent / $studentCount) * 100, 1) : 0;
  // Teacher attendance today (optional models)
  $tPresent = null; $tAbsent = null;
  if ($school) {
    $teacherUserIds = \App\Models\Teacher::where('school_id',$school->id)->where('status','active')->pluck('user_id');

    // Prefer the explicit TeacherAttendance model (if available)
    if (class_exists(\App\Models\TeacherAttendance::class)) {
      try {
        $tPresent = \App\Models\TeacherAttendance::where('school_id',$school->id)->whereDate('date',$today)->where('status','present')->count();
        $tAbsent  = \App\Models\TeacherAttendance::where('school_id',$school->id)->whereDate('date',$today)->where('status','absent')->count();
      } catch (\Throwable $e) {
        \Log::warning('TeacherAttendance count failed: '.$e->getMessage());
        $tPresent = 0; $tAbsent = 0;
      }

    // If TeacherAttendance not present, allow ExtraClassAttendance only when it can be filtered by teacher user ids
    } elseif (class_exists(\App\Models\ExtraClassAttendance::class)) {
      try {
        if (!empty($teacherUserIds)) {
          // ExtraClassAttendance typically does not have user_id; only use if it does
          if (method_exists(\App\Models\ExtraClassAttendance::class, 'whereIn') && \Schema::hasColumn('extra_class_attendances', 'user_id')) {
            $tPresent = \App\Models\ExtraClassAttendance::whereIn('user_id',$teacherUserIds)->whereDate('date',$today)->where('status','present')->count();
            $tAbsent  = \App\Models\ExtraClassAttendance::whereIn('user_id',$teacherUserIds)->whereDate('date',$today)->where('status','absent')->count();
          } else {
            // Do not do a global fallback; set zeros to avoid misleading counts
            $tPresent = 0; $tAbsent = 0;
          }
        } else {
          $tPresent = 0; $tAbsent = 0;
        }
      } catch (\Throwable $e) {
        \Log::warning('ExtraClassAttendance teacher-count attempt failed: '.$e->getMessage());
        $tPresent = 0; $tAbsent = 0;
      }
    }
  }

  $tRate = ($tPresent !== null && $tAbsent !== null && ($tPresent + $tAbsent) > 0) ? round(($tPresent / ($tPresent + $tAbsent)) * 100, 1) : null;
  // Gender distribution (students)
  $maleCount = null; $femaleCount = null;
  if ($school && class_exists(\App\Models\StudentEnrollment::class) && class_exists(\App\Models\Student::class)) {
    $enrollQ = \App\Models\StudentEnrollment::where('school_id',$school->id)->when($ayId, fn($q)=>$q->where('academic_year_id',$ayId))->where('status','active');
    $studentIdsForAy = $enrollQ->pluck('student_id');
    $maleCount = \App\Models\Student::whereIn('id',$studentIdsForAy)->where('status','active')->where('gender','male')->count();
    $femaleCount = \App\Models\Student::whereIn('id',$studentIdsForAy)->where('status','active')->where('gender','female')->count();
  } elseif ($school && class_exists(\App\Models\Student::class)) {
  $maleCount = \App\Models\Student::where('school_id',$school->id)->where('status','active')->where('gender','male')->count();
  $femaleCount = \App\Models\Student::where('school_id',$school->id)->where('status','active')->where('gender','female')->count();
  }
  // Fees (payments summary)
  $feesToday = null; $feesMonth = null;
  if ($school && class_exists(\App\Models\AdmissionPayment::class) && class_exists(\App\Models\AdmissionApplication::class)) {
    $feesToday = \App\Models\AdmissionPayment::whereHas('application', function($q) use ($school){ $q->where('school_id', $school->id); })
      ->whereDate('created_at',$today)->where('status','paid')->sum('amount');
    $feesMonth = \App\Models\AdmissionPayment::whereHas('application', function($q) use ($school){ $q->where('school_id', $school->id); })
      ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('status','paid')->sum('amount');
  }
@endphp

<style>
.dashboard-wrap{display:flex;flex-direction:column;gap:16px}
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}
.kpi{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px;box-shadow:0 4px 12px rgba(0,0,0,.04)}
.kpi .label{color:#6b7280;font-size:14px}
.kpi .value{font-size:28px;font-weight:800;color:#111;}
.kpi .meta{font-size:12px;color:#6b7280;margin-top:4px}
.section{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px}
.section h3{font-size:18px;margin:0 0 10px 0}
.table{width:100%;border-collapse:separate;border-spacing:0 8px}
.table th{font-weight:700;color:#374151}
.table td, .table th{padding:8px 10px;border-bottom:1px solid #eee}
.header{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
.header .title{font-size:22px;font-weight:800}
.header .school{color:#6b7280}
.quick-links{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-top:6px}
.quick-links a{display:flex;align-items:center;gap:8px;padding:12px;border:1px solid #e5e7eb;border-radius:10px;text-decoration:none;color:#111;background:#fafafa}
.quick-links a:hover{background:#f1f5f9}
</style>

<div class="dashboard-wrap">
  <div class="header">
    <div class="title">ড্যাশবোর্ড</div>
    @if($currentAy)
      <div class="school" style="font-weight:700;">শিক্ষাবর্ষ: {{ $currentAy->name }}</div>
    @endif
  </div>

  <div class="kpi-grid">
    <div class="kpi"><div class="label">মোট শ্রেণি</div><div class="value">{{ $classCount }}</div><div class="meta">শ্রেণি কনফিগারেশন</div></div>
    <div class="kpi"><div class="label">মোট শিক্ষার্থী</div><div class="value">{{ $studentCount }}</div><div class="meta">ভর্তি/নিবন্ধিত</div></div>
    <div class="kpi"><div class="label">আজ উপস্থিত</div><div class="value">{{ $attPresent }}</div><div class="meta">শিক্ষার্থী</div></div>
    <div class="kpi"><div class="label">আজ অনুপস্থিত</div><div class="value">{{ $attAbsent }}</div><div class="meta">শিক্ষার্থী</div></div>
    <div class="kpi"><div class="label">উপস্থিতির হার</div><div class="value">{{ $attRate }}%</div><div class="meta">শিক্ষার্থী</div></div>
    @if($tPresent !== null)
      <div class="kpi"><div class="label">মোট শিক্ষক</div><div class="value">{{ $teacherCount }}</div><div class="meta">নিবন্ধিত শিক্ষক সংখ্যা</div></div>
      <div class="kpi"><div class="label">শিক্ষক উপস্থিত</div><div class="value">{{ $tPresent }}</div><div class="meta">আজ</div></div>
      <div class="kpi"><div class="label">শিক্ষক অনুপস্থিত</div><div class="value">{{ $tAbsent }}</div><div class="meta">আজ</div></div>
      <div class="kpi"><div class="label">শিক্ষক উপস্থিতির হার</div><div class="value">{{ $tRate }}%</div><div class="meta">আজ</div></div>
    @endif
    @if($feesToday !== null)
      <div class="kpi"><div class="label">আজকের ফিস</div><div class="value">{{ number_format($feesToday,2) }}</div><div class="meta">পরিশোধিত</div></div>
    @endif
    @if($feesMonth !== null)
      <div class="kpi"><div class="label">মাসিক ফিস</div><div class="value">{{ number_format($feesMonth,2) }}</div><div class="meta">পরিশোধিত</div></div>
    @endif
  </div>

  <div class="section">
    <h3>শ্রেণি ও শাখা ভিত্তিক উপস্থিতি/অনুপস্থিতি</h3>
    @php
      // Option C: produce per-section datasets for each class (present counts)
      $classLabels = [];
      $sectionLabels = [];
      $datasetsBySection = [];
      if ($school && class_exists(\App\Models\StudentEnrollment::class) && class_exists(\App\Models\Attendance::class) && class_exists(\App\Models\SchoolClass::class) && class_exists(\App\Models\Section::class)) {
        $enrolls = \App\Models\StudentEnrollment::where('school_id',$school->id)
          ->when($ayId, fn($q)=>$q->where('academic_year_id',$ayId))
          ->where('status','active')
          ->get(['student_id','class_id','section_id']);

        $studentMap = [];
        $classIds = [];
        $sectionIds = [];
        foreach ($enrolls as $e) {
          $studentMap[$e->student_id] = ['class' => $e->class_id, 'section' => $e->section_id];
          if ($e->class_id) $classIds[] = $e->class_id;
          if ($e->section_id) $sectionIds[] = $e->section_id;
        }
        $classIds = array_values(array_unique($classIds));
        $sectionIds = array_values(array_unique($sectionIds));

        // init counts[classId][sectionId] for present and absent
        $countsPresent = [];
        $countsAbsent = [];
        foreach ($classIds as $cid) {
          $countsPresent[$cid] = [];
          $countsAbsent[$cid] = [];
          foreach ($sectionIds as $sid) {
            $countsPresent[$cid][$sid] = 0;
            $countsAbsent[$cid][$sid] = 0;
          }
        }

        if (!empty($studentMap)) {
          $attRecords = \App\Models\Attendance::whereIn('student_id', array_keys($studentMap))
            ->whereDate('date',$today)
            ->get(['student_id','status']);

          foreach ($attRecords as $rec) {
            $m = $studentMap[$rec->student_id] ?? null;
            if (!$m) continue;
            $cid = $m['class']; $sid = $m['section'];
            if ($cid && $sid) {
              if (!isset($countsPresent[$cid])) $countsPresent[$cid] = [];
              if (!isset($countsAbsent[$cid])) $countsAbsent[$cid] = [];
              if (!isset($countsPresent[$cid][$sid])) $countsPresent[$cid][$sid] = 0;
              if (!isset($countsAbsent[$cid][$sid])) $countsAbsent[$cid][$sid] = 0;
              if ($rec->status === 'present') $countsPresent[$cid][$sid]++;
              elseif ($rec->status === 'absent') $countsAbsent[$cid][$sid]++;
            }
          }
        }

        // load labels
        $classes = \App\Models\SchoolClass::whereIn('id', $classIds)->orderBy('numeric_value')->get()->keyBy('id');
        $sections = \App\Models\Section::whereIn('id', $sectionIds)->get()->keyBy('id');

        foreach ($classIds as $cid) {
          $c = $classes[$cid] ?? null;
          $classLabels[] = $c ? ($c->full_name ?? $c->name) : ("শ্রেণি " . $cid);
        }

        foreach ($sectionIds as $sid) {
          $s = $sections[$sid] ?? null;
          $sectionLabels[] = $s ? ($s->name ?? 'শাখা '.$sid) : ('শাখা '.$sid);
        }

        // prepare datasets: for each class-section pair create Present and Absent datasets
        $datasetsByPair = [];
        foreach ($classIds as $cid) {
          foreach ($sectionIds as $sid) {
            $pval = $countsPresent[$cid][$sid] ?? 0;
            $aval = $countsAbsent[$cid][$sid] ?? 0;
            if ($pval === 0 && $aval === 0) continue; // skip empty pairs
            $className = $classes[$cid]->full_name ?? $classes[$cid]->name ?? ('শ্রেণি '.$cid);
            $sectionName = $sections[$sid]->name ?? ('শাখা '.$sid);
            $labelBase = $className . '-' . $sectionName;

            // present dataset
            $pdata = [];
            foreach ($classIds as $cid2) {
              $pdata[] = ($cid2 == $cid) ? $pval : 0;
            }
            $datasetsByPair[] = ['label'=>$labelBase . ' (P)','data'=>$pdata,'kind'=>'present'];

            // absent dataset
            $adata = [];
            foreach ($classIds as $cid2) {
              $adata[] = ($cid2 == $cid) ? $aval : 0;
            }
            $datasetsByPair[] = ['label'=>$labelBase . ' (A)','data'=>$adata,'kind'=>'absent'];
          }
        }
      }
    @endphp

    <div id="attendanceByClassSection" style="min-height:220px; display:flex; align-items:center; justify-content:center; color:#6b7280;">
      @if(!empty($classLabels) && (!empty($sectionLabels) || !empty($datasetsByPair)))
        <canvas id="attendanceByClassChart" style="width:100%;max-height:420px"></canvas>
      @else
        <div style="padding:24px;color:#6b7280;">কোনো উপস্থিতি ডেটা উপলব্ধ নেই</div>
      @endif
    </div>
  </div>

  @if(!empty($classLabels) && (!empty($sectionLabels) || !empty($datasetsByPair)))
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
      (function(){
        const labels = {!! json_encode($classLabels) !!};
        const sections = {!! json_encode($sectionLabels) !!};
        const datasets = {!! json_encode($datasetsByPair ?? []) !!};

        const palette = [
          '#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#06B6D4','#F97316','#6366F1'
        ];

        const chartDatasets = datasets.map((d, i) => ({
          label: d.label,
          data: d.data,
          backgroundColor: d.kind === 'present' ? 'rgba(34,197,94,0.85)' : 'rgba(239,68,68,0.7)'
        }));

        const ctx = document.getElementById('attendanceByClassChart').getContext('2d');
        new Chart(ctx, {
          type: 'bar',
          data: { labels: labels, datasets: chartDatasets },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: { x: { stacked: false }, y: { beginAtZero: true } }
          }
        });
      })();
    </script>
  @endif

  <div class="section">
    <h3>পুরুষ/মহিলা শিক্ষার্থী</h3>
    <div style="display:flex; gap:16px; flex-wrap:wrap;">
      <div class="kpi" style="flex:1 1 220px;">
        <div class="label">পুরুষ</div>
        <div class="value">{{ $maleCount ?? '—' }}</div>
      </div>
      <div class="kpi" style="flex:1 1 220px;">
        <div class="label">মহিলা</div>
        <div class="value">{{ $femaleCount ?? '—' }}</div>
      </div>
    </div>
  </div>

  <div class="section">
    <h3>দ্রুত লিংক</h3>
    <div class="quick-links">
      @if($school)
        <a href="{{ route('principal.institute.attendance.dashboard', $school->id) }}"><i class="fas fa-chart-line"></i> উপস্থিতি ড্যাশবোর্ড</a>
        <a href="{{ route('principal.institute.teachers.index', $school->id) }}"><i class="fas fa-user-tie"></i> শিক্ষক তালিকা</a>
        <a href="{{ route('principal.institute.routine.panel', $school->id) }}"><i class="fas fa-calendar-alt"></i> রুটিন প্যানেল</a>
      @endif
    </div>
  </div>
</div>
@endsection
