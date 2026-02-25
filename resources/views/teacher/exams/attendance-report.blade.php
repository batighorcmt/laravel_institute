@extends('layouts.admin')

@section('title', 'Exam Attendance Report')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>পরীক্ষার উপস্থিতি রিপোর্ট</h3>
                <div class="card-tools">
                    <span class="badge badge-warning">Exam Controller</span>
                </div>
            </div>
            <div class="card-body">
                <div class="form-group row">
                    <label class="col-sm-2 col-form-label">পরীক্ষা বাছাই করুন</label>
                    <div class="col-sm-6">
                        <select class="form-control" id="examSelect">
                            <option value="">-- পরীক্ষা বাছাই করুন --</option>
                            @foreach($exams as $exam)
                                <option value="{{ $exam->id }}">{{ $exam->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <button class="btn btn-primary" disabled>
                            <i class="fas fa-search mr-1"></i> রিপোর্ট দেখুন
                        </button>
                    </div>
                </div>

                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    পরীক্ষার উপস্থিতি নেওয়ার পরে এখানে রিপোর্ট দেখা যাবে।
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
