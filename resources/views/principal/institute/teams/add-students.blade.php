@extends('layouts.admin')
@section('title','দলে শিক্ষার্থী যুক্ত করুন')
@section('content')
<div class="d-flex justify-content-between mb-3">
  <h1 class="m-0">দলে শিক্ষার্থী যুক্ত করুন - {{ $team->name }}</h1>
  <a href="{{ route('principal.institute.teams.index',$school) }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> ফিরে যান</a>
</div>

<div class="card mb-3">
  <div class="card-header bg-light"><h5 class="m-0"><i class="fas fa-filter mr-1"></i> ফিল্টার ও অনুসন্ধান</h5></div>
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
          <button class="btn btn-primary w-100"><i class="fas fa-search mr-1"></i> ফিল্টার করুন</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="m-0"><i class="fas fa-user-check mr-1"></i> শিক্ষার্থী তালিকা</h5>
    <div>
      <a href="{{ route('principal.institute.teams.members',[$school,$team]) }}" class="btn btn-sm btn-info" target="_blank">
        <i class="fas fa-users mr-1"></i> বর্তমান সদস্য তালিকা ({{ $team->students()->count() }})
      </a>
    </div>
  </div>
  <div class="card-body">
    @php($total = $enrollments->count())
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="m-0 text-muted">এই ফিল্টারে মোট: <strong>{{ $total }}</strong> জন শিক্ষার্থী পাওয়া গেছে।</h6>
      @if($total > 0)
        <div>
          <button type="button" class="btn btn-sm btn-outline-success mr-1" id="selectAllBtn">
            <i class="fas fa-check-double mr-1"></i> সব যুক্ত করুন
          </button>
          <button type="button" class="btn btn-sm btn-outline-danger" id="clearAllBtn">
            <i class="fas fa-times mr-1"></i> সব বাদ দিন
          </button>
        </div>
      @endif
    </div>
    
    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
      <table class="table table-bordered table-striped table-hover table-sm mb-0">
        <thead class="thead-light">
          <tr>
            <th style="width: 50px;" class="text-center">#</th>
            <th>শিক্ষার্থীর নাম</th>
            <th class="text-center" style="width: 100px;">রোল</th>
            <th class="text-center" style="width: 120px;">শ্রেণি</th>
            <th class="text-center" style="width: 120px;">শাখা</th>
            <th class="text-center" style="width: 150px;">দলে সংযুক্তি</th>
          </tr>
        </thead>
        <tbody>
          @forelse($enrollments as $i=>$row)
            @php($isMember = in_array($row->student_id,$existingIds))
            <tr id="row_{{ $row->student_id }}" class="{{ $isMember ? 'table-success' : '' }}">
              <td class="text-center align-middle">{{ $i+1 }}</td>
              <td class="align-middle">
                <strong>{{ $row->student_name_bn ?? $row->student_name_en }}</strong>
              </td>
              <td class="text-center align-middle">{{ $row->roll_no }}</td>
              <td class="text-center align-middle">{{ $row->class_name }}</td>
              <td class="text-center align-middle">{{ $row->section_name ?? '-' }}</td>
              <td class="text-center align-middle">
                <button type="button" 
                        class="btn btn-xs btn-block toggle-btn {{ $isMember ? 'btn-danger' : 'btn-success' }}" 
                        data-student-id="{{ $row->student_id }}">
                  @if($isMember)
                    <i class="fas fa-minus-circle mr-1"></i> বাদ দিন
                  @else
                    <i class="fas fa-plus-circle mr-1"></i> যুক্ত করুন
                  @endif
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted py-4">কোনো শিক্ষার্থী পাওয়া যায়নি। ফিল্টার পরিবর্তন করে দেখুন।</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  // Dynamic section load
  const classSel = document.getElementById('class_id');
  const sectionSel = document.getElementById('section_id');
  const sectionsUrl = '{{ route("principal.institute.meta.sections", $school) }}';
  
  if (classSel) {
    classSel.addEventListener('change', function(){
      const val = this.value;
      if(!val){
        sectionSel.disabled = true; sectionSel.innerHTML='';
        const opt = document.createElement('option'); opt.value=''; opt.textContent='আগে ক্লাস নির্বাচন করুন'; sectionSel.appendChild(opt);
        return;
      }
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
  }

  // Toggle single student
  document.querySelectorAll('.toggle-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const studentId = this.getAttribute('data-student-id');
      toggleStudent(studentId, this);
    });
  });

  function toggleStudent(studentId, buttonEl) {
    buttonEl.disabled = true;
    const originalHtml = buttonEl.innerHTML;
    buttonEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    return fetch('{{ route("principal.institute.teams.toggle-student", [$school, $team]) }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ student_id: studentId })
    })
    .then(res => res.json())
    .then(data => {
      buttonEl.disabled = false;
      if (data.success) {
        const row = document.getElementById('row_' + studentId);
        if (data.added) {
          buttonEl.className = 'btn btn-xs btn-block toggle-btn btn-danger';
          buttonEl.innerHTML = '<i class="fas fa-minus-circle mr-1"></i> বাদ দিন';
          if (row) row.classList.add('table-success');
        } else {
          buttonEl.className = 'btn btn-xs btn-block toggle-btn btn-success';
          buttonEl.innerHTML = '<i class="fas fa-plus-circle mr-1"></i> যুক্ত করুন';
          if (row) row.classList.remove('table-success');
        }
      } else {
        buttonEl.innerHTML = originalHtml;
        alert('ত্রুটি ঘটেছে। আবার চেষ্টা করুন।');
      }
    })
    .catch(() => {
      buttonEl.disabled = false;
      buttonEl.innerHTML = originalHtml;
      alert('যোগাযোগ ব্যর্থ হয়েছে।');
    });
  }

  // Sequential toggle all
  async function toggleAllVisible(action) {
    const buttons = Array.from(document.querySelectorAll('.toggle-btn'));
    const targetButtons = buttons.filter(btn => {
      const isMember = btn.classList.contains('btn-danger');
      return (action === 'add' && !isMember) || (action === 'remove' && isMember);
    });

    if (targetButtons.length === 0) return;

    // Show loading UI on the action buttons
    const originalContents = [];
    targetButtons.forEach(btn => {
      originalContents.push({ btn: btn, html: btn.innerHTML });
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    });

    for (const btn of targetButtons) {
      const studentId = btn.getAttribute('data-student-id');
      await fetch('{{ route("principal.institute.teams.toggle-student", [$school, $team]) }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ student_id: studentId })
      })
      .then(res => res.json())
      .then(data => {
        btn.disabled = false;
        if (data.success) {
          const row = document.getElementById('row_' + studentId);
          if (data.added) {
            btn.className = 'btn btn-xs btn-block toggle-btn btn-danger';
            btn.innerHTML = '<i class="fas fa-minus-circle mr-1"></i> বাদ দিন';
            if (row) row.classList.add('table-success');
          } else {
            btn.className = 'btn btn-xs btn-block toggle-btn btn-success';
            btn.innerHTML = '<i class="fas fa-plus-circle mr-1"></i> যুক্ত করুন';
            if (row) row.classList.remove('table-success');
          }
        }
      })
      .catch(() => {
        btn.disabled = false;
        // restore original content for this button if failed
        const item = originalContents.find(x => x.btn === btn);
        if (item) btn.innerHTML = item.html;
      });
    }
  }

  // Attach bulk buttons
  const selectAllBtn = document.getElementById('selectAllBtn');
  const clearAllBtn = document.getElementById('clearAllBtn');
  
  if (selectAllBtn) {
    selectAllBtn.addEventListener('click', function(){
      if(confirm('সব দৃশ্যমান শিক্ষার্থীকে এই টিমে যুক্ত করতে চান?')) {
        toggleAllVisible('add');
      }
    });
  }
  if (clearAllBtn) {
    clearAllBtn.addEventListener('click', function(){
      if(confirm('সব দৃশ্যমান শিক্ষার্থীকে এই টিম থেকে বাদ দিতে চান?')) {
        toggleAllVisible('remove');
      }
    });
  }
});
</script>
@endsection