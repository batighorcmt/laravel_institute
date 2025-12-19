@extends('layouts.print')

@php
  $lang = request('lang', 'bn');
  $printTitle = $lang === 'bn' ? 'ভর্তি আবেদন তালিকা' : 'Admission Applications';
  $printSubtitle = $lang === 'bn' ? ($school->name_bn ?: $school->name) : ($school->name);
  $pageSize = request('pageSize','a4'); // a4 or legal
  // Bengali number conversion helper
  function toBnDigits($s){
    $en = ['0','1','2','3','4','5','6','7','8','9'];
    $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
    return str_replace($en,$bn,(string)$s);
  }
@endphp

@push('print_head')
<style>
  .config-panel { background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; }
  .config-panel .title { font-weight:700; }
  .config-grid { display:grid; grid-template-columns: repeat(2, minmax(260px, 1fr)); gap:14px; }
  @media (max-width: 768px){ .config-grid { grid-template-columns: 1fr; } }
  .config-card { background:#ffffff; border:1px solid #e5e7eb; border-radius:8px; padding:12px; }
  .config-card h6 { margin:0 0 8px 0; font-weight:700; font-size:14px; }
  .form-inline-gap { display:flex; gap:10px; align-items:center; }
  .btn-row { display:flex; gap:8px; align-items:center; }
  .drag-item { display:flex; align-items:center; gap:8px; padding:6px 8px; border:1px solid #e5e7eb; border-radius:6px; background:#fff; margin-bottom:6px; cursor:grab; }
  .drag-item.dragging { opacity:0.6; }
  .drag-item input { margin-right:6px; }
  .drag-item .handle { cursor:grab; color:#6b7280; }
  .table-print { width:100%; border-collapse: collapse; table-layout: auto; }
  .table-print th, .table-print td { font-size:13px; vertical-align:top; border: 1px solid #000; padding:6px; word-break: break-word; overflow-wrap: anywhere; }
  .table-print th { white-space: normal; }
  @page {
    @if($pageSize === 'legal')
      size: legal landscape;
    @else
      size: A4 landscape;
    @endif
    margin: {{ $pageSize === 'legal' ? '6mm' : '8mm' }};
  }
  @media print {
    .no-print, .config-panel { display:none !important; }
    body, html, .content-wrapper, .card, .card-body, table, th, td { background:#ffffff !important; }
    .content-wrapper { margin:0; padding:0; }
    * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { color:#000 !important; }
  }
  .photo { width:55px; height:70px; object-fit:cover; border:1px solid #ddd; border-radius:4px; }
</style>
@endpush

@section('content')
<div class="no-print mb-3">
  <div class="config-panel p-3">
    <div class="title mb-2">{{ $lang==='bn' ? 'প্রিন্ট কনফিগারেশন' : 'Print Configuration' }}</div>
    <div class="config-grid">
      <div class="config-card">
        <h6>{{ $lang==='bn' ? 'কলামসমূহ' : 'Columns' }}</h6>
        <div id="columnsList" class="mt-1"></div>
        <div class="small text-muted mt-1">{{ $lang==='bn' ? 'ড্র্যাগ করে ক্রম পরিবর্তন করুন। আনচেক করে লুকান।' : 'Drag to reorder. Uncheck to hide.' }}</div>
      </div>
      <div class="config-card">
        <h6>{{ $lang==='bn' ? 'সোর্টিং' : 'Sorting' }}</h6>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" id="applySortToggle">
          <label class="form-check-label" for="applySortToggle">{{ $lang==='bn' ? 'সোর্টিং প্রযোজ্য করুন' : 'Apply Sorting' }}</label>
        </div>
        <div class="form-inline-gap mb-1">
          <select id="sortColumn1" class="form-control form-control-sm w-50"></select>
          <select id="sortDir1" class="form-control form-control-sm w-50">
            <option value="asc">Ascending</option>
            <option value="desc">Descending</option>
          </select>
        </div>
        <div class="form-inline-gap mb-1">
          <select id="sortColumn2" class="form-control form-control-sm w-50"></select>
          <select id="sortDir2" class="form-control form-control-sm w-50">
            <option value="asc">Ascending</option>
            <option value="desc">Descending</option>
          </select>
        </div>
        <div class="form-inline-gap mb-2">
          <select id="sortColumn3" class="form-control form-control-sm w-50"></select>
          <select id="sortDir3" class="form-control form-control-sm w-50">
            <option value="asc">Ascending</option>
            <option value="desc">Descending</option>
          </select>
        </div>
        <div class="btn-row">
          <button id="applySort" class="btn btn-sm btn-primary">{{ $lang==='bn' ? 'সোর্ট প্রযোজ্য করুন' : 'Apply Sort' }}</button>
          <button id="resetConfig" class="btn btn-sm btn-secondary">{{ $lang==='bn' ? 'রিসেট' : 'Reset' }}</button>
        </div>

        <div class="mt-3">
          <h6 class="mb-2">{{ $lang==='bn' ? 'স্ট্যাটাস ফিল্টার' : 'Status Filters' }}</h6>
          <div class="d-flex flex-wrap" style="gap:10px">
            <div class="form-check"><input class="form-check-input" type="checkbox" id="fltAccepted" {{ empty($statusFilter) || in_array('accepted',$statusFilter) ? 'checked' : '' }}><label class="form-check-label" for="fltAccepted">{{ $lang==='bn' ? 'গৃহীত' : 'Accepted' }}</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" id="fltPending" {{ empty($statusFilter) || in_array('pending',$statusFilter) ? 'checked' : '' }}><label class="form-check-label" for="fltPending">{{ $lang==='bn' ? 'অপেক্ষমান' : 'Pending' }}</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" id="fltCancelled" {{ empty($statusFilter) || in_array('cancelled',$statusFilter) ? 'checked' : '' }}><label class="form-check-label" for="fltCancelled">{{ $lang==='bn' ? 'বাতিল' : 'Cancelled' }}</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" id="fltPaid" {{ empty($payFilter) || in_array('paid',$payFilter) ? 'checked' : '' }}><label class="form-check-label" for="fltPaid">{{ $lang==='bn' ? 'পরিশোধিত' : 'Paid' }}</label></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" id="fltUnpaid" {{ empty($payFilter) || in_array('unpaid',$payFilter) ? 'checked' : '' }}><label class="form-check-label" for="fltUnpaid">{{ $lang==='bn' ? 'অপরিশোধিত' : 'Unpaid' }}</label></div>
          </div>
          <div class="mt-2">
            <div class="mb-1" style="font-weight:600">{{ $lang==='bn' ? 'শ্রেণি নির্বাচন' : 'Class Filters' }}</div>
            <div id="fltClasses" class="d-flex flex-wrap" style="gap:10px"></div>
          </div>
        </div>
      </div>
      
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    
    <div class="table-responsive">
      <table class="table table-bordered table-sm table-print" id="printTable">
        <thead><tr id="printHeader"></tr></thead>
        <tbody id="printBody"></tbody>
      </table>
    </div>
  </div>
</div>
@endsection

<script>
document.addEventListener('DOMContentLoaded', function(){
 (function(){
  const apps = @json($appsJson);
  let lang = '{{ $lang }}';

  const columns = [
    { key:'serial', label_en:'#', label_bn:'ক্রমিক নং', sortable:false },
    { key:'app_id', label_en:'Application ID', label_bn:'আবেদন আইডি', sortable:true },
    { key:'admission_roll_no', label_en:'Roll No', label_bn:'রোল নং', sortable:true },
    { key:'class_name', label_en:'Class', label_bn:'শ্রেণি', sortable:true },
    { key:'name', label_en:'Student Name', label_bn:'শিক্ষার্থীর নাম', sortable:true },
    { key:'father_name', label_en:"Father's Name", label_bn:'পিতার নাম', sortable:true },
    { key:'mother_name', label_en:"Mother's Name", label_bn:'মাতার নাম', sortable:true },
    { key:'mobile', label_en:'Mobile No', label_bn:'মোবাইল নং', sortable:true },
    { key:'dob', label_en:'Date of Birth', label_bn:'জন্ম তারিখ', sortable:true },
    { key:'gender', label_en:'Gender', label_bn:'লিঙ্গ', sortable:true },
    { key:'religion', label_en:'Religion', label_bn:'ধর্ম', sortable:true },
    { key:'present_address', label_en:'Present Address', label_bn:'বর্তমান ঠিকানা', sortable:true },
    { key:'last_school', label_en:'Previous School', label_bn:'পূর্ববর্তী বিদ্যালয়ের নাম', sortable:true },
    { key:'result', label_en:'Result', label_bn:'ফলাফল', sortable:true },
    { key:'photo', label_en:'Photo', label_bn:'ছবি', sortable:false },
    { key:'fee_amount', label_en:'Fee Amount', label_bn:'ফিসের পরিমান', sortable:true },
    { key:'payment_method', label_en:'Payment Method', label_bn:'পরিশোধের মাধ্যম', sortable:true },
    { key:'status', label_en:'Application Status', label_bn:'আবেদন স্ট্যাটাস', sortable:true },
    { key:'created_at', label_en:'Application Date', label_bn:'আবেদনের তারিখ', sortable:true },
  ];

  const state = {
    lang: 'en',
    order: columns.map(c=>c.key),
    visible: Object.fromEntries(columns.map(c=>[c.key,true])),
    sort: { key:'id', dir:'desc' }
  };

  const elColumns = document.getElementById('columnsList');
  const elHeader = document.getElementById('printHeader');
  const elBody = document.getElementById('printBody');
  const applySortToggle = document.getElementById('applySortToggle');
  const elSortCol1 = document.getElementById('sortColumn1');
  const elSortDir1 = document.getElementById('sortDir1');
  const elSortCol2 = document.getElementById('sortColumn2');
  const elSortDir2 = document.getElementById('sortDir2');
  const elSortCol3 = document.getElementById('sortColumn3');
  const elSortDir3 = document.getElementById('sortDir3');
  const fltAccepted = document.getElementById('fltAccepted');
  const fltPending = document.getElementById('fltPending');
  const fltCancelled = document.getElementById('fltCancelled');
  const fltPaid = document.getElementById('fltPaid');
  const fltUnpaid = document.getElementById('fltUnpaid');

  // Build sort column options
  function refreshSortOptions(){
    const sortSelects = [elSortCol1, elSortCol2, elSortCol3];
    sortSelects.forEach(sel=> sel.innerHTML = '');
    // Blank option for disabling sort
    sortSelects.forEach(sel=>{
      const blank = document.createElement('option');
      blank.value = '';
      blank.textContent = lang==='bn' ? 'সোর্ট নেই' : 'No Sort';
      sel.appendChild(blank);
    });
    columns.forEach(c=>{
      if(c.sortable){
        const opt = document.createElement('option');
        opt.value = c.key;
        opt.textContent = lang==='bn' ? c.label_bn : c.label_en;
        sortSelects.forEach(sel=> sel.appendChild(opt.cloneNode(true)));
      }
    });
    // Default: no sort selected
    if (elSortCol1) elSortCol1.value = '';
    if (elSortDir1) elSortDir1.value = 'asc';
    if (elSortCol2) elSortCol2.value = '';
    if (elSortDir2) elSortDir2.value = 'asc';
    if (elSortCol3) elSortCol3.value = '';
    if (elSortDir3) elSortDir3.value = 'asc';
  }

  function composeAddress(a){
    const parts = [];
    if(a.present_village){
      let v = a.present_village;
      if(a.present_para_moholla){ v += ' ('+a.present_para_moholla+')'; }
      parts.push(v);
    }
    if(a.present_post_office){ parts.push(a.present_post_office); }
    if(a.present_upazilla){ parts.push(a.present_upazilla); }
    if(a.present_district){ parts.push(a.present_district); }
    return parts.join(', ');
  }

  function translateGender(v){
    if(!v) return '';
    const m = {
      'male':'ছেলে','female':'মেয়ে','other':'অন্যান্য'
    };
    return lang==='bn' ? (m[String(v).toLowerCase()] || v) : v;
  }
  function translateReligion(v){
    if(!v) return '';
    const m = { 'islam':'ইসলাম','hindu':'হিন্দু','buddhist':'বৌদ্ধ','christian':'খ্রিস্টান' };
    const key = String(v).toLowerCase();
    return lang==='bn' ? (m[key] || v) : v;
  }
  function translateMethod(v){
    if(!v) return '';
    const mapEn = { 'sslcommerz':'SSLCommerz','bkash':'bKash','nagad':'Nagad','cash':'Cash','bank':'Bank' };
    const mapBn = { 'sslcommerz':'অনলাইন','bkash':'বিকাশ','nagad':'নগদ','cash':'নগদে','bank':'ব্যাংক' };
    const key = String(v).toLowerCase();
    return lang==='bn' ? (mapBn[key] || v) : (mapEn[key] || v);
  }
  function toBn(s){
    const en = ['0','1','2','3','4','5','6','7','8','9'];
    const bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
    return String(s).replace(/[0-9]/g, d=>bn[en.indexOf(d)]);
  }

  function labelFor(key){
    const c = columns.find(x=>x.key===key);
    return c ? (lang==='bn' ? c.label_bn : c.label_en) : key;
  }

  // use the single `lang` declared above; avoid redeclaration errors
  function valueFor(a, key, idx){
    switch(key){
      case 'serial': return lang==='bn' ? toBn(idx+1) : (idx+1);
      case 'class_name': return a.class_name || '';
      case 'app_id': return a.app_id ? String(a.app_id) : '—';
      case 'admission_roll_no': {
        const r = a.admission_roll_no ? String(a.admission_roll_no).padStart(3,'0') : '—';
        return lang==='bn' ? toBn(r) : r;
      }
      case 'name': return lang==='bn' ? (a.name_bn||'') : (a.name_en||'');
      case 'father_name': return lang==='bn' ? (a.father_name_bn||'') : (a.father_name_en||'');
      case 'mother_name': return lang==='bn' ? (a.mother_name_bn||'') : (a.mother_name_en||'');
      case 'mobile': return a.mobile ? (lang==='bn' ? toBn(a.mobile) : a.mobile) : '';
      case 'dob': return a.dob ? (lang==='bn' ? toBn(a.dob) : a.dob) : '';
      case 'gender': return translateGender(a.gender);
      case 'religion': return translateReligion(a.religion);
      case 'present_address': return composeAddress(a) || '—';
      case 'last_school': return a.last_school || '';
      case 'result': return a.result || '';
      case 'photo': {
        const src = a.photo ? '{{ asset('storage/admission') }}/' + a.photo : '{{ asset('images/default-avatar.png') }}';
        return `<img src="${src}" alt="Photo" class="photo">`;
      }
      case 'fee_amount': {
        if(a.fee_amount == null) return '—';
        const v = Number(a.fee_amount).toFixed(2);
        const txt = (lang==='bn' ? '৳ ' + toBn(v) : '৳ ' + v);
        return txt;
      }
      case 'payment_method': return translateMethod(a.payment_method);
      case 'status':
        if(a.accepted_at){ return lang==='bn' ? 'গৃহীত' : 'Accepted'; }
        if(a.status==='cancelled'){ return lang==='bn' ? 'বাতিল' : 'Cancelled'; }
        return lang==='bn' ? 'অপেক্ষমান' : 'Pending';
      case 'payment_status':
        return (a.payment_status==='Paid') ? (lang==='bn' ? 'পরিশোধিত' : 'Paid') : (lang==='bn' ? 'অপরিশোধিত' : 'Unpaid');
      case 'created_at': return a.created_at ? (lang==='bn' ? toBn(a.created_at) : a.created_at) : '';
      default: return a[key] ?? '';
    }
  }

  function renderTable(){
    // Header
    elHeader.innerHTML = '';
    state.order.filter(k=>state.visible[k]).forEach(key=>{
      const th = document.createElement('th');
      th.textContent = labelFor(key);
      elHeader.appendChild(th);
    });
    // Body
    elBody.innerHTML = '';
    let rows = apps.slice();
    // Compose sorts (optional)
      const sorts = [
        { key: elSortCol1.value || '', dir: elSortDir1.value || 'asc' },
        { key: elSortCol2.value || '', dir: elSortDir2.value || 'asc' },
        { key: elSortCol3.value || '', dir: elSortDir3.value || 'asc' },
      ].filter(s=>!!s.key);

      // Filter by status
      rows = rows.filter(a => {
        const appStatusOk = (a.accepted_at && fltAccepted.checked) ||
                            (!a.accepted_at && a.status!=='cancelled' && fltPending.checked) ||
                            (a.status==='cancelled' && fltCancelled.checked);
        const payOk = (a.payment_status==='Paid' && fltPaid.checked) ||
                      ((a.payment_status!=='Paid' || !a.payment_status) && fltUnpaid.checked);
        let classOk = true;
        if (document.getElementById('fltClasses')) {
          const selected = Array.from(document.getElementById('fltClasses').querySelectorAll('input[type="checkbox"]'))
                               .filter(el=>el.checked)
                               .map(el=>el.value);
          if (selected.length > 0) {
            classOk = selected.includes(String(a.class_name||''));
          }
        }
        return appStatusOk && payOk && classOk;
      });

      // Multi-column sort (only if enabled)
      if (applySortToggle && applySortToggle.checked && sorts.length > 0) {
        rows.sort((a,b)=>{
          for(const s of sorts){
            const av = valueFor(a, s.key, 0);
            const bv = valueFor(b, s.key, 0);
            const dir = s.dir==='desc' ? -1 : 1;
            const an = parseFloat(av);
            const bn = parseFloat(bv);
            let cmp = 0;
            if(!isNaN(an) && !isNaN(bn)){
              cmp = an < bn ? -1 : (an > bn ? 1 : 0);
            } else {
              const as = String(av||'');
              const bs = String(bv||'');
              cmp = as.localeCompare(bs, lang==='bn' ? 'bn' : 'en', { numeric:true });
            }
            if(cmp !== 0) return cmp * dir;
          }
          return 0;
        });
      }

    rows.forEach((a, idx)=>{
      const tr = document.createElement('tr');
      state.order.filter(k=>state.visible[k]).forEach(key=>{
        const td = document.createElement('td');
        const val = valueFor(a, key, idx);
        // Allow HTML for photo
        if(key==='photo') { td.innerHTML = val; }
        else { td.textContent = val; }
        tr.appendChild(td);
      });
      elBody.appendChild(tr);
    });
    refreshSortOptions();
  }

  function renderColumnsList(){
    if (!elColumns) return;
    elColumns.innerHTML = '';
    state.order.forEach(key=>{
      const row = document.createElement('div');
      row.className = 'drag-item';
      row.draggable = true;
      row.dataset.key = key;
      const cb = document.createElement('input');
      cb.type = 'checkbox';
      cb.checked = !!state.visible[key];
      cb.addEventListener('change', ()=>{ state.visible[key] = cb.checked; renderTable(); });
      const label = document.createElement('span');
      label.textContent = labelFor(key);
      const handle = document.createElement('span');
      handle.className = 'handle';
      handle.innerHTML = '<i class="fas fa-grip-vertical"></i>';
      row.appendChild(cb);
      row.appendChild(handle);
      row.appendChild(label);
      elColumns.appendChild(row);
    });

    let dragSrc;
    elColumns.addEventListener('dragstart', e=>{
      const item = e.target.closest('.drag-item');
      if(!item) return; dragSrc = item; item.classList.add('dragging');
    });
    elColumns.addEventListener('dragend', e=>{
      const item = e.target.closest('.drag-item');
      if(item) item.classList.remove('dragging');
    });
    elColumns.addEventListener('dragover', e=>{
      e.preventDefault();
      const item = e.target.closest('.drag-item');
      if(!item || item===dragSrc) return;
      const rect = item.getBoundingClientRect();
      const after = (e.clientY - rect.top) > rect.height/2;
      if(after) {
        item.after(dragSrc);
      } else {
        item.before(dragSrc);
      }
    });
    elColumns.addEventListener('drop', ()=>{
      // Update order from DOM
      state.order = Array.from(elColumns.querySelectorAll('.drag-item')).map(it=>it.dataset.key);
      renderTable();
    });
  }

  // Events
  document.getElementById('applySort').addEventListener('click', ()=>{ renderTable(); });
  const resetBtn = document.getElementById('resetConfig');
  if (resetBtn) {
    resetBtn.addEventListener('click', ()=>{
      // Reset sorting to none
      refreshSortOptions(); if (applySortToggle) applySortToggle.checked = false;
      // Show all columns
      state.visible = Object.fromEntries(state.order.map(k=>[k,true]));
      renderColumnsList();
      renderTable();
    });
  }
  [fltAccepted, fltPending, fltCancelled, fltPaid, fltUnpaid].forEach(cb => cb.addEventListener('change', renderTable));
  if (applySortToggle) { applySortToggle.addEventListener('change', renderTable); }

  // Build class filter list from data
  const fltClasses = document.getElementById('fltClasses');
  function renderClassFilters(){
    if (!fltClasses) return;
    const classes = Array.from(new Set(apps.map(a=>String(a.class_name||'')).filter(v=>v)) ).sort();
    fltClasses.innerHTML = '';
    classes.forEach(cls=>{
      const wrap = document.createElement('div'); wrap.className = 'form-check';
      const cb = document.createElement('input'); cb.className = 'form-check-input'; cb.type = 'checkbox'; cb.id = 'class_'+cls; cb.value = cls; cb.checked = true;
      const label = document.createElement('label'); label.className = 'form-check-label'; label.setAttribute('for', 'class_'+cls); label.textContent = cls;
      wrap.appendChild(cb); wrap.appendChild(label);
      fltClasses.appendChild(wrap);
      cb.addEventListener('change', renderTable);
    });
  }

  // Initialize
  refreshSortOptions();
  renderClassFilters();
  renderColumnsList();
  renderTable();
})();
});
</script>
 
