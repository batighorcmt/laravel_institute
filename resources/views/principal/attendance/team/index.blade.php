@extends('layouts.admin')
@section('title','Team Attendance')
@section('content')
<div class="d-flex justify-content-between mb-3">
  <h1 class="m-0">টিম / গ্রুপ হাজিরা</h1>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="card">
  <div class="card-header"><h3 class="card-title">ফিল্টার</h3></div>
  <div class="card-body">
  <form method="GET" action="{{ route('principal.institute.attendance.team.take', $school) }}" class="mb-2" id="teamAttendanceFilter">
      <div class="row">
        <div class="col-md-3">
          <label class="required-field">টিম নির্বাচন করুন</label>
          <select name="team_id" id="team_id" class="form-control" required>
            <option value="">নির্বাচন করুন</option>
            @foreach($teams as $t)
              <option value="{{ $t->id }}" {{ $selectedTeam==$t->id?'selected':'' }}>{{ $t->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label>ক্লাস (ঐচ্ছিক)</label>
          <select name="class_id" id="class_id" class="form-control">
            <option value="">সব</option>
            @foreach($classes as $c)
              <option value="{{ $c->id }}" {{ $selectedClass==$c->id?'selected':'' }}>{{ $c->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label>শাখা (ঐচ্ছিক)</label>
          <select name="section_id" id="section_id" class="form-control" {{ $selectedClass ? '' : 'disabled' }}>
            <option value="">{{ $selectedClass? 'সব' : 'আগে ক্লাস নির্বাচন করুন' }}</option>
            @foreach($sections as $s)
              <option value="{{ $s->id }}" {{ $selectedSection==$s->id?'selected':'' }}>{{ $s->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="required-field">তারিখ</label>
          <input type="date" class="form-control" name="date" value="{{ $date }}" required>
        </div>
      </div>
      <div class="row mt-3">
        <div class="col-md-12">
          <button type="submit" class="btn btn-primary"><i class="fas fa-eye"></i> দেখুন / হাজিরা নিন</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const classSelect = document.getElementById('class_id');
  const sectionSelect = document.getElementById('section_id');
  const sectionsUrl = '{{ route("principal.institute.meta.sections", $school) }}';

  function resetSections(placeholder){
    sectionSelect.innerHTML = '';
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = placeholder || 'শাখা নির্বাচন করুন';
    sectionSelect.appendChild(opt);
  }

  async function loadSections(classId){
    resetSections('লোড হচ্ছে...');
    sectionSelect.disabled = true;
    try{
      const resp = await fetch(sectionsUrl + '?class_id=' + encodeURIComponent(classId));
      if(!resp.ok) throw new Error('Network error');
      const data = await resp.json();
      resetSections('শাখা নির্বাচন করুন');
      if(Array.isArray(data) && data.length){
        data.forEach(sec => {
          const opt = document.createElement('option');
          opt.value = sec.id;
          opt.textContent = sec.name;
          // preserve previously selected
          if ('{{ (string)$selectedSection }}' && String(sec.id) === '{{ (string)$selectedSection }}') {
            opt.selected = true;
          }
          sectionSelect.appendChild(opt);
        });
      } else {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = 'কোন শাখা নেই';
        sectionSelect.appendChild(opt);
      }
    } catch(e){
      resetSections('লোড হতে ব্যর্থ');
      console.error('Section load failed', e);
    } finally {
      sectionSelect.disabled = false;
    }
  }

  classSelect.addEventListener('change', function(){
    const val = this.value;
    if(val){ loadSections(val); }
    else { resetSections('আগে ক্লাস নির্বাচন করুন'); sectionSelect.disabled = true; }
  });

  // If page loaded with a selected class but no sections filled (e.g., first load), optionally load
  if (classSelect.value && sectionSelect.options.length <= 1) {
    loadSections(classSelect.value);
  }
});
</script>
@endsection