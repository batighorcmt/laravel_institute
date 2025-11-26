@extends('layouts.admin')

@section('title', 'হোমওয়ার্ক বিস্তারিত')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h1 class="m-0">হোমওয়ার্ক বিস্তারিত</h1>
    <a href="{{ route('teacher.institute.homework.index', $school) }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> ফিরে যান
    </a>
</div>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h3 class="card-title mb-0">
            <i class="fas fa-book"></i> {{ $homework->title }}
        </h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th width="40%">শ্রেণি</th>
                        <td>{{ $homework->schoolClass->name }}</td>
                    </tr>
                    <tr>
                        <th>শাখা</th>
                        <td>{{ $homework->section->name }}</td>
                    </tr>
                    <tr>
                        <th>বিষয়</th>
                        <td>{{ $homework->subject->name }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th width="40%">দেওয়ার তারিখ</th>
                        <td>{{ $homework->homework_date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <th>জমা দেওয়ার তারিখ</th>
                        <td>{{ $homework->submission_date ? $homework->submission_date->format('d/m/Y') : 'নির্ধারিত নয়' }}</td>
                    </tr>
                    <tr>
                        <th>শিক্ষক</th>
                        <td>{{ $homework->teacher->first_name_bn ?? $homework->teacher->first_name }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <h4>বিস্তারিত:</h4>
            <div class="border p-3 bg-light">
                {!! nl2br(e($homework->description)) !!}
            </div>
        </div>

        @if($homework->attachment)
            <div class="mt-4">
                <h4>সংযুক্তি:</h4>
                <a href="{{ asset('storage/' . $homework->attachment) }}" target="_blank" class="btn btn-info">
                    <i class="fas fa-download"></i> ফাইল ডাউনলোড করুন
                </a>
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
