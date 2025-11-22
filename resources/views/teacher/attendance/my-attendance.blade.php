@extends('layouts.admin')

@section('title', 'আমার হাজিরার রেকর্ড')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">আমার হাজিরার রেকর্ড</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    @if(auth()->user()->isPrincipal())
                        <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                    @else
                        <li class="breadcrumb-item"><a href="{{ route('teacher.dashboard') }}">ড্যাশবোর্ড</a></li>
                    @endif
                    <li class="breadcrumb-item"><a href="{{ route('teacher.attendance.index') }}">হাজিরা</a></li>
                    <li class="breadcrumb-item active">রেকর্ড</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">চলতি মাসের হাজিরা - {{ \Carbon\Carbon::now()->format('F Y') }}</h3>
                    </div>
                    <div class="card-body">
                        @if ($attendances->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>তারিখ</th>
                                            <th>চেক-ইন</th>
                                            <th>চেক-আউট</th>
                                            <th>মোট সময়</th>
                                            <th>স্ট্যাটাস</th>
                                            <th>ছবি</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($attendances as $attendance)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($attendance->date)->format('d/m/Y (l)') }}</td>
                                                <td>
                                                    @if ($attendance->check_in_time)
                                                        <i class="fas fa-sign-in-alt text-success"></i>
                                                        {{ \Carbon\Carbon::parse($attendance->check_in_time)->format('h:i A') }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($attendance->check_out_time)
                                                        <i class="fas fa-sign-out-alt text-warning"></i>
                                                        {{ \Carbon\Carbon::parse($attendance->check_out_time)->format('h:i A') }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($attendance->check_in_time && $attendance->check_out_time)
                                                        @php
                                                            $checkIn = \Carbon\Carbon::parse($attendance->check_in_time);
                                                            $checkOut = \Carbon\Carbon::parse($attendance->check_out_time);
                                                            $diff = $checkIn->diff($checkOut);
                                                        @endphp
                                                        {{ $diff->h }} ঘণ্টা {{ $diff->i }} মিনিট
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($attendance->status === 'present')
                                                        <span class="badge badge-success">উপস্থিত</span>
                                                    @elseif ($attendance->status === 'late')
                                                        <span class="badge badge-warning">বিলম্ব</span>
                                                    @elseif ($attendance->status === 'absent')
                                                        <span class="badge badge-danger">অনুপস্থিত</span>
                                                    @else
                                                        <span class="badge badge-info">হাফ ডে</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($attendance->check_in_photo)
                                                        <button class="btn btn-xs btn-info view-photo" 
                                                                data-photo="{{ asset('storage/' . $attendance->check_in_photo) }}"
                                                                data-title="চেক-ইন ছবি">
                                                            <i class="fas fa-image"></i> চেক-ইন
                                                        </button>
                                                    @endif
                                                    @if ($attendance->check_out_photo)
                                                        <button class="btn btn-xs btn-warning view-photo" 
                                                                data-photo="{{ asset('storage/' . $attendance->check_out_photo) }}"
                                                                data-title="চেক-আউট ছবি">
                                                            <i class="fas fa-image"></i> চেক-আউট
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Summary -->
                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <div class="info-box bg-success">
                                        <div class="info-box-content">
                                            <span class="info-box-text">মোট উপস্থিত</span>
                                            <span class="info-box-number">{{ $attendances->where('status', 'present')->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box bg-warning">
                                        <div class="info-box-content">
                                            <span class="info-box-text">মোট বিলম্ব</span>
                                            <span class="info-box-number">{{ $attendances->where('status', 'late')->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box bg-danger">
                                        <div class="info-box-content">
                                            <span class="info-box-text">মোট অনুপস্থিত</span>
                                            <span class="info-box-number">{{ $attendances->where('status', 'absent')->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-box bg-info">
                                        <div class="info-box-content">
                                            <span class="info-box-text">মোট হাফ ডে</span>
                                            <span class="info-box-number">{{ $attendances->where('status', 'half_day')->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> এই মাসে কোনো হাজিরা রেকর্ড নেই।
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Photo Modal -->
<div class="modal fade" id="photoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="photoModalTitle"></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="photoModalImage" src="" alt="Attendance Photo" class="img-fluid" style="max-height: 500px;">
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Wait for jQuery to be loaded
(function checkJQuery() {
    if (typeof jQuery === 'undefined') {
        setTimeout(checkJQuery, 50);
        return;
    }
    
    $(document).ready(function() {
        $('.view-photo').click(function() {
            const photo = $(this).data('photo');
            const title = $(this).data('title');
            
            $('#photoModalTitle').text(title);
            $('#photoModalImage').attr('src', photo);
            $('#photoModal').modal('show');
        });
    });
})();
</script>
@endsection
