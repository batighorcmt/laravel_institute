@extends('layouts.admin')

@section('title','SMS সেটিংস')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="m-0"><i class="fas fa-sms mr-1"></i> SMS সেটিংস</h1>
    <button onclick="window.print()" class="btn btn-outline-primary d-none d-md-inline">
        <i class="fas fa-print mr-1"></i> প্রিন্ট
    </button>
</div>

{{-- Tabs --}}
<ul class="nav nav-tabs mb-3" id="smsTab" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#api">API Settings</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#class-attendance">Class Attendance SMS</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#extra-class-attendance">Extra Class Attendance SMS</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#templates">SMS Templates</a></li>
</ul>

<div class="tab-content">

    {{-- API SETTINGS --}}
    <div class="tab-pane fade show active" id="api">
        <form method="post" action="{{ route('principal.institute.sms.api.save',$school) }}" class="card p-4">
            @csrf
            <div class="form-group">
                <label>SMS API URL</label>
                <input type="text" name="sms_api_url" class="form-control"
                       value="{{ $api['sms_api_url'] ?? '' }}">
            </div>
            <div class="form-group">
                <label>API Key</label>
                <input type="text" name="sms_api_key" class="form-control"
                       value="{{ $api['sms_api_key'] ?? '' }}">
            </div>
            <div class="form-group">
                <label>Sender ID</label>
                <input type="text" name="sms_sender_id" class="form-control"
                       value="{{ $api['sms_sender_id'] ?? '' }}">
            </div>
            <div class="form-group">
                <label>Masking</label>
                <input type="text" name="sms_masking" class="form-control"
                       value="{{ $api['sms_masking'] ?? '' }}">
                <small class="text-muted">প্রোভাইডার মাস্কিং সাপোর্ট করলে এখানে দিন</small>
            </div>
            <button class="btn btn-primary"><i class="fa fa-save mr-1"></i> সংরক্ষণ</button>
        </form>
    </div>

    {{-- CLASS ATTENDANCE --}}
    <div class="tab-pane fade" id="class-attendance">
        <div class="card p-4">
            <h5>ক্লাস হাজিরা SMS সেটিংস</h5>
            <form method="post" action="{{ route('principal.institute.sms.class-attendance.save',$school) }}">
                @csrf
                @foreach([
                    'present' => 'উপস্থিত',
                    'absent' => 'অনুপস্থিত',
                    'late' => 'বিলম্ব',
                    'half_day' => 'আধা দিন'
                ] as $k => $label)
                <div class="custom-control custom-switch mb-2">
                    <input type="checkbox"
                           class="custom-control-input"
                           id="sms_class_{{ $k }}"
                           name="sms_class_attendance_{{ $k }}"
                           {{ ($classAttendance['sms_class_attendance_'.$k] ?? 0) == 1 ? 'checked' : '' }}>
                    <label class="custom-control-label" for="sms_class_{{ $k }}">{{ ucfirst($k) }} ({{ $label }})</label>
                </div>
                @endforeach
                <button class="btn btn-primary mt-3"><i class="fa fa-save mr-1"></i> সংরক্ষণ</button>
            </form>
        </div>
    </div>

    {{-- EXTRA CLASS ATTENDANCE --}}
    <div class="tab-pane fade" id="extra-class-attendance">
        <div class="card p-4">
            <h5>এক্সট্রা ক্লাস হাজিরা SMS সেটিংস</h5>
            <form method="post" action="{{ route('principal.institute.sms.extra-class-attendance.save',$school) }}">
                @csrf
                @foreach([
                    'present' => 'উপস্থিত',
                    'absent' => 'অনুপস্থিত',
                    'late' => 'বিলম্ব',
                    'half_day' => 'আধা দিন'
                ] as $k => $label)
                <div class="custom-control custom-switch mb-2">
                    <input type="checkbox"
                           class="custom-control-input"
                           id="sms_extra_{{ $k }}"
                           name="sms_extra_class_attendance_{{ $k }}"
                           {{ ($extraClassAttendance['sms_extra_class_attendance_'.$k] ?? 0) == 1 ? 'checked' : '' }}>
                    <label class="custom-control-label" for="sms_extra_{{ $k }}">{{ ucfirst($k) }} ({{ $label }})</label>
                </div>
                @endforeach
                <button class="btn btn-primary mt-3"><i class="fa fa-save mr-1"></i> সংরক্ষণ</button>
            </form>
        </div>
    </div>

    {{-- TEMPLATES --}}
    <div class="tab-pane fade" id="templates">
        <div class="d-flex justify-content-between mb-2">
            <h5>SMS টেমপ্লেট ({{ $templates->count() }})</h5>
            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addTemplateModal">
                <i class="fa fa-plus"></i> নতুন
            </button>
        </div>

        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>ধরন</th>
                    <th>শিরোনাম</th>
                    <th>বডি</th>
                    <th width="120">অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
            @forelse($templates as $t)
                <tr>
                    <td>
                        <span class="badge badge-{{ $t->type=='general'?'secondary':($t->type=='class'?'primary':'warning') }}">
                            {{ $t->type }}
                        </span>
                    </td>
                    <td>{{ $t->title }}</td>
                    <td><pre class="mb-0">{{ $t->content }}</pre></td>
                    <td>
                        <button class="btn btn-warning btn-sm"
                            data-toggle="modal"
                            data-target="#editTemplateModal"
                            data-id="{{ $t->id }}"
                            data-type="{{ $t->type }}"
                            data-title='@json($t->title)'
                            data-content='@json($t->content)'
                            data-action="{{ route('principal.institute.sms.templates.update',[$school,$t->id]) }}"
                            onclick="populateEditTemplate(this)">
                            <i class="fa fa-edit"></i>
                        </button>

                        <form method="post"
                              action="{{ route('principal.institute.sms.templates.destroy',[$school,$t]) }}"
                              class="d-inline"
                              onsubmit="return confirm('মুছতে নিশ্চিত?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">কোনো টেমপ্লেট নেই</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- EDIT MODAL --}}
{{-- ADD MODAL --}}
<div class="modal fade" id="addTemplateModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" id="addTemplateForm" action="{{ route('principal.institute.sms.templates.store', $school) }}">
                @csrf
                <div class="modal-header">
                    <h5>নতুন টেমপ্লেট</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>ধরন</label>
                        <select name="type" id="addTemplateType" class="form-control">
                            <option value="">নির্বাচন</option>
                            <option value="general">সাধারণ</option>
                            <option value="class">ক্লাস হাজিরা</option>
                            <option value="extra_class">এক্সট্রা ক্লাস হাজিরা</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>শিরোনাম</label>
                        <input type="text" id="addTemplateTitle" name="title" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>বডি</label>
                        <textarea id="addTemplateContent" name="content" rows="4" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success"><i class="fa fa-save mr-1"></i> সেভ</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="editTemplateModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" id="editTemplateForm">
                @csrf @method('PATCH')
                <div class="modal-header">
                    <h5>টেমপ্লেট সম্পাদনা</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editTemplateId">
                    <div class="form-group">
                        <label>ধরন</label>
                        <select name="type" id="editTemplateType" class="form-control">
                            <option value="">নির্বাচন</option>
                            <option value="general">সাধারণ</option>
                            <option value="class">ক্লাস হাজিরা</option>
                            <option value="extra_class">এক্সট্রা ক্লাস হাজিরা</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>শিরোনাম</label>
                        <input type="text" id="editTemplateTitle" name="title" value="" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>বডি</label>
                        <textarea id="editTemplateContent" name="content" rows="4" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-warning"><i class="fa fa-save mr-1"></i> আপডেট</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function populateEditTemplate(btn){
    const getRaw = (name) => (btn.getAttribute('data-' + name) || btn.dataset[name] || '');
    const tryParse = (v) => { try { return JSON.parse(v); } catch (e) { return v; } };
    const htmlDecode = (input) => { var e = document.createElement('textarea'); e.innerHTML = input || ''; return e.value; };
    const rawId = getRaw('id');
    const rawType = getRaw('type');
    const rawTitle = getRaw('title');
    const rawContent = getRaw('content');
    const rawAction = getRaw('action');
    const id = tryParse(rawId);
    const type = tryParse(rawType);
    const title = tryParse(rawTitle);
    const content = tryParse(rawContent);
    document.getElementById('editTemplateId').value = id;
    document.getElementById('editTemplateType').value = htmlDecode(type);
    document.getElementById('editTemplateTitle').value = htmlDecode(title);
    document.getElementById('editTemplateContent').value = htmlDecode(content);
    if(rawAction){ document.getElementById('editTemplateForm').setAttribute('action', rawAction); }
}
$('#editTemplateModal').on('show.bs.modal', function (event) {
    const btn = event.relatedTarget;

    const getRaw = (name) => (btn.getAttribute('data-' + name) || btn.dataset[name] || '');
    const tryParse = (v) => { try { return JSON.parse(v); } catch (e) { return v; } };
    const htmlDecode = (input) => { var e = document.createElement('textarea'); e.innerHTML = input || ''; return e.value; };

    const rawId = getRaw('id');
    const rawType = getRaw('type');
    const rawTitle = getRaw('title');
    const rawContent = getRaw('content');
    const rawAction = getRaw('action');

    const id = tryParse(rawId);
    const type = tryParse(rawType);
    const title = tryParse(rawTitle);
    const content = tryParse(rawContent);

    $('#editTemplateId').val(id);
    $('#editTemplateType').val(htmlDecode(type));
    $('#editTemplateTitle').val(htmlDecode(title));
    $('#editTemplateContent').val(htmlDecode(content));

    // Prefer an explicit per-button data-action if available (avoids replacement bugs)
    if (rawAction) {
        $('#editTemplateForm').attr('action', rawAction);
    } else {
        $('#editTemplateForm').attr(
            'action',
            "{{ route('principal.institute.sms.templates.update',[$school,'__ID__']) }}".replace('__ID__', id)
        );
    }
});

// Reset add modal fields when opened
$('#addTemplateModal').on('show.bs.modal', function () {
    const form = document.getElementById('addTemplateForm');
    if (form) { form.reset(); }
});
</script>
@endpush

<style>
@media print {
    .nav-tabs, .modal, button { display:none !important; }
}
</style>
