@extends('layouts.admin')
@section('title','ক্লাস রুটিন')

@section('content')
@push('styles')
<style>
@media (max-width: 576px){
  .routine-top .card { margin-bottom: .85rem; }
}
#routineGrid th, #routineGrid td { vertical-align: middle; }
.cell-entry { background:#f8f9fa; border:1px dashed #e1e7ee; border-radius:4px; padding:.25rem .4rem; margin-bottom:.35rem; }
.cell-entry .small { line-height:1.2; }
</style>
<!-- Select2 CSS is bundled via Vite (resources/css/app.css) in production -->
@endpush
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0"><i class="fas fa-table mr-1"></i> ক্লাস রুটিন</h1>
</div>
<div class="card routine-top">
  <div class="card-body">
    <div class="form-row">
      <div class="form-group col-md-4">
        <label>শ্রেণি</label>
        <select id="class_id" class="form-control">
          <option value="">— শ্রেণি —</option>
          @foreach($classes as $c)
            <option value="{{ $c->id }}">{{ $c->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group col-md-4">
        <label>শাখা</label>
        <select id="section_id" class="form-control" disabled>
          <option value="">— শাখা —</option>
        </select>
        <small id="classTeacherInfo" class="text-muted d-block mt-1" style="display:none"></small>
      </div>
      <div class="form-group col-md-4 align-self-end text-right">
        <div id="periodControls" class="d-inline-block" style="display:none">
          <span class="mr-2">পিরিয়ড: <b id="periodCountDisplay">0</b></span>
          <button class="btn btn-sm btn-outline-primary" id="addPeriodBtn">+ যোগ</button>
          <button class="btn btn-sm btn-outline-danger" id="removePeriodBtn">- কমান</button>
          <a id="printRoutineLink" href="#" target="_blank" class="btn btn-sm btn-outline-secondary ml-2" style="display:none"><i class="fas fa-print"></i> প্রিন্ট</a>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="routineGridWrap" style="display:none">
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered" id="routineGrid">
          <thead>
            <tr>
              <th style="width:110px">পিরিয়ড\\দিন</th>
              @php($days=['saturday'=>'শনিবার','sunday'=>'রবিবার','monday'=>'সোমবার','tuesday'=>'মঙ্গলবার','wednesday'=>'বুধবার','thursday'=>'বৃহস্পতিবার','friday'=>'শুক্রবার'])
              @foreach($days as $dk=>$dn)
                <th data-day="{{ $dk }}">{{ $dn }}</th>
              @endforeach
            </tr>
          </thead>
          <tbody id="routineGridBody"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal: manage cell (multiple entries) -->
<div class="modal fade" id="cellModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">পিরিয়ড এন্ট্রি ম্যানেজ করুন</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="table table-sm" id="entryTable">
            <thead><tr><th style="width:80px">পিরিয়ড</th><th>বিষয়</th><th>শিক্ষক</th><th style="width:120px">শুরু</th><th style="width:120px">শেষ</th><th style="width:80px"></th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
        <button class="btn btn-outline-primary btn-sm" id="addRow"><i class="fas fa-plus mr-1"></i> নতুন সারি</button>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" id="saveAll">সংরক্ষণ</button>
        <button class="btn btn-secondary" data-dismiss="modal">বন্ধ</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<!-- Select2 JS is bundled via Vite (resources/js/app.js) in production -->
<script>
(function(){
  var sections = @json($sections);
  var teachers = @json($teachers);
  var days = ['saturday','sunday','monday','tuesday','wednesday','thursday','friday'];

  // Helper: fetch JSON with CSRF + credentials
  function fetchJSON(url, opts){
    opts = opts || {}; opts.headers = opts.headers || {};
    opts.headers['Accept'] = 'application/json';
    // Only set content-type if not provided
    if (!opts.headers['Content-Type'] && opts.body) {
      opts.headers['Content-Type'] = 'application/json';
    }
    opts.headers['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
    // Ensure cookies (session) are sent
    opts.credentials = 'same-origin';
    return fetch(url, opts).then(function(r){
      if(!r.ok){
        // Try to surface CSRF/session issues clearly
        if (r.status === 419) throw new Error('Session expired (419). Please refresh the page.');
        throw new Error('HTTP '+r.status);
      }
      return r.json();
    });
  }

  function loadSections(classId){
    var sel = document.getElementById('section_id');
    sel.innerHTML = '<option value="">— শাখা —</option>';
    sel.disabled = !classId;
    document.getElementById('classTeacherInfo').style.display='none';
    if(!classId) return;
    sections.filter(s=>String(s.class_id)===String(classId)).forEach(function(s){
      var o=document.createElement('option'); o.value=s.id; o.textContent=s.name; sel.appendChild(o);
    });
  }

  function buildGrid(period){
    var tbody = document.getElementById('routineGridBody');
    tbody.innerHTML = '';
    for (var p=1; p<=period; p++){
      var tr = document.createElement('tr');
      tr.innerHTML = '<th>পিরিয়ড '+p+'</th>' + days.map(function(d){ return '<td class="cell-td" data-day="'+d+'" data-period="'+p+'"><button class="btn btn-xs btn-outline-success add-cell">ম্যানেজ</button><div class="cell-list mt-2"></div></td>'; }).join('');
      tbody.appendChild(tr);
    }
  }

  function refreshCellUI(cell, entries){
    var list = cell.querySelector('.cell-list'); list.innerHTML='';
    (entries||[]).forEach(function(e){
      var div=document.createElement('div'); div.className='cell-entry';
      div.innerHTML='<div class="small"><b>'+(e.subject_name||'')+'</b> — '+(e.teacher_name||'')+'</div>'+
                    '<div class="small text-muted">'+(e.start_time||'')+(e.end_time?(' - '+e.end_time):'')+'</div>'+
                    '<button class="btn btn-xs btn-outline-danger mt-1 del-entry" data-id="'+e.id+'">মুছুন</button>';
      list.appendChild(div);
    });
  }

  function loadGrid(){
    var cls = document.getElementById('class_id').value; var sec=document.getElementById('section_id').value;
    if(!cls||!sec){ document.getElementById('routineGridWrap').style.display='none'; return; }
    document.getElementById('routineGridWrap').style.display='block';
    document.getElementById('periodControls').style.display='inline-block';
    // Update print link
    var printLink = document.getElementById('printRoutineLink');
    printLink.href = '{{ route('principal.institute.routine.print',$school) }}?class_id='+cls+'&section_id='+sec;
    printLink.style.display = 'inline-block';
    // reset to 0 until loaded
    document.getElementById('periodCountDisplay').textContent = '0';
    fetchJSON('{{ route('principal.institute.routine.period-count',$school) }}?class_id='+cls+'&section_id='+sec)
      .then(pc=>{
        var c = (typeof pc.period_count==='number') ? pc.period_count : 0;
        document.getElementById('periodCountDisplay').textContent=c;
        buildGrid(c);
        return fetchJSON('{{ route('principal.institute.routine.grid',$school) }}?class_id='+cls+'&section_id='+sec)
      }).then(grid=>{
        var maxP = parseInt(document.getElementById('periodCountDisplay').textContent)||0;
        days.forEach(function(d){ for (var p=1;p<=maxP;p++){
          var cell = document.querySelector('.cell-td[data-day="'+d+'"][data-period="'+p+'"]');
          var entries = (grid[d] && grid[d][p]) ? grid[d][p] : [];
          refreshCellUI(cell, entries);
        }});
      }).catch(function(err){ console.error(err); alert('লোড ব্যর্থ: '+err.message); });
  }

  document.getElementById('class_id').addEventListener('change', function(){ loadSections(this.value); document.getElementById('section_id').value=''; document.getElementById('section_id').disabled=!this.value; document.getElementById('routineGridWrap').style.display='none'; });
  document.getElementById('section_id').addEventListener('change', function(){
    var secId = this.value; var info = document.getElementById('classTeacherInfo');
    if(!secId){ info.style.display='none'; info.textContent=''; loadGrid(); return; }
    var sec = sections.find(function(s){ return String(s.id)===String(secId); });
    var teacherName = '';
    try { teacherName = (sec && sec.class_teacher && sec.class_teacher.user && sec.class_teacher.user.name) ? sec.class_teacher.user.name : (sec.class_teacher_name || ''); } catch(_) {}
    if(teacherName){ info.style.display='block'; info.textContent = 'এই শাখার শ্রেণি শিক্ষক: ' + teacherName; } else { info.style.display='none'; info.textContent=''; }
    loadGrid();
  });

  document.getElementById('addPeriodBtn').addEventListener('click', function(){
    var cls=document.getElementById('class_id').value, sec=document.getElementById('section_id').value; if(!cls||!sec) return;
    var current=parseInt(document.getElementById('periodCountDisplay').textContent)||8; var next=current+1;
    var btn=this; btn.disabled=true;
    fetchJSON('{{ route('principal.institute.routine.period-count.set',$school) }}', {method:'POST', body: JSON.stringify({class_id:cls, section_id:sec, period_count:next})})
      .then(()=> loadGrid())
      .catch(function(err){ alert('আপডেট ব্যর্থ: '+err.message); })
      .finally(function(){ btn.disabled=false; });
  });
  document.getElementById('removePeriodBtn').addEventListener('click', function(){
    var cls=document.getElementById('class_id').value, sec=document.getElementById('section_id').value; if(!cls||!sec) return;
  var current=parseInt(document.getElementById('periodCountDisplay').textContent)||0; if(current<=1) return alert('কমপক্ষে 1 পিরিয়ড থাকতে হবে'); var next=current-1;
    var btn=this; btn.disabled=true;
    fetchJSON('{{ route('principal.institute.routine.period-count.set',$school) }}', {method:'POST', body: JSON.stringify({class_id:cls, section_id:sec, period_count:next})})
      .then(()=> loadGrid())
      .catch(function(err){ alert('আপডেট ব্যর্থ: '+err.message); })
      .finally(function(){ btn.disabled=false; });
  });

  // Entry modal mechanics (multiple rows)
  function subjectOptionsHtml(){ return '<option value="">— বিষয় —</option>'; }
  function populateTeacherSelect(sel, selected){
    sel.innerHTML = '';
    var empty = document.createElement('option'); empty.value = ''; empty.textContent = '— শিক্ষক —'; sel.appendChild(empty);
    (teachers||[]).forEach(function(t){
      var o = document.createElement('option');
      o.value = t.id;
      var name = (t.user && t.user.name) ? t.user.name : ('Teacher #'+t.id);
      o.textContent = name + (t.initials ? (' ('+t.initials+')') : '');
      sel.appendChild(o);
    });
    if(typeof selected !== 'undefined' && selected !== null) sel.value = String(selected);
  }

  function addEntryRow(tblBody, defaults){
    var tr=document.createElement('tr');
    tr.innerHTML = '<td><input type="number" class="form-control form-control-sm period-input" min="1" required></td>'+
             '<td><select class="form-control form-control-sm subject-input"></select></td>'+
             '<td><select class="form-control form-control-sm teacher-input"></select></td>'+
             '<td><input type="time" class="form-control form-control-sm start-input"></td>'+
             '<td><input type="time" class="form-control form-control-sm end-input"></td>'+
             '<td><button type="button" class="btn btn-xs btn-outline-danger del-row">বাদ</button></td>';
    tblBody.appendChild(tr);
    // populate subjects for current class
    var cls=document.getElementById('class_id').value; 
    var sel=tr.querySelector('.subject-input');
    sel.innerHTML = '<option value="">লোড হচ্ছে...</option>';
    if(cls){
      fetchJSON('{{ route('principal.institute.routine.subjects',$school) }}?class_id='+cls)
        .then(function(list){
          sel.innerHTML='<option value="">— বিষয় —</option>'+(list||[]).map(function(s){ return '<option value="'+s.subject_id+'">'+s.name+'</option>'; }).join('');
          if(defaults && defaults.subject_id) sel.value = String(defaults.subject_id);
        })
        .catch(function(){ sel.innerHTML='<option value="">লোড ব্যর্থ</option>'; });
    } else {
      sel.innerHTML = '<option value="">— প্রথমে শ্রেণি নির্বাচন করুন —</option>';
    }
    var teacherSel = tr.querySelector('.teacher-input');
    populateTeacherSelect(teacherSel, defaults ? defaults.teacher_id : '');
    if(defaults){
      tr.querySelector('.period-input').value = defaults.period_number || document.getElementById('cellModal').dataset.period;
      tr.querySelector('.start-input').value = defaults.start_time || '';
      tr.querySelector('.end-input').value = defaults.end_time || '';
    } else {
      tr.querySelector('.period-input').value = document.getElementById('cellModal').dataset.period;
    }
    // Initialize Select2 for teacher select (attach to modal so search input receives focus)
    (function initSelect2For(el, tries){
      tries = typeof tries === 'number' ? tries : 20;
      if (window.$ && $.fn && $.fn.select2) {
        try {
          $(el).select2({ theme: 'bootstrap4', width: '100%', dropdownParent: $('#cellModal'), minimumResultsForSearch: 0 });
        } catch(err){ console.error('Select2 init error', err); }
        return;
      }
      if (tries > 0) {
        setTimeout(function(){ initSelect2For(el, tries-1); }, 100);
      } else {
        console.error('Select2 plugin not available to initialize element', el);
      }
    })(teacherSel, 20);
  }

  document.getElementById('routineGrid').addEventListener('click', function(e){
    if(e.target.classList.contains('add-cell')){
      var td = e.target.closest('.cell-td');
      var day = td.getAttribute('data-day'); var period = td.getAttribute('data-period');
      var cls=document.getElementById('class_id').value; var sec=document.getElementById('section_id').value;
      var modal=document.getElementById('cellModal'); modal.dataset.day=day; modal.dataset.period=period; modal.dataset.class=cls; modal.dataset.section=sec;
      var tbody = document.querySelector('#entryTable tbody'); tbody.innerHTML='';
      // If no teachers available, inform the user upfront
      try {
        if ((teachers||[]).length === 0) {
          alert('এই স্কুলে কোনো শিক্ষক যুক্ত নেই। আগে শিক্ষক যুক্ত করুন, তারপর রুটিনে অ্যাসাইন করুন।');
        }
      } catch(_){}
      // preload existing entries
      var entries = td.querySelectorAll('.cell-list .cell-entry');
      fetchJSON('{{ route('principal.institute.routine.grid',$school) }}?class_id='+cls+'&section_id='+sec)
        .then(grid=>{
          var exist = (grid[day] && grid[day][period]) ? grid[day][period] : [];
          if(exist.length){ exist.forEach(function(e){ addEntryRow(tbody, e); }); } else { addEntryRow(tbody); }
          $('#cellModal').modal('show');
        }).catch(function(err){ alert('লোড ব্যর্থ: '+err.message); });
    }
  });

  document.getElementById('entryTable').addEventListener('click', function(e){
    if(e.target.classList.contains('del-row')){ var tr=e.target.closest('tr'); tr.parentNode.removeChild(tr); }
  });
  document.getElementById('addRow').addEventListener('click', function(){ addEntryRow(document.querySelector('#entryTable tbody')); });

  document.getElementById('saveAll').addEventListener('click', function(){
    var modal=document.getElementById('cellModal'); var day=modal.dataset.day; var cls=modal.dataset.class; var sec=modal.dataset.section;
    var rows = Array.from(document.querySelectorAll('#entryTable tbody tr'));
    if(rows.length===0){ $('#cellModal').modal('hide'); return; }
    // Client-side validation with helpful messages
    var issues = [];
    if ((teachers||[]).length === 0) {
      issues.push('কোনো শিক্ষক পাওয়া যায়নি। আগে শিক্ষক যুক্ত করুন।');
    }
    rows.forEach(function(tr, idx){
      var r = idx+1;
      var p = parseInt(tr.querySelector('.period-input').value||'0');
      var s = parseInt(tr.querySelector('.subject-input').value||'0');
      var t = parseInt(tr.querySelector('.teacher-input').value||'0');
      if(!p || p<1) issues.push('সারি '+r+': পিরিয়ড নম্বর সঠিক নয়');
      if(!s) issues.push('সারি '+r+': বিষয় নির্বাচন করুন');
      if((teachers||[]).length>0 && !t) issues.push('সারি '+r+': শিক্ষক নির্বাচন করুন');
    });
    if (issues.length){ alert('অনুগ্রহ করে নিচের সমস্যা সমূহ ঠিক করুন:\n- '+issues.join('\n- ')); return; }
    // Strategy: replace all existing entries for the cell; delete then create
    // First, fetch existing and delete
    fetchJSON('{{ route('principal.institute.routine.grid',$school) }}?class_id='+cls+'&section_id='+sec).then(function(grid){
      var exist = (grid[day] && grid[day][modal.dataset.period]) ? grid[day][modal.dataset.period] : [];
      var delPromises = exist.map(function(e){ return fetchJSON('{{ route('principal.institute.routine.entry.delete',$school) }}', {method:'DELETE', body: JSON.stringify({id:e.id})}); });
      return Promise.all(delPromises);
    }).then(function(){
      // create new entries
      var createPromises = rows.map(function(tr){
        var payload = {
          class_id: cls,
          section_id: sec,
          day_of_week: day,
          period_number: parseInt(tr.querySelector('.period-input').value||'0'),
          subject_id: parseInt(tr.querySelector('.subject-input').value||'0'),
          teacher_id: parseInt(tr.querySelector('.teacher-input').value||'0'),
          start_time: tr.querySelector('.start-input').value || null,
          end_time: tr.querySelector('.end-input').value || null,
        };
        return fetchJSON('{{ route('principal.institute.routine.entry.save',$school) }}', {method:'POST', body: JSON.stringify(payload)}).then(function(resp){ return {ok: resp && resp.success!==false, resp: resp, payload: payload}; });
      });
      return Promise.all(createPromises);
    }).then(function(results){
        var failures = results.filter(function(r){ return !r.ok; });
        if (failures.length){
          var msgs = failures.map(function(f){ return (f.resp && f.resp.error) ? f.resp.error : 'অজানা ত্রুটি'; });
          alert('কিছু এন্ট্রি সংরক্ষণ হয়নি:\n- '+msgs.join('\n- '));
          // keep modal open so user can fix
          return;
        }
        $('#cellModal').modal('hide'); loadGrid();
      })
      .catch(function(err){ alert('সংরক্ষণে ত্রুটি: '+err.message); });
  });

  // delete single existing entry from cell list
  document.getElementById('routineGrid').addEventListener('click', function(e){
    if(e.target.classList.contains('del-entry')){
      var id=e.target.getAttribute('data-id');
      fetchJSON('{{ route('principal.institute.routine.entry.delete',$school) }}', {method:'DELETE', body: JSON.stringify({id:id})})
        .then(()=> loadGrid())
        .catch(function(err){ alert('মুছতে ব্যর্থ: '+err.message); });
    }
  });
})();
</script>
@endpush
@endsection
