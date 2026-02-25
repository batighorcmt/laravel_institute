@extends('layouts.admin')

@section('title', 'Mark Exam Attendance')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clipboard-check mr-2"></i>পরীক্ষার উপস্থিতি নেওয়া</h3>
                <div class="card-tools">
                    <span class="badge badge-warning">Exam Controller</span>
                </div>
            </div>
            <div class="card-body">
                @if($exams->isEmpty())
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-1"></i> বর্তমানে কোনো সক্রিয় পরীক্ষা নেই।
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>পরীক্ষার নাম</th>
                                    <th>শুরুর তারিখ</th>
                                    <th>শেষের তারিখ</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($exams as $i => $exam)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $exam->name }}</td>
                                    <td>{{ $exam->start_date }}</td>
                                    <td>{{ $exam->end_date }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-success" disabled>
                                            <i class="fas fa-check"></i> উপস্থিতি নিন
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
