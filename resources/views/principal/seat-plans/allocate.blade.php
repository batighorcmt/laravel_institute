@extends('layouts.admin')

@section('title', 'সিট বরাদ্দ - ' . $seatPlan->name)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">সিট বরাদ্দ</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.seat-plans.index', $school) }}">সিট প্ল্যান</a></li>
                    <li class="breadcrumb-item active">সিট বরাদ্দ</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="row">
            <!-- Student Search & Selection -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h3 class="card-title">শিক্ষার্থী খুঁজুন</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>রুম নির্বাচন করুন</label>
                            <select id="room_select" class="form-control">
                                <option value="">-- রুম নির্বাচন করুন --</option>
                                @foreach($seatPlan->rooms as $r)
                                    <option value="{{ $r->id }}" {{ request('room_id') == $r->id ? 'selected' : '' }}>
                                        রুম {{ $r->room_no }} - {{ $r->title }} ({{ $r->allocated_count }}/{{ $r->total_capacity }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>শ্রেণি নির্বাচন করুন</label>
                            <select id="class_select" class="form-control">
                                <option value="">-- শ্রেণি নির্বাচন করুন --</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>শিক্ষার্থী খুঁজুন</label>
                            <input type="text" id="student_search" class="form-control" placeholder="নাম বা রোল লিখুন...">
                        </div>

                        <div id="student_list" style="max-height: 400px; overflow-y: auto;">
                            <div class="alert alert-info">
                                শ্রেণি নির্বাচন করুন এবং শিক্ষার্থী খুঁজুন
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seat Plan Visual -->
            <div class="col-md-8">
                @if($room)
                    <div class="card">
                        <div class="card-header bg-info">
                            <h3 class="card-title">
                                রুম {{ $room->room_no }} - {{ $room->title }}
                                <span class="badge badge-light ml-2">{{ $room->allocated_count }}/{{ $room->total_capacity }}</span>
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-sm btn-light" onclick="autoAllocate()">
                                    <i class="fas fa-magic"></i> স্বয়ংক্রিয় বরাদ্দ
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="seat-plan-container" style="overflow-x: auto;">
                                <div class="d-flex justify-content-around">
                                    @for($col = 1; $col <= $room->columns_count; $col++)
                                        @php
                                            $benches = $col == 1 ? $room->col1_benches : ($col == 2 ? $room->col2_benches : $room->col3_benches);
                                        @endphp
                                        <div class="column-container" style="flex: 1; margin: 0 10px;">
                                            <h5 class="text-center mb-3">কলাম {{ $col }}</h5>
                                            @for($bench = 1; $bench <= $benches; $bench++)
                                                @php
                                                    $leftAllocation = $allocations->where('col_no', $col)->where('bench_no', $bench)->where('position', 'Left')->first();
                                                    $rightAllocation = $allocations->where('col_no', $col)->where('bench_no', $bench)->where('position', 'Right')->first();
                                                @endphp
                                                <div class="bench-row mb-2" style="display: flex; border: 2px solid #ddd; padding: 5px; background: #f8f9fa;">
                                                    <!-- Left Seat -->
                                                    <div class="seat left-seat {{ $leftAllocation ? 'occupied' : 'empty' }}" 
                                                         data-col="{{ $col }}" 
                                                         data-bench="{{ $bench }}" 
                                                         data-position="Left"
                                                         style="flex: 1; padding: 10px; margin-right: 5px; border: 2px solid {{ $leftAllocation ? '#28a745' : '#6c757d' }}; background: {{ $leftAllocation ? '#d4edda' : '#fff' }}; cursor: pointer; text-align: center;">
                                                        @if($leftAllocation)
                                                            <strong>{{ $leftAllocation->student->student_id }}</strong><br>
                                                            <small>{{ Str::limit($leftAllocation->student->student_name_en, 20) }}</small>
                                                            <button type="button" class="btn btn-xs btn-danger mt-1" onclick="removeSeat({{ $leftAllocation->id }})">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        @else
                                                            <i class="fas fa-chair text-muted"></i><br>
                                                            <small class="text-muted">খালি</small>
                                                        @endif
                                                    </div>

                                                    <!-- Bench Number -->
                                                    <div style="width: 40px; text-align: center; display: flex; align-items: center; justify-content: center;">
                                                        <strong>{{ $bench }}</strong>
                                                    </div>

                                                    <!-- Right Seat -->
                                                    <div class="seat right-seat {{ $rightAllocation ? 'occupied' : 'empty' }}" 
                                                         data-col="{{ $col }}" 
                                                         data-bench="{{ $bench }}" 
                                                         data-position="Right"
                                                         style="flex: 1; padding: 10px; margin-left: 5px; border: 2px solid {{ $rightAllocation ? '#28a745' : '#6c757d' }}; background: {{ $rightAllocation ? '#d4edda' : '#fff' }}; cursor: pointer; text-align: center;">
                                                        @if($rightAllocation)
                                                            <strong>{{ $rightAllocation->student->student_id }}</strong><br>
                                                            <small>{{ Str::limit($rightAllocation->student->student_name_en, 20) }}</small>
                                                            <button type="button" class="btn btn-xs btn-danger mt-1" onclick="removeSeat({{ $rightAllocation->id }})">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        @else
                                                            <i class="fas fa-chair text-muted"></i><br>
                                                            <small class="text-muted">খালি</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endfor
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="card">
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> অনুগ্রহ করে একটি রুম নির্বাচন করুন।
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
let selectedStudent = null;

$(document).ready(function() {
    // Room selection change
    $('#room_select').on('change', function() {
        const roomId = $(this).val();
        if (roomId) {
            window.location.href = "{{ route('principal.institute.seat-plans.allocate', [$school, $seatPlan]) }}?room_id=" + roomId;
        }
    });

    // Class selection change - load students
    $('#class_select').on('change', function() {
        loadStudents();
    });

    // Student search
    $('#student_search').on('keyup', function() {
        loadStudents();
    });

    // Seat click event
    $('.seat.empty').on('click', function() {
        if (!selectedStudent) {
            alert('প্রথমে একজন শিক্ষার্থী নির্বাচন করুন');
            return;
        }

        const col = $(this).data('col');
        const bench = $(this).data('bench');
        const position = $(this).data('position');

        allocateSeat(selectedStudent, col, bench, position);
    });
});

function loadStudents() {
    const classId = $('#class_select').val();
    const search = $('#student_search').val();

    if (!classId) {
        $('#student_list').html('<div class="alert alert-info">শ্রেণি নির্বাচন করুন</div>');
        return;
    }

    $.ajax({
        url: "{{ route('principal.institute.seat-plans.search-students', [$school, $seatPlan]) }}",
        method: 'GET',
        data: {
            class_id: classId,
            search: search,
            room_id: $('#room_select').val()
        },
        success: function(response) {
            let html = '';
            if (response.students.length > 0) {
                response.students.forEach(function(student) {
                    const isAllocated = student.seat_allocation ? true : false;
                    html += `
                        <div class="student-item ${isAllocated ? 'allocated' : ''}" 
                             data-student-id="${student.id}"
                             style="padding: 8px; border-bottom: 1px solid #ddd; cursor: ${isAllocated ? 'not-allowed' : 'pointer'}; background: ${isAllocated ? '#f0f0f0' : '#fff'};">
                            <strong>${student.student_id}</strong> - ${student.student_name_en}
                            ${isAllocated ? '<span class="badge badge-success float-right">বরাদ্দকৃত</span>' : ''}
                        </div>
                    `;
                });
            } else {
                html = '<div class="alert alert-warning">কোনো শিক্ষার্থী পাওয়া যায়নি</div>';
            }
            $('#student_list').html(html);

            // Add click event to student items
            $('.student-item:not(.allocated)').on('click', function() {
                $('.student-item').removeClass('selected');
                $(this).addClass('selected').css('background', '#e3f2fd');
                selectedStudent = $(this).data('student-id');
            });
        }
    });
}

function allocateSeat(studentId, col, bench, position) {
    $.ajax({
        url: "{{ route('principal.institute.seat-plans.allocate.store', [$school, $seatPlan]) }}",
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            student_id: studentId,
            room_id: $('#room_select').val(),
            col_no: col,
            bench_no: bench,
            position: position
        },
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.message || 'সিট বরাদ্দ করতে সমস্যা হয়েছে');
            }
        },
        error: function(xhr) {
            alert(xhr.responseJSON?.message || 'সিট বরাদ্দ করতে সমস্যা হয়েছে');
        }
    });
}

function removeSeat(allocationId) {
    if (!confirm('আপনি কি নিশ্চিত?')) return;

    $.ajax({
        url: "{{ route('principal.institute.seat-plans.allocate.remove', [$school, $seatPlan]) }}/" + allocationId,
        method: 'DELETE',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                location.reload();
            }
        },
        error: function(xhr) {
            alert('সিট মুছে ফেলতে সমস্যা হয়েছে');
        }
    });
}

function autoAllocate() {
    const classId = $('#class_select').val();
    const roomId = $('#room_select').val();

    if (!classId) {
        alert('প্রথমে শ্রেণি নির্বাচন করুন');
        return;
    }

    if (!roomId) {
        alert('প্রথমে রুম নির্বাচন করুন');
        return;
    }

    if (!confirm('স্বয়ংক্রিয়ভাবে শিক্ষার্থীদের সিট বরাদ্দ করা হবে। আপনি কি নিশ্চিত?')) {
        return;
    }

    $.ajax({
        url: "{{ route('principal.institute.seat-plans.auto-allocate', [$school, $seatPlan]) }}",
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            class_id: classId,
            room_id: roomId
        },
        success: function(response) {
            if (response.success) {
                alert(response.message);
                location.reload();
            } else {
                alert(response.message || 'স্বয়ংক্রিয় বরাদ্দ করতে সমস্যা হয়েছে');
            }
        },
        error: function(xhr) {
            alert(xhr.responseJSON?.message || 'স্বয়ংক্রিয় বরাদ্দ করতে সমস্যা হয়েছে');
        }
    });
}
</script>
@endpush
@endsection
