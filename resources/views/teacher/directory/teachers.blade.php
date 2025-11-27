@extends('layouts.admin')

@section('title', 'Teachers Directory')

@section('content')
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0">Teachers Directory</h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="{{ route('teacher.dashboard') }}">Dashboard</a></li>
          <li class="breadcrumb-item active">Teachers</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-body">
        <form method="GET" class="mb-3">
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Name/Designation/Mobile/Email</label>
              <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="Search...">
            </div>
            <div class="form-group col-md-3 align-self-end">
              <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
              <a href="{{ route('teacher.institute.directory.teachers', $school) }}" class="btn btn-secondary">Reset</a>
            </div>
          </div>
        </form>

        <div class="row">
          @forelse($teachers as $t)
            <div class="col-md-3 col-sm-4 col-12 mb-3">
              <div class="card h-100">
                <div class="card-body d-flex">
                  <img src="{{ $t->photo ? asset('storage/'.$t->photo) : asset('images/avatar-teacher.png') }}" class="img-thumbnail mr-2" style="width:64px;height:64px;object-fit:cover;" alt="Photo">
                  <div>
                    <div class="font-weight-bold">{{ $t->full_name }}</div>
                    <div class="text-muted small">{{ $t->designation ?? 'Teacher' }}</div>
                    <div class="small">📧 {{ $t->user->email ?? '-' }}</div>
                    <div class="small">📞 {{ $t->phone ?? '-' }}</div>
                  </div>
                </div>
              </div>
            </div>
          @empty
            <div class="col-12 text-center text-muted">No teachers found</div>
          @endforelse
        </div>
      </div>
      <div class="card-footer">{{ $teachers->links() }}</div>
    </div>
  </div>
</section>
@endsection
