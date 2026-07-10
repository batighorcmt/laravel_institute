@extends('layouts.admin')

@section('title', 'বায়োমেট্রিক ডিভাইস ম্যানেজমেন্ট')

@section('content')
<div class="container-fluid py-3">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold"><i class="fas fa-microchip text-primary me-2"></i>ডিভাইস ম্যানেজমেন্ট</h4>
            <small class="text-muted">{{ $school->name }}</small>
        </div>
        <div>
            <a href="{{ route('principal.institute.biometric.dashboard', $school) }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> ড্যাশবোর্ড
            </a>
            <button class="btn btn-sm btn-primary ml-1" data-toggle="modal" data-target="#addDeviceModal">
                <i class="fas fa-plus"></i> নতুন ডিভাইস
            </button>
            <button class="btn btn-sm btn-outline-info ml-1" data-toggle="modal" data-target="#addGroupModal">
                <i class="fas fa-layer-group"></i> নতুন গ্রুপ
            </button>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    {{-- Agent Connection Info Card --}}
    <div class="card border-info mb-4 shadow-sm">
        <div class="card-header bg-info text-white py-2">
            <i class="fas fa-desktop mr-2"></i> <strong>লোকাল এজেন্ট কানেকশন ইনফরমেশন</strong>
        </div>
        <div class="card-body bg-light py-3">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <label class="text-muted small mb-1 fw-bold">SaaS API URL</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control bg-white" value="{{ url('/api') }}" readonly id="apiUrlCopy">
                        <div class="input-group-append">
                            <button class="btn btn-info text-white" type="button" onclick="copyToClipboard('apiUrlCopy')" title="Copy URL">
                                <i class="far fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-2">
                    <label class="text-muted small mb-1 fw-bold">School Code</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control bg-white font-weight-bold text-primary" value="{{ $school->code ?? 'N/A' }}" readonly id="schoolCodeCopy">
                        <div class="input-group-append">
                            <button class="btn btn-info text-white" type="button" onclick="copyToClipboard('schoolCodeCopy')" title="Copy Code">
                                <i class="far fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-2">
                    <label class="text-muted small mb-1 fw-bold">Agent Token</label>
                    @if($school->agent_token)
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control bg-white" value="{{ $school->agent_token }}" readonly id="agentTokenCopy">
                            <div class="input-group-append">
                                <button class="btn btn-info text-white" type="button" onclick="copyToClipboard('agentTokenCopy')" title="Copy Token">
                                    <i class="far fa-copy"></i>
                                </button>
                                <form action="{{ route('principal.institute.biometric.generate_token', $school) }}" method="POST" class="d-inline" onsubmit="return confirm('আপনি কি নিশ্চিত? নতুন টোকেন তৈরি করলে চলমান এজেন্ট ডিসকানেক্ট হয়ে যাবে।')">
                                    @csrf
                                    <button class="btn btn-warning text-dark" type="submit" title="Reset Token">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <small class="form-text text-muted" style="font-size: 0.75rem;">(নিরাপদ সংযোগের জন্য জেনারেটেড টোকেন)</small>
                    @else
                        <form action="{{ route('principal.institute.biometric.generate_token', $school) }}" method="POST">
                            @csrf
                            <button class="btn btn-sm btn-primary w-100" type="submit">
                                <i class="fas fa-key"></i> Generate Token
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Device Cards --}}
    <div class="row g-3">
        @forelse($devices as $device)
        <div class="col-md-4 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="fw-bold mb-1">{{ $device->device_name }}</h6>
                            <small class="text-muted">{{ $device->brand }} {{ $device->model }}</small>
                        </div>
                        <span class="badge {{ $device->status === 'online' ? 'bg-success' : 'bg-danger' }} rounded-pill">
                            {{ $device->status === 'online' ? '🟢 Online' : '🔴 Offline' }}
                        </span>
                    </div>
                    <hr class="my-2">
                    <div class="small text-muted">
                        <div><i class="fas fa-network-wired me-1"></i>{{ $device->ip_address }}:{{ $device->port ?? 4370 }}</div>
                        <div><i class="fas fa-map-marker-alt me-1"></i>{{ $device->location ?? 'অনির্দিষ্ট' }}</div>
                        <div><i class="fas fa-barcode me-1"></i>{{ $device->serial_number ?? 'N/A' }}</div>
                        @if($device->last_seen)
                        <div class="mt-1"><i class="fas fa-clock me-1"></i>শেষ দেখা: {{ $device->last_seen->diffForHumans() }}</div>
                        @endif
                        @if($device->deviceGroup)
                        <div><i class="fas fa-layer-group me-1"></i>{{ $device->deviceGroup->name }}</div>
                        @endif
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top-0 d-flex gap-2">
                    <button class="btn btn-xs btn-outline-primary btn-sm flex-fill"
                        onclick="editDevice({{ $device->id }}, '{{ $device->device_name }}', '{{ $device->ip_address }}', {{ $device->port ?? 4370 }}, '{{ $device->location }}', {{ $device->device_group_id ?? 'null' }})">
                        <i class="fas fa-edit"></i> সম্পাদনা
                    </button>
                    <form action="{{ route('principal.institute.biometric.devices.destroy', [$school, $device]) }}" method="POST"
                          onsubmit="return confirm('এই ডিভাইসটি মুছতে চান?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-xs btn-outline-danger btn-sm">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="text-center py-5 text-muted">
                <i class="fas fa-microchip fa-3x mb-3 d-block"></i>
                কোনো ডিভাইস যোগ করা হয়নি। উপরের বাটন দিয়ে নতুন ডিভাইস যোগ করুন।
            </div>
        </div>
        @endforelse
    </div>
</div>

{{-- Add Device Modal --}}
<div class="modal fade" id="addDeviceModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('principal.institute.biometric.devices.store', $school) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>নতুন ডিভাইস যোগ করুন</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">ডিভাইসের নাম <span class="text-danger">*</span></label>
                            <input type="text" name="device_name" class="form-control" placeholder="যেমন: Main Gate F18" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">ব্র্যান্ড</label>
                            <select name="brand" class="form-select">
                                <option value="ZKTeco">ZKTeco</option>
                                <option value="Hikvision">Hikvision</option>
                                <option value="Suprema">Suprema</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">মডেল</label>
                            <input type="text" name="model" class="form-control" placeholder="F18, MB20...">
                        </div>
                        <div class="col-8">
                            <label class="form-label fw-semibold">আইপি ঠিকানা</label>
                            <input type="text" name="ip_address" class="form-control" placeholder="192.168.1.100">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold">পোর্ট</label>
                            <input type="number" name="port" class="form-control" value="4370">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">সিরিয়াল নম্বর</label>
                            <input type="text" name="serial_number" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">অবস্থান</label>
                            <input type="text" name="location" class="form-control" placeholder="মূল গেট, অফিস...">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">ডিভাইস গ্রুপ</label>
                            <select name="device_group_id" class="form-select">
                                <option value="">-- গ্রুপ নির্বাচন করুন --</option>
                                @foreach($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>সংরক্ষণ করুন</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Edit Device Modal --}}
<div class="modal fade" id="editDeviceModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="editDeviceForm" method="POST">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>ডিভাইস সম্পাদনা</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">ডিভাইসের নাম</label>
                        <input type="text" name="device_name" id="editDeviceName" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-8 mb-3">
                            <label class="form-label fw-semibold">আইপি ঠিকানা</label>
                            <input type="text" name="ip_address" id="editDeviceIp" class="form-control">
                        </div>
                        <div class="col-4 mb-3">
                            <label class="form-label fw-semibold">পোর্ট</label>
                            <input type="number" name="port" id="editDevicePort" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">অবস্থান</label>
                        <input type="text" name="location" id="editDeviceLocation" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">ডিভাইস গ্রুপ</label>
                        <select name="device_group_id" id="editDeviceGroup" class="form-select">
                            <option value="">-- গ্রুপ নির্বাচন করুন --</option>
                            @foreach($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn btn-warning text-dark"><i class="fas fa-save mr-1"></i>আপডেট করুন</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Add Group Modal --}}
<div class="modal fade" id="addGroupModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form action="{{ route('principal.institute.biometric.groups.store', $school) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">নতুন গ্রুপ</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">গ্রুপের নাম <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Main Building Group" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">বিবরণ</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn btn-info text-white"><i class="fas fa-save mr-1"></i>তৈরি করুন</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function editDevice(id, name, ip, port, location, groupId) {
    const baseUrl = "{{ route('principal.institute.biometric.devices.update', [$school, '__ID__']) }}";
    document.getElementById('editDeviceForm').action = baseUrl.replace('__ID__', id);
    document.getElementById('editDeviceName').value = name;
    document.getElementById('editDeviceIp').value = ip;
    document.getElementById('editDevicePort').value = port;
    document.getElementById('editDeviceLocation').value = location;
    if (groupId) document.getElementById('editDeviceGroup').value = groupId;
    $('#editDeviceModal').modal('show');
}

function copyToClipboard(id) {
    var copyText = document.getElementById(id);
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value).then(function() {
        if(typeof toastr !== 'undefined') {
            toastr.success('কপি করা হয়েছে!');
        } else {
            alert('কপি করা হয়েছে!');
        }
    }).catch(function(err) {
        console.error('Failed to copy text: ', err);
    });
}
</script>
@endpush
@endsection
