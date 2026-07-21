@extends('layouts.admin')

@section('title', 'Student Leave Detail')

@section('content')
@php
    $enrollment = optional($leave->student)->currentEnrollment;
@endphp
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">ছুটির আবেদনের বিস্তারিত</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.student-leaves.index', $school) }}">Student Leaves</a></li>
                    <li class="breadcrumb-item active">#{{ $leave->id }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">আবেদনের তথ্য</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th style="width:220px">শিক্ষার্থীর নাম</th>
                        <td>{{ optional($leave->student)->full_name ?? optional($leave->student)->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>শ্রেণী / শাখা</th>
                        <td>
                            {{ optional(optional($enrollment)->class)->bangla_name ?? optional(optional($enrollment)->class)->name ?? '-' }}
                            / {{ optional(optional($enrollment)->section)->bangla_name ?? optional(optional($enrollment)->section)->name ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>রোল</th>
                        <td>{{ optional($enrollment)->roll_no ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>অভিভাবকের মোবাইল</th>
                        <td>{{ optional($leave->student)->guardian_phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>শিরোনাম</th>
                        <td>{{ $leave->title ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>বিষয়বস্তু</th>
                        <td>{{ $leave->reason }}</td>
                    </tr>
                    <tr>
                        <th>ছুটির মেয়াদ</th>
                        <td>{{ $leave->start_date->format('d M, Y') }} - {{ $leave->end_date->format('d M, Y') }}
                            ({{ $leave->start_date->diffInDays($leave->end_date) + 1 }} দিন)</td>
                    </tr>
                    <tr>
                        <th>বর্তমান স্ট্যাটাস</th>
                        <td>
                            @if($leave->status === 'approved')
                                <span class="badge badge-success">অনুমোদিত</span>
                            @elseif($leave->status === 'rejected')
                                <span class="badge badge-danger">বাতিল</span>
                            @elseif($leave->status === 'on_hold')
                                <span class="badge badge-secondary">স্থগিত</span>
                            @else
                                <span class="badge badge-warning">মুলতুবি</span>
                            @endif
                        </td>
                    </tr>
                    @if($leave->reviewer)
                        <tr>
                            <th>রিভিউ করেছেন</th>
                            <td>{{ $leave->reviewer->full_name ?? $leave->reviewer->name }} ({{ optional($leave->reviewed_at)->format('d M, Y h:i A') }})</td>
                        </tr>
                        <tr>
                            <th>রিভিউ মন্তব্য</th>
                            <td>{{ $leave->review_note ?? '-' }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">সিদ্ধান্ত নিন</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('principal.institute.student-leaves.review', [$school, $leave]) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>মন্তব্য (ঐচ্ছিক)</label>
                        <textarea name="note" class="form-control" rows="2" maxlength="1000"></textarea>
                    </div>
                    <button type="submit" name="action" value="approved" class="btn btn-success" onclick="return confirm('আবেদনটি অনুমোদন করবেন?')">অনুমোদন</button>
                    <button type="submit" name="action" value="on_hold" class="btn btn-secondary" onclick="return confirm('আবেদনটি স্থগিত করবেন?')">স্থগিত</button>
                    <button type="submit" name="action" value="rejected" class="btn btn-danger" onclick="return confirm('আবেদনটি বাতিল করবেন?')">বাতিল</button>
                </form>
            </div>
        </div>
    </div>
</section>

@endsection
