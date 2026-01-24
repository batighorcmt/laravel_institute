@extends('layouts.admin')
@section('title','SMS সেটিংস')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0"><i class="fas fa-sms mr-1"></i> SMS সেটিংস</h1>
  <button onclick="window.print()" class="btn btn-outline-primary d-none d-md-inline"><i class="fas fa-print mr-1"></i> প্রিন্ট</button>
</div>

<ul class="nav nav-tabs mb-3" id="smsTab" role="tablist">
  <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#api" role="tab">API Settings</a></li>
  <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#class-attendance" role="tab">Class Attendance SMS</a></li>
  <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#extra-class-attendance" role="tab">Extra Class Attendance SMS</a></li>
  <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#templates" role="tab">SMS Templates</a></li>
</ul>
<div class="tab-content" id="smsTabContent">
  <div class="tab-pane fade show active" id="api" role="tabpanel">
    <form method="post" action="{{ route('principal.institute.sms.api.save',$school) }}" class="card p-4">
      @csrf
      <div class="form-group">
        <label>SMS API URL</label>
        <input type="text" name="sms_api_url" value="{{ $api['sms_api_url'] }}" class="form-control" placeholder="https://sms.example.com/api/send">
      </div>
      <div class="form-group">
        <label>API Key</label>
        <input type="text" name="sms_api_key" value="{{ $api['sms_api_key'] }}" class="form-control">
      </div>
      <div class="form-group">
        <label>Sender ID</label>
        <input type="text" name="sms_sender_id" value="{{ $api['sms_sender_id'] }}" class="form-control">
      </div>
      <div class="form-group">
        <label>Masking</label>
        <input type="text" name="sms_masking" value="{{ $api['sms_masking'] }}" class="form-control">
        <small class="text-muted">প্রোভাইডার মাস্কিং সাপোর্ট করলে এখানে দিন।</small>
      </div>
      <button class="btn btn-primary"><i class="fas fa-save mr-1"></i> সংরক্ষণ</button>
    </form>
  </div>
  <div class="tab-pane fade" id="class-attendance" role="tabpanel">
    <div class="card p-4">
      <h5 class="mb-3">ক্লাস হাজিরা SMS সেটিংস</h5>
      <p class="text-muted">ক্লাস হাজিরার ক্ষেত্রে যে স্ট্যাটাসে SMS যাবে তা নির্বাচন করুন।</p>
      <form method="post" action="{{ route('principal.institute.sms.class-attendance.save',$school) }}">
        @csrf
        <div class="custom-control custom-switch mb-2">
          <input type="checkbox" class="custom-control-input" id="sms_class_present" name="sms_class_attendance_present" {{ $classAttendance['sms_class_attendance_present']=='1'?'checked':'' }}>
          <label class="custom-control-label" for="sms_class_present">Present (উপস্থিত)</label>
        </div>
        <div class="custom-control custom-switch mb-2">
          <input type="checkbox" class="custom-control-input" id="sms_class_absent" name="sms_class_attendance_absent" {{ $classAttendance['sms_class_attendance_absent']=='1'?'checked':'' }}>
          <label class="custom-control-label" for="sms_class_absent">Absent (অনুপস্থিত)</label>
        </div>
        <div class="custom-control custom-switch mb-2">
          <input type="checkbox" class="custom-control-input" id="sms_class_late" name="sms_class_attendance_late" {{ $classAttendance['sms_class_attendance_late']=='1'?'checked':'' }}>
          <label class="custom-control-label" for="sms_class_late">Late (বিলম্ব)</label>
        </div>
        <div class="custom-control custom-switch mb-4">
          <input type="checkbox" class="custom-control-input" id="sms_class_halfday" name="sms_class_attendance_half_day" {{ $classAttendance['sms_class_attendance_half_day']=='1'?'checked':'' }}>
          <label class="custom-control-label" for="sms_class_halfday">Half Day (আধা দিন)</label>
        </div>
        <button class="btn btn-primary"><i class="fas fa-save mr-1"></i> সংরক্ষণ</button>
      </form>
    </div>
  </div>
  <div class="tab-pane fade" id="extra-class-attendance" role="tabpanel">
    <div class="card p-4">
      <h5 class="mb-3">এক্সট্রা ক্লাস হাজিরা SMS সেটিংস</h5>
      <p class="text-muted">এক্সট্রা ক্লাস হাজিরার ক্ষেত্রে যে স্ট্যাটাসে SMS যাবে তা নির্বাচন করুন।</p>
      <form method="post" action="{{ route('principal.institute.sms.extra-class-attendance.save',$school) }}">
        @csrf
        <div class="custom-control custom-switch mb-2">
          <input type="checkbox" class="custom-control-input" id="sms_extra_present" name="sms_extra_class_attendance_present" {{ $extraClassAttendance['sms_extra_class_attendance_present']=='1'?'checked':'' }}>
          <label class="custom-control-label" for="sms_extra_present">Present (উপস্থিত)</label>
        </div>
        <div class="custom-control custom-switch mb-2">
          <input type="checkbox" class="custom-control-input" id="sms_extra_absent" name="sms_extra_class_attendance_absent" {{ $extraClassAttendance['sms_extra_class_attendance_absent']=='1'?'checked':'' }}>
          <label class="custom-control-label" for="sms_extra_absent">Absent (অনুপস্থিত)</label>
        </div>
        <div class="custom-control custom-switch mb-2">
          <input type="checkbox" class="custom-control-input" id="sms_extra_late" name="sms_extra_class_attendance_late" {{ $extraClassAttendance['sms_extra_class_attendance_late']=='1'?'checked':'' }}>
          <label class="custom-control-label" for="sms_extra_late">Late (বিলম্ব)</label>
        </div>
        <div class="custom-control custom-switch mb-4">
          <input type="checkbox" class="custom-control-input" id="sms_extra_halfday" name="sms_extra_class_attendance_half_day" {{ $extraClassAttendance['sms_extra_class_attendance_half_day']=='1'?'checked':'' }}>
          <label class="custom-control-label" for="sms_extra_halfday">Half Day (আধা দিন)</label>
        </div>
        <button class="btn btn-primary"><i class="fas fa-save mr-1"></i> সংরক্ষণ</button>
      </form>
    </div>
  </div>
  <div class="tab-pane fade" id="templates" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="mb-0">SMS টেমপ্লেট ({{ $templates->count() }})</h5>
      <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addTemplateModal"><i class="fa fa-plus"></i> নতুন</button>
    </div>
    <table class="table table-bordered table-striped table-sm">
      <thead><tr><th style="width:100px">ধরন</th><th style="width:150px">শিরোনাম</th><th>বডি</th><th style="width:115px">অ্যাকশন</th></tr></thead>
      <tbody>
      @forelse($templates as $t)
        <tr>
          <td>
            @if($t->type == 'general')
              <span class="badge badge-secondary">সাধারণ</span>
            @elseif($t->type == 'class')
              <span class="badge badge-primary">ক্লাস</span>
            @elseif($t->type == 'extra_class')
              <span class="badge badge-warning">এক্সট্রা ক্লাস</span>
            @endif
          </td>
          <td>{{ $t->title }}</td>
          <td><pre class="mb-0" style="white-space:pre-wrap;word-break:break-word;">{{ $t->content }}</pre></td>
          <td>
            <button class="btn btn-warning btn-sm edit-btn" data-data="{{ json_encode(['id' => $t->id, 'title' => $t->title, 'content' => $t->content, 'type' => $t->type]) }}" data-toggle="modal" data-target="#editTemplateModal"><i class="fa fa-edit"></i></button>
            <form method="post" action="{{ route('principal.institute.sms.templates.destroy',[$school,$t]) }}" style="display:inline" onsubmit="return confirm('মুছতে নিশ্চিত?');">
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

<!-- Add Template Modal -->
<div class="modal fade" id="addTemplateModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="{{ route('principal.institute.sms.templates.store',$school) }}">
        @csrf
        <div class="modal-header"><h5 class="modal-title">টেমপ্লেট যুক্ত করুন</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">
          <div class="form-group">
            <label>ধরন</label>
            <select name="type" class="form-control" required>
              <option value="general">সাধারণ</option>
              <option value="class">ক্লাস হাজিরা</option>
              <option value="extra_class">এক্সট্রা ক্লাস হাজিরা</option>
            </select>
          </div>
          <div class="form-group">
            <label>শিরোনাম</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="form-group">
            <label>বডি</label>
            <textarea name="content" class="form-control" rows="4" required></textarea>
            <small class="text-muted">ব্যবহারযোগ্য ভ্যারিয়েবল: {student_name}, {date}, {status}, {school_name}, {amount}, {month}, {exam_name}, {exam_date}, {code}</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">বন্ধ</button>
          <button class="btn btn-success"><i class="fa fa-save mr-1"></i> সংরক্ষণ</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Edit Template Modal -->
<div class="modal fade" id="editTemplateModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" id="editTemplateForm">
        @csrf @method('PATCH')
        <div class="modal-header"><h5 class="modal-title">টেমপ্লেট সম্পাদনা</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">
          <input type="hidden" name="template_id" id="editTemplateId">
          <div class="form-group">
            <label>ধরন</label>
            <select name="type" id="editTemplateType" class="form-control" required>
              <option value="general">সাধারণ</option>
              <option value="class">ক্লাস হাজিরা</option>
              <option value="extra_class">এক্সট্রা ক্লাস হাজিরা</option>
            </select>
          </div>
          <div class="form-group">
            <label>শিরোনাম</label>
            <input type="text" name="title" id="editTemplateTitle" class="form-control" required>
          </div>
          <div class="form-group">
            <label>বডি</label>
            <textarea name="content" id="editTemplateContent" class="form-control" rows="4" required></textarea>
            <small class="text-muted">ভ্যারিয়েবল: {student_name}, {date}, {status}, {school_name}, {amount}, {month}, {exam_name}, {exam_date}, {code}</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">বন্ধ</button>
          <button class="btn btn-warning"><i class="fa fa-save mr-1"></i> আপডেট</button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function(){
  // Hash based tab activation
  if(window.location.hash){
    const h = window.location.hash.replace('#','');
    const tab = document.querySelector('a[href="#'+h+'"]');
    if(tab){ $(tab).tab('show'); }
  }
  $('a[data-toggle="tab"]').on('shown.bs.tab', function(e){
    history.replaceState(null,null,e.target.getAttribute('href'));
  });

  // Edit template modal fill
  $('#editTemplateModal').on('show.bs.modal', function (event) {
    const button = event.relatedTarget; // Button that triggered the modal
    const data = JSON.parse(button.dataset.data);
    document.getElementById('editTemplateId').value = data.id;
    document.getElementById('editTemplateTitle').value = data.title;
    document.getElementById('editTemplateContent').value = data.content;
    document.getElementById('editTemplateType').value = data.type;
    const form = document.getElementById('editTemplateForm');
    form.action = '{{ route('principal.institute.sms.templates.update',[$school,'__ID__']) }}'.replace('__ID__', data.id);
  });
})();
</script>
@endpush
<style>@media print { .nav-tabs, .tab-content > .tab-pane:not(.active), .modal { display:none !important; } }</style>
@endsection