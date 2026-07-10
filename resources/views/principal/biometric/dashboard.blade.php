@extends('layouts.admin')

@section('title', 'বায়োমেট্রিক ড্যাশবোর্ড')

@push('styles')
<style>
.bio-stat-card {
    background: linear-gradient(135deg, #1e1e2e 0%, #2a2a3e 100%);
    border: 1px solid rgba(125,211,252,0.15);
    border-radius: 16px;
    padding: 1.5rem;
    color: #fff;
    transition: transform .2s, box-shadow .2s;
}
.bio-stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 30px rgba(0,0,0,.3); }
.bio-stat-card .icon { font-size: 2.2rem; }
.bio-stat-card .value { font-size: 2rem; font-weight: 700; }
.bio-stat-card .label { font-size: .85rem; opacity: .7; margin-top: .2rem; }
.device-card {
    background: #1e1e2e;
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 12px;
    padding: 1rem 1.2rem;
}
.status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
.status-online  { background: #34d399; box-shadow: 0 0 8px #34d399; }
.status-offline { background: #f87171; box-shadow: 0 0 8px #f87171; }
.log-badge { font-size: .75rem; padding: .25rem .6rem; border-radius: 50px; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold">🔏 বায়োমেট্রিক ড্যাশবোর্ড</h4>
            <small class="text-muted">{{ $school->name }}</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('principal.institute.biometric.monitor', $school) }}" class="btn btn-sm btn-outline-info">
                <i class="fas fa-tv"></i> লাইভ মনিটর
            </a>
            <a href="{{ route('principal.institute.biometric.reports.index', $school) }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-chart-bar"></i> রিপোর্ট
            </a>
        </div>
    </div>

    {{-- Desktop Agent Info Notice --}}
    @if(session('info'))
    <div class="alert alert-info d-flex align-items-center gap-2 mb-3 border-0"
         style="background:rgba(14,165,233,.12);border-left:4px solid #0ea5e9!important;border-radius:10px;">
        <i class="fas fa-desktop text-info fs-5"></i>
        <div>
            <strong class="text-info">ডেস্কটপ এজেন্ট প্রয়োজন</strong><br>
            <small>{{ session('info') }}</small>
        </div>
    </div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="bio-stat-card">
                <div class="icon">📡</div>
                <div class="value text-info">{{ $totalDevices }}</div>
                <div class="label">মোট ডিভাইস</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="bio-stat-card">
                <div class="icon">🟢</div>
                <div class="value text-success">{{ $onlineDevices }}</div>
                <div class="label">অনলাইন</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="bio-stat-card">
                <div class="icon">🔴</div>
                <div class="value text-danger">{{ $offlineDevices }}</div>
                <div class="label">অফলাইন</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="bio-stat-card">
                <div class="icon">📥</div>
                <div class="value text-warning">{{ $todayLogs }}</div>
                <div class="label">আজকের পাঞ্চ</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="bio-stat-card">
                <div class="icon">⏳</div>
                <div class="value text-warning">{{ $pendingSync }}</div>
                <div class="label">পেন্ডিং সিঙ্ক</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="bio-stat-card">
                <div class="icon">❌</div>
                <div class="value text-danger">{{ $failedSync }}</div>
                <div class="label">ব্যর্থ সিঙ্ক</div>
            </div>
        </div>
        <div class="col-12 col-md-2">
            <a href="{{ route('principal.institute.biometric.reports.index', $school) }}" class="text-decoration-none">
                <div class="bio-stat-card" style="border-color:rgba(245,158,11,.4)">
                    <div class="icon">📊</div>
                    <div class="value text-warning">রিপোর্ট</div>
                    <div class="label">রিপোর্ট দেখুন</div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Device monitoring --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between">
                    <span><i class="fas fa-microchip me-1"></i> ডিভাইস মনিটরিং</span>
                    <a href="{{ route('principal.institute.biometric.devices.index', $school) }}" class="btn btn-xs btn-outline-light btn-sm">সব দেখুন</a>
                </div>
                <div class="card-body p-3" style="max-height:320px;overflow-y:auto;">
                    @forelse($devices as $device)
                    <div class="device-card mb-2 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="status-dot status-{{ $device->status }} me-2"></span>
                            <strong>{{ $device->device_name }}</strong>
                            <span class="text-muted ms-2 small">{{ $device->location }}</span>
                        </div>
                        <div class="text-end">
                            <span class="badge {{ $device->status === 'online' ? 'bg-success' : 'bg-danger' }}">
                                {{ strtoupper($device->status) }}
                            </span>
                            @if($device->last_seen)
                            <div class="small text-muted">{{ $device->last_seen->diffForHumans() }}</div>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-microchip fa-2x mb-2 d-block"></i>
                        কোনো ডিভাইস যোগ করা হয়নি।
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Recent Sync Logs --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between">
                    <span><i class="fas fa-history me-1"></i> সাম্প্রতিক সিঙ্ক লগ</span>
                    <a href="{{ route('principal.institute.biometric.reports.sync-history', $school) }}" class="btn btn-xs btn-outline-light btn-sm">সব দেখুন</a>
                </div>
                <div class="card-body p-0" style="max-height:320px;overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>কার্যক্রম</th>
                                <th>ডিভাইস</th>
                                <th>স্ট্যাটাস</th>
                                <th>সময়</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentLogs as $log)
                            <tr>
                                <td>{{ $log->action }}</td>
                                <td>{{ $log->device?->device_name ?? 'N/A' }}</td>
                                <td>
                                    <span class="log-badge badge
                                        {{ $log->status === 'success' ? 'bg-success' :
                                          ($log->status === 'failed' ? 'bg-danger' :
                                          ($log->status === 'queued' ? 'bg-warning text-dark' : 'bg-secondary')) }}">
                                        {{ $log->status }}
                                    </span>
                                </td>
                                <td class="text-muted small">{{ $log->created_at->diffForHumans() }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">কোনো লগ নেই</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
