@extends('layouts.admin')
@section('title','শিক্ষার্থী তালিকা')
@section('content')
<div class="d-flex justify-content-between mb-3">
  <h1 class="m-0">শিক্ষার্থী তালিকা - {{ $school->name }}</h1>
  <a href="{{ route('principal.institute.students.create',$school) }}" class="btn btn-success"><i class="fas fa-user-plus mr-1"></i> নতুন শিক্ষার্থী</a>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<form class="form-inline mb-3" method="get">
  <input type="text" name="q" value="{{ $q }}" class="form-control mr-2" placeholder="নাম / আইডি সার্চ...">
  <button class="btn btn-outline-secondary">সার্চ</button>
</form>
@php
  $yearLabel = $currentYear? $currentYear->name : 'বর্তমান বর্ষ নির্ধারিত হয়নি';
@endphp
<div class="table-responsive" style="overflow: visible;">
  <table class="table table-bordered table-sm">
    <thead class="thead-light">
      <tr>
        <th style="width:60px">ক্রমিক</th>
        <th>আইডি নং</th>
        <th>নাম</th>
        <th>পিতার নাম</th>
        <th>শ্রেণি</th>
        <th>শাখা</th>
        <th>রোল</th>
        <th>গ্রুপ</th>
        <th>মোবাইল নং</th>
        <th>স্ট্যাটাস</th>
        <th>বিষয়সমূহ ({{ $yearLabel }})</th>
        <th style="width:120px">অ্যাকশন</th>
      </tr>
    </thead>
    <tbody>
    @foreach($students as $idx=>$stu)
      @php
        $en = $stu->enrollments->first();
        $subsHtml = '';
        if ($en) {
          $subs = collect($en->subjects);
          $subsSorted = $subs->sortBy(function($ss){
            $code = optional($ss->subject)->code;
            $num  = $code ? intval(preg_replace('/\D+/', '', $code)) : PHP_INT_MAX;
            return [ $ss->is_optional ? 1 : 0, $num, $code ]; // optional last, then by numeric code
          })->values();
          $parts = [];
          foreach ($subsSorted as $ss) {
            $code = optional($ss->subject)->code;
            if (!$code) { continue; }
            if ($ss->is_optional) {
              $parts[] = '<span class="text-primary">'.e($code).'</span>';
            } else {
              $parts[] = e($code);
            }
          }
          $subsHtml = implode(', ', $parts);
        }
      @endphp
      <tr>
        <td>{{ $students->firstItem() + $idx }}</td>
        <td>{{ $stu->student_id }}</td>
        <td>{{ $stu->full_name }}</td>
        <td>{{ $stu->father_name_bn ?: $stu->father_name }}</td>
        <td>{{ $en? $en->class?->name : '-' }}</td>
        <td>{{ $en? $en->section?->name : '-' }}</td>
        <td>{{ $en? $en->roll_no : '-' }}</td>
        <td>{{ $en? $en->group?->name : '-' }}</td>
        <td>{{ $stu->guardian_phone }}</td>
        <td>
          @php($st = $stu->status)
          <span class="badge badge-{{ $st==='active'?'success':($st==='inactive'?'secondary':($st==='graduated'?'info':'warning')) }}">{{ $st }}</span>
        </td>
        <td class="small">{!! $subsHtml ?: '-' !!}</td>
        <td class="text-nowrap">
          <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle student-action-dd" type="button" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">
              Action
            </button>
            <div class="dropdown-menu dropdown-menu-right">
              <a class="dropdown-item" href="{{ route('principal.institute.students.show',[$school,$stu]) }}"><i class="fas fa-id-card mr-1"></i> প্রোফাইল</a>
              <a class="dropdown-item" href="{{ route('principal.institute.students.edit',[$school,$stu]) }}"><i class="fas fa-edit mr-1"></i> সম্পাদনা</a>
              @if($en)
                <a class="dropdown-item" href="{{ route('principal.institute.enrollments.subjects.edit',[$school,$en]) }}"><i class="fas fa-book mr-1"></i> বিষয় নির্বাচন</a>
              @endif
              <div class="dropdown-divider"></div>
              <form action="{{ route('principal.institute.students.toggle-status',[$school,$stu]) }}" method="post" class="px-3 py-1">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-link p-0 m-0 align-baseline">
                  <i class="fas fa-toggle-{{ $stu->status==='active'?'on':'off' }} mr-1"></i>
                  {{ $stu->status==='active' ? 'নিষ্ক্রিয় করুন' : 'সক্রিয় করুন' }}
                </button>
              </form>
            </div>
          </div>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>
<div class="d-flex justify-content-between align-items-center mt-2">
  <div>মোট {{ $students->total() }}টির মধ্যে {{ $students->firstItem() }}–{{ $students->lastItem() }}</div>
  <div>{{ $students->links() }}</div>
</div>
@endsection
@push('scripts')
<script>
(function(){
  // Detach dropdown menus to body so table/layout overflow/transform doesn't misplace them
  function positionMenu(button, menu){
    const rect = button.getBoundingClientRect();
    const top = rect.bottom + window.scrollY;
    menu.style.position = 'absolute';
    menu.style.top = top + 'px';
    menu.style.minWidth = rect.width + 'px';
    menu.style.zIndex = 2000;
    // Align by direction
    // Measure after show to get correct width
    const menuWidth = menu.offsetWidth || rect.width;
    const rightAligned = menu.classList.contains('dropdown-menu-right');
    const left = rightAligned
      ? (rect.right + window.scrollX - menuWidth)
      : (rect.left + window.scrollX);
    menu.style.left = Math.max(0, left) + 'px';
  }

  document.addEventListener('shown.bs.dropdown', function(e){
    const btn = e.target; // button
    if(!btn.classList.contains('student-action-dd')) return;
    const menu = btn.parentElement.querySelector('.dropdown-menu');
    if(!menu) return;
    // Move to body
    document.body.appendChild(menu);
    menu.classList.add('show'); // ensure visible after move
    positionMenu(btn, menu);
    // Reposition on resize/scroll
    function realign(){ if(menu.classList.contains('show')) positionMenu(btn, menu); }
    window.addEventListener('scroll', realign, {passive:true});
    window.addEventListener('resize', realign);
    menu._realignHandler = realign;
    menu._originButton = btn;
  });

  document.addEventListener('hide.bs.dropdown', function(e){
    const btn = e.target;
    if(!btn.classList.contains('student-action-dd')) return;
    const menu = document.querySelector('.dropdown-menu.show');
    if(menu && menu._originButton === btn){
      menu.classList.remove('show');
      // Put back inside dropdown container to keep DOM tidy
      btn.parentElement.appendChild(menu);
      window.removeEventListener('scroll', menu._realignHandler);
      window.removeEventListener('resize', menu._realignHandler);
      delete menu._realignHandler;
      delete menu._originButton;
    }
  });
})();
</script>
@endpush