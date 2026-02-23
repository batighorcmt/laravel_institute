@extends('layouts.admin')
@section('title', 'শিক্ষক তালিকা')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><i class="fas fa-user-tie mr-2"></i> শিক্ষক তালিকা</h1>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    @include('parent.partials.selector')

    <div class="row">
        @forelse($teachers as $teacher)
        <div class="col-12 col-sm-6 col-md-4 d-flex align-items-stretch flex-column">
            <div class="card bg-light d-flex flex-fill">
                <div class="card-header text-muted border-bottom-0">
                    শিক্ষক
                </div>
                <div class="card-body pt-0">
                    <div class="row">
                        <div class="col-7">
                            <h2 class="lead"><b>{{ $teacher->name }}</b></h2>
                            <p class="text-muted text-sm"><b>বিষয: </b> {{ $teacher->specialization ?? 'N/A' }} </p>
                            <ul class="ml-4 mb-0 fa-ul text-muted">
                                <li class="small"><span class="fa-li"><i class="fas fa-lg fa-building"></i></span> পদবী: {{ $teacher->designation ?? 'Teacher' }}</li>
                                <li class="small"><span class="fa-li"><i class="fas fa-lg fa-phone"></i></span> ফোন: {{ $teacher->phone ?? 'N/A' }}</li>
                            </ul>
                        </div>
                        <div class="col-5 text-center">
                            <img src="{{ $teacher->photo ? asset('storage/'.$teacher->photo) : asset('images/default-teacher.png') }}" alt="user-avatar" class="img-circle img-fluid">
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="text-right">
                        <a href="#" class="btn btn-sm btn-primary">
                            <i class="fas fa-user"></i> প্রোফাইল দেখুন
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center">
            <p class="text-muted">কোনো শিক্ষক পাওয়া যায়নি।</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
