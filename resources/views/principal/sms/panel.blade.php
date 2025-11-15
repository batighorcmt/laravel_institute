@extends('layouts.admin')
@section('title','এসএমএস প্যানেল')
@section('content')
@push('styles')
<style>
/* Mobile spacing adjustments */
@media (max-width: 576px) {
  .sms-balance-row .card { margin-bottom: .85rem; }
  .sms-main-row > [class^="col"] { margin-bottom: 1.2rem; }
  .sms-main-row .card { margin-bottom: 1rem; }
  .sms-main-row .card:last-child { margin-bottom: 0; }
  .sms-recipient-group .section { margin-bottom: 1rem; }
}
/* Consistent spacing for inner dynamic sections */
.sms-recipient-group .section { padding-bottom: .25rem; border-bottom: 1px dashed #e2e6ea; margin-bottom: 1.1rem; }
.sms-recipient-group .section:last-of-type { border-bottom: none; margin-bottom: 0; }
/* Tighter header spacing */
.sms-balance-row .card-body .h4 { font-size: 1.35rem; }
</style>
@endpush
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0"><i class="fas fa-paper-plane mr-1"></i> এসএমএস প্যানেল</h1>
  <div>
    <a class="btn btn-outline-secondary" href="{{ route('principal.institute.sms.logs',$school) }}"><i class="fas fa-list mr-1"></i> লগসমূহ</a>
    <a class="btn btn-outline-primary" href="{{ route('principal.institute.sms.index',$school) }}"><i class="fas fa-cog mr-1"></i> সেটিংস</a>
  </div>
  </div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

{{-- Balance & Capacity Info --}}
<div class="row mb-3 sms-balance-row">
  <div class="col-md-6">
    <div class="card h-100 @if($smsBalance!==null) border-success @else border-warning @endif">
      <div class="card-body py-2">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="small text-muted">ব্যালেন্স (টাকা)</div>
            <div class="h4 mb-0">{{ $smsBalance!==null ? '৳ '.number_format($smsBalance,2) : '—' }}</div>
          </div>
          <div><i class="fas fa-wallet fa-2x text-secondary"></i></div>
        </div>
        <div class="mt-2 small text-muted">
          @if($smsBalance!==null)
            সর্বশেষ হালনাগাদ: {{ $smsBalanceFetchedAt? $smsBalanceFetchedAt->format('d M Y, h:i A') : '—' }}
          @else
            ব্যালেন্স আনতে পারিনি{{ $smsBalanceError? ' - '.e($smsBalanceError):'' }}
          @endif
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card h-100 @if($smsPossible!==null) border-info @else border-warning @endif">
      <div class="card-body py-2">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="small text-muted">সম্ভাব্য এসএমএস সংখ্যা</div>
            <div class="h4 mb-0">{{ $smsPossible!==null ? number_format($smsPossible) : '—' }}</div>
          </div>
          <div><i class="fas fa-sms fa-2x text-secondary"></i></div>
        </div>
        <div class="mt-2 small text-muted">
          @if($smsPossible!==null)
            প্রতি এসএমএস: ৳ {{ number_format($perSmsCost,2) }}
          @else
            নির্ণয় করা যায়নি
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row sms-main-row">
  <div class="col-lg-7">
    <div class="card sms-recipient-group mb-4">
      <div class="card-header"><strong>রিসিপিয়েন্ট নির্বাচন</strong></div>
      <div class="card-body">
        <div class="form-group">
          <label>টার্গেট</label>
          <div>
            <div class="custom-control custom-radio d-inline-block mr-3">
              <input type="radio" id="t_teacher_one" name="target" value="teacher_one" class="custom-control-input" checked>
              <label class="custom-control-label" for="t_teacher_one">একজন শিক্ষক</label>
            </div>
            <div class="custom-control custom-radio d-inline-block mr-3">
              <input type="radio" id="t_teachers_selected" name="target" value="teachers_selected" class="custom-control-input">
              <label class="custom-control-label" for="t_teachers_selected">নির্বাচিত শিক্ষক</label>
            </div>
            <div class="custom-control custom-radio d-inline-block mr-3">
              <input type="radio" id="t_teacher_all" name="target" value="teacher_all" class="custom-control-input">
              <label class="custom-control-label" for="t_teacher_all">সব শিক্ষক</label>
            </div>
            <div class="custom-control custom-radio d-inline-block mr-3">
              <input type="radio" id="t_student_one" name="target" value="student_one" class="custom-control-input">
              <label class="custom-control-label" for="t_student_one">একজন শিক্ষার্থী</label>
            </div>
            <div class="custom-control custom-radio d-inline-block mr-3">
              <input type="radio" id="t_students_all" name="target" value="students_all" class="custom-control-input">
              <label class="custom-control-label" for="t_students_all">ক্লাস/শাখা সব শিক্ষার্থী</label>
            </div>
            <div class="custom-control custom-radio d-inline-block mr-3">
              <input type="radio" id="t_students_selected" name="target" value="students_selected" class="custom-control-input">
              <label class="custom-control-label" for="t_students_selected">নির্বাচিত শিক্ষার্থী</label>
            </div>
            <div class="custom-control custom-radio d-inline-block mr-3">
              <input type="radio" id="t_custom_numbers" name="target" value="custom_numbers" class="custom-control-input">
              <label class="custom-control-label" for="t_custom_numbers">কাস্টম নম্বর</label>
            </div>
          </div>
        </div>

  <div id="sec-teacher-one" class="section mt-3">
          <label>শিক্ষক</label>
          <select id="teacher_one" class="form-control">
            <option value="">— শিক্ষক —</option>
            @foreach($teachers as $t)
              <option value="{{ $t->id }}" data-name="{{ $t->name }}" data-phone="{{ $t->phone }}">{{ $t->name }} ({{ $t->phone }})</option>
            @endforeach
          </select>
          <button id="btn-add-teacher-one" class="btn btn-sm btn-outline-primary mt-2">যোগ করুন</button>
        </div>

  <div id="sec-teachers-selected" class="section mt-3">
          <label>শিক্ষক (একাধিক নির্বাচন)</label>
          <select id="teachers_multi" class="form-control" multiple size="6">
            @foreach($teachers as $t)
              <option value="{{ $t->id }}" data-name="{{ $t->name }}" data-phone="{{ $t->phone }}">{{ $t->name }} ({{ $t->phone }})</option>
            @endforeach
          </select>
          <button id="btn-add-teachers-selected" class="btn btn-sm btn-outline-primary mt-2">নির্বাচিতগুলো যোগ করুন</button>
        </div>

  <div id="sec-student-one" class="section mt-3">
          <div class="form-row">
            <div class="form-group col-md-4">
              <label>শ্রেণি</label>
              <select id="one_class" class="form-control"><option value="">— শ্রেণি —</option>@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
            </div>
            <div class="form-group col-md-4">
              <label>শাখা</label>
              <select id="one_section" class="form-control"><option value="">— শাখা —</option></select>
            </div>
            <div class="form-group col-md-4">
              <label>শিক্ষার্থী</label>
              <select id="one_student" class="form-control" disabled><option value="">— শিক্ষার্থী —</option></select>
            </div>
          </div>
          <button id="btn-add-student-one" class="btn btn-sm btn-outline-primary">যোগ করুন</button>
        </div>

  <div id="sec-students-all" class="section mt-3">
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>শ্রেণি</label>
              <select id="all_class" class="form-control"><option value="">— শ্রেণি —</option>@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
            </div>
            <div class="form-group col-md-6">
              <label>শাখা</label>
              <select id="all_section" class="form-control"><option value="">— শাখা —</option></select>
            </div>
          </div>
          <button id="btn-add-students-all" class="btn btn-sm btn-outline-primary" disabled>সব যোগ করুন</button>
        </div>

  <div id="sec-students-selected" class="section mt-3">
          <div class="form-row">
            <div class="form-group col-md-4">
              <label>শ্রেণি</label>
              <select id="sel_class" class="form-control"><option value="">— শ্রেণি —</option>@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
            </div>
            <div class="form-group col-md-4">
              <label>শাখা</label>
              <select id="sel_section" class="form-control"><option value="">— শাখা —</option></select>
            </div>
            <div class="form-group col-md-4">
              <label>সার্চ</label>
              <input type="text" id="stu_search" class="form-control" placeholder="নাম/রোল">
            </div>
          </div>
          <div id="sel_students_wrap" class="border rounded p-2" style="max-height:260px;overflow:auto">
            <div class="text-muted text-center py-2">শ্রেণি/শাখা বাছাই করুন বা সার্চ করুন</div>
          </div>
          <div class="d-flex align-items-center mt-2">
            <div class="custom-control custom-checkbox mr-3">
              <input type="checkbox" id="sel_all_toggle" class="custom-control-input">
              <label for="sel_all_toggle" class="custom-control-label">সব বাছাই</label>
            </div>
            <span id="sel_count" class="text-muted">0 নির্বাচিত</span>
          </div>
          <button id="btn-add-students-selected" class="btn btn-sm btn-outline-primary mt-2" disabled>নির্বাচিতগুলো যোগ করুন</button>
        </div>

  <div id="sec-custom-numbers" class="section mt-3">
          <label>কাস্টম নম্বর (কমা/নিউলাইন দিয়ে আলাদা)</label>
          <textarea id="custom_numbers" class="form-control" rows="3" placeholder="017..., 018..."></textarea>
          <button id="btn-add-custom" class="btn btn-sm btn-outline-primary mt-2">যোগ করুন</button>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <form method="post" action="{{ route('principal.institute.sms.send',$school) }}" id="sendForm">
      @csrf
      <input type="hidden" name="submission_uid" value="{{ \Illuminate\Support\Str::uuid() }}">
      <input type="hidden" name="target" id="target_input" value="teacher_one">
      <input type="hidden" name="recipients_json" id="recipients_json">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong>রিসিপিয়েন্ট তালিকা</strong>
          <span class="text-muted">মোট: <span id="agg-count">0</span></span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive" style="max-height:220px;overflow:auto">
            <table class="table table-sm table-striped mb-0" id="agg-table">
              <thead><tr><th style="width:40px">#</th><th>নাম/বিভাগ</th><th>নম্বর</th><th style="width:60px">একশন</th></tr></thead>
              <tbody><tr class="text-muted"><td colspan="4" class="text-center">কোনো প্রাপক যোগ করা হয়নি</td></tr></tbody>
            </table>
          </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
          <button type="button" id="agg-clear" class="btn btn-outline-secondary btn-sm">সাফ করুন</button>
          <small class="text-muted">প্রতি সেশন সর্বোচ্চ 1000 প্রাপক</small>
        </div>
      </div>

      <div class="card mt-3">
        <div class="card-header d-flex align-items-center">
          <strong>বার্তা</strong>
          <select id="template_select" class="form-control form-control-sm ml-2" style="max-width:240px;">
            <option value="">— টেমপ্লেট —</option>
            @foreach($templates as $t)
              <option value="{{ $t->content }}">{{ $t->title }}</option>
            @endforeach
          </select>
        </div>
        <div class="card-body">
          <textarea name="message" id="msg_text" class="form-control" rows="4" required>{{ old('message') }}</textarea>
          <div class="small text-muted mt-1" id="sms_counter">0 অক্ষর • 0 অংশ</div>
        </div>
        <div class="card-footer">
          <button class="btn btn-primary btn-block" id="sendBtn"><i class="fas fa-paper-plane mr-1"></i> পাঠান</button>
        </div>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
(function(){
  var recipients = {}; // number -> meta
  function normalize(n){ return (n||'').replace(/[^0-9]/g,''); }
  function refreshAgg(){
    var keys = Object.keys(recipients);
    document.getElementById('agg-count').textContent = keys.length;
    var tb = document.querySelector('#agg-table tbody');
    if(keys.length===0){ tb.innerHTML = '<tr class="text-muted"><td colspan="4" class="text-center">কোনো প্রাপক যোগ করা হয়নি</td></tr>'; return; }
    keys.sort(); var idx=1; var html='';
    keys.forEach(function(k){
      var m = recipients[k]||{};
      var name = m.name || m.category || '';
      html += '<tr><td>'+ (idx++) +'</td><td>'+ (name) +'</td><td>'+ k +'</td>'+
              '<td><button type="button" class="btn btn-xs btn-outline-danger agg-remove" data-num="'+k+'">বাদ</button></td></tr>';
    });
    tb.innerHTML = html;
    document.getElementById('recipients_json').value = JSON.stringify(keys.map(function(k){ return recipients[k]; }));
  }
  document.getElementById('agg-table').addEventListener('click', function(e){
    if(e.target.classList.contains('agg-remove')){
      var num = e.target.getAttribute('data-num'); delete recipients[num]; refreshAgg();
    }
  });
  document.getElementById('agg-clear').addEventListener('click', function(){ recipients = {}; refreshAgg(); });

  function addRecipient(number, meta){
    var n = normalize(number); if(!n) return; meta = meta||{}; meta.number = n; recipients[n] = meta; refreshAgg();
  }

  // Toggle sections based on target
  function showSection(val){
    document.querySelectorAll('.section').forEach(function(el){ el.style.display='none'; });
    if(val==='teacher_one') document.getElementById('sec-teacher-one').style.display='block';
    if(val==='teachers_selected') document.getElementById('sec-teachers-selected').style.display='block';
    if(val==='teacher_all') {/* none */}
    if(val==='student_one') document.getElementById('sec-student-one').style.display='block';
    if(val==='students_all') document.getElementById('sec-students-all').style.display='block';
    if(val==='students_selected') document.getElementById('sec-students-selected').style.display='block';
    if(val==='custom_numbers') document.getElementById('sec-custom-numbers').style.display='block';
    document.getElementById('target_input').value = val;
  }
  document.querySelectorAll('input[name="target"]').forEach(function(r){ r.addEventListener('change', function(){ showSection(this.value); }); });
  showSection('teacher_one');

  // Sections AJAX loader (reuse existing meta endpoint)
  function loadSections(classId, select){
    select.disabled = true; select.innerHTML = '<option>লোড হচ্ছে...</option>';
    if(!classId){ select.innerHTML = '<option value="">— শাখা —</option>'; select.disabled=false; return; }
    fetch('{{ route('principal.institute.meta.sections',$school) }}?class_id='+encodeURIComponent(classId))
      .then(r=>r.json()).then(list=>{
        select.innerHTML = '<option value="">— শাখা —</option>';
        (list||[]).forEach(function(s){ var o=document.createElement('option'); o.value=s.id; o.textContent=s.name; select.appendChild(o); });
        select.disabled=false;
      }).catch(()=>{ select.innerHTML = '<option value="">— শাখা —</option>'; select.disabled=false; });
  }

  // Students loader
  function loadStudents(classId, sectionId, targetEl, multiple, q){
    targetEl.innerHTML = multiple? '<div class="text-center text-muted py-2">লোড হচ্ছে...</div>' : '<option>লোড হচ্ছে...</option>';
    var url = '{{ route('principal.institute.meta.students',$school) }}?class_id='+(classId||'')+'&section_id='+(sectionId||'')+'&q='+(encodeURIComponent(q||''));
    fetch(url).then(r=>r.json()).then(list=>{
      if(multiple){
        if(!list || !list.length){ targetEl.innerHTML='<div class="text-muted text-center py-2">কিছু পাওয়া যায়নি</div>'; return; }
        var html='';
        list.forEach(function(s){
          var id=s.student_id, name=s.name||'', phone=s.phone||'', roll=s.roll_no||'', klass=s.class_name||'', sect=s.section_name||'';
          html += '<div><label class="mb-0"><input type="checkbox" class="sel-stu mr-2" value="'+id+'" data-name="'+name+'" data-phone="'+phone+'" data-roll="'+roll+'" data-class="'+klass+'" data-section="'+sect+'">'+name+' — রোল: '+roll+' — '+klass+(sect?('/ '+sect):'')+'</label></div>';
        });
        targetEl.innerHTML = html;
        document.getElementById('sel_count').textContent = '0 নির্বাচিত';
      } else {
        targetEl.innerHTML = '<option value="">— শিক্ষার্থী —</option>';
        (list||[]).forEach(function(s){
          var o=document.createElement('option'); o.value=s.student_id; o.textContent=(s.name||'')+' — রোল: '+(s.roll_no||''); o.setAttribute('data-name', s.name||''); o.setAttribute('data-mobile', s.phone||''); targetEl.appendChild(o);
        });
        targetEl.disabled=false;
      }
    }).catch(()=>{ if(!multiple){ targetEl.innerHTML='<option value="">— শিক্ষার্থী —</option>'; targetEl.disabled=true; } else { targetEl.innerHTML='<div class="text-center text-muted py-2">ত্রুটি</div>'; } });
  }

  // Binders
  document.getElementById('teacher_one').addEventListener('change', function(){ /* noop */ });
  document.getElementById('btn-add-teacher-one').addEventListener('click', function(){
    var sel=document.getElementById('teacher_one'); var id=sel.value; if(!id) return; var opt=sel.options[sel.selectedIndex];
    addRecipient(opt.getAttribute('data-phone'), {category:'teacher', id:parseInt(id,10)||null, name:opt.getAttribute('data-name'), role:'teacher'});
  });
  document.getElementById('btn-add-teachers-selected').addEventListener('click', function(){
    var sel=document.getElementById('teachers_multi'); Array.from(sel.selectedOptions).forEach(function(opt){
      addRecipient(opt.getAttribute('data-phone'), {category:'teacher', id:parseInt(opt.value,10)||null, name:opt.getAttribute('data-name'), role:'teacher'});
    });
  });

  document.getElementById('one_class').addEventListener('change', function(){ loadSections(this.value, document.getElementById('one_section')); document.getElementById('one_student').innerHTML='<option value="">— শিক্ষার্থী —</option>'; document.getElementById('one_student').disabled=true; });
  document.getElementById('one_section').addEventListener('change', function(){ loadStudents(document.getElementById('one_class').value, this.value, document.getElementById('one_student'), false, null); });
  document.getElementById('btn-add-student-one').addEventListener('click', function(){ var sel=document.getElementById('one_student'); var id=sel.value; if(!id) return; var opt=sel.options[sel.selectedIndex]; var klass=document.getElementById('one_class').options[document.getElementById('one_class').selectedIndex].text; var sect=document.getElementById('one_section').options[document.getElementById('one_section').selectedIndex]?.text||''; addRecipient(opt.getAttribute('data-mobile'), {category:'student', id:parseInt(id,10)||null, name:opt.getAttribute('data-name'), role:'student', class_name:klass, section_name:sect}); });

  document.getElementById('all_class').addEventListener('change', function(){ loadSections(this.value, document.getElementById('all_section')); document.getElementById('btn-add-students-all').disabled = !this.value; });
  document.getElementById('btn-add-students-all').addEventListener('click', function(){
    // We won't expand all to list; we'll add a virtual marker so server falls back to students_all
    document.querySelector('input[name="target"][value="students_all"]').checked = true; showSection('students_all'); document.getElementById('target_input').value='students_all';
    // No immediate aggregation; server will resolve based on class/section posted; keep hidden fields via temp inputs
    ensureHidden('class_id', document.getElementById('all_class').value);
    ensureHidden('section_id', document.getElementById('all_section').value);
    alert('ক্লাস/শাখা নির্ধারিত হয়েছে। বার্তা লিখে পাঠান।');
  });

  document.getElementById('sel_class').addEventListener('change', function(){ loadSections(this.value, document.getElementById('sel_section')); document.getElementById('sel_students_wrap').innerHTML='<div class="text-muted text-center py-2">শ্রেণি/শাখা বাছাই করুন বা সার্চ করুন</div>'; updateSelState(); });
  document.getElementById('sel_section').addEventListener('change', function(){ loadStudents(document.getElementById('sel_class').value, this.value, document.getElementById('sel_students_wrap'), true, document.getElementById('stu_search').value); });
  var searchTimer; document.getElementById('stu_search').addEventListener('input', function(){ clearTimeout(searchTimer); var q=this.value; searchTimer = setTimeout(function(){ loadStudents(document.getElementById('sel_class').value, document.getElementById('sel_section').value, document.getElementById('sel_students_wrap'), true, q); }, 300); });
  document.getElementById('sel_students_wrap').addEventListener('change', function(e){ if(e.target.classList.contains('sel-stu')) updateSelState(); });
  document.getElementById('sel_all_toggle').addEventListener('change', function(){ var on=this.checked; document.querySelectorAll('#sel_students_wrap input.sel-stu').forEach(function(cb){ cb.checked=on; }); updateSelState(); });
  function updateSelState(){ var c=document.querySelectorAll('#sel_students_wrap input.sel-stu:checked').length; document.getElementById('sel_count').textContent=c+' নির্বাচিত'; document.getElementById('btn-add-students-selected').disabled = c===0; }
  document.getElementById('btn-add-students-selected').addEventListener('click', function(){ document.querySelectorAll('#sel_students_wrap input.sel-stu:checked').forEach(function(cb){ addRecipient(cb.getAttribute('data-phone'), {category:'student', id:parseInt(cb.value,10)||null, name:cb.getAttribute('data-name'), role:'student', roll: cb.getAttribute('data-roll'), class_name: cb.getAttribute('data-class'), section_name: cb.getAttribute('data-section')}); }); });

  document.getElementById('btn-add-custom').addEventListener('click', function(){
    (document.getElementById('custom_numbers').value||'').split(/[\s,;]+/).forEach(function(n){ if(n) addRecipient(n, {category:'custom'}); });
  });

  // Template fill and SMS counter
  document.getElementById('template_select').addEventListener('change', function(){ var v=this.value||''; if(v){ document.getElementById('msg_text').value=v; updateCounter(); }});
  function detectUnicode(str){ for(var i=0;i<str.length;i++){ if(str.charCodeAt(i) > 127) return true; } return false; }
  function computeParts(len, unicode){ if(len===0) return {parts:0, per:unicode?70:160}; var single = unicode?70:160, multi=unicode?67:153; if(len<=single) return {parts:1, per:single}; return {parts: Math.ceil(len/multi), per: multi}; }
  function updateCounter(){ var txt=document.getElementById('msg_text').value||''; var uni=detectUnicode(txt); var calc=computeParts(txt.length, uni); document.getElementById('sms_counter').textContent = txt.length+' অক্ষর • '+calc.parts+' অংশ'+(calc.parts>1?(' (প্রতি অংশ '+calc.per+' অক্ষর)'):''); }
  document.getElementById('msg_text').addEventListener('input', updateCounter); updateCounter();

  // Ensure fallback hidden inputs exist for server fallbacks
  function ensureHidden(name, value){ var ex=document.querySelector('input[name="'+name+'"][type="hidden"]'); if(!ex){ ex=document.createElement('input'); ex.type='hidden'; ex.name=name; document.getElementById('sendForm').appendChild(ex);} ex.value=value||''; }

  // Keep target value synced from radios
  document.querySelectorAll('input[name="target"]').forEach(function(r){ r.addEventListener('change', function(){ document.getElementById('target_input').value=this.value; }); });

  // Prevent double submit
  document.getElementById('sendForm').addEventListener('submit', function(){
    var btn = document.getElementById('sendBtn');
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> পাঠানো হচ্ছে...';
  });
})();
</script>
@endpush
@endsection
