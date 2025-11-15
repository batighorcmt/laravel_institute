@extends('layouts.admin')
@section('title','ক্লাসের বিষয় ম্যাপিং')
@section('content')
<div class="d-flex justify-content-between mb-3">
  <h1 class="m-0">{{ $class->name }} ({{ $class->numeric_value }}) এর বিষয়সমূহ</h1>
  <a href="{{ route('principal.institute.classes.index',$school) }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> ক্লাস তালিকা</a>
</div>
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row">
  <div class="col-md-5">
    <div class="card">
      <div class="card-header"><strong>নতুন বিষয় ম্যাপ করুন</strong></div>
      <div class="card-body">
        <form method="post" action="{{ route('principal.institute.classes.subjects.store',[$school,$class]) }}">@csrf
          <div class="form-group">
            <label>বিষয় *</label>
            <select name="subject_id" class="form-control" required>
              <option value="">-- নির্বাচন করুন --</option>
              @foreach($subjects as $sub)
                <option value="{{ $sub->id }}">{{ $sub->name }}</option>
              @endforeach
            </select>
          </div>
          @if($class->usesGroups())
          <div class="form-group">
            <label>গ্রুপসমূহ (একাধিক নির্বাচন করলে আলাদাভাবে যোগ হবে)</label>
            <select name="group_ids[]" class="form-control" multiple size="5">
              @foreach($groups as $grp)
                <option value="{{ $grp->id }}">{{ $grp->name }}</option>
              @endforeach
            </select>
            <small class="text-muted d-block mt-1">ফাঁকা রাখলে Common (সব গ্রুপ) হিসেবে যোগ হবে। Optional নির্বাচিত এবং ফাঁকা রাখলে সব গ্রুপেই প্রয়োগের চেষ্টা করবে।</small>
          </div>
          @endif
          <div class="form-group">
            <label>প্রকার *</label>
            <select name="offered_mode" class="form-control" required>
              <option value="compulsory">বাধ্যতামূলক</option>
              <option value="optional">অপশনাল</option>
              <option value="both">উভয় (বাধ্যতামূলক + অপশনাল)</option>
            </select>
            <small class="text-muted">উভয় নির্বাচন করলে বিষয়টি বাধ্যতামূলক তালিকায় থাকবে এবং অপশনাল হিসেবেও নেওয়া যাবে।</small>
          </div>
          <button class="btn btn-success"><i class="fas fa-plus mr-1"></i> যুক্ত করুন</button>
        </form>
      </div>
    </div>
    @if($class->usesGroups())
    <div class="card mt-3">
      <div class="card-header d-flex justify-content-between align-items-center"><strong>Bulk Add (গ্রুপ / Common)</strong></div>
      <div class="card-body">
        <form method="post" action="{{ route('principal.institute.classes.subjects.bulk',[$school,$class]) }}">@csrf
          <div class="form-group">
            <label>বিষয়সমূহ *</label>
            <select name="subject_ids[]" class="form-control" multiple size="8" required>
              @foreach($subjects as $sub)
                <option value="{{ $sub->id }}">{{ $sub->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">একাধিক নির্বাচন করতে Ctrl/Command ধরে ক্লিক করুন</small>
          </div>
          <div class="form-group">
            <label>গ্রুপসমূহ (Multi)</label>
            <select name="group_ids[]" class="form-control" multiple size="6">
              @foreach($groups as $grp)
                <option value="{{ $grp->id }}">{{ $grp->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">ফাঁকা রাখলে Common অথবা Optional হলে সকল গ্রুপে প্রয়োগের চেষ্টা।</small>
          </div>
          <div class="form-group">
            <label>প্রকার *</label>
            <select name="offered_mode" class="form-control" required>
              <option value="compulsory">বাধ্যতামূলক</option>
              <option value="optional">অপশনাল</option>
              <option value="both">উভয় (বাধ্যতামূলক + অপশনাল)</option>
            </select>
          </div>
          <button class="btn btn-success"><i class="fas fa-layer-group mr-1"></i> Bulk Add</button>
        </form>
      </div>
    </div>
    @endif
    <div class="mt-3 small text-muted">
      <ul class="mb-0 pl-3">
        <li>৬–৮ শ্রেণিতে গ্রুপ নেই; একটি মাত্র অপশনাল বিষয় নেওয়া যাবে।</li>
        <li>৯–১০ শ্রেণিতে গ্রুপ নির্বাচন করলে বিষয়টি শুধু সেই গ্রুপের জন্য প্রযোজ্য হবে। গ্রুপ ফাঁকা রাখলে সব গ্রুপেই প্রযোজ্য।</li>
        <li>একই বিষয়কে একাধিক গ্রুপে আলাদাভাবে যোগ করা যাবে।</li>
        <li>Optional Allowed (Science): জীববিজ্ঞান, উচ্চতর গণিত, কৃষিশিক্ষা</li>
        <li>Optional Allowed (Humanities / Business): কৃষিশিক্ষা</li>
      </ul>
    </div>
  </div>
  <div class="col-md-7">
    <div class="card">
      <div class="card-header"><strong>ম্যাপ করা বিষয়সমূহ</strong></div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped mb-0" id="mapping-table">
            <thead>
              <tr>
                <th>#</th>
                <th>বিষয়</th>
                @if($class->usesGroups())<th>গ্রুপ</th>@endif
                <th>অপশনাল</th>
                <th>প্যাটার্ন</th>
                <th>অ্যাকশন</th>
              </tr>
            </thead>
            <tbody>
              @forelse($mappings as $i=>$map)
                <tr data-id="{{ $map->id }}">
                  <td class="drag-handle" style="cursor:move"><i class="fas fa-grip-vertical text-muted"></i></td>
                  <td>{{ $map->subject->name }}</td>
                  @if($class->usesGroups())<td>{{ $map->group?->name ?? 'সাধারণ' }}</td>@endif
                  <td>
                    @if($map->offered_mode==='both')
                      <span class="badge badge-secondary">উভয়</span>
                    @elseif($map->offered_mode==='optional')
                      <span class="badge badge-warning">অপশনাল</span>
                    @else
                      <span class="badge badge-info">বাধ্যতামূলক</span>
                    @endif
                  </td>
                  <td>
                    @php $s=$map->subject; $parts=[]; if($s->has_creative) $parts[]='সৃজনশীল'; if($s->has_mcq) $parts[]='MCQ'; if($s->has_practical) $parts[]='ব্যবহারিক'; @endphp
                    <span class="badge badge-info">{{ implode('+',$parts) ?: 'Single' }}</span>
                  </td>
                  <td>
                    <a href="{{ route('principal.institute.classes.subjects.edit',[$school,$class,$map]) }}" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                    <form method="post" action="{{ route('principal.institute.classes.subjects.destroy',[$school,$class,$map]) }}" onsubmit="return confirm('মুছে ফেলবেন?')" class="d-inline">@csrf @method('DELETE')
                      <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="{{ $class->usesGroups()?6:5 }}" class="text-center text-muted">কোনও বিষয় ম্যাপ হয়নি</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer text-right">
        <button id="save-order" class="btn btn-primary btn-sm"><i class="fas fa-save mr-1"></i> ক্রম সংরক্ষণ</button>
      </div>
    </div>
  </div>
</div>
@endsection
@push('scripts')
<script>
  // Simple drag & drop ordering
  const tbody = document.querySelector('#mapping-table tbody');
  let draggingEl;
  tbody.querySelectorAll('tr').forEach(tr=>{
    tr.draggable = true;
    tr.addEventListener('dragstart', e=>{ draggingEl = tr; tr.classList.add('table-active'); });
    tr.addEventListener('dragend', e=>{ tr.classList.remove('table-active'); draggingEl = null; });
    tr.addEventListener('dragover', e=>{ e.preventDefault(); const after = getDragAfterElement(tbody, e.clientY); if(after==null){ tbody.appendChild(draggingEl);} else { tbody.insertBefore(draggingEl, after); } });
  });
  function getDragAfterElement(container, y){
    const els = [...container.querySelectorAll('tr:not(.table-active)')];
    return els.reduce((closest,child)=>{
      const box = child.getBoundingClientRect();
      const offset = y - box.top - box.height/2;
      if(offset < 0 && offset > closest.offset){ return {offset:offset, element:child}; }
      else return closest;
    },{offset:Number.NEGATIVE_INFINITY}).element;
  }
  document.getElementById('save-order').addEventListener('click', function(){
    const order = [...tbody.querySelectorAll('tr')].map(r=>r.dataset.id).filter(Boolean);
    fetch('{{ route('principal.institute.classes.subjects.order',[$school,$class]) }}', {
      method:'PATCH',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
      body: JSON.stringify({order:order})
    }).then(r=>r.json()).then(j=>{
      if(j.status==='ok'){
        toastr.success('ক্রম সংরক্ষণ হয়েছে');
      } else {
        toastr.error('ক্রম সংরক্ষণ ব্যর্থ');
      }
    }).catch(()=>toastr.error('নেটওয়ার্ক ত্রুটি'));
  });
</script>
@endpush