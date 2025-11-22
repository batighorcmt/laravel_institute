@extends('layouts.admin')

@section('title', 'Teacher Daily Attendance Report')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">শিক্ষক দৈনিক হাজিরা রিপোর্ট</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.manage', $school) }}">{{ $school->name }}</a></li>
                    <li class="breadcrumb-item active">শিক্ষক দৈনিক হাজিরা</li>
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
                        <h3 class="card-title">দৈনিক হাজিরা রিপোর্ট</h3>
                        <div class="card-tools">
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-print"></i> প্রিন্ট
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a href="{{ route('principal.institute.teacher-attendance.reports.daily.print', ['school' => $school, 'date' => $date, 'lang' => 'bn']) }}" 
                                       target="_blank" 
                                       class="dropdown-item">
                                        <i class="fas fa-file-alt"></i> বাংলা
                                    </a>
                                    <a href="{{ route('principal.institute.teacher-attendance.reports.daily.print', ['school' => $school, 'date' => $date, 'lang' => 'en']) }}" 
                                       target="_blank" 
                                       class="dropdown-item">
                                        <i class="fas fa-file-alt"></i> English
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('principal.institute.teacher-attendance.reports.daily', $school) }}" class="mb-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="date">তারিখ নির্বাচন করুন</label>
                                        <input type="date" 
                                               class="form-control" 
                                               id="date" 
                                               name="date" 
                                               value="{{ $date }}"
                                               max="{{ \Carbon\Carbon::today()->format('Y-m-d') }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-search"></i> দেখুন
                                    </button>
                                </div>
                            </div>
                        </form>

                        @if($teachers->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="bg-light">
                                        <tr>
                                            <th width="5%">ক্রমিক</th>
                                            <th width="25%">শিক্ষকের নাম</th>
                                            <th width="15%">চেক-ইন</th>
                                            <th width="15%">চেক-আউট</th>
                                            <th width="10%">মোট সময়</th>
                                            <th width="10%">স্ট্যাটাস</th>
                                            <th width="10%">ছবি</th>
                                            <th width="10%">লোকেশন</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($teachers as $index => $teacher)
                                            @php
                                                $attendance = $teacher->teacherAttendances->first();
                                            @endphp
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $teacher->full_name }}</td>
                                                <td>
                                                    @if($attendance && $attendance->check_in_time)
                                                        <i class="fas fa-sign-in-alt text-success"></i>
                                                        {{ \Carbon\Carbon::parse($attendance->check_in_time)->format('h:i A') }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($attendance && $attendance->check_out_time)
                                                        <i class="fas fa-sign-out-alt text-warning"></i>
                                                        {{ \Carbon\Carbon::parse($attendance->check_out_time)->format('h:i A') }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($attendance && $attendance->check_in_time && $attendance->check_out_time)
                                                        @php
                                                            $checkIn = \Carbon\Carbon::parse($attendance->check_in_time);
                                                            $checkOut = \Carbon\Carbon::parse($attendance->check_out_time);
                                                            $diff = $checkIn->diff($checkOut);
                                                        @endphp
                                                        {{ $diff->h }}ঘ {{ $diff->i }}মি
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($attendance)
                                                        @if($attendance->status === 'present')
                                                            <span class="badge badge-success">উপস্থিত</span>
                                                        @elseif($attendance->status === 'late')
                                                            <span class="badge badge-warning">বিলম্ব</span>
                                                        @elseif($attendance->status === 'absent')
                                                            <span class="badge badge-danger">অনুপস্থিত</span>
                                                        @else
                                                            <span class="badge badge-info">হাফ ডে</span>
                                                        @endif
                                                    @else
                                                        <span class="badge badge-secondary">নেই</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($attendance && $attendance->check_in_photo)
                                                        <button class="btn btn-xs btn-info view-photo" 
                                                                data-photo="{{ asset('storage/' . $attendance->check_in_photo) }}"
                                                                data-title="{{ $teacher->full_name }} - চেক-ইন">
                                                            <i class="fas fa-camera"></i> In
                                                        </button>
                                                    @endif
                                                    @if($attendance && $attendance->check_out_photo)
                                                        <button class="btn btn-xs btn-warning view-photo" 
                                                                data-photo="{{ asset('storage/' . $attendance->check_out_photo) }}"
                                                                data-title="{{ $teacher->full_name }} - চেক-আউট">
                                                            <i class="fas fa-camera"></i> Out
                                                        </button>
                                                    @endif
                                                </td>
                                                <td style="white-space: nowrap;">
                                                    @if($attendance && $attendance->check_in_latitude && $attendance->check_in_longitude)
                                                        <a href="https://www.google.com/maps?q={{ $attendance->check_in_latitude }},{{ $attendance->check_in_longitude }}" 
                                                           target="_blank" 
                                                           class="btn btn-xs btn-success"
                                                           title="চেক-ইন লোকেশন দেখুন">
                                                            <i class="fas fa-map-marker-alt"></i> In
                                                        </a>
                                                    @endif
                                                    @if($attendance && $attendance->check_out_latitude && $attendance->check_out_longitude)
                                                        <a href="https://www.google.com/maps?q={{ $attendance->check_out_latitude }},{{ $attendance->check_out_longitude }}" 
                                                           target="_blank" 
                                                           class="btn btn-xs btn-warning"
                                                           title="চেক-আউট লোকেশন দেখুন">
                                                            <i class="fas fa-map-marker-alt"></i> Out
                                                        </a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-light">
                                        <tr>
                                            <td colspan="5" class="text-right"><strong>সারসংক্ষেপ:</strong></td>
                                            <td colspan="3">
                                                <strong>উপস্থিত:</strong> {{ $teachers->filter(fn($t) => $t->teacherAttendances->first()?->status === 'present')->count() }} |
                                                <strong>বিলম্ব:</strong> {{ $teachers->filter(fn($t) => $t->teacherAttendances->first()?->status === 'late')->count() }} |
                                                <strong>অনুপস্থিত:</strong> {{ $teachers->filter(fn($t) => !$t->teacherAttendances->first())->count() }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> এই প্রতিষ্ঠানে কোনো শিক্ষক নেই।
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
