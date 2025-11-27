@extends('layouts.admin')

@section('title', 'My Leaves')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">আমার ছুটি</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('teacher.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Leaves</li>
                </ol>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <a href="{{ route('teacher.leave.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> নতুন ছুটি আবেদন</a>
    </div>
    <div class="container-fluid mt-3">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>তারিখ</th>
                                <th>ধরণ</th>
                                <th>স্থিতি</th>
                                <th>কারণ</th>
                                <th>রিভিউ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leaves as $i => $leave)
                                <tr>
                                    <td>{{ $leaves->firstItem() + $i }}</td>
                                    <td>{{ $leave->start_date->format('d M, Y') }} - {{ $leave->end_date->format('d M, Y') }}</td>
                                    <td>{{ $leave->type ?? '-' }}</td>
                                    <td>
                                        @if($leave->status === 'approved')
                                            <span class="badge badge-success">Approved</span>
                                        @elseif($leave->status === 'rejected')
                                            <span class="badge badge-danger">Rejected</span>
                                        @else
                                            <span class="badge badge-warning">Pending</span>
                                        @endif
                                    </td>
                                    <td>{{ \Illuminate\Support\Str::limit($leave->reason, 60) }}</td>
                                    <td>
                                        @if($leave->reviewer_id)
                                            <small>{{ optional($leave->reviewed_at)->format('d M Y') }}</small>
                                        @else
                                            <small>-</small>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">কোনো আবেদন পাওয়া যায়নি।</td>
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
</div>
@endsection
