@extends('layouts.admin')

@section('title', 'হোমওয়ার্ক')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h1 class="m-0">হোমওয়ার্ক</h1>
    <div>
        <a href="{{ route('teacher.institute.homework.create', $school) }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> নতুন হোমওয়ার্ক
        </a>
        <a href="{{ route('teacher.dashboard') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> ড্যাশবোর্ড
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<!-- Search Form -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('teacher.institute.homework.index', $school) }}">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>তারিখ</label>
                        <input type="date" name="date" class="form-control" value="{{ $date }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i> সার্চ করুন
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Homeworks List -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <h3 class="card-title mb-0">
            <i class="fas fa-book"></i> হোমওয়ার্ক তালিকা ({{ \Carbon\Carbon::parse($date)->format('d/m/Y') }})
        </h3>
    </div>
    <div class="card-body">
        @if($homeworks->count() > 0)
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>শ্রেণি/শাখা</th>
                            <th>বিষয়</th>
                            <th>শিরোনাম</th>
                            <th>জমা দেওয়ার তারিখ</th>
                            <th width="150">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($homeworks as $homework)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $homework->schoolClass->name }} - {{ $homework->section->name }}</td>
                                <td>{{ $homework->subject->name }}</td>
                                <td>{{ $homework->title }}</td>
                                <td>{{ $homework->submission_date ? $homework->submission_date->format('d/m/Y') : 'নির্ধারিত নয়' }}</td>
                                <td>
                                    <a href="{{ route('teacher.institute.homework.show', [$school, $homework]) }}" class="btn btn-sm btn-info" title="বিস্তারিত">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form action="{{ route('teacher.institute.homework.destroy', [$school, $homework]) }}" method="POST" class="d-inline" onsubmit="return confirm('আপনি কি নিশ্চিত?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="মুছুন">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle"></i> এই তারিখে কোনো হোমওয়ার্ক নেই।
            </div>
        @endif
    </div>
</div>

<style>
.card-header.bg-primary {
    background: linear-gradient(45deg, #4e73df, #224abe) !important;
}
</style>
@endsection
