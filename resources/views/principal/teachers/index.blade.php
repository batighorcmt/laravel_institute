@extends('layouts.admin')
@section('title','শিক্ষক ব্যবস্থাপনা')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0"><i class="fas fa-user-tie mr-1"></i> শিক্ষক ব্যবস্থাপনা</h1>
</div>

@if(session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row">
  <div class="col-md-5">
    <div class="card">
      <div class="card-header">নতুন শিক্ষক যুক্ত করুন</div>
      <div class="card-body">
        <form method="POST" action="{{ route('principal.institute.teachers.store', $school) }}">
          @csrf
          <div class="form-group">
            <label>নাম (প্রথম)</label>
            <input type="text" name="first_name" class="form-control" required value="{{ old('first_name') }}">
          </div>
          <div class="form-group">
            <label>নাম (শেষ)</label>
            <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}">
          </div>
          <div class="form-group">
            <label>মোবাইল</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
          </div>
          <div class="form-group">
            <label>পদবী</label>
            <input type="text" name="designation" class="form-control" value="{{ old('designation') }}" placeholder="যেমন: সহকারী শিক্ষক">
          </div>
          <div class="form-group">
            <label>সিরিয়াল নং</label>
            <input type="number" name="serial_number" class="form-control" value="{{ old('serial_number') }}" placeholder="যেমন: 1,2,3...">
            <small class="form-text text-muted">সিরিয়াল অনুযায়ী শিক্ষক তালিকা সাজানো হবে</small>
          </div>
          <button class="btn btn-primary">সংরক্ষণ</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-md-7">
    <div class="card">
      <div class="card-header">সকল শিক্ষক</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th style="width:80px">সিরিয়াল</th>
                <th>নাম</th>
                <th>মোবাইল</th>
                <th>পদবী</th>
                <th style="width:140px">কার্য</th>
              </tr>
            </thead>
            <tbody>
              @forelse($teachers as $t)
                <tr>
                  <td>{{ $t->serial_number }}</td>
                  <td>{{ $t->user->first_name }} {{ $t->user->last_name }}</td>
                  <td>{{ $t->user->phone }}</td>
                  <td>{{ $t->designation }}</td>
                  <td>
                    <button class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#editTeacher{{ $t->id }}">সম্পাদনা</button>
                    <form action="{{ route('principal.institute.teachers.destroy', [$school, $t->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('মুছতে নিশ্চিত?');">
                      @csrf @method('DELETE')
                      <button class="btn btn-sm btn-outline-danger">মুছুন</button>
                    </form>
                  </td>
                </tr>
                <!-- Edit Modal -->
                <div class="modal fade" id="editTeacher{{ $t->id }}" tabindex="-1" role="dialog">
                  <div class="modal-dialog" role="document">
                    <div class="modal-content">
                      <div class="modal-header"><h5 class="modal-title">শিক্ষক সম্পাদনা</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                      <div class="modal-body">
                        <form method="POST" action="{{ route('principal.institute.teachers.update', [$school, $t->id]) }}">
                          @csrf @method('PUT')
                          <div class="form-group">
                            <label>নাম (প্রথম)</label>
                            <input type="text" name="first_name" class="form-control" required value="{{ $t->user->first_name }}">
                          </div>
                          <div class="form-group">
                            <label>নাম (শেষ)</label>
                            <input type="text" name="last_name" class="form-control" value="{{ $t->user->last_name }}">
                          </div>
                          <div class="form-group">
                            <label>মোবাইল</label>
                            <input type="text" name="phone" class="form-control" value="{{ $t->user->phone }}">
                          </div>
                          <div class="form-group">
                            <label>পদবী</label>
                            <input type="text" name="designation" class="form-control" value="{{ $t->designation }}">
                          </div>
                          <div class="form-group">
                            <label>সিরিয়াল নং</label>
                            <input type="number" name="serial_number" class="form-control" value="{{ $t->serial_number }}">
                          </div>
                          <button class="btn btn-primary">আপডেট</button>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
              @empty
                <tr><td colspan="5" class="text-center text-muted">কোনো শিক্ষক পাওয়া যায়নি</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
