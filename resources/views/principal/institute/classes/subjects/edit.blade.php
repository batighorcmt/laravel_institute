@extends('layouts.admin')
@section('title','বিষয় ম্যাপিং সম্পাদনা')
@section('content')
<div class="d-flex justify-content-between mb-3">
  <h1 class="m-0">ম্যাপিং সম্পাদনা: {{ $mapping->subject->name }}</h1>
  <a href="{{ route('principal.institute.classes.subjects.index',[$school,$class]) }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> ফিরে যান</a>
</div>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<div class="card">
  <div class="card-body">
    <form method="post" action="{{ route('principal.institute.classes.subjects.update',[$school,$class,$mapping]) }}">@csrf @method('PATCH')
      <div class="form-group">
        <label>বিষয়</label>
        <input type="text" class="form-control" value="{{ $mapping->subject->name }}" disabled>
      </div>
      @if($class->usesGroups())
      <div class="form-group">
        <label>গ্রুপ</label>
        <input type="text" class="form-control" value="{{ $mapping->group?->name ?? 'Common' }}" disabled>
      </div>
      @endif
      <div class="form-group">
        <label>প্রকার (Offered Mode) *</label>
        <select name="offered_mode" class="form-control" required>
          <option value="compulsory" {{ $mapping->offered_mode==='compulsory'?'selected':'' }}>বাধ্যতামূলক</option>
          <option value="optional" {{ $mapping->offered_mode==='optional'?'selected':'' }}>অপশনাল</option>
          <option value="both" {{ $mapping->offered_mode==='both'?'selected':'' }}>উভয় (বাধ্যতামূলক + অপশনাল)</option>
        </select>
      </div>
      <div class="alert alert-info small">
        <ul class="mb-0 pl-3">
          <li>৬–৮ শ্রেণিতে একটির বেশি অপশনাল অনুমোদিত নয়।</li>
          <li>৯–১০ এ Optional/Both করতে গ্রুপ আবশ্যক এবং নির্দিষ্ট বিষয়সমূহ (বিজ্ঞান: জীববিজ্ঞান/উচ্চতর গণিত/কৃষি, মানবিক/বাণিজ্য: কৃষি) অনুমোদিত।</li>
          <li>"উভয়" নির্বাচন করলে তালিকায় ব্যাজ থাকবে এবং প্রয়োজনে ছাত্র বাছাই পর্যায়ে অপশনাল হিসেবে নেওয়া যাবে।</li>
        </ul>
      </div>
      <button class="btn btn-primary"><i class="fas fa-save mr-1"></i> সংরক্ষণ</button>
    </form>
  </div>
</div>
@endsection