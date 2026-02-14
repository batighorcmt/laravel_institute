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
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>শিক্ষাবর্ষ</label>
                                <select name="academic_year_id" class="form-control" required>
                                    <option value="">-- নির্বাচন করুন --</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                            {{ $year->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>শ্রেণি</label>
                                <select name="class_id" class="form-control" required id="class_selector">
                                    <option value="">-- নির্বাচন করুন --</option>
                                    @foreach($classes as $cls)
                                        <option value="{{ $cls->id }}" {{ request('class_id') == $cls->id ? 'selected' : '' }}>
                                            {{ $cls->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>শাখা (অপশনাল)</label>
                                <select name="section_id" class="form-control" id="section_selector">
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
                                <select name="exam_id" class="form-control" required>
                                    <option value="">-- নির্বাচন করুন --</option>
                                    @foreach($exams as $ex)
                                        <option value="{{ $ex->id }}" {{ request('exam_id') == $ex->id ? 'selected' : '' }}>
                                            {{ $ex->name }}
                                        </option>
                                    @endforeach
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
                <div class="card-header">
                    <h3 class="card-title">
                        ফলাফল তালিকা ({{ $exam->name }} - {{ $class->name }})
                    </h3>
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

@push('js')
<script>
    $('#class_selector').change(function(){
        var classId = $(this).val();
        var sectionSelector = $('#section_selector');
        sectionSelector.html('<option value="">লোড হচ্ছে...</option>');
        
        if(classId) {
            $.get("{{ route('principal.institute.meta.sections', $school) }}", { class_id: classId }, function(data){
                var options = '<option value="">-- সকল শাখা --</option>';
                data.forEach(function(sec){
                    options += '<option value="'+sec.id+'">'+sec.name+'</option>';
                });
                sectionSelector.html(options);
            });
        } else {
            sectionSelector.html('<option value="">-- সকল শাখা --</option>');
        }
    });
</script>
@endpush
@endsection
