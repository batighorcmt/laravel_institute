@extends('layouts.admin')

@section('title', 'মতামত ও অভিযোগ')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">মতামত ও অভিযোগ</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.manage', $school) }}">{{ $school->name }}</a></li>
                    <li class="breadcrumb-item active">মতামত ও অভিযোগ</li>
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">আবেদনসমূহ</h3>
                <form method="GET" class="form-inline">
                    <select class="form-control form-control-sm" name="status" onchange="this.form.submit()">
                        <option value="">সব</option>
                        <option value="pending" {{ $status==='pending'?'selected':'' }}>নতুন</option>
                        <option value="read" {{ $status==='read'?'selected':'' }}>দেখা হয়েছে</option>
                        <option value="replied" {{ $status==='replied'?'selected':'' }}>উত্তর দেওয়া হয়েছে</option>
                    </select>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>অভিভাবক</th>
                                <th>শিক্ষার্থী</th>
                                <th>বিষয়</th>
                                <th>তারিখ</th>
                                <th>অবস্থা</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($feedbacks as $i => $fb)
                                <tr>
                                    <td>{{ $feedbacks->firstItem() + $i }}</td>
                                    <td>{{ optional($fb->user)->name ?? '-' }}</td>
                                    <td>{{ optional($fb->student)->full_name ?? '-' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($fb->subject, 60) }}</td>
                                    <td>{{ $fb->created_at->format('d M, Y') }}</td>
                                    <td>
                                        @if($fb->status === 'replied')
                                            <span class="badge badge-success">উত্তর দেওয়া হয়েছে</span>
                                        @elseif($fb->status === 'read')
                                            <span class="badge badge-info">দেখা হয়েছে</span>
                                        @else
                                            <span class="badge badge-warning">নতুন</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('principal.institute.parent-feedback.show', [$school, $fb]) }}" class="btn btn-primary btn-sm">দেখুন</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">কোনো আবেদন নেই।</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                {{ $feedbacks->links() }}
            </div>
        </div>
    </div>
</section>
@endsection
