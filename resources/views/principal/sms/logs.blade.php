@extends('layouts.admin')
@section('title','এসএমএস লগ')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0">এসএমএস লগ</h1>
  <div>
    <a href="{{ route('principal.institute.sms.panel',$school) }}" class="btn btn-outline-primary"><i class="fas fa-paper-plane mr-1"></i> প্যানেল</a>
    <a href="{{ route('principal.institute.sms.index',$school) }}" class="btn btn-outline-secondary"><i class="fas fa-cog mr-1"></i> সেটিংস</a>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header"><strong>ফিল্টার</strong></div>
  <div class="card-body">
    <form method="get" class="form-row">
      <div class="form-group col-md-2">
        <label>নম্বর</label>
        <input type="text" name="number" value="{{ request('number') }}" class="form-control" placeholder="017...">
      </div>
      <div class="form-group col-md-2">
        <label>স্ট্যাটাস</label>
        <select name="status" class="form-control">
          <option value="">সব</option>
          <option value="success" {{ request('status')==='success'?'selected':'' }}>সফল</option>
          <option value="failed" {{ request('status')==='failed'?'selected':'' }}>বিফল</option>
        </select>
      </div>
      <div class="form-group col-md-2">
        <label>ধরন</label>
        <input type="text" name="type" value="{{ request('type') }}" class="form-control" placeholder="teacher|student">
      </div>
      <div class="form-group col-md-2">
        <label>তারিখ (হতে)</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
      </div>
      <div class="form-group col-md-2">
        <label>তারিখ (পর্যন্ত)</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
      </div>
      <div class="form-group col-md-2 d-flex align-items-end">
        <button class="btn btn-primary w-100"><i class="fas fa-search mr-1"></i> খুঁজুন</button>
      </div>
    </form>
  </div>
</div>

<div class="row mb-3">
  <div class="col-md-4">
    <div class="card text-white bg-primary">
      <div class="card-body py-3 d-flex align-items-center justify-content-between">
        <div>
          <h6 class="mb-0">মোট পাঠানো মেসেজ (লগ)</h6>
          <h3 class="mb-0 font-weight-bold">{{ number_format($totalLogsCount) }}</h3>
        </div>
        <i class="fas fa-history fa-2x opacity-50"></i>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card text-white bg-info">
      <div class="card-body py-3 d-flex align-items-center justify-content-between">
        <div>
          <h6 class="mb-0">মোট এসএমএস ইউনিট (পার্ট)</h6>
          <h3 class="mb-0 font-weight-bold">{{ number_format($totalParts) }}</h3>
        </div>
        <i class="fas fa-sms fa-2x opacity-50"></i>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card text-white bg-success">
      <div class="card-body py-3 d-flex align-items-center justify-content-between">
        <!-- <div>
          <h6 class="mb-0">সফল মেসেজ (ফিল্টার অনুযায়ী)</h6>
          <h3 class="mb-0 font-weight-bold">{{ number_format($successCount) }}</h3>
          <small>সফলভাবে পাঠানো হয়েছে</small>
        </div> --> 
        <i class="fas fa-check-circle fa-2x opacity-50"></i>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>লগসমূহ ({{ $logs->total() }})</strong>
    <small class="text-muted">পৃষ্ঠা {{ $logs->currentPage() }} / {{ $logs->lastPage() }}</small>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped table-hover table-sm mb-0">
        <thead class="thead-light">
          <tr>
            <th>ক্র.নং</th>
            <th>সময়</th>
            <th>স্ট্যাটাস</th>
            <th>ধরন</th>
            <th>বিভাগ</th>
            <th>প্রাপকের নাম</th>
            <th>রোল</th>
            <th>শ্রেণি</th>
            <th>শাখা</th>
            <th>নম্বর</th>
            <th>প্রেরক</th>
            <th>বার্তা</th>
            <th>পার্ট</th>
            <th>একশন</th>
          </tr>
        </thead>
        <tbody>
          @foreach($logs as $log)
            <tr>
              <td>{{ ($logs->currentPage() - 1) * $logs->perPage() + $loop->iteration }}</td>
              <td>{{ $log->created_at }}</td>
              <td>{!! in_array($log->status, ['success', 'sent']) ? '<span class="badge badge-success">সফল</span>' : '<span class="badge badge-danger">বিফল</span>' !!}</td>
              <td>{{ $log->recipient_type }}</td>
              <td>{{ $log->recipient_category }}</td>
              <td>{{ $log->recipient_name }}</td>
              <td>{{ $log->roll_number }}</td>
              <td>{{ $log->class_name }}</td>
              <td>{{ $log->section_name }}</td>
              <td>{{ $log->recipient_number }}</td>
              <td>{{ $log->sender?->name ?? ($log->sent_by_user_id ? 'User#'.$log->sent_by_user_id : '') }}</td>
              <td style="max-width:240px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="{{ $log->message }}">{{ $log->message }}</td>
              <td class="text-center"><span class="badge badge-light border">{{ $log->getPartsCount() }}</span></td>
              <td><a href="{{ route('principal.institute.sms.logs.view',[$school,$log]) }}" target="_blank" class="btn btn-xs btn-outline-primary"><i class="fas fa-eye"></i> View</a></td>
            </tr>
          @endforeach
          @if($logs->isEmpty())
            <tr><td colspan="14" class="text-center text-muted">কোনো তথ্য নেই</td></tr>
          @endif
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer">
    {{ $logs->onEachSide(0)->links() }}
  </div>
</div>
@endsection
