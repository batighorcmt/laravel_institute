@extends('layouts.admin')
@section('title', 'নোটিস বোর্ড')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><i class="fas fa-bullhorn mr-2"></i> নোটিস বোর্ড</h1>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    @include('parent.partials.selector')

    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title">নোটিস বোর্ড</h3>
        </div>
        <div class="card-body">
            @forelse($notices as $notice)
            <div class="post">
                <div class="user-block">
                    <span class="username">
                        <a href="#">{{ $notice->title }}</a>
                    </span>
                    <span class="description">প্রকাশিত: {{ $notice->publish_at ? $notice->publish_at->format('d M, Y h:i A') : '-' }}</span>
                </div>
                <!-- /.user-block -->
                <p>
                    {!! nl2br(e($notice->body)) !!}
                </p>
            </div>
            @empty
            <div class="text-center p-5">
                <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                <p class="text-muted">কোনো নোটিস নেই।</p>
            </div>
            @endforelse

            <div class="mt-4">
                {{ $notices->appends(['student_id' => $selectedStudent->id])->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
