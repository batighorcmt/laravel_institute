@extends('layouts.admin')

@section('title', 'মার্কশিট')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">মার্কশিট</h1>
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
                <h3 class="card-title">ফলাফল খুঁজুন</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('principal.institute.results.marksheet', $school) }}">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="exam_id">পরীক্ষা নির্বাচন করুন</label>
                                <select name="exam_id" id="exam_id" class="form-control" required>
                                    <option value="">-- নির্বাচন করুন --</option>
                                    @foreach($exams as $examItem)
                                        <option value="{{ $examItem->id }}" {{ request('exam_id') == $examItem->id ? 'selected' : '' }}>
                                            {{ $examItem->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="class_id">শ্রেণি নির্বাচন করুন</label>
                                <select name="class_id" id="class_id" class="form-control" required>
                                    <option value="">-- নির্বাচন করুন --</option>
                                    @foreach($classes as $classItem)
                                        <option value="{{ $classItem->id }}" {{ request('class_id') == $classItem->id ? 'selected' : '' }}>
                                            {{ $classItem->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> খুঁজুন
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if($results)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <strong>{{ $exam->name }}</strong> - {{ $class->name }}
                    </h3>
                </div>
                <div class="card-body">
                    @if($results->count() > 0)
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">ক্রমিক</th>
                                    <th>রোল</th>
                                    <th>শিক্ষার্থীর নাম</th>
                                    <th>মোট নম্বর</th>
                                    <th>GPA</th>
                                    <th>গ্রেড</th>
                                    <th>ফলাফল</th>
                                    <th width="10%">কার্যক্রম</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($results as $result)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $result->student->student_id ?? 'N/A' }}</td>
                                        <td>{{ $result->student->student_name_en ?? 'N/A' }}</td>
                                        <td>{{ number_format($result->total_marks, 2) }} / {{ number_format($result->total_possible_marks, 0) }}</td>
                                        <td>{{ number_format($result->gpa, 2) }}</td>
                                        <td><strong>{{ $result->letter_grade }}</strong></td>
                                        <td>
                                            @if($result->result_status == 'pass')
                                                <span class="badge badge-success">পাস</span>
                                            @elseif($result->result_status == 'fail')
                                                <span class="badge badge-danger">ফেল</span>
                                            @else
                                                <span class="badge badge-secondary">অসম্পূর্ণ</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('principal.institute.results.marksheet.print', [$school, $exam, $result->student]) }}" class="btn btn-sm btn-info" target="_blank" title="মার্কশিট প্রিন্ট করুন">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-3">
                            {{ $results->links() }}
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> এই পরীক্ষার জন্য কোনো ফলাফল পাওয়া যায়নি।
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
