@extends('layouts.admin')

@section('title', 'Overall Attendance Summary')

@section('content')
<div class="content-header d-print-none">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h4>Overall Attendance (All Dates)</h4>
            </div>
            <div class="col-sm-6 text-right">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('teacher.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('teacher.institute.exams.attendance-report', $school) }}">Report</a></li>
                    <li class="breadcrumb-item active">Overall</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <style>
            .table-bright thead th { background: #f8f9fa; color: #000; }
            .text-present { color: #28a745 !important; font-weight: 700; }
            .text-absent { color: #dc3545 !important; font-weight: 700; }
            .text-total { color: #007bff !important; font-weight: 700; }

            .table-fit { table-layout: fixed; width: 100%; font-size: 12px; border-collapse: collapse; }
            .table-fit th, .table-fit td { padding: .2rem .35rem; box-sizing: border-box; overflow: hidden; text-overflow: ellipsis; vertical-align: middle !important; }
            .no-wrap { white-space: nowrap; }
            .page-break-print { page-break-before: always; }

            /* Column widths */
            col.col-idx { width: 35px; }
            col.col-date { width: 90px; }
            col.col-class { width: 100px; }
            col.col-room { width: 30px; }
            col.col-total { width: 60px; }

            /* Print styles */
            @media print {
                body { -webkit-print-color-adjust: exact; print-color-adjust: exact; font-size: 10px; background: #fff !important; }
                .content-wrapper, .content, .container-fluid, .card, .card-body { background: #fff !important; border: none !important; margin: 0 !important; padding: 0 !important; }
                .breadcrumb, .d-print-none, .main-footer, .sidebar, .navbar, .card-header { display: none !important; }
                .table-responsive { overflow: visible !important; }
                .table-fit { font-size: 9px; }
                col.col-room { width: 25px; }
                th, td { border: 1px solid #000 !important; }
            }

            .ph-grid { display: grid; grid-template-columns: auto 1fr auto; align-items: center; }
            .ph-left img { height: 60px; object-fit: contain; }
            .ph-center { text-align: center; }
            .ph-name { font-size: 24px; font-weight: 800; }
            .ph-title { font-size: 18px; font-weight: 700; background: #eee; padding: 2px 10px; border-radius: 4px; display: inline-block; margin-top: 5px; }
            .ph-sub { font-size: 12px; margin-top: 5px; }
        </style>

        <!-- Print Header -->
        <div class="d-none d-print-block mb-3">
            <div class="ph-grid">
                <div class="ph-left">
                    @if($school->logo)
                        <img src="{{ asset('storage/' . $school->logo) }}" alt="Logo">
                    @endif
                </div>
                <div class="ph-center">
                    <div class="ph-name">{{ $school->name }}</div>
                    <div class="ph-meta">{{ $school->address }}</div>
                    <div class="ph-title">Overall Attendance Summary (All Dates)</div>
                    @php $currPlan = $plans->where('id', $plan_id)->first(); @endphp
                    <div class="ph-sub">Seat Plan: {{ $currPlan?->name }} ({{ $currPlan?->shift }}) | Printed: {{ date('d/m/Y') }}</div>
                </div>
                <div class="ph-right"></div>
            </div>
            <hr>
        </div>

        <div class="card mb-3 d-print-none">
            <div class="card-body">
                <form id="overallForm" class="form-inline" method="GET" action="{{ auth()->user()->isPrincipal($school->id) ? route('principal.institute.exams.attendance-report.overall', $school) : route('teacher.institute.exams.attendance-report.overall', $school) }}">
                    <div class="form-row align-items-end w-100">
                        <div class="form-group mr-2">
                            <label class="mr-2">Seat Plan</label>
                            <select id="ovPlan" name="plan_id" class="form-control" required>
                                @foreach($plans as $p)
                                    <option value="{{ $p->id }}" {{ $plan_id == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }} ({{ $p->shift }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ml-auto">
                            @php
                                $dailyRoute = auth()->user()->isPrincipal($school->id)
                                    ? route('principal.institute.exams.attendance-report', [$school, 'plan_id' => $plan_id])
                                    : route('teacher.institute.exams.attendance-report', [$school, 'plan_id' => $plan_id]);
                            @endphp
                            <a class="btn btn-outline-secondary mr-2" href="{{ $dailyRoute }}">Daily (By Date)</a>
                            <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fas fa-print mr-1"></i> Print</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card overflow-auto">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0 table-bright text-center table-fit">
                        <colgroup>
                            <col class="col-idx" />
                            <col class="col-date" />
                            <col class="col-class" />
                            @foreach($rooms as $r)
                                <col class="col-room" />
                                <col class="col-room" />
                                <col class="col-room" />
                            @endforeach
                            <col class="col-total" />
                            <col class="col-total" />
                        </colgroup>
                        <thead class="thead-light">
                            <tr>
                                <th rowspan="2" class="align-middle no-wrap">#</th>
                                <th rowspan="2" class="align-middle no-wrap">Date</th>
                                <th rowspan="2" class="align-middle no-wrap">Class</th>
                                @foreach($rooms as $rm)
                                    <th colspan="3" class="align-middle text-center">{{ $rm->room_no }}</th>
                                @endforeach
                                <th rowspan="2" class="align-middle text-center no-wrap">T. Present</th>
                                <th rowspan="2" class="align-middle text-center no-wrap">T. Absent</th>
                            </tr>
                            <tr>
                                @foreach($rooms as $rm)
                                    <th class="text-center align-middle">P</th>
                                    <th class="text-center align-middle">A</th>
                                    <th class="text-center align-middle" style="background: #f0f7ff;">T</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php $dateSn = 0; @endphp
                            @foreach($dates as $d)
                                @php
                                    $dateSn++;
                                    $clsMap = $matrix[$d] ?? [];
                                    $classes = array_keys($clsMap);
                                    sort($classes);
                                    if (empty($classes)) $classes = ['-'];
                                    $rowspan = count($classes);
                                    $first = true;
                                @endphp
                                @foreach($classes as $cn)
                                    @php
                                        $cells = $clsMap[$cn] ?? [];
                                        $tpAll = 0; $taAll = 0; $anyAll = false;
                                    @endphp
                                    <tr>
                                        @if($first)
                                            <td rowspan="{{ $rowspan }}" class="align-middle">{{ $dateSn }}</td>
                                            <td rowspan="{{ $rowspan }}" class="align-middle no-wrap">{{ \Carbon\Carbon::parse($d)->format('d/m/y') }}</td>
                                            @php $first = false; @endphp
                                        @endif
                                        <td class="no-wrap text-left">{{ $cn }}</td>
                                        @foreach($rooms as $rm)
                                            @php
                                                $rid = (int)$rm->id;
                                                $has = isset($cells[$rid]);
                                                $p = $has ? (int)$cells[$rid]['p'] : '';
                                                $a = $has ? (int)$cells[$rid]['a'] : '';
                                                $t = $has ? ($p + $a) : '';
                                                if ($has) {
                                                    $tpAll += $p; $taAll += $a; $anyAll = true;
                                                }
                                            @endphp
                                            <td class="text-present">{{ $p !== '' ? $p : '' }}</td>
                                            <td class="text-absent">{{ $a !== '' ? $a : '' }}</td>
                                            <td class="text-total" style="background: #f0f7ff;">{{ $t !== '' ? $t : '' }}</td>
                                        @endforeach
                                        <td class="text-present" style="background: #f0fff0;">{{ $anyAll ? $tpAll : '' }}</td>
                                        <td class="text-absent" style="background: #fff0f0;">{{ $anyAll ? $taAll : '' }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                            @if($dateSn === 0)
                                <tr>
                                    <td colspan="{{ 3 + (count($rooms) * 3) + 2 }}" class="text-center text-muted py-4">No data to display.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-none d-print-block mt-3 text-right text-muted" style="font-size: 10px;">
            Software by Batighor Computers
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    $(document).ready(function() {
        $('#ovPlan').on('change', function() {
            $('#overallForm').submit();
        });
    });
</script>
@endpush
