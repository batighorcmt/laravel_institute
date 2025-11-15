@extends('layouts.admin')
@section('title','বিষয় নির্বাচন')
@section('content')
<div class="d-flex justify-content-between mb-3">
  <h1 class="m-0">বিষয় নির্বাচন</h1>
  <a href="{{ route('principal.institute.students.show',[$school,$enrollment->student_id]) }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> প্রোফাইলে ফিরে যান</a>
</div>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<div class="card mb-3">
  <div class="card-body">
    <div>
      <strong>শিক্ষার্থী:</strong> {{ $enrollment->student->full_name }}
      <span class="mx-2">|</span>
      <strong>বর্ষ:</strong> {{ $enrollment->academic_year }}
      <span class="mx-2">|</span>
      <strong>শ্রেণি:</strong> {{ $enrollment->class?->name }}
      @if($enrollment->section)<span class="mx-2">|</span><strong>শাখা:</strong> {{ $enrollment->section->name }}@endif
      @if($enrollment->group)<span class="mx-2">|</span><strong>গ্রুপ:</strong> {{ $enrollment->group->name }}@endif
      <span class="mx-2">|</span>
      <strong>রোল:</strong> {{ $enrollment->roll_no }}
    </div>
  </div>
</div>

<form method="post" action="{{ route('principal.institute.enrollments.subjects.update',[$school,$enrollment]) }}">@csrf
  <div class="row">
    <div class="col-md-6">
      <h5>আবশ্যিক (স্থির) বিষয়</h5>
      <ul class="list-group mb-3">
        @forelse($compulsoryFixed as $sub)
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>{{ $sub->name }} <small class="text-muted">({{ $sub->code }})</small></span>
            <span class="badge badge-primary">Fixed</span>
          </li>
        @empty
          <li class="list-group-item text-muted">কোনো স্থির আবশ্যিক বিষয় নেই</li>
        @endforelse
      </ul>
      <h5 class="mt-3">আবশ্যিক নির্বাচন (উভয় ধরন থেকে ১টি)</h5>
      <div class="list-group mb-3">
        @forelse($bothList as $sub)
          <label class="list-group-item d-flex align-items-center">
            <input type="radio" name="compulsory_both_id" value="{{ $sub->id }}" class="mr-2" {{ (int)$currentCompulsoryBothId === (int)$sub->id ? 'checked':'' }}>
            {{ $sub->name }} <small class="text-muted">({{ $sub->code }})</small>
          </label>
        @empty
          <div class="list-group-item text-muted">উভয় ধরন বিষয় নেই</div>
        @endforelse
        <label class="list-group-item">
          <input type="radio" name="compulsory_both_id" value="" class="mr-2" {{ !$currentCompulsoryBothId ? 'checked':'' }}>
          <span class="text-muted">(কোনোটি নির্বাচন না)</span>
        </label>
      </div>
    </div>
    <div class="col-md-6">
      <h5>ঐচ্ছিক নির্বাচন (একটি)</h5>
      <div class="list-group mb-3">
        @forelse($optionalOnly as $sub)
          <label class="list-group-item d-flex align-items-center">
            <input type="radio" name="optional_subject_id" value="{{ $sub->id }}" class="mr-2" {{ (int)$currentOptionalId === (int)$sub->id ? 'checked':'' }}>
            {{ $sub->name }} <small class="text-muted">({{ $sub->code }})</small>
          </label>
        @empty
          <div class="list-group-item text-muted">কোনো ঐচ্ছিক বিষয় নেই</div>
        @endforelse
        @if($bothList->count())
          <div class="list-group-item bg-light">
            <small class="text-muted">নিচে উভয় ধরন বিষয়কে ঐচ্ছিক হিসেবে নিতে চাইলে নির্বাচন করুন (আবশ্যিক হিসেবে নির্বাচিতটির সাথে একই হতে পারবে না).</small>
          </div>
          @foreach($bothList as $sub)
            <label class="list-group-item d-flex align-items-center">
              <input type="radio" name="optional_subject_id" value="{{ $sub->id }}" class="mr-2" {{ (int)$currentOptionalId === (int)$sub->id ? 'checked':'' }} {{ (int)$currentCompulsoryBothId === (int)$sub->id ? 'disabled':'' }}>
              {{ $sub->name }} <small class="text-muted">({{ $sub->code }})</small>
              @if((int)$currentCompulsoryBothId === (int)$sub->id)<span class="badge badge-secondary ml-2">আবশ্যিক হিসাবে নির্বাচিত</span>@endif
            </label>
          @endforeach
        @endif
        <label class="list-group-item">
          <input type="radio" name="optional_subject_id" value="" class="mr-2" {{ !$currentOptionalId ? 'checked':'' }}>
          <span class="text-muted">(কোনো ঐচ্ছিক বিষয় নয়)</span>
        </label>
      </div>
    </div>
  </div>
  <button class="btn btn-success"><i class="fas fa-save mr-1"></i> সংরক্ষণ</button>
</form>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const compRadios = Array.from(document.querySelectorAll('input[name="compulsory_both_id"]'));
  const optRadios = Array.from(document.querySelectorAll('input[name="optional_subject_id"]'));

  function getSelected(radios){
    const r = radios.find(x=>x.checked);
    return r ? r.value : '';
  }

  function onCompChange(){
    const compVal = getSelected(compRadios);
    if (!compVal) return; // 'none' selected
    // If same selected in optional, deselect optional
    const sameOpt = optRadios.find(x => x.value === compVal && x.checked);
    if (sameOpt) { sameOpt.checked = false; }
  }

  function onOptChange(){
    const compVal = getSelected(compRadios);
    const optVal = getSelected(optRadios);
    if (optVal && compVal && optVal === compVal) {
      // Deselect compulsory if same picked as optional
      const compSel = compRadios.find(x=>x.checked);
      if (compSel) compSel.checked = false;
    }
  }

  compRadios.forEach(r => r.addEventListener('change', onCompChange));
  optRadios.forEach(r => r.addEventListener('change', onOptChange));
});
</script>
@endpush
