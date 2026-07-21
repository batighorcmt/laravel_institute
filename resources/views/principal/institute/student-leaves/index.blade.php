@extends('layouts.admin')

@section('title', 'Student Leaves')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">শিক্ষার্থীর ছুটির আবেদন</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.manage', $school) }}">{{ $school->name }}</a></li>
                    <li class="breadcrumb-item active">Student Leaves</li>
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

        <div class="row">
            <div class="col-md-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner"><h3>{{ $stats['pending'] }}</h3><p>মুলতুবি</p></div>
                    <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner"><h3>{{ $stats['approved'] }}</h3><p>অনুমোদিত</p></div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner"><h3>{{ $stats['rejected'] }}</h3><p>বাতিল</p></div>
                    <div class="icon"><i class="fas fa-times-circle"></i></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner"><h3>{{ $stats['on_hold'] }}</h3><p>স্থগিত</p></div>
                    <div class="icon"><i class="fas fa-pause-circle"></i></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">আবেদন তালিকা</h3>
                <form method="GET" class="form-inline">
                    <select class="form-control form-control-sm" name="status" onchange="this.form.submit()">
                        <option value="">সকল</option>
                        <option value="pending" {{ $status==='pending'?'selected':'' }}>মুলতুবি</option>
                        <option value="approved" {{ $status==='approved'?'selected':'' }}>অনুমোদিত</option>
                        <option value="rejected" {{ $status==='rejected'?'selected':'' }}>বাতিল</option>
                        <option value="on_hold" {{ $status==='on_hold'?'selected':'' }}>স্থগিত</option>
                    </select>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>শিক্ষার্থী</th>
                                <th>শ্রেণী/শাখা</th>
                                <th>রোল</th>
                                <th>তারিখ</th>
                                <th>শিরোনাম</th>
                                <th>স্ট্যাটাস</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leaves as $i => $leave)
                                @php
                                    $enrollment = optional($leave->student)->currentEnrollment;
                                @endphp
                                <tr>
                                    <td>{{ $leaves->firstItem() + $i }}</td>
                                    <td>{{ optional($leave->student)->full_name ?? optional($leave->student)->name ?? '-' }}</td>
                                    <td>
                                        {{ optional(optional($enrollment)->class)->bangla_name ?? optional(optional($enrollment)->class)->name ?? '-' }}
                                        / {{ optional(optional($enrollment)->section)->bangla_name ?? optional(optional($enrollment)->section)->name ?? '-' }}
                                    </td>
                                    <td>{{ optional($enrollment)->roll_no ?? '-' }}</td>
                                    <td>{{ $leave->start_date->format('d M, Y') }} - {{ $leave->end_date->format('d M, Y') }}</td>
                                    <td>{{ $leave->title ?? \Illuminate\Support\Str::limit($leave->reason, 40) }}</td>
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
                                    <td>
                                        <a href="{{ route('principal.institute.student-leaves.show', [$school, $leave]) }}" class="btn btn-primary btn-sm">বিস্তারিত</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">কোনো আবেদন পাওয়া যায়নি।</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                {{ $leaves->links() }}
            </div>
        </div>
    </div>
</section>

@endsection
