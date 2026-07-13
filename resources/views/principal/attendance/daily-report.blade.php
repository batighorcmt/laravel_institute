@extends('layouts.admin')

@section('title', 'শিক্ষার্থী দৈনিক হাজিরা রিপোর্ট')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="m-0"><i class="fas fa-calendar-check mr-1 text-primary"></i> শিক্ষার্থী দৈনিক হাজিরা</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 bg-transparent p-0">
            <li class="breadcrumb-item"><a href="{{ route('principal.institute.attendance.dashboard', $school) }}">হাজিরা</a></li>
            <li class="breadcrumb-item active">দৈনিক রিপোর্ট</li>
        </ol>
    </nav>
</div>

{{-- Filter Form --}}
<div class="card card-outline card-primary mb-3">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('principal.institute.attendance.daily_report', $school) }}" id="filter-form">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label class="mb-1"><i class="fas fa-calendar"></i> তারিখ</label>
                    <input type="date" name="date" id="date-input" class="form-control" value="{{ $date }}">
                </div>
                <div class="col-md-3">
                    <label class="mb-1"><i class="fas fa-school"></i> শ্রেণি</label>
                    <select name="class_id" id="class-select" class="form-control">
                        <option value="">সকল শ্রেণি</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ $classId == $class->id ? 'selected' : '' }}>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="mb-1"><i class="fas fa-code-branch"></i> শাখা</label>
                    <select name="section_id" id="section-select" class="form-control">
                        <option value="">সকল শাখা</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}" {{ $sectionId == $section->id ? 'selected' : '' }}>
                                {{ $section->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="mb-1"><i class="fas fa-filter"></i> অবস্থা</label>
                    <select name="status" class="form-control">
                        <option value="">সকল</option>
                        <option value="present" {{ $status == 'present' ? 'selected' : '' }}>উপস্থিত</option>
                        <option value="late"    {{ $status == 'late'    ? 'selected' : '' }}>দেরী</option>
                        <option value="absent"  {{ $status == 'absent'  ? 'selected' : '' }}>অনুপস্থিত</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="fas fa-search"></i> দেখুন
                    </button>
                    @if($records->count())
                    <a href="{{ route('principal.institute.attendance.daily_report.print', $school) }}?{{ http_build_query(request()->only(['date','class_id','section_id','status'])) }}"
                       target="_blank" class="btn btn-outline-secondary flex-fill">
                        <i class="fas fa-print"></i>
                    </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Summary Cards --}}
@if($records->count())
@php
    $presentCount = $records->where('status', 'present')->count();
    $lateCount    = $records->where('status', 'late')->count();
    $absentCount  = $records->where('status', 'absent')->count();
    $total        = $records->count();
@endphp
<div class="row mb-3">
    <div class="col-6 col-md-3">
        <div class="small-box bg-success">
            <div class="inner"><h3>{{ $presentCount }}</h3><p>উপস্থিত</p></div>
            <div class="icon"><i class="fas fa-check"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="small-box bg-warning">
            <div class="inner"><h3>{{ $lateCount }}</h3><p>দেরী</p></div>
            <div class="icon"><i class="fas fa-clock"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="small-box bg-danger">
            <div class="inner"><h3>{{ $absentCount }}</h3><p>অনুপস্থিত</p></div>
            <div class="icon"><i class="fas fa-times"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="small-box bg-info">
            <div class="inner"><h3>{{ $total }}</h3><p>মোট রেকর্ড</p></div>
            <div class="icon"><i class="fas fa-users"></i></div>
        </div>
    </div>
</div>
@endif

{{-- Data Table --}}
<div class="card">
    <div class="card-body p-0">
        @if($records->count())
        <div class="table-responsive">
            <table class="table table-sm table-hover table-striped mb-0">
                <thead class="bg-dark text-white">
                    <tr>
                        <th>#</th>
                        <th>শিক্ষার্থীর নাম</th>
                        <th>আইডি</th>
                        <th>শ্রেণি / শাখা</th>
                        <th>পাঞ্চ সময় (প্রবেশ)</th>
                        <th>পাঞ্চ সময় (প্রস্থান)</th>
                        <th>অবস্থা</th>
                        <th>মাধ্যম</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $i => $rec)
                    @php
                        $student = $rec->student;
                        $enrollment = $student?->currentEnrollment;
                        $className  = $enrollment?->class?->name ?? ($rec->class_id ? '—' : '—');
                        $sectionName = $enrollment?->section?->name ?? '—';
                    @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $student?->student_name_bn ?? $student?->student_name_en ?? '—' }}</td>
                        <td class="text-muted small">{{ $student?->student_id ?? '—' }}</td>
                        <td>{{ $className }} / {{ $sectionName }}</td>
                        <td>{{ $rec->entry_time ? \Carbon\Carbon::parse($rec->entry_time)->format('h:i A') : '—' }}</td>
                        <td>{{ $rec->exit_time  ? \Carbon\Carbon::parse($rec->exit_time)->format('h:i A')  : '—' }}</td>
                        <td>
                            @if($rec->status === 'present')
                                <span class="badge badge-success">উপস্থিত</span>
                            @elseif($rec->status === 'late')
                                <span class="badge badge-warning">দেরী</span>
                            @elseif($rec->status === 'absent')
                                <span class="badge badge-danger">অনুপস্থিত</span>
                            @else
                                <span class="badge badge-secondary">{{ $rec->status }}</span>
                            @endif
                        </td>
                        <td>
                            @if($rec->medium === 'biometric')
                                <span class="badge badge-primary"><i class="fas fa-fingerprint mr-1"></i>মেশিন</span>
                            @elseif($rec->medium === 'mobile_app')
                                <span class="badge badge-info"><i class="fas fa-mobile-alt mr-1"></i>অ্যাপ</span>
                            @elseif($rec->medium === 'system')
                                <span class="badge badge-secondary"><i class="fas fa-robot mr-1"></i>সিস্টেম</span>
                            @else
                                <span class="badge badge-light border"><i class="fas fa-globe mr-1"></i>ওয়েব</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
            <p class="mb-0">নির্বাচিত তারিখ ও ফিল্টারের জন্য কোনো হাজিরা রেকর্ড পাওয়া যায়নি।</p>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('class-select').addEventListener('change', function () {
    const classId = this.value;
    const sectionSelect = document.getElementById('section-select');
    sectionSelect.innerHTML = '<option value="">সকল শাখা</option>';

    if (!classId) return;

    fetch(`{{ url('/api/v1') }}/meta/sections?school_id={{ $school->id }}&class_id=${classId}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        const sections = Array.isArray(data) ? data : (data.sections || []);
        sections.forEach(sec => {
            const opt = document.createElement('option');
            opt.value = sec.id;
            opt.textContent = sec.name;
            sectionSelect.appendChild(opt);
        });
    })
    .catch(() => {});
});
</script>
@endpush
