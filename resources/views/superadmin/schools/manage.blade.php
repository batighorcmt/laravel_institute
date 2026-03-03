@extends('layouts.admin')
@section('title', $school->name . ' ব্যবস্থাপনা')
@section('content')
    <div class="row mb-3">
        <div class="col-sm-8"><h1 class="m-0">{{ $school->name }} ব্যবস্থাপনা</h1></div>
        <div class="col-sm-4 text-right"><a href="{{ route('superadmin.schools.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> ফিরে তালিকায়</a></div>
    </div>

    <div class="card mb-3">
        <div class="card-body d-flex align-items-center">
            <div class="mr-3" style="width:90px;height:90px;">
                @if($school->logo)
                    <img src="{{ Storage::url($school->logo) }}" alt="logo" class="rounded" style="width:100%;height:100%;object-fit:cover;">
                @else
                    <div class="bg-light border rounded d-flex align-items-center justify-content-center" style="width:100%;height:100%;">—</div>
                @endif
            </div>
            <div>
                <h4 class="mb-1">{{ $school->name }} <small class="text-muted">({{ $school->code }})</small></h4>
                <p class="mb-1">স্ট্যাটাস: <span class="badge badge-{{ $school->status === 'active' ? 'success':'secondary' }}">{{ $school->status }}</span></p>
                @if($school->phone) <p class="mb-1">ফোন: {{ $school->phone }}</p> @endif
                @if($school->email) <p class="mb-1">ইমেইল: {{ $school->email }}</p> @endif
                @if($school->website) <p class="mb-1">ওয়েবসাইট: <a href="{{ $school->website }}" target="_blank">{{ $school->website }}</a></p> @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="info-box bg-info">
                <span class="info-box-icon"><i class="fas fa-users-cog"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">ব্যবহারকারী</span>
                    <span class="info-box-number">--</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-layer-group"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">ক্লাস</span>
                    <span class="info-box-number">--</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="info-box bg-warning">
                <span class="info-box-icon"><i class="fas fa-book"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">বিষয়</span>
                    <span class="info-box-number">--</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="info-box bg-danger">
                <span class="info-box-icon"><i class="fas fa-user-graduate"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">স্টুডেন্ট</span>
                    <span class="info-box-number">--</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title mb-0">পরবর্তী কাজ</h3></div>
        <div class="card-body">
            <ul class="mb-0">
                <li>এই প্রতিষ্ঠানের জন্য ক্লাস তৈরি করুন</li>
                <li>বিষয় (Subject) যোগ করুন</li>
                <li>টিচার / প্রিন্সিপাল ইউজার অ্যাসাইন করুন</li>
                <li>স্টুডেন্ট ইমপোর্ট বা ম্যানুয়াল এন্ট্রি করুন</li>
            </ul>
        </div>
    </div>
@endsection
