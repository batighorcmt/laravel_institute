@extends('layouts.admin')
@section('title', 'পঠিত বিষয় সমূহ')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><i class="fas fa-book-open mr-2"></i> পঠিত বিষয় সমূহ</h1>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    @include('parent.partials.selector')

    <div class="row">
        @forelse($subjects as $s)
        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box shadow-none border">
                <span class="info-box-icon bg-{{ $s->is_optional ? 'warning' : 'primary' }}"><i class="fas fa-book"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text font-weight-bold">{{ $s->subject->name }}</span>
                    <span class="info-box-number text-sm text-muted">কোড: {{ $s->subject->code }}</span>
                    @if($s->is_optional)
                    <span class="badge badge-warning">ঐচ্ছিক বিষয়</span>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center">
            <p class="text-muted">কোনো বিষয় পাওয়া যায়নি।</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
