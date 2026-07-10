@extends('layouts.admin')

@section('title', 'বায়োমেট্রিক এনরোলমেন্ট')

@push('styles')
<style>
.enroll-badge { font-size: .72rem; padding: .2rem .55rem; border-radius: 50px; }
.bio-id-cell { font-family: monospace; font-weight: 700; color: #7dd3fc; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold"><i class="fas fa-fingerprint text-success me-2"></i>বায়োমেট্রিক এনরোলমেন্ট</h4>
            <small class="text-muted">{{ $school->name }}</small>
        </div>
        <a href="{{ route('principal.institute.biometric.dashboard', $school) }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> ড্যাশবোর্ড
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">{{ $errors->first() }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Summary Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-success">{{ $totalEnrolled }}</div>
                <div class="small text-muted">এনরোলড শিক্ষার্থী</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="fs-2 fw-bold text-warning">{{ $totalPending }}</div>
                <div class="small text-muted">পেন্ডিং এনরোলমেন্ট</div>
            </div>
        </div>
        <div class="col-12 col-md-6 d-flex align-items-center">
            {{-- Sync to Device --}}
            @if($devices->isNotEmpty())
            <button class="btn btn-outline-primary btn-sm me-2" onclick="syncAll()">
                <i class="fas fa-sync me-1"></i> সব স্টুডেন্ট ডিভাইসে সিঙ্ক করুন
            </button>
            @endif
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-6">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="নাম, আইডি বা বায়োমেট্রিক আইডি দিয়ে খুঁজুন..." value="{{ $search }}">
            </div>
        </div>
        <div class="col-md-3">
            <select name="filter" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="all"      {{ $filter === 'all'      ? 'selected' : '' }}>সকল শিক্ষার্থী</option>
                <option value="enrolled" {{ $filter === 'enrolled' ? 'selected' : '' }}>শুধু এনরোলড</option>
                <option value="pending"  {{ $filter === 'pending'  ? 'selected' : '' }}>এনরোলমেন্ট বাকি</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary btn-sm w-100">
                <i class="fas fa-filter me-1"></i> ফিল্টার
            </button>
        </div>
    </form>

    {{-- Students Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>ছাত্র আইডি</th>
                            <th>নাম</th>
                            <th>শ্রেণি / সেকশন</th>
                            <th>বায়োমেট্রিক আইডি</th>
                            <th>স্ট্যাটাস</th>
                            <th class="text-end">কার্যক্রম</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $i => $student)
                        <tr>
                            <td>{{ $students->firstItem() + $i }}</td>
                            <td><span class="badge bg-light text-dark">{{ $student->student_id }}</span></td>
                            <td>
                                <div class="fw-semibold">{{ $student->full_name }}</div>
                            </td>
                            <td>
                                {{ $student->class?->name ?? '-' }}
                                @if($student->currentEnrollment?->section)
                                <span class="text-muted"> / {{ $student->currentEnrollment->section->name }}</span>
                                @endif
                            </td>
                            <td class="bio-id-cell">
                                {{ $student->biometric_id ?? '—' }}
                            </td>
                            <td>
                                @if($student->biometric_id)
                                <span class="enroll-badge badge bg-success">✅ এনরোলড</span>
                                @else
                                <span class="enroll-badge badge bg-warning text-dark">⏳ বাকি</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button class="btn btn-xs btn-primary btn-sm"
                                    onclick="assignBiometric({{ $student->id }}, '{{ addslashes($student->full_name) }}', '{{ $student->biometric_id }}')">
                                    <i class="fas fa-fingerprint"></i> {{ $student->biometric_id ? 'পরিবর্তন' : 'আইডি সেট' }}
                                </button>
                                @if($student->biometric_id)
                                <form action="{{ route('principal.institute.biometric.enrollment.remove', [$school, $student]) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('এই শিক্ষার্থীর এনরোলমেন্ট বাতিল করবেন?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-outline-danger btn-sm">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">কোনো শিক্ষার্থী পাওয়া যায়নি।</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($students->hasPages())
        <div class="card-footer bg-transparent">
            {{ $students->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Assign Modal --}}
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form id="assignForm" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-fingerprint me-2"></i>বায়োমেট্রিক আইডি সেট</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">শিক্ষার্থী: <strong id="assignStudentName"></strong></p>
                    <label class="form-label fw-semibold">বায়োমেট্রিক আইডি <span class="text-danger">*</span></label>
                    <input type="number" name="biometric_id" id="assignBioId" class="form-control" placeholder="যেমন: 1001" required min="1">
                    <small class="text-muted">ZKTeco ডিভাইসে শিক্ষার্থীর Enroll Number / User ID দিন।</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>সংরক্ষণ</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Sync to Device Modal --}}
@if($devices->isNotEmpty())
<div class="modal fade" id="syncModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-sync me-2"></i>ডিভাইসে সিঙ্ক করুন</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">ডিভাইস নির্বাচন করুন</label>
                    <select id="syncDeviceId" class="form-select">
                        @foreach($devices as $device)
                        <option value="{{ $device->id }}">{{ $device->device_name }}</option>
                        @endforeach
                    </select>
                </div>
                <p class="small text-muted">সব এনরোলড শিক্ষার্থীর তথ্য নির্বাচিত ডিভাইসে পাঠানো হবে।</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                <button type="button" class="btn btn-success" onclick="doSync()"><i class="fas fa-sync me-1"></i>সিঙ্ক করুন</button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
const assignBaseUrl = "{{ route('principal.institute.biometric.enrollment.assign', [$school, '__ID__']) }}";
const syncUrl = "{{ route('principal.institute.biometric.enrollment.sync-device', $school) }}";

function assignBiometric(id, name, currentBioId) {
    document.getElementById('assignForm').action = assignBaseUrl.replace('__ID__', id);
    document.getElementById('assignStudentName').textContent = name;
    document.getElementById('assignBioId').value = currentBioId || '';
    new bootstrap.Modal(document.getElementById('assignModal')).show();
}

function syncAll() {
    new bootstrap.Modal(document.getElementById('syncModal')).show();
}

async function doSync() {
    const deviceId = document.getElementById('syncDeviceId').value;
    const resp = await fetch(syncUrl, {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
        body: JSON.stringify({ device_id: deviceId, scope: 'all' })
    });
    const data = await resp.json();
    bootstrap.Modal.getInstance(document.getElementById('syncModal')).hide();
    alert(data.message);
}
</script>
@endpush
@endsection
