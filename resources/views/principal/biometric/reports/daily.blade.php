@extends('layouts.admin')
@section('title', 'দৈনিক বায়োমেট্রিক পাঞ্চ রিপোর্ট')

@section('content')
<div class="container-fluid py-3">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold"><i class="fas fa-calendar-day text-primary me-2"></i>দৈনিক পাঞ্চ রিপোর্ট</h4>
            <small class="text-muted">{{ $school->name }}</small>
        </div>
        <a href="{{ route('principal.institute.biometric.reports.index', $school) }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> রিপোর্ট তালিকা
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">তারিখ</label>
                    <input type="date" name="date" class="form-control" value="{{ $date }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">ডিভাইস ফিল্টার</label>
                    <select name="device_id" class="form-select">
                        <option value="">সব ডিভাইস</option>
                        @foreach($devices as $device)
                        <option value="{{ $device->id }}" {{ $deviceId == $device->id ? 'selected' : '' }}>
                            {{ $device->device_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> দেখুন
                    </button>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> প্রিন্ট
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- Summary --}}
    <div class="row g-3 mb-3">
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center py-2">
                <div class="fs-3 fw-bold text-success">{{ $present }}</div>
                <div class="small text-muted">প্রসেসড পাঞ্চ</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center py-2">
                <div class="fs-3 fw-bold text-warning">{{ $pending }}</div>
                <div class="small text-muted">পেন্ডিং পাঞ্চ</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm text-center py-2">
                <div class="fs-3 fw-bold text-info">{{ $logs->count() }}</div>
                <div class="small text-muted">মোট পাঞ্চ</div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>বায়োমেট্রিক আইডি</th>
                            <th>শিক্ষার্থীর নাম</th>
                            <th>শ্রেণি</th>
                            <th>পাঞ্চ সময়</th>
                            <th>ধরন</th>
                            <th>ডিভাইস</th>
                            <th>স্ট্যাটাস</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $i => $log)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td><code>{{ $log->biometric_id }}</code></td>
                            <td>
                                @if($log->student)
                                    {{ $log->student->full_name }}
                                @else
                                    <span class="text-danger small">অজানা আইডি</span>
                                @endif
                            </td>
                            <td>{{ $log->student?->class?->name ?? '—' }}</td>
                            <td>{{ \Carbon\Carbon::parse($log->punch_time)->format('h:i:s A') }}</td>
                            <td>
                                <span class="badge {{ $log->punch_type === 'check_in' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $log->punch_type === 'check_in' ? '↓ প্রবেশ' : '↑ বের' }}
                                </span>
                            </td>
                            <td class="small text-muted">{{ $log->device?->device_name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge
                                    {{ $log->sync_status === 'processed' ? 'bg-success' :
                                      ($log->sync_status === 'failed'    ? 'bg-danger'  : 'bg-warning text-dark') }}">
                                    {{ $log->sync_status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                {{ $date }} তারিখে কোনো পাঞ্চ লগ পাওয়া যায়নি।
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
