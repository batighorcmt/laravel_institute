@extends('layouts.admin')
@section('title','Bulk Student Add')
@section('content')
<div class="d-flex justify-content-between mb-3">
  <h1 class="m-0">Bulk Student Add - {{ $school->name }}</h1>
  <a href="{{ route('principal.institute.students.index',$school) }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> তালিকা</a>
</div>

@if(session('bulk_import_report'))
  @php($r = session('bulk_import_report'))
  <div class="alert alert-info">
    <strong>সফল:</strong> {{ $r['success'] }} টি রেকর্ড যোগ হয়েছে<br>
    @if(count($r['errors'])>0)
      <strong>ত্রুটি:</strong>
      <ul>
        @foreach($r['errors'] as $err)
          <li class="text-danger small">{!! e($err) !!}</li>
        @endforeach
      </ul>
    @endif
  </div>
@endif

<div class="card">
  <div class="card-body">
    <form method="post" action="{{ route('principal.institute.students.bulk.import',$school) }}" enctype="multipart/form-data" id="bulkImportForm">
      @csrf
      <div id="bulkProgress" class="mb-3" style="display:none">
        <label class="small">Processing...</label>
        <div class="progress">
          <div id="bulkProgressBar" class="progress-bar" role="progressbar" style="width:0%">0%</div>
        </div>
      </div>
      <div class="form-group">
        <label>CSV বা Excel ফাইল (XLSX/XLS/ODS) — Excel থেকে সরাসরি আপলোড করতে পারেন</label>
        <input type="file" name="file" accept=".csv,text/csv,.xlsx,.xls,.ods" class="form-control-file" required>
        @error('file') <div class="text-danger small">{{ $message }}</div> @enderror
      </div>
      <div class="form-group">
        <button class="btn btn-primary" id="bulkSubmit">ফাইল আপলোড ও ইমপোর্ট</button>
        <button class="btn btn-success" id="bulkQueue">ফাইল আপলোড ও কিউতে পাঠান</button>
        <a href="{{ route('principal.institute.students.bulk.template', $school) }}" class="btn btn-outline-secondary">নমুনা টেমপ্লেট ডাউনলোড</a>
      </div>
    </form>
    <div id="bulkResult" class="mt-3" style="display:none"></div>

    <hr>
    <h5>CSV হেডার নির্দেশিকা</h5>
    <p class="small">নিম্নলিখিত হেডারগুলো ব্যবহার করুন (অর্ডার শর্ত নয়, কিন্তু নামগুলো লাগবে):</p>
    <ul class="small">
      <li><code>student_name_bn</code> (আবশ্যক)</li>
      <li><code>student_name_en</code> (ঐচ্ছিক)</li>
      <li><code>date_of_birth</code> (আবশ্যক) — YYYY-MM-DD বা DD/MM/YYYY</li>
      <li><code>gender</code> (আবশ্যক) — <code>male</code> বা <code>female</code></li>
      <li><code>father_name</code>, <code>mother_name</code> (আবশ্যক)</li>
      <li><code>guardian_phone</code> (আবশ্যক)</li>
      <li><code>address</code> (আবশ্যক)</li>
      <li><code>admission_date</code> (আবশ্যক) — YYYY-MM-DD বা DD/MM/YYYY</li>
      <li><code>status</code> (ঐচ্ছিক) — <code>active</code>, <code>inactive</code>, <code>graduated</code>, <code>transferred</code></li>
      <li>অতিরিক্ত (ঐচ্ছিক) এনরোলমেন্টের জন্য: <code>enroll_academic_year</code>, <code>enroll_class_id</code>, <code>enroll_section_id</code>, <code>enroll_group_id</code>, <code>enroll_roll_no</code></li>
    </ul>

    <p class="small text-muted">নোট: এক্সেল থেকে সরাসরি XLSX ইমপোর্ট করতে চাইলে প্রোজেকটে <code>maatwebsite/excel</code> প্যাকেজ ইনস্টল করতে হবে; আপাতত CSV সাপোর্ট আছে।</p>
  </div>
</div>

@endsection

@push('scripts')
<script>
// Sample template now served by backend route; inline generator removed.

// AJAX submit with simple progress animation
;(function(){
  const form = document.getElementById('bulkImportForm');
  if (!form) return;
  const progressWrap = document.getElementById('bulkProgress');
  const progressBar = document.getElementById('bulkProgressBar');
  const resultBox = document.getElementById('bulkResult');
  const submitBtn = document.getElementById('bulkSubmit');

  form.addEventListener('submit', async function(ev){
    ev.preventDefault();
    resultBox.style.display='none'; resultBox.innerHTML='';
    progressWrap.style.display='block'; progressBar.style.width='5%'; progressBar.textContent='5%';
    submitBtn.disabled = true;

    // animate until response
    let p = 5; const id = setInterval(()=>{ if (p < 90) { p += Math.floor(Math.random()*6)+1; p = Math.min(90,p); progressBar.style.width = p + '%'; progressBar.textContent = p + '%'; } }, 400);

    try {
      const fd = new FormData(form);
      const res = await fetch(form.action, {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With':'XMLHttpRequest' },
        credentials: 'same-origin'
      });
      clearInterval(id);
      if (!res.ok) {
        const txt = await res.text();
        progressBar.style.width='100%'; progressBar.textContent='100%';
        resultBox.style.display='block'; resultBox.className='alert alert-danger mt-2';
        resultBox.innerHTML = '<strong>Import failed</strong><pre class="small">'+escapeHtml(txt.substring(0,1000))+'</pre>';
      } else {
        const json = await res.json();
        progressBar.style.width='100%'; progressBar.textContent='100%';
        resultBox.style.display='block'; resultBox.className='alert alert-info';
        let html = '<strong>সফল:</strong> ' + (json.success||0) + ' টি রেকর্ড যোগ হয়েছে<ol>';
        if (json.errors && json.errors.length) {
          html += '</ol><strong>ত্রুটি:</strong><ul class="small text-danger">';
          for (const e of json.errors) { html += '<li>'+escapeHtml(e)+'</li>'; }
          html += '</ul>';
        } else { html += '</ol>'; }
        resultBox.innerHTML = html;
      }
    } catch (err) {
      clearInterval(id);
      progressBar.style.width='100%'; progressBar.textContent='100%';
      resultBox.style.display='block'; resultBox.className='alert alert-danger';
      resultBox.textContent = 'Import failed: ' + (err.message||err);
    } finally {
      submitBtn.disabled = false;
    }
  });

  // Queue submit: uploads file and dispatches background job, then polls status
  const queueBtn = document.getElementById('bulkQueue');
  if (queueBtn) {
    queueBtn.addEventListener('click', async function(ev){
      ev.preventDefault();
      resultBox.style.display='none'; resultBox.innerHTML='';
      progressWrap.style.display='block'; progressBar.style.width='5%'; progressBar.textContent='5%';
      queueBtn.disabled = true; submitBtn.disabled = true;

      try {
        const fd = new FormData(form);
        // determine queue endpoint from data attribute or fallback to a named URL
        const url = form.getAttribute('data-queue-action') || ('{{ route("principal.institute.students.bulk.queue", $school) }}');
        const res = await fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With':'XMLHttpRequest' }, credentials: 'same-origin' });
        if (!res.ok) throw new Error('Upload failed: '+res.statusText);
        const json = await res.json();
        // json should contain { id }
        const jobId = json.id || json.import_id || json.job_id;
        if (!jobId) throw new Error('No job id returned from server');

        // poll status endpoint
        // Laravel url() helper strips trailing slash; ensure manual slash for concatenation
        const statusUrlBase = ('{{ url("principal/institute/".$school->id."/students/bulk/status") }}/');
        const reportUrlBase = ('{{ url("principal/institute/".$school->id."/students/bulk/report") }}/');

        let finished = false;
        let pollCount = 0;
        while (!finished && pollCount < 240) { // up to ~4 minutes with 1s interval
          await new Promise(r=>setTimeout(r, 1000));
          pollCount++;
          try {
            const sres = await fetch(statusUrlBase + jobId, { headers: { 'X-Requested-With':'XMLHttpRequest' }, credentials: 'same-origin' });
            if (!sres.ok) continue;
            const sjson = await sres.json();
            const pct = Math.min(100, Math.max(0, Math.round((sjson.progress||0))));
            progressBar.style.width = pct + '%'; progressBar.textContent = pct + '%';
            if (sjson.status === 'completed' || sjson.status === 'failed' || (sjson.progress||0) >= 100) {
              finished = true;
              // show report
              resultBox.style.display='block'; resultBox.className='alert alert-info';
              let html = '<strong>সফল:</strong> ' + (sjson.success||0) + ' টি রেকর্ড যোগ হয়েছে<ol>';
              if (sjson.errors && sjson.errors.length) {
                html += '</ol><strong>ত্রুটি:</strong><ul class="small text-danger">';
                for (const e of sjson.errors) { html += '<li>'+escapeHtml(e)+'</li>'; }
                html += '</ul>';
              } else { html += '</ol>'; }
              // if report file available, show download link
              if (sjson.report_available) {
                html += '<p class="mt-2"><a class="btn btn-sm btn-outline-primary" href="'+ (reportUrlBase + jobId) +'">ত্রুটি রিপোর্ট ডাউনলোড করুন</a></p>';
              }
              resultBox.innerHTML = html;
            }
          } catch (err) {
            // ignore poll errors, continue
          }
        }

      } catch (err) {
        progressBar.style.width='100%'; progressBar.textContent='100%';
        resultBox.style.display='block'; resultBox.className='alert alert-danger';
        resultBox.textContent = 'Queue upload failed: ' + (err.message||err);
      } finally {
        queueBtn.disabled = false; submitBtn.disabled = false;
      }
    });
  }

  function escapeHtml(s){ return String(s).replace(/[&<>\\"]/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }
})();
</script>
@endpush
