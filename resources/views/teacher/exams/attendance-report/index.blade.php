@extends('layouts.admin')

@section('title', 'Room-wise Attendance Stats')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h4>Room-wise Attendance Stats</h4>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('teacher.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Attendance Stats</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <div class="card mb-3">
            <div class="card-body">
                <form id="statsForm" class="form-inline" method="GET" action="{{ auth()->user()->isPrincipal($school->id) ? route('principal.institute.exams.attendance-report', $school) : route('teacher.institute.exams.attendance-report', $school) }}">
                    <div class="form-row align-items-end w-100">
                        <div class="form-group mr-2">
                            <label class="mr-2">Date</label>
                            <select id="statDate" name="date" class="form-control" required>
                                @foreach($dateOptions as $d)
                                    <option value="{{ $d }}" {{ $date == $d ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::parse($d)->format('d/m/Y') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mr-2">
                            <label class="mr-2">Seat Plan</label>
                            <select id="statPlan" name="plan_id" class="form-control" required>
                                @foreach($plans as $p)
                                    <option value="{{ $p->id }}" {{ $plan_id == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }} ({{ $p->shift }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ml-auto">
                            @php
                                $overallRoute = auth()->user()->isPrincipal($school->id)
                                    ? route('principal.institute.exams.attendance-report.overall', [$school, 'plan_id' => $plan_id])
                                    : route('teacher.institute.exams.attendance-report.overall', [$school, 'plan_id' => $plan_id]);
                            @endphp
                            <a class="btn btn-outline-secondary" href="{{ $overallRoute }}">Overall (All Dates)</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @php
            $classes = [];
            $byRoom = [];
            foreach ($rows as $r) {
                $room = (string)$r->room_no;
                $cls = (string)($r->class_name ?? '-');
                $p = (int)$r->present_cnt;
                $a = (int)$r->absent_cnt;
                $teacherName = $r->marker_name ?: $r->marker_username;
                
                if (!isset($classes[$cls])) $classes[$cls] = true;
                if (!isset($byRoom[$room])) {
                    $byRoom[$room] = ['teacher' => $teacherName, 'classes' => [], 'tp' => 0, 'ta' => 0];
                }
                $byRoom[$room]['classes'][$cls] = ['p' => $p, 'a' => $a];
                $byRoom[$room]['tp'] += $p;
                $byRoom[$room]['ta'] += $a;
            }
            $classList = array_keys($classes);
            sort($classList);
        @endphp

        @if(!empty($byRoom))
        <style>
            .table-compact th, .table-compact td { padding: .25rem .35rem; }
            .table-compact thead th { font-size: 14px; }
            .table-compact tbody td { font-size: 15px; }
            @media (max-width: 576px){
                .table-compact th, .table-compact td { padding: .2rem .3rem; }
                .table-compact thead th { font-size: 13px; }
                .table-compact tbody td { font-size: 14px; }
            }
        </style>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0 table-compact">
                        <thead class="thead-light">
                            <tr>
                                <th rowspan="2" class="align-middle text-center">Room</th>
                                <th rowspan="2" class="align-middle text-center">Invigilator</th>
                                @foreach($classList as $cn)
                                    <th colspan="2" class="text-center">{{ $cn }}</th>
                                @endforeach
                                <th rowspan="2" class="align-middle text-center">Total Present</th>
                                <th rowspan="2" class="align-middle text-center">Total Absent</th>
                            </tr>
                            <tr>
                                @foreach($classList as $cn)
                                    <th class="text-center">P</th>
                                    <th class="text-center">A</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($byRoom as $roomNo => $info)
                                <tr>
                                    <td class="text-center font-weight-bold">{{ $roomNo }}</td>
                                    <td>{{ $info['teacher'] }}</td>
                                    @foreach($classList as $cn)
                                        @php $cell = $info['classes'][$cn] ?? ['p' => 0, 'a' => 0]; @endphp
                                        <td class="text-center text-success font-weight-bold">{{ $cell['p'] }}</td>
                                        <td class="text-center text-danger font-weight-bold">{{ $cell['a'] }}</td>
                                    @endforeach
                                    <td class="text-center font-weight-bold text-success" style="background: #f0fff0;">{{ $info['tp'] }}</td>
                                    <td class="text-center font-weight-bold text-danger" style="background: #fff0f0;">{{ $info['ta'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @else
            @if($plan_id > 0 && empty($dateOptions))
                <div class="alert alert-info">এই সীট প্ল্যানের সাথে কোনো পরীক্ষা (Exams) ম্যাপ করা নেই।</div>
            @else
                <div class="alert alert-info py-5 text-center">
                    <i class="fas fa-info-circle fa-2x mb-3"></i><br>
                    তারিখ ও সীট প্ল্যান নির্বাচন করুন অথবা এই তারিখে কোনো হাজিরা পাওয়া যায়নি।
                </div>
            @endif
        @endif
    </div>
</div>
@endsection

@push('js')
<script>
    $(document).ready(function() {
        $('#statDate, #statPlan').on('change', function() {
            $('#statsForm').submit();
        });
    });
</script>
@endpush
