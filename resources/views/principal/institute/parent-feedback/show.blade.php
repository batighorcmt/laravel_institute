@extends('layouts.admin')

@section('title', 'মতামত/অভিযোগ বিস্তারিত')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">মতামত/অভিযোগ বিস্তারিত</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.parent-feedback.index', $school) }}">মতামত ও অভিযোগ</a></li>
                    <li class="breadcrumb-item active">বিস্তারিত</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="card">
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-3">অভিভাবক</dt>
                    <dd class="col-sm-9">{{ optional($feedback->user)->name ?? '-' }}</dd>

                    <dt class="col-sm-3">শিক্ষার্থী</dt>
                    <dd class="col-sm-9">{{ optional($feedback->student)->full_name ?? '-' }}</dd>

                    <dt class="col-sm-3">বিষয়</dt>
                    <dd class="col-sm-9">{{ $feedback->subject }}</dd>

                    <dt class="col-sm-3">বার্তা</dt>
                    <dd class="col-sm-9" style="white-space: pre-wrap;">{{ $feedback->message }}</dd>

                    <dt class="col-sm-3">তারিখ</dt>
                    <dd class="col-sm-9">{{ $feedback->created_at->format('d M, Y h:i A') }}</dd>

                    @if($feedback->reply)
                        <dt class="col-sm-3">পূর্বের উত্তর</dt>
                        <dd class="col-sm-9" style="white-space: pre-wrap;">{{ $feedback->reply }}</dd>
                    @endif
                </dl>

                <form action="{{ route('principal.institute.parent-feedback.reply', [$school, $feedback]) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>উত্তর লিখুন</label>
                        <textarea name="reply" class="form-control" rows="4" required>{{ old('reply', $feedback->reply) }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">উত্তর পাঠান</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
