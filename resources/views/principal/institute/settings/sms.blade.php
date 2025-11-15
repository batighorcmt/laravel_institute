@extends('layouts.admin')
@section('title','SMS সেটিংস')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0"><i class="fas fa-sms mr-1"></i> SMS সেটিংস</h1>
  <button onclick="window.print()" class="btn btn-outline-primary d-none d-md-inline"><i class="fas fa-print mr-1"></i> প্রিন্ট</button>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<ul class="nav nav-tabs mb-3" id="smsTab" role="tablist">
  <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#api" role="tab">API Settings</a></li>
  <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#attendance" role="tab">Attendance SMS</a></li>
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
  <div class="tab-pane fade" id="attendance" role="tabpanel">
    <div class="card p-4">
      <h5 class="mb-3">হাজিরা SMS সেটিংস</h5>
      <p class="text-muted">যে স্ট্যাটাসে SMS যাবে তা নির্বাচন করুন।</p>
      <form method="post" action="{{ route('principal.institute.sms.attendance.save',$school) }}">
        @csrf
        <div class="custom-control custom-switch mb-2">
          <input type="checkbox" class="custom-control-input" id="sms_present" name="sms_attendance_present" {{ $attendance['sms_attendance_present']=='1'?'checked':'' }}>
          <label class="custom-control-label" for="sms_present">Present (উপস্থিত)</label>
        </div>
        <div class="custom-control custom-switch mb-2">
          <input type="checkbox" class="custom-control-input" id="sms_absent" name="sms_attendance_absent" {{ $attendance['sms_attendance_absent']=='1'?'checked':'' }}>
          <label class="custom-control-label" for="sms_absent">Absent (অনুপস্থিত)</label>
        </div>
        <div class="custom-control custom-switch mb-2">
          <input type="checkbox" class="custom-control-input" id="sms_late" name="sms_attendance_late" {{ $attendance['sms_attendance_late']=='1'?'checked':'' }}>
          <label class="custom-control-label" for="sms_late">Late (বিলম্ব)</label>
        </div>
        <div class="custom-control custom-switch mb-4">
          <input type="checkbox" class="custom-control-input" id="sms_halfday" name="sms_attendance_half_day" {{ $attendance['sms_attendance_half_day']=='1'?'checked':'' }}>
          <label class="custom-control-label" for="sms_halfday">Half Day (আধা দিন)</label>
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
      <thead><tr><th style="width:150px">শিরোনাম</th><th>বডি</th><th style="width:115px">অ্যাকশন</th></tr></thead>
      <tbody>
      @forelse($templates as $t)
        <tr>
          <td>{{ $t->title }}</td>
          <td><pre class="mb-0" style="white-space:pre-wrap;word-break:break-word;">{{ $t->content }}</pre></td>
          <td>
            <button class="btn btn-warning btn-sm edit-btn" data-id="{{ $t->id }}" data-title="{{ e($t->title) }}" data-content="{{ e($t->content) }}" data-toggle="modal" data-target="#editTemplateModal"><i class="fa fa-edit"></i></button>
            <form method="post" action="{{ route('principal.institute.sms.templates.destroy',[$school,$t]) }}" style="display:inline" onsubmit="return confirm('মুছতে নিশ্চিত?');">
              @csrf @method('DELETE')
              <button class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="3" class="text-center text-muted">কোনো টেমপ্লেট নেই</td></tr>
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
            <label>শিরোনাম</label>
            <input type="text" name="title" id="editTemplateTitle" class="form-control" required>
          </div>
          <div class="form-group">
            <label>বডি</label>
            <textarea name="content" id="editTemplateContent" class="form-control" rows="4" required></textarea>
            <small class="text-muted">ভ্যারিয়েবল: {student_name}, {date}, {status}, {school_name}, {amount}, {month}, {exam_name}, {exam_date}, {code}</small>
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
  document.querySelectorAll('.edit-btn').forEach(btn=>{
    btn.addEventListener('click', function(){
      const id = this.dataset.id;
      const title = this.dataset.title;
      const content = this.dataset.content;
      document.getElementById('editTemplateId').value = id;
      document.getElementById('editTemplateTitle').value = title;
      document.getElementById('editTemplateContent').value = content;
      const form = document.getElementById('editTemplateForm');
      form.action = '{{ route('principal.institute.sms.templates.update',[$school,'__ID__']) }}'.replace('__ID__', id);
    });
  });
})();
</script>
@endpush
<style>@media print { .nav-tabs, .tab-content > .tab-pane:not(.active), .modal { display:none !important; } }</style>
@endsection