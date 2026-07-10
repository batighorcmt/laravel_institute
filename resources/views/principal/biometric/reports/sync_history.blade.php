@extends('layouts.admin')
@section('title', 'সিঙ্ক ইতিহাস')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold"><i class="fas fa-history text-warning me-2"></i>সিঙ্ক ইতিহাস</h4>
            <small class="text-muted">{{ $school->name }}</small>
        </div>
        <a href="{{ route('principal.institute.biometric.reports.index', $school) }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> রিপোর্ট তালিকা
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>কার্যক্রম</th>
                            <th>ধরন</th>
                            <th>ডিভাইস</th>
                            <th>স্ট্যাটাস</th>
                            <th>বার্তা</th>
                            <th>সময়</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $i => $log)
                        <tr>
                            <td>{{ $logs->firstItem() + $i }}</td>
                            <td><strong>{{ $log->action }}</strong></td>
                            <td><span class="badge bg-secondary">{{ $log->record_type ?? '—' }}</span></td>
                            <td class="small">{{ $log->device?->device_name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge
                                    {{ $log->status === 'success' ? 'bg-success' :
                                      ($log->status === 'failed'  ? 'bg-danger'  :
                                      ($log->status === 'queued'  ? 'bg-warning text-dark' : 'bg-secondary')) }}">
                                    {{ $log->status }}
                                </span>
                            </td>
                            <td class="small text-muted" style="max-width:250px;">
                                <span title="{{ $log->message }}">{{ \Str::limit($log->message, 60) }}</span>
                            </td>
                            <td class="small text-muted">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">কোনো সিঙ্ক লগ নেই।</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
        <div class="card-footer bg-transparent">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
@endsection
