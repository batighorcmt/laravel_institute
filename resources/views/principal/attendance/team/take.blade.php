@extends('layouts.admin')
@section('title','Team Attendance - ' . ($team->name ?? ''))
@section('content')
<div class="d-flex justify-content-between mb-3">
  <h1 class="m-0">টিম হাজিরা - {{ $team->name }} @if($schoolClass) {{ $schoolClass->name }} @endif @if($section) {{ $section->name }} @endif</h1>
  <a href="{{ route('principal.institute.attendance.team.index', $school) }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left mr-1"></i> ফিরে যান
  </a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

@if($enrollments->count() > 0)
    @if($isExistingRecord)
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> এই তারিখের টিম হাজিরা ইতিমধ্যে রেকর্ড করা হয়েছে। আপনি এখন এটি আপডেট করতে পারেন।
        </div>
    @endif
    <form method="POST" action="{{ route('principal.institute.attendance.team.store', $school) }}" id="teamAttendanceForm">
        @csrf
        <input type="hidden" name="team_id" value="{{ $team->id }}">
        @if($schoolClass)<input type="hidden" name="class_id" value="{{ $schoolClass->id }}">@endif
        @if($section)<input type="hidden" name="section_id" value="{{ $section->id }}">@endif
        <input type="hidden" name="date" value="{{ $date }}">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>
                {{ $isExistingRecord ? 'টিম হাজিরা আপডেট করুন' : 'টিম হাজিরা রেকর্ড করুন' }}
                <small class="text-muted">({{ \Carbon\Carbon::parse($date)->format('d/m/Y') }})</small>
            </h4>
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> {{ $isExistingRecord ? 'আপডেট করুন' : 'সংরক্ষণ করুন' }}</button>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped team-attendance-table">
                        <thead>
                            <tr>
                                <th width="60">রোল</th>
                                <th>শিক্ষার্থীর নাম</th>
                                <th class="radio-cell"><button type="button" class="btn btn-attendance-header" data-status="present" id="select-all-present"><i class="fas fa-check-circle"></i><br>Present</button></th>
                                <th class="radio-cell"><button type="button" class="btn btn-attendance-header" data-status="absent" id="select-all-absent"><i class="fas fa-times-circle"></i><br>Absent</button></th>
                                <th class="radio-cell"><button type="button" class="btn btn-attendance-header" data-status="late" id="select-all-late"><i class="fas fa-clock"></i><br>Late</button></th>
                                <th width="200">মন্তব্য</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($enrollments as $en)
                            @php $sid = $en->student_id; @endphp
                            <tr>
                                <td>{{ $en->roll_no }}</td>
                                <td class="student-name">{{ $en->student->student_name_en }}</td>
                                <td class="radio-present">
                                    <input type="radio" name="attendance[{{ $sid }}][status]" id="present_{{ $sid }}" value="present" {{ (isset($existingAttendance[$sid]) && $existingAttendance[$sid]=='present')?'checked':'' }}>
                                    <label for="present_{{ $sid }}" class="radio-label"><i class="fas fa-check-circle"></i></label>
                                </td>
                                <td class="radio-absent">
                                    <input type="radio" name="attendance[{{ $sid }}][status]" id="absent_{{ $sid }}" value="absent" {{ (isset($existingAttendance[$sid]) && $existingAttendance[$sid]=='absent')?'checked':'' }}>
                                    <label for="absent_{{ $sid }}" class="radio-label"><i class="fas fa-times-circle"></i></label>
                                </td>
                                <td class="radio-late">
                                    <input type="radio" name="attendance[{{ $sid }}][status]" id="late_{{ $sid }}" value="late" {{ (isset($existingAttendance[$sid]) && $existingAttendance[$sid]=='late')?'checked':'' }}>
                                    <label for="late_{{ $sid }}" class="radio-label"><i class="fas fa-clock"></i></label>
                                </td>
                                <td><input type="text" class="form-control form-control-sm" name="attendance[{{ $sid }}][remarks]" value="{{ $remarks[$sid] ?? '' }}" placeholder="মন্তব্য"></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="sticky-submit text-right mt-2">
            <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save"></i> {{ $isExistingRecord ? 'আপডেট করুন' : 'সংরক্ষণ করুন' }}</button>
        </div>
    </form>
@else
  <div class="alert alert-info text-center"><i class="fas fa-info-circle"></i> এই ফিল্টার অনুযায়ী কোনো শিক্ষার্থী পাওয়া যায়নি।</div>
@endif

<style>
.team-attendance-table th { background-color:#f8f9fc; color:#4e73df; font-weight:600; text-align:center; vertical-align:middle; padding:10px 5px; }
.team-attendance-table td { text-align:center; vertical-align:middle; padding:8px 5px; }
.radio-cell { width:80px; text-align:center; }
.radio-label { display:block; width:40px; height:40px; line-height:40px; border-radius:50%; cursor:pointer; transition:all .3s; margin:0 auto; font-size:18px; background:#e9ecef; color:#6c757d; border:2px solid #6c757d; }
.radio-present input[type="radio"]:checked + .radio-label { background:#28a745; color:#fff; border-color:#28a745; }
.radio-absent input[type="radio"]:checked + .radio-label { background:#dc3545; color:#fff; border-color:#dc3545; }
.radio-late input[type="radio"]:checked + .radio-label { background:#ffc107; color:#fff; border-color:#ffc107; }
input[type="radio"] { display:none; }
.student-name { text-align:left; padding-left:15px!important; }
.btn-attendance-header { width:100%; font-size:1rem; font-weight:bold; color:#adb5bd; background:#e9ecef; border:1px solid #ced4da; transition:all .3s; padding:10px 0; }
.btn-attendance-header.active-present { background:#28a745; color:#fff; }
.btn-attendance-header.active-absent { background:#dc3545; color:#fff; }
.btn-attendance-header.active-late { background:#ffc107; color:#fff; }
.sticky-submit { position:sticky; bottom:0; background:#fff; padding:5px 10px; border-top:1px solid #eee; box-shadow:0 -2px 10px rgba(0,0,0,0.1); z-index:100; }
tbody tr.att-row-present { background:#e8f7ee; }
tbody tr.att-row-absent { background:#fde8eb; }
tbody tr.att-row-late { background:#fff6e0; }
</style>

<script>
(function(){
 const studentIds = @json($enrollments->pluck('student_id')->values());
 let lastBulkStatus = null;
 function updateRowStyles(){
   document.querySelectorAll('table.team-attendance-table tbody tr').forEach(function(tr){
     tr.classList.remove('att-row-present','att-row-absent','att-row-late');
     if(tr.querySelector('input[type="radio"][value="present"]:checked')) tr.classList.add('att-row-present');
     else if(tr.querySelector('input[type="radio"][value="absent"]:checked')) tr.classList.add('att-row-absent');
     else if(tr.querySelector('input[type="radio"][value="late"]:checked')) tr.classList.add('att-row-late');
   });
 }
 function updateHeaderButtons(){
   const total = document.querySelectorAll('table.team-attendance-table tbody tr').length;
   const present = document.querySelectorAll('input[type="radio"][value="present"]:checked').length;
   const absent = document.querySelectorAll('input[type="radio"][value="absent"]:checked').length;
   const late = document.querySelectorAll('input[type="radio"][value="late"]:checked').length;
   document.querySelectorAll('.btn-attendance-header').forEach(btn=>btn.classList.remove('active-present','active-absent','active-late'));
   if(total>0){
     if(present===total) document.getElementById('select-all-present').classList.add('active-present');
     else if(absent===total) document.getElementById('select-all-absent').classList.add('active-absent');
     else if(late===total) document.getElementById('select-all-late').classList.add('active-late');
   }
 }
 document.querySelectorAll('.btn-attendance-header').forEach(function(btn){
   btn.addEventListener('click', function(){
     const status = btn.getAttribute('data-status');
     lastBulkStatus = status;
     studentIds.forEach(function(id){
       const el = document.getElementById(status+'_'+id);
       if(el){ el.checked = true; el.dispatchEvent(new Event('change',{bubbles:true})); }
     });
     updateHeaderButtons();
     updateRowStyles();
   });
 });
 document.addEventListener('change', function(e){
   if(e.target.matches('input[type="radio"][name^="attendance["]')){
     updateHeaderButtons();
     updateRowStyles();
   }
 });
 const form = document.getElementById('teamAttendanceForm');
 if(form){
   form.addEventListener('submit', function(e){
     let allOk = true;
     document.querySelectorAll('table.team-attendance-table tbody tr').forEach(function(tr){
       const anyChecked = tr.querySelector('input[type="radio"]:checked');
       if(!anyChecked && lastBulkStatus){
         const sid = tr.querySelector('input[type="radio"][value="present"]').id.split('_')[1];
         const el = document.getElementById(lastBulkStatus+'_'+sid);
         if(el){ el.checked = true; }
       }
     });
     document.querySelectorAll('table.team-attendance-table tbody tr').forEach(function(tr){
       const anyChecked = tr.querySelector('input[type="radio"]:checked');
       if(!anyChecked){ allOk=false; tr.classList.add('table-danger'); }
       else { tr.classList.remove('table-danger'); }
     });
     updateRowStyles();
     if(!allOk){ e.preventDefault(); alert('সকল শিক্ষার্থীর জন্য স্ট্যাটাস নির্বাচন বাধ্যতামূলক।'); }
   });
 }
 updateHeaderButtons();
 updateRowStyles();
})();
</script>
@endsection