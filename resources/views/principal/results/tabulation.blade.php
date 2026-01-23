@extends('layouts.admin')

@section('title', 'ট্যাবুলেশন শিট' . (optional($exam)->name ? ' - ' . optional($exam)->name : ''))

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">ট্যাবুলেশন শিট</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.results.marksheet', $school) }}">ফলাফল</a></li>
                    <li class="breadcrumb-item active">ট্যাবুলেশন শিট</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
                <div class="card-header bg-info">
                    <h3 class="card-title">{{ optional($exam)->name ?? 'ট্যাবুলেশন শীট' }}@if(optional($exam)->class) - {{ optional($exam)->class->name }}@endif</h3>
                    <div class="card-tools">
                        @php $classIdForPrint = $class->id ?? request('class_id'); @endphp
                        @if($exam && $classIdForPrint)
                            <a href="{{ route('principal.institute.results.tabulation.print', [$school, $exam->id, $classIdForPrint]) }}?section_id={{ request('section_id') }}"
                               class="btn btn-sm btn-light"
                               target="_blank">
                                <i class="fas fa-print"></i> প্রিন্ট করুন
                            </a>
                        @endif
                    </div>
                </div>
            <div class="card-body">
                <!-- Filter Form -->
                <form method="GET" action="{{ route('principal.institute.results.tabulation', $school) }}" class="mb-3" id="tabulation-filter">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>শিক্ষাবর্ষ</label>
                                <select name="academic_year_id" id="academic_year_id" class="form-control">
                                    <option value="">-- শিক্ষাবর্ষ নির্বাচন করুন --</option>
                                    @foreach($academicYears as $ay)
                                        <option value="{{ $ay->id }}" {{ request('academic_year_id') == $ay->id ? 'selected' : '' }}>
                                            {{ $ay->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>পরীক্ষা</label>
                                <select name="exam_id" id="exam_id" class="form-control">
                                    <option value="">-- পরীক্ষা নির্বাচন করুন --</option>
                                    @foreach($exams as $examItem)
                                        <option value="{{ $examItem->id }}" {{ request('exam_id') == $examItem->id ? 'selected' : '' }}>
                                            {{ $examItem->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>শ্রেণি</label>
                                <select name="class_id" id="class_id" class="form-control">
                                    <option value="">-- শ্রেণি নির্বাচন করুন --</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>শাখা</label>
                                <select name="section_id" id="section_id" class="form-control">
                                    <option value="">-- (সব শাখা) --</option>
                                    @foreach($sections as $section)
                                        <option value="{{ $section->id }}" {{ request('section_id') == $section->id ? 'selected' : '' }}>
                                            {{ $section->name ?? $section->section_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-1">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                @if($results->count() > 0 && $exam && $exam->examSubjects->count() > 0)
                    <div class="table-responsive" style="overflow-x: auto;">
                        <table class="table table-bordered table-sm" style="font-size: 11px;">
                            <thead class="thead-light">
                                <tr>
                                    <th rowspan="2" class="align-middle text-center" style="width: 50px;">ক্রমিক</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 80px;">রোল</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 100px;">শাখা</th>
                                    <th rowspan="2" class="align-middle" style="min-width: 200px;">শিক্ষার্থীর নাম</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 80px;">গ্রুপ</th>
                                    @foreach($classSubjects as $classSubject)
                                        @php $examSubject = $examSubjects->firstWhere('subject_id', $classSubject->subject_id); @endphp
                                        @php
                                            $parts = [];
                                            if($examSubject && $examSubject->hasCreative) $parts[] = 'creative';
                                            if($examSubject && $examSubject->hasMcq) $parts[] = 'mcq';
                                            if($examSubject && $examSubject->hasPractical) $parts[] = 'practical';
                                            // always include total and gpa
                                            $parts[] = 'total';
                                            $parts[] = 'gpa';
                                            $colspan = count($parts);
                                        @endphp
                                        <th colspan="{{ $colspan }}" class="text-center" style="min-width: 120px;">
                                            {{ $classSubject->subject->name }}<br>
                                            <small>({{ $examSubject->total_full_mark ?? '-' }})</small>
                                        </th>
                                    @endforeach
                                    <th rowspan="2" class="align-middle text-center" style="width: 120px;">চতুর্থ বিষয় কোড</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 80px;">মোট</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 60px;">GPA</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 60px;">গ্রেড</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 80px;">ফলাফল</th>
                                </tr>
                                <tr>
                                    @foreach($classSubjects as $classSubject)
                                        @php $examSubject = $examSubjects->firstWhere('subject_id', $classSubject->subject_id); @endphp
                                        @if($examSubject && $examSubject->hasCreative)
                                            <th class="text-center" style="width: 50px;">সৃজনশীল</th>
                                        @endif
                                        @if($examSubject && $examSubject->hasMcq)
                                            <th class="text-center" style="width: 50px;">নৈর্বর্ত্তিক</th>
                                        @endif
                                        @if($examSubject && $examSubject->hasPractical)
                                            <th class="text-center" style="width: 50px;">ব্যবহারিক</th>
                                        @endif
                                        <th class="text-center" style="width: 50px;">মোট</th>
                                        <th class="text-center" style="width: 40px;">জিপিএ</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($results as $result)
                                    @php
                                        $studentMarks = $marks->where('student_id', $result->student_id)->keyBy('exam_subject_id');
                                    @endphp
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td class="text-center">{{ $result->student->roll ?? $result->student->student_id }}</td>
                                        <td class="text-center">{{ optional($result->section)->name ?? optional($result->student->currentEnrollment->section)->name ?? '-' }}</td>
                                        <td>{{ $result->student->student_name_en }}</td>
                                        <td class="text-center">{{ $result->group_name ?? optional(optional($result->student)->currentEnrollment->group)->name ?? '-' }}</td>

                                        @foreach($classSubjects as $classSubject)
                                            @php
                                                $examSubject = $examSubjects->firstWhere('subject_id', $classSubject->subject_id);
                                                $mark = $examSubject ? $studentMarks->get($examSubject->id) : null;
                                            @endphp
                                            @if($examSubject && $examSubject->hasCreative)
                                            <td class="text-center">
                                                @if($mark)
                                                    @if($mark->is_absent)
                                                        <span class="text-danger">Ab</span>
                                                    @else
                                                        {{ number_format($mark->creative_marks ?? 0, 0) }}
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            @endif

                                            @if($examSubject && $examSubject->hasMcq)
                                            <td class="text-center">
                                                @if($mark)
                                                    @if($mark->is_absent)
                                                        <span class="text-danger">Ab</span>
                                                    @else
                                                        {{ number_format($mark->mcq_marks ?? 0, 0) }}
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            @endif

                                            @if($examSubject && $examSubject->hasPractical)
                                            <td class="text-center">
                                                @if($mark)
                                                    @if($mark->is_absent)
                                                        <span class="text-danger">Ab</span>
                                                    @else
                                                        {{ number_format($mark->practical_marks ?? 0, 0) }}
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            @endif

                                            <td class="text-center">
                                                @if($mark)
                                                    @if($mark->is_absent)
                                                        <span class="text-danger">Ab</span>
                                                    @else
                                                        {{ number_format($mark->total_marks ?? 0, 0) }}
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>

                                            <td class="text-center">
                                                @if($mark && !$mark->is_absent)
                                                    {{ number_format($mark->grade_point ?? 0, 2) }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        @endforeach

                                        <td class="text-center">{{ $result->fourth_subject_code ?? '-' }}</td>
                                        <td class="text-center"><strong>{{ number_format($result->computed_total_marks ?? $result->total_marks ?? 0, 0) }}</strong></td>
                                        <td class="text-center"><strong>{{ number_format($result->computed_gpa ?? $result->gpa ?? 0, 2) }}</strong></td>
                                        <td class="text-center">
                                            @php $letter = $result->computed_letter ?? $result->letter_grade; $gpaValue = $result->computed_gpa ?? $result->gpa ?? 0; @endphp
                                            @if($gpaValue <= 0)
                                                <span class="badge badge-danger">অকৃতকার্য</span>
                                            @else
                                                <span class="badge badge-{{ $letter == 'A+' ? 'success' : ($letter == 'F' ? 'danger' : 'info') }}">{{ $letter }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @php $status = $result->computed_status ?? ($result->result_status == 'passed' ? 'উত্তীর্ণ' : 'অনুত্তীর্ণ'); @endphp
                                            @if($status == 'উত্তীর্ণ')
                                                <span class="badge badge-success" style="font-size: 9px;">উত্তীর্ণ</span>
                                            @elseif($status == 'অনুপস্থিত')
                                                <span class="badge badge-warning" style="font-size: 9px;">অনুপস্থিত</span>
                                            @elseif($status == 'অকৃতকার্য')
                                                <span class="badge badge-danger" style="font-size: 9px;">অকৃতকার্য</span>
                                            @else
                                                <span class="badge badge-secondary" style="font-size: 9px;">{{ $status }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif(request('class_id'))
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> নির্বাচিত ফিল্টারের জন্য কোনো ফলাফল পাওয়া যায়নি।
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> অনুগ্রহ করে শ্রেণি নির্বাচন করুন।
                    </div>
                @endif
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const examsUrl = "{{ route('principal.institute.results.exams-by-year', $school) }}";
    const sectionsUrl = "{{ route('principal.institute.results.sections-by-class', $school) }}";

    const examSelect = document.getElementById('exam_id');
    const yearSelect = document.getElementById('academic_year_id');
    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');

    function clearSelect(sel, placeholder){
        sel.innerHTML = '';
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = placeholder;
        sel.appendChild(opt);
    }

    function loadExams(yearId, selected){
        clearSelect(examSelect, '-- পরীক্ষা নির্বাচন করুন --');
        if(!yearId) return;
        fetch(examsUrl + '?academic_year_id=' + encodeURIComponent(yearId))
            .then(r => r.json())
            .then(data => {
                data.forEach(function(e){
                    const opt = document.createElement('option');
                    opt.value = e.id;
                    opt.textContent = e.name;
                    if(selected && String(selected) === String(e.id)) opt.selected = true;
                    examSelect.appendChild(opt);
                });
            }).catch(()=>{});
    }

    function loadSections(classId, selected){
        clearSelect(sectionSelect, '-- (সব শাখা) --');
        if(!classId) return;
        fetch(sectionsUrl + '?class_id=' + encodeURIComponent(classId))
            .then(r => r.json())
            .then(data => {
                data.forEach(function(s){
                    const name = s.name || s.section_name || ('Section ' + s.id);
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = name;
                    if(selected && String(selected) === String(s.id)) opt.selected = true;
                    sectionSelect.appendChild(opt);
                });
            }).catch(()=>{});
    }

    // initialize if values present
    const initYear = yearSelect ? yearSelect.value : '';
    const initExam = '{{ request("exam_id") }}';
    if(initYear) loadExams(initYear, initExam);

    const initClass = classSelect ? classSelect.value : '';
    const initSection = '{{ request("section_id") }}';
    if(initClass) loadSections(initClass, initSection);

    if(yearSelect){
        yearSelect.addEventListener('change', function(){ loadExams(this.value, null); });
    }
    if(classSelect){
        classSelect.addEventListener('change', function(){ loadSections(this.value, null); });
    }
});
</script>
@endpush
            </div>
        </div>
    </div>
</section>
@endsection
