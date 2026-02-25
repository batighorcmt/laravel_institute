@extends('layouts.admin')

@section('title', 'Find Student Seat')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h4>Find Student Seat</h4>
            </div>
            <div class="col-sm-6 text-right">
                <nav aria-label="breadcrumb">
                    
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex align-items-center flex-wrap">
                <form class="form-inline w-100" method="GET" action="{{ route('teacher.institute.exams.find-seat', $school) }}">
                    <div class="form-row align-items-end w-100">
                        <div class="form-group col-md-4 col-12">
                            <label class="d-block">Seat Plan</label>
                            <select name="plan_id" class="form-control w-100" required>
                                <option value="">-- Select Plan --</option>
                                @foreach($plans as $p)
                                    <option value="{{ $p->id }}" {{ $plan_id == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }} ({{ $p->shift }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-5 col-12">
                            <label class="d-block">Find by Roll or Name</label>
                            <input type="text" name="find" value="{{ $find }}" class="form-control w-100" placeholder="e.g., 102 or Hasan" required>
                        </div>
                        <div class="form-group col-md-3 col-12">
                            <label class="d-none d-md-block">&nbsp;</label>
                            <button class="btn btn-primary btn-block" type="submit"><i class="fas fa-search mr-1"></i> Find</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                @if($plan_id > 0 && $find !== '')
                    @if($results->isEmpty())
                        <div class="p-3 text-muted text-center">No assigned seat found for "<strong>{{ $find }}</strong>" in this plan.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Roll</th>
                                        <th>Name</th>
                                        <th>Class</th>
                                        <th>Room</th>
                                        <th>Column</th>
                                        <th>Bench</th>
                                        <th>Side</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($results as $row)
                                        @php
                                            $student = $row->student;
                                            $enrollment = $student?->currentEnrollment;
                                            $className = $enrollment?->class?->name ?? ($student?->class?->name ?? 'Unknown');
                                            $studentName = $student?->student_name_bn ?: ($student?->student_name_en ?: 'Unknown');
                                            $rollNo = $enrollment?->roll_no ?: ($student?->roll_no ?: '-');
                                        @endphp
                                        <tr>
                                            <td>{{ $rollNo }}</td>
                                            <td>{{ $studentName }}</td>
                                            <td>{{ $className }}</td>
                                            <td>{{ $row->room?->room_no }}</td>
                                            <td>{{ $row->col_no }}</td>
                                            <td>{{ $row->bench_no }}</td>
                                            <td>{{ $row->position === 'R' ? 'Right' : 'Left' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @else
                    <div class="p-3 text-muted text-center">Select a plan and search to find seats.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
