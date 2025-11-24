@extends('layouts.admin')

@section('title', 'নম্বর Entry')

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
                    <li class="breadcrumb-item active">নম্বর Entry</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">পরীক্ষা নির্বাচন করুন</h3>
            </div>
            <div class="card-body">
                @if($exams->count() > 0)
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th width="5%">ক্রমিক</th>
                                <th>পরীক্ষার নাম</th>
                                <th>শ্রেণি</th>
                                <th>শিক্ষাবর্ষ</th>
                                <th>বিষয় সংখ্যা</th>
                                <th>অবস্থা</th>
                                <th width="15%">কার্যক্রম</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($exams as $exam)
                                <tr>
                                    <td>{{ $loop->iteration + ($exams->currentPage() - 1) * $exams->perPage() }}</td>
                                    <td><strong>{{ $exam->name }}</strong></td>
                                    <td>{{ $exam->class->name ?? 'N/A' }}</td>
                                    <td>{{ $exam->academicYear->name ?? 'N/A' }}</td>
                                    <td>{{ $exam->examSubjects->count() }}</td>
                                    <td>
                                        @if($exam->status == 'active')
                                            <span class="badge badge-success">সক্রিয়</span>
                                        @elseif($exam->status == 'completed')
                                            <span class="badge badge-info">সম্পন্ন</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $exam->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('principal.institute.marks.show', [$school, $exam]) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-pen"></i> নম্বর Entry
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-3">
                        {{ $exams->links() }}
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> এখনো কোনো পরীক্ষা তৈরি করা হয়নি।
                        <a href="{{ route('principal.institute.exams.create', $school) }}" class="alert-link">নতুন পরীক্ষা তৈরি করুন</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
