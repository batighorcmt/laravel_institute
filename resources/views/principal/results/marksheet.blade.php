@extends('layouts.admin')

@section('title', 'মার্কশিট প্রিন্ট')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">মার্কশিট প্রিন্ট</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                    <li class="breadcrumb-item active">মার্কশিট</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">শিক্ষার্থী খুঁজুন</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('principal.institute.results.marksheet', $school) }}">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>শিক্ষাবর্ষ</label>
                                <select name="academic_year_id" id="academic_year_id" class="form-control" required>
                                    <option value="">-- নির্বাচন করুন --</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                            {{ $year->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>শ্রেণি</label>
                                <select name="class_id" id="class_id" class="form-control" required>
                                    <option value="">-- নির্বাচন করুন --</option>
                                    @foreach($classes as $cls)
                                        <option value="{{ $cls->id }}" {{ request('class_id') == $cls->id ? 'selected' : '' }}>
                                            {{ $cls->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>শাখা (অপশনাল)</label>
                                <select name="section_id" id="section_id" class="form-control">
                                    <option value="">-- সকল শাখা --</option>
                                    @foreach($sections as $sec)
                                        <option value="{{ $sec->id }}" {{ request('section_id') == $sec->id ? 'selected' : '' }}>
                                            {{ $sec->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>পরীক্ষা</label>
                                <select name="exam_id" id="exam_id" class="form-control" required>
                                    <option value="">-- নির্বাচন করুন --</option>
                                    @foreach($exams as $ex)
                                        <option value="{{ $ex->id }}" {{ request('exam_id') == $ex->id ? 'selected' : '' }}>
                                            {{ $ex->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>শিক্ষার্থী (অপশনাল)</label>
                                <select name="student_id" id="student_id" class="form-control select2">
                                    <option value="">-- সকল শিক্ষার্থী --</option>
                                    {{-- Populated via AJAX --}}
                                    @if(request('student_id') && isset($results))
                                        @foreach($results as $res)
                                            @if($res->student->id == request('student_id'))
                                               <option value="{{ $res->student->id }}" selected>
                                                    {{ $res->student->currentEnrollment->roll_no ?? '-' }} - 
                                                    {{ $res->student->student_name_en ?: $res->student->student_name_bn }}
                                               </option>
                                            @endif
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12 text-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> খুঁজুন
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if(isset($results) && $results->count() > 0)
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        ফলাফল তালিকা ({{ $exam->name }} - {{ $class->name }})
                    </h3>
                    
                    {{-- Print All Button --}}
                    <div>
                        <a href="{{ route('principal.institute.results.marksheet', [$school] + request()->all() + ['print_all' => 1]) }}" target="_blank" class="btn btn-success btn-sm">
                            <i class="fas fa-print"></i> সব মার্কশিট প্রিন্ট করুন
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>রোল</th>
                                <th>নাম</th>
                                <th class="text-center">মোট নম্বর</th>
                                <th class="text-center">GPA</th>
                                <th class="text-center">গ্রেড</th>
                                <th class="text-center">অবস্থা</th>
                                <th class="text-center">অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $res)
                                {{-- Filter logic in view if student_id was selected but controller returned all (just in case), though controller should filter --}}
                                @if(request('student_id') && $res->student->id != request('student_id'))
                                    @continue
                                @endif

                                <tr>
                                    <td>{{ $res->student->currentEnrollment->roll_no ?? '-' }}</td>
                                    <td>
                                        {{ $res->student->student_name_bn ?? $res->student->student_name_en }}<br>
                                        <small class="text-muted">ID: {{ $res->student->student_id }}</small>
                                    </td>
                                    <td class="text-center">{{ number_format($res->computed_total_marks, 0) }}</td>
                                    <td class="text-center">{{ number_format($res->computed_gpa, 2) }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $res->computed_letter == 'F' ? 'badge-danger' : 'badge-success' }}">
                                            {{ $res->computed_letter }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($res->computed_letter == 'F')
                                            <span class="text-danger">অকৃতকার্য</span>
                                        @else
                                            <span class="text-success">উত্তীর্ণ</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('principal.institute.results.marksheet.print', [$school, $exam, $res->student]) }}" target="_blank" class="btn btn-sm btn-info">
                                            <i class="fas fa-print"></i> প্রিন্ট
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif(request()->has('exam_id'))
            <div class="alert alert-warning mt-3">
                <i class="fas fa-exclamation-triangle"></i> কোনো ফলাফল পাওয়া যায়নি।
            </div>
        @endif
    </div>
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize cascading dropdowns
    const examsUrl = "{{ route('principal.institute.results.exams-by-year', $school) }}";
    const sectionsUrl = "{{ route('principal.institute.results.sections-by-class', $school) }}";
    const studentsUrl = "{{ route('principal.institute.results.students-by-class', $school) }}";

    const academicYearSelect = document.getElementById('academic_year_id');
    const classSelect = document.getElementById('class_id');
    const sectionsSelect = document.getElementById('section_id');
    const examSelect = document.getElementById('exam_id');
    const studentSelect = document.getElementById('student_id');

    function clearSelect(sel, placeholder) {
        if (!sel) return;
        sel.innerHTML = '';
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = placeholder;
        sel.appendChild(opt);
    }

    // Helper to load exams
    function loadExams(yearId, classId, selected) {
        if (!examSelect) return;
        clearSelect(examSelect, '-- নির্বাচন করুন --');
        
        if (yearId && classId) {
            fetch(examsUrl + '?academic_year_id=' + encodeURIComponent(yearId) + '&class_id=' + encodeURIComponent(classId))
                .then(r => r.json())
                .then(data => {
                    data.forEach(function(item) {
                        const opt = document.createElement('option');
                        opt.value = item.id;
                        opt.textContent = item.name;
                        if (selected && String(selected) === String(item.id)) opt.selected = true;
                        examSelect.appendChild(opt);
                    });
                }).catch(() => {});
        }
    }

    // Helper to load sections
    function loadSections(classId, selected) {
        if (!sectionsSelect) return;
        clearSelect(sectionsSelect, '-- সকল শাখা --');

        if (classId) {
            fetch(sectionsUrl + '?class_id=' + encodeURIComponent(classId))
                .then(r => r.json())
                .then(data => {
                    data.forEach(function(item) {
                        const opt = document.createElement('option');
                        opt.value = item.id;
                        opt.textContent = item.name || item.section_name;
                        if (selected && String(selected) === String(item.id)) opt.selected = true;
                        sectionsSelect.appendChild(opt);
                    });
                }).catch(() => {});
        }
    }

    // Helper to load students
    function loadStudents(yearId, classId, sectionId, selected) {
        if (!studentSelect) return;
        clearSelect(studentSelect, '-- সকল শিক্ষার্থী --');

        if (yearId && classId) {
            let url = studentsUrl + '?academic_year_id=' + encodeURIComponent(yearId) + '&class_id=' + encodeURIComponent(classId);
            if (sectionId) url += '&section_id=' + encodeURIComponent(sectionId);

            fetch(url)
                .then(r => r.json())
                .then(data => {
                    data.forEach(function(item) {
                        const opt = document.createElement('option');
                        opt.value = item.id;
                        opt.textContent = item.text;
                        if (selected && String(selected) === String(item.id)) opt.selected = true;
                        studentSelect.appendChild(opt);
                    });
                }).catch(() => {});
        }
    }

    // Initial Load Logic
    const initYear = academicYearSelect ? academicYearSelect.value : '';
    const initClass = classSelect ? classSelect.value : '';
    const initSection = "{{ request('section_id') }}";
    const initExam = "{{ request('exam_id') }}";
    const initStudent = "{{ request('student_id') }}";

    if (initYear && initClass) {
        loadExams(initYear, initClass, initExam);
        loadStudents(initYear, initClass, initSection, initStudent);
    }
    if (initClass) {
        loadSections(initClass, initSection);
    }

    // Event Listeners
    if (academicYearSelect) {
        academicYearSelect.addEventListener('change', function() {
            const yearId = this.value;
            const classId = classSelect ? classSelect.value : '';
            loadExams(yearId, classId, null);
            loadStudents(yearId, classId, sectionsSelect ? sectionsSelect.value : '', null);
        });
    }

    if (classSelect) {
        classSelect.addEventListener('change', function() {
            const classId = this.value;
            const yearId = academicYearSelect ? academicYearSelect.value : '';
            loadSections(classId, null);
            loadExams(yearId, classId, null);
            loadStudents(yearId, classId, '', null);
        });
    }

    if (sectionsSelect) {
        sectionsSelect.addEventListener('change', function() {
            const sectionId = this.value;
            const yearId = academicYearSelect ? academicYearSelect.value : '';
            const classId = classSelect ? classSelect.value : '';
            loadStudents(yearId, classId, sectionId, null);
        });
    }
});
</script>
@endpush
@endsection
