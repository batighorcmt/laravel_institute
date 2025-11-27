@extends('layouts.admin')

@section('title', 'Teacher Leaves')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">শিক্ষক ছুটি ব্যবস্থাপনা</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.manage', $school) }}">{{ $school->name }}</a></li>
                    <li class="breadcrumb-item active">Teacher Leaves</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Applications</h3>
                <form method="GET" class="form-inline">
                    <select class="form-control form-control-sm" name="status" onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="pending" {{ $status==='pending'?'selected':'' }}>Pending</option>
                        <option value="approved" {{ $status==='approved'?'selected':'' }}>Approved</option>
                        <option value="rejected" {{ $status==='rejected'?'selected':'' }}>Rejected</option>
                    </select>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Teacher</th>
                                <th>Dates</th>
                                <th>Type</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leaves as $i => $leave)
                                <tr>
                                    <td>{{ $leaves->firstItem() + $i }}</td>
                                    <td>{{ optional($leave->teacher)->full_name ?? '-' }}</td>
                                    <td>{{ $leave->start_date->format('d M, Y') }} - {{ $leave->end_date->format('d M, Y') }}</td>
                                    <td>{{ $leave->type ?? '-' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($leave->reason, 80) }}</td>
                                    <td>
                                        @if($leave->status === 'approved')
                                            <span class="badge badge-success">Approved</span>
                                        @elseif($leave->status === 'rejected')
                                            <span class="badge badge-danger">Rejected</span>
                                        @else
                                            <span class="badge badge-warning">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($leave->status === 'pending')
                                            <form action="{{ route('principal.institute.teacher-leaves.approve', [$school, $leave]) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Approve this leave?')">Approve</button>
                                            </form>
                                            <form action="{{ route('principal.institute.teacher-leaves.reject', [$school, $leave]) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="reject_reason" value="Insufficient cause">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reject this leave?')">Reject</button>
                                            </form>
                                        @else
                                            <small>-</small>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No applications found.</td>
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
