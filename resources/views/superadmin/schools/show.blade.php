@extends('layouts.admin')

@section('title', 'স্কুল বিস্তারিত')
@section('nav.superadmin.dashboard') @endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">স্কুল বিস্তারিত</h1>
        <div class="btn-group">
            <a href="{{ route('superadmin.schools.index') }}" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left mr-1"></i> তালিকা</a>
            <a href="{{ route('superadmin.schools.edit', $school) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit mr-1"></i> সম্পাদনা</a>
            <a href="{{ route('superadmin.schools.manage', $school) }}" class="btn btn-sm btn-info"><i class="fas fa-cogs mr-1"></i> ম্যানেজ</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7 mb-3">
            <div class="card h-100">
                <div class="card-header py-2"><strong>প্রতিষ্ঠানের তথ্য</strong></div>
                <div class="card-body">
                    <div class="d-flex mb-3">
                        <div style="width:110px;" class="mr-3 text-center">
                            @if($school->logo)
                                <img src="{{ Storage::url($school->logo) }}" alt="Logo" class="img-fluid rounded" style="max-height:100px;object-fit:cover">
                            @else
                                <div class="border rounded d-flex align-items-center justify-content-center bg-light" style="height:100px;">লোগো নেই</div>
                            @endif
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-1">{{ $school->name }} <small class="text-muted">({{ $school->code }})</small></h5>
                            @if($school->name_bn)
                                <p class="mb-1"><span class="text-muted">বাংলা নাম:</span> {{ $school->name_bn }}</p>
                            @endif
                            <span class="badge badge-{{ $school->status === 'active' ? 'success' : 'secondary' }}">{{ $school->status }}</span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <p class="mb-1"><strong>ফোন:</strong> {{ $school->phone ?: '—' }}</p>
                            <p class="mb-1"><strong>ইমেইল:</strong> {{ $school->email ?: '—' }}</p>
                            <p class="mb-1"><strong>ওয়েবসাইট:</strong> {{ $school->website ? \Illuminate\Support\Str::limit($school->website,40) : '—' }}</p>
                        </div>
                        <div class="col-sm-6">
                            <p class="mb-1"><strong>ঠিকানা:</strong> {{ $school->address ?: '—' }}</p>
                            <p class="mb-1"><strong>ঠিকানা (বাংলা):</strong> {{ $school->address_bn ?: '—' }}</p>
                        </div>
                    </div>
                    @if($school->description)
                        <hr>
                        <p class="mb-0">{!! nl2br(e($school->description)) !!}</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-5 mb-3">
            <div class="card h-100">
                <div class="card-header py-2"><strong>প্রতিষ্ঠান প্রধান</strong></div>
                <div class="card-body">
                    @if($principal && $principal->user)
                        <h5 class="mb-1">{{ $principal->user->name }}</h5>
                        @if(!empty($principal->designation))
                            <p class="mb-1"><strong>পদবী:</strong> {{ $principal->designation }}</p>
                        @endif
                        <p class="mb-1"><strong>ইমেইল:</strong> {{ $principal->user->email }}</p>
                        <p class="mb-1"><strong>ফোন:</strong> {{ $principal->user->phone ?: '—' }}</p>
                        <p class="mb-1"><strong>স্ট্যাটাস:</strong> <span class="badge badge-{{ $principal->status === 'active' ? 'success' : 'secondary' }}">{{ $principal->status }}</span></p>
                        <hr>
                        <p class="small text-muted mb-0">প্রধান ইউজারটি স্বয়ংক্রিয়ভাবে শিক্ষক তালিকাতেও অন্তর্ভুক্ত করা হয়েছে।</p>
                    @else
                        <p class="text-muted mb-0">প্রধানের তথ্য পাওয়া যায়নি বা এখনো সেট করা হয়নি।</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection