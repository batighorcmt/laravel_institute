@extends('layouts.admin')
@section('title','দলে শিক্ষার্থী যুক্ত করুন')
@section('content')
<div class="d-flex justify-content-between mb-3">
  <h1 class="m-0">দলে শিক্ষার্থী যুক্ত করুন - {{ $team->name }}</h1>
  <a href="{{ route('principal.institute.teams.index',$school) }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> ফিরে যান</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="card mb-3">
  <div class="card-header"><h5 class="m-0">ফিল্টার</h5></div>
  <div class="card-body">
    <form method="get" action="{{ route('principal.institute.teams.add-students',[$school,$team]) }}" id="filterForm" class="mb-0">
      <div class="form-row">
        <div class="form-group col-md-3">
          <label>ক্লাস</label>
          <select name="class_id" id="class_id" class="form-control">
            <option value="">সব</option>
            @foreach($classes as $c)
              <option value="{{ $c->id }}" {{ $selectedClass==$c->id?'selected':'' }}>{{ $c->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-3">
          <label>শাখা</label>
          <select name="section_id" id="section_id" class="form-control" {{ $selectedClass? '' : 'disabled' }}>
            <option value="">{{ $selectedClass? 'সব' : 'আগে ক্লাস নির্বাচন করুন' }}</option>
            @foreach($sections as $s)
              <option value="{{ $s->id }}" {{ $selectedSection==$s->id?'selected':'' }}>{{ $s->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-3">
          <label>নাম / রোল সার্চ</label>
          <input type="text" name="q" class="form-control" value="{{ $q }}" placeholder="নাম বা রোল...">
        </div>
        <div class="form-group col-md-3 d-flex align-items-end">
          <button class="btn btn-primary w-100"><i class="fas fa-search mr-1"></i> ফিল্টার</button>
        </div>
      </div>
    </form>
  </div>
</div>

<form method="post" action="{{ route('principal.institute.teams.store-students',[$school,$team]) }}" id="addStudentsForm">
  @csrf
  <div class="card">
    <div class="card-header">
      <h5 class="m-0">শিক্ষার্থী নির্বাচন করুন</h5>
    </div>
    <div class="card-body">
      @php($total = $enrollments->count())
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="m-0">মিলেছে: {{ $total }} শিক্ষার্থী</h6>
        @if($total>0)
          <div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="selectAllBtn">সব নির্বাচন</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearAllBtn">সাফ করুন</button>
            <a href="{{ route('principal.institute.teams.members',[$school,$team]) }}" class="btn btn-sm btn-outline-primary ml-1" target="_blank"><i class="fas fa-print mr-1"></i> সদস্য তালিকা / প্রিন্ট</a>
          </div>
        @endif
      </div>
      <div class="form-group mb-0">
        <div class="border rounded p-3" style="max-height: 420px; overflow-y: auto;">
          @forelse($enrollments as $row)
            @php($isMember = in_array($row->student_id,$existingIds))
            <div class="form-check {{ $isMember? 'bg-light rounded px-2 py-1' : '' }}">
              <input class="form-check-input" type="checkbox" name="student_ids[]" value="{{ $row->student_id }}" id="student_{{ $row->student_id }}" {{ $isMember? 'checked' : '' }}>
              <label class="form-check-label" for="student_{{ $row->student_id }}">
                {{ $row->student_name_bn ?? $row->student_name_en }}
                <span class="text-muted">- রোল: {{ $row->roll_no }} - {{ $row->class_name }}@if($row->section_name) / {{ $row->section_name }}@endif</span>
                @if($isMember)<span class="badge badge-success ml-1">দলে আছে</span>@endif
              </label>
            </div>
          @empty
            <div class="text-muted">কোনো শিক্ষার্থী পাওয়া যায়নি। ফিল্টার পরিবর্তন করে দেখুন।</div>
          @endforelse
        </div>
      </div>
    </div>
    <div class="card-footer">
      <button type="submit" class="btn btn-success"><i class="fas fa-plus mr-1"></i> যুক্ত করুন</button>
      <a href="{{ route('principal.institute.teams.index',$school) }}" class="btn btn-secondary ml-2">বাতিল</a>
    </div>
  </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const classSel = document.getElementById('class_id');
  const sectionSel = document.getElementById('section_id');
  const filterForm = document.getElementById('filterForm');
  const sectionsUrl = '{{ route("principal.institute.meta.sections", $school) }}';
  classSel.addEventListener('change', function(){
    const val = this.value;
    if(!val){
      sectionSel.disabled = true; sectionSel.innerHTML='';
      const opt = document.createElement('option'); opt.value=''; opt.textContent='আগে ক্লাস নির্বাচন করুন'; sectionSel.appendChild(opt);
      return;
    }
    // AJAX load sections
    sectionSel.disabled = true;
    sectionSel.innerHTML='';
    const loadingOpt = document.createElement('option'); loadingOpt.value=''; loadingOpt.textContent='লোড হচ্ছে...'; sectionSel.appendChild(loadingOpt);
    fetch(sectionsUrl + '?class_id=' + encodeURIComponent(val))
      .then(r=>r.json())
      .then(data=>{
        sectionSel.innerHTML='';
        const allOpt = document.createElement('option'); allOpt.value=''; allOpt.textContent='সব'; sectionSel.appendChild(allOpt);
        if(Array.isArray(data) && data.length){
          data.forEach(sec=>{
            const opt = document.createElement('option'); opt.value=sec.id; opt.textContent=sec.name; sectionSel.appendChild(opt);
          });
        } else {
          const opt = document.createElement('option'); opt.value=''; opt.textContent='কোন শাখা নেই'; sectionSel.appendChild(opt);
        }
        sectionSel.disabled = false;
      })
      .catch(()=>{
        sectionSel.innerHTML='';
        const errOpt = document.createElement('option'); errOpt.value=''; errOpt.textContent='লোড ব্যর্থ'; sectionSel.appendChild(errOpt);
        sectionSel.disabled = false;
      });
  });

  // Select all / clear all
  const selectAllBtn = document.getElementById('selectAllBtn');
  const clearAllBtn = document.getElementById('clearAllBtn');
  if(selectAllBtn){
    selectAllBtn.addEventListener('click', function(){
      document.querySelectorAll('input.form-check-input[type="checkbox"][name="student_ids[]"]').forEach(cb=>cb.checked=true);
    });
  }
  if(clearAllBtn){
    clearAllBtn.addEventListener('click', function(){
      document.querySelectorAll('input.form-check-input[type="checkbox"][name="student_ids[]"]').forEach(cb=>cb.checked=false);
    });
  }

  // Prevent submit if no students selected
  const addForm = document.getElementById('addStudentsForm');
  addForm.addEventListener('submit', function(e){
    const any = document.querySelector('input[name="student_ids[]"]:checked');
    if(!any){
      e.preventDefault();
      alert('কমপক্ষে একজন শিক্ষার্থী নির্বাচন করুন।');
    }
  });
});
</script>
@endsection