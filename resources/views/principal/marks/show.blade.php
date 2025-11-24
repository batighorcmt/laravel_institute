@extends('layouts.admin')

@section('title', 'নম্বর Entry - ' . $exam->name)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">নম্বর Entry</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.marks.index', $school) }}">নম্বর Entry</a></li>
                    <li class="breadcrumb-item active">{{ $exam->name }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Exam Info Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ $exam->name }} - {{ $exam->class->name }}</h3>
                <div class="card-tools">
                    <form action="{{ route('principal.institute.marks.calculate-results', [$school, $exam]) }}" method="POST" class="d-inline" onsubmit="return confirm('ফলাফল হিসাব করা হবে। আপনি কি নিশ্চিত?')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="fas fa-calculator"></i> ফলাফল হিসাব করুন
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if($exam->examSubjects->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">ক্রমিক</th>
                                    <th>বিষয়ের নাম</th>
                                    <th>শিক্ষক</th>
                                    <th>সৃজনশীল</th>
                                    <th>MCQ</th>
                                    <th>ব্যবহারিক</th>
                                    <th>মোট</th>
                                    <th>পরীক্ষার তারিখ</th>
                                    <th>নম্বর Entry</th>
                                    <th width="15%">কার্যক্রম</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($exam->examSubjects->sortBy('display_order') as $examSubject)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><strong>{{ $examSubject->subject->name }}</strong></td>
                                        <td>{{ $examSubject->teacher->name ?? 'Not Assigned' }}</td>
                                        <td>{{ $examSubject->creative_full_mark }}</td>
                                        <td>{{ $examSubject->mcq_full_mark }}</td>
                                        <td>{{ $examSubject->practical_full_mark }}</td>
                                        <td><strong>{{ $examSubject->total_full_mark }}</strong></td>
                                        <td>
                                            @if($examSubject->exam_date)
                                                {{ $examSubject->exam_date->format('d/m/Y') }}
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($examSubject->mark_entry_completed)
                                                <span class="badge badge-success">সম্পন্ন</span>
                                            @else
                                                <span class="badge badge-warning">চলমান</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('principal.institute.marks.entry', [$school, $exam, $examSubject]) }}" class="btn btn-sm btn-primary" title="নম্বর Entry করুন">
                                                <i class="fas fa-pen"></i> Entry
                                            </a>
                                            <a href="{{ route('principal.institute.marks.print-blank', [$school, $exam, $examSubject]) }}" class="btn btn-sm btn-secondary" target="_blank" title="খালি ফর্ম প্রিন্ট">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <a href="{{ route('principal.institute.marks.print-filled', [$school, $exam, $examSubject]) }}" class="btn btn-sm btn-info" target="_blank" title="পূর্ণ ফর্ম প্রিন্ট">
                                                <i class="fas fa-file-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> এই পরীক্ষায় কোনো বিষয় যুক্ত করা হয়নি। 
                        <a href="{{ route('principal.institute.exams.show', [$school, $exam]) }}" class="alert-link">প্রথমে বিষয় যুক্ত করুন</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
