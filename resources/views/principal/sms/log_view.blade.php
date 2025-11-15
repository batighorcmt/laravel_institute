@extends('layouts.admin')
@section('title','এসএমএস লগ #'.$log->id)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <h1 class="m-0">এসএমএস লগ ভিউ #{{ $log->id }}</h1>
  <div>
    <a href="{{ route('principal.institute.sms.logs',$school) }}" class="btn btn-secondary btn-sm">Back</a>
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print mr-1"></i> Print</button>
  </div>
  </div>

<div class="card">
  <div class="card-body">
    <div class="row">
      <div class="col-md-6">
        <dl class="row mb-0">
          <dt class="col-5">সময়</dt><dd class="col-7">{{ $log->created_at }}</dd>
          <dt class="col-5">স্ট্যাটাস</dt><dd class="col-7">{{ $log->status }}</dd>
          <dt class="col-5">ধরন</dt><dd class="col-7">{{ $log->recipient_type }}</dd>
          <dt class="col-5">বিভাগ</dt><dd class="col-7">{{ $log->recipient_category }}</dd>
          <dt class="col-5">নাম</dt><dd class="col-7">{{ $log->recipient_name }}</dd>
          <dt class="col-5">রোল</dt><dd class="col-7">{{ $log->roll_number }}</dd>
        </dl>
      </div>
      <div class="col-md-6">
        <dl class="row mb-0">
          <dt class="col-5">শ্রেণি</dt><dd class="col-7">{{ $log->class_name }}</dd>
          <dt class="col-5">শাখা</dt><dd class="col-7">{{ $log->section_name }}</dd>
          <dt class="col-5">মোবাইল</dt><dd class="col-7">{{ $log->recipient_number }}</dd>
          <dt class="col-5">প্রেরক</dt><dd class="col-7">{{ $log->sender?->name ?? ($log->sent_by_user_id ? 'User#'.$log->sent_by_user_id : '') }}</dd>
        </dl>
      </div>
    </div>
    <hr>
    <div>
      <div class="mb-1 text-muted">বার্তা</div>
      <div class="border rounded p-3" style="white-space:pre-wrap;background:#fafafa">{{ $log->message }}</div>
    </div>
  </div>
</div>

<style>
@media print { .no-print, .no-print * { display: none !important; } }
</style>
@endsection
