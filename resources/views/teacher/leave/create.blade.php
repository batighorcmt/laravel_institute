@extends('layouts.admin')

@section('title', 'Apply Leave')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">ছুটি আবেদন</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('teacher.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('teacher.leave.index') }}">Leaves</a></li>
                    <li class="breadcrumb-item active">Apply</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Leave Application</h3></div>
                    <form method="POST" action="{{ route('teacher.leave.store') }}">
                        @csrf
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="start_date">শুরু</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ old('start_date') }}" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="end_date">শেষ</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="{{ old('end_date') }}" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="type">ধরণ</label>
                                    <select class="form-control" id="type" name="type">
                                        <option value="">Select type</option>
                                        <option value="CL" {{ old('type')==='CL'?'selected':'' }}>Casual Leave (CL)</option>
                                        <option value="SL" {{ old('type')==='SL'?'selected':'' }}>Sick Leave (SL)</option>
                                        <option value="ML" {{ old('type')==='ML'?'selected':'' }}>Medical Leave (ML)</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="reason">কারণ</label>
                                    <input type="text" class="form-control" id="reason" name="reason" value="{{ old('reason') }}" placeholder="সংক্ষিপ্ত কারণ">
                                </div>
                            </div>

                            @if($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Submit</button>
                            <a href="{{ route('teacher.leave.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
