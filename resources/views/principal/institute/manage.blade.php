@extends('layouts.admin')
@section('title', $school->name.' - ব্যবস্থাপনা')
@section('content')
<div class="row mb-3">
  <div class="col-sm-7"><h1 class="m-0">{{ $school->name }} <small class="text-muted">({{ $school->code }})</small></h1></div>
  <div class="col-sm-5 text-right">
    <a href="{{ route('principal.institute') }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> ফিরে তালিকায়</a>
  </div>
</div>
<div class="row">
  <div class="col-md-4 mb-3">
    <div class="info-box bg-info">
      <span class="info-box-icon"><i class="fas fa-layer-group"></i></span>
      <div class="info-box-content">
        <span class="info-box-text">ক্লাস</span>
        <span class="info-box-number">--</span>
      </div>
    </div>
  </div>
  <div class="col-md-4 mb-3">
    <div class="info-box bg-success">
      <span class="info-box-icon"><i class="fas fa-book"></i></span>
      <div class="info-box-content">
        <span class="info-box-text">বিষয়</span>
        <span class="info-box-number">--</span>
      </div>
    </div>
  </div>
  <div class="col-md-4 mb-3">
    <div class="info-box bg-warning">
      <span class="info-box-icon"><i class="fas fa-user-graduate"></i></span>
      <div class="info-box-content">
        <span class="info-box-text">স্টুডেন্ট</span>
        <span class="info-box-number">--</span>
      </div>
    </div>
  </div>
</div>
<div class="card mb-4">
  <div class="card-header"><h3 class="card-title mb-0">সেটিংস ও ব্যবস্থাপনা</h3></div>
  <div class="card-body">
    <div class="list-group">
      <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
        <span><i class="fas fa-users-cog mr-2"></i> টিচার ব্যবস্থাপনা</span><i class="fas fa-angle-right"></i>
      </a>
      <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
        <span><i class="fas fa-layer-group mr-2"></i> ক্লাস সেটআপ</span><i class="fas fa-angle-right"></i>
      </a>
      <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
        <span><i class="fas fa-book-open mr-2"></i> বিষয় সেটআপ</span><i class="fas fa-angle-right"></i>
      </a>
      <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
        <span><i class="fas fa-user-graduate mr-2"></i> স্টুডেন্ট তালিকা / ইমপোর্ট</span><i class="fas fa-angle-right"></i>
      </a>
      <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
        <span><i class="fas fa-bullhorn mr-2"></i> নোটিশ বোর্ড</span><i class="fas fa-angle-right"></i>
      </a>
      <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
        <span><i class="fas fa-cogs mr-2"></i> ইনস্টিটিউট সেটিংস</span><i class="fas fa-angle-right"></i>
      </a>
    </div>
  </div>
</div>
@endsection
