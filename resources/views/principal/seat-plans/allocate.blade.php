@extends('layouts.admin')

@section('title', 'Seat Allocation - ' . $seatPlan->name)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Seat Allocation</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.seat-plans.index', $school) }}">Seat Plans</a></li>
                    <li class="breadcrumb-item active">Seat Allocation</li>
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
            <!-- Room Selection -->
            <div class="col-md-12 mb-3">
                <div class="card">
                    <div class="card-header bg-light">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-0 mt-1">Room Selection</h5>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="{{ route('principal.institute.seat-plans.edit', [$school, $seatPlan]) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Edit Plan
                                </a>
                                <a href="{{ route('principal.institute.seat-plans.show', [$school, $seatPlan]) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-0">
                            <select id="room_select" class="form-control" style="max-width: 500px;">
                                <option value="">-- Select Room --</option>
                                @foreach($seatPlan->rooms as $r)
                                    <option value="{{ $r->id }}" {{ request('room_id') == $r->id ? 'selected' : '' }}>
                                        Room {{ $r->room_no }} - {{ $r->title }} (Allocated: {{ $r->allocated_count ?? 0 }}/{{ $r->total_capacity }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seat Plan Visual -->
            <div class="col-md-12">
                @if($room)
                    <div class="card">
                        <div class="card-header bg-info">
                            <h3 class="card-title">
                                Room {{ $room->room_no }} - {{ $room->title }}
                                <span class="badge badge-light ml-2">{{ $room->allocated_count }}/{{ $room->total_capacity }}</span>
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="seat-plan-container" style="overflow-x: auto;">
                                <div class="d-flex justify-content-around">
                                    @for($col = 1; $col <= $room->columns_count; $col++)
                                        @php
                                            $benches = $col == 1 ? $room->col1_benches : ($col == 2 ? $room->col2_benches : $room->col3_benches);
                                        @endphp
                                        <div class="column-container" style="flex: 1; margin: 0 10px;">
                                            <h5 class="text-center mb-3">Column {{ $col }}</h5>
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
                                                         data-allocation-id="{{ $leftAllocation ? $leftAllocation->id : '' }}"
                                                         data-student-roll="{{ $leftAllocation && $leftAllocation->student ? $leftAllocation->student->roll : '' }}"
                                                         data-student-name="{{ $leftAllocation && $leftAllocation->student ? $leftAllocation->student->student_name_en : '' }}"
                                                         data-student-class="{{ $leftAllocation && $leftAllocation->student && $leftAllocation->student->class ? $leftAllocation->student->class->name : '' }}"
                                                         data-student-photo="{{ $leftAllocation && $leftAllocation->student && $leftAllocation->student->photo ? asset('storage/' . $leftAllocation->student->photo) : asset('images/default-avatar.png') }}"
                                                         onclick="openSeatModal(this)"
                                                         style="flex: 1; padding: 10px; margin-right: 5px; border: 2px solid {{ $leftAllocation ? '#28a745' : '#6c757d' }}; background: {{ $leftAllocation ? '#d4edda' : '#fff' }}; cursor: pointer; text-align: center;">
                                                        @if($leftAllocation)
                                                            <strong>{{ $leftAllocation->student->roll ?? $leftAllocation->student->student_id }}</strong><br>
                                                            <small>{{ Str::limit($leftAllocation->student->student_name_en, 15) }}</small><br>
                                                            <small class="text-muted">{{ $leftAllocation->student->class->name ?? '' }}</small>
                                                        @else
                                                            <i class="fas fa-chair text-muted"></i><br>
                                                            <small class="text-muted">Empty</small>
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
                                                         data-allocation-id="{{ $rightAllocation ? $rightAllocation->id : '' }}"
                                                         data-student-roll="{{ $rightAllocation && $rightAllocation->student ? $rightAllocation->student->roll : '' }}"
                                                         data-student-name="{{ $rightAllocation && $rightAllocation->student ? $rightAllocation->student->student_name_en : '' }}"
                                                         data-student-class="{{ $rightAllocation && $rightAllocation->student && $rightAllocation->student->class ? $rightAllocation->student->class->name : '' }}"
                                                         data-student-photo="{{ $rightAllocation && $rightAllocation->student && $rightAllocation->student->photo ? asset('storage/' . $rightAllocation->student->photo) : asset('images/default-avatar.png') }}"
                                                         onclick="openSeatModal(this)"
                                                         style="flex: 1; padding: 10px; margin-left: 5px; border: 2px solid {{ $rightAllocation ? '#28a745' : '#6c757d' }}; background: {{ $rightAllocation ? '#d4edda' : '#fff' }}; cursor: pointer; text-align: center;">
                                                        @if($rightAllocation)
                                                            <strong>{{ $rightAllocation->student->roll ?? $rightAllocation->student->student_id }}</strong><br>
                                                            <small>{{ Str::limit($rightAllocation->student->student_name_en, 15) }}</small><br>
                                                            <small class="text-muted">{{ $rightAllocation->student->class->name ?? '' }}</small>
                                                        @else
                                                            <i class="fas fa-chair text-muted"></i><br>
                                                            <small class="text-muted">Empty</small>
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
                                <i class="fas fa-exclamation-triangle"></i> Please select a room.
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

<!-- Seat Allocation Modal -->
<div class="modal fade" id="seatModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">Seat Allocation - <span id="modalSeatInfo"></span></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="modalContent">
                    <!-- Class buttons will be loaded here -->
                    <div id="classButtonsDiv" class="mb-3">
                        <label>Select Class:</label>
                        <div id="classButtons" class="d-flex flex-column">
                            @foreach($classes as $class)
                                <button type="button" class="btn btn-outline-primary btn-block mb-2" onclick="selectClass({{ $class->id }}, '{{ $class->name }}')">
                                    {{ $class->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Student selection (hidden initially) -->
                    <div id="studentSelectionDiv" style="display: none;">
                        <label>শিক্ষার্থী নির্বাচন করুন: <span id="selectedClassName" class="badge badge-info"></span></label>
                        <p class="text-muted small">নাম বা রোল নং লিখে খুঁজুন এবং ক্লিক করুন</p>
                        <select id="studentSelect" class="form-control" style="width: 100%;">
                            <option value="">-- শিক্ষার্থী খুঁজুন --</option>
                        </select>
                    </div>

                    <!-- Current allocation info -->
                    <div id="currentAllocation" style="display: none;">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="alert alert-info mb-0">
                                    <strong>Current Allocation:</strong><br>
                                    <strong>Class:</strong> <span id="currentStudentClass"></span><br>
                                    <strong>Roll:</strong> <span id="currentStudentRoll"></span><br>
                                    <strong>Name:</strong> <span id="currentStudentName"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <img id="currentStudentPhoto" src="" alt="Student Photo" class="img-fluid rounded" style="max-height: 150px; width: 100%; object-fit: cover;">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-danger btn-block" onclick="removeCurrentAllocation()">
                                <i class="fas fa-trash"></i> Remove Allocation
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
console.log('Script loaded');
console.log('jQuery available?', typeof window.$);
console.log('Select2 available?', typeof window.$.fn?.select2);

(function() {
    let currentSeat = null;
    let currentAllocationId = null;
    let selectedClassId = null;
    let jQueryReady = false;

    // Helper function to wait for jQuery
    function waitForjQuery(callback) {
        if (jQueryReady || (typeof window.$ !== 'undefined' && typeof window.$.fn !== 'undefined' && typeof window.$.fn.select2 !== 'undefined')) {
            jQueryReady = true;
            callback();
        } else {
            setTimeout(function() { waitForjQuery(callback); }, 50);
        }
    }

    // Initialize room selection on load
    waitForjQuery(function() {
        console.log('jQuery loaded successfully');
        $('#room_select').on('change', function() {
            const roomId = $(this).val();
            if (roomId) {
                window.location.href = "{{ route('principal.institute.seat-plans.allocate', [$school, $seatPlan]) }}?room_id=" + roomId;
            }
        });
    });

    // Make functions globally accessible
    window.openSeatModal = function(seatElement) {
        console.log('openSeatModal called', seatElement);
        waitForjQuery(function() {
            currentSeat = seatElement;
            currentAllocationId = $(seatElement).data('allocation-id');
            
            const col = $(seatElement).data('col');
            const bench = $(seatElement).data('bench');
            const position = $(seatElement).data('position');
            
            $('#modalSeatInfo').text(`Column ${col}, Bench ${bench}, ${position === 'Left' ? 'Left' : 'Right'}`);
            
            // Reset modal state
            window.resetClassSelection();
            $('#currentAllocation').hide();
            
            // If seat is occupied, show remove option
            if (currentAllocationId) {
                const studentRoll = $(seatElement).data('student-roll');
                const studentName = $(seatElement).data('student-name');
                const studentClass = $(seatElement).data('student-class');
                const studentPhoto = $(seatElement).data('student-photo');
                
                $('#currentStudentClass').text(studentClass);
                $('#currentStudentRoll').text(studentRoll);
                $('#currentStudentName').text(studentName);
                $('#currentStudentPhoto').attr('src', studentPhoto);
                $('#currentAllocation').show();
                $('#classButtonsDiv').hide();
            } else {
                $('#classButtonsDiv').show();
            }
            
            $('#seatModal').modal('show');
        });
    };

    window.selectClass = function(classId, className) {
        console.log('selectClass called', classId, className);
        waitForjQuery(function() {
            selectedClassId = classId;
            $('#selectedClassName').text(className);
            $('#classButtonsDiv').hide();
            $('#studentSelectionDiv').show();
            loadStudentsForClass(classId);
        });
    };

    window.resetClassSelection = function() {
        waitForjQuery(function() {
            selectedClassId = null;
            $('#classButtonsDiv').show();
            $('#studentSelectionDiv').hide();
            $('#studentSelect').empty().append('<option value="">-- শিক্ষার্থী খুঁজুন --</option>');
        });
    };

    window.removeCurrentAllocation = function() {
        waitForjQuery(function() {
            if (!currentAllocationId) return;
            
            if (!confirm('Do you want to remove this seat allocation?')) return;
            
            $.ajax({
                url: "{{ route('principal.institute.seat-plans.allocations.remove', [$school, $seatPlan, '__ALLOCATION__']) }}".replace('__ALLOCATION__', currentAllocationId),
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        $('#seatModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Failed to remove seat allocation');
                    }
                },
                error: function() {
                    alert('Failed to remove seat allocation');
                }
            });
        });
    };

    function loadStudentsForClass(classId) {
        // Destroy existing select2 if any
        if ($('#studentSelect').data('select2')) {
            $('#studentSelect').select2('destroy');
        }
        
        // Clear and reset select
        $('#studentSelect').empty().append('<option value="">-- শিক্ষার্থী খুঁজুন --</option>');
        
        // Initialize select2 with AJAX search
        $('#studentSelect').select2({
            dropdownParent: $('#seatModal'),
            placeholder: 'নাম বা রোল নং লিখে খুঁজুন',
            allowClear: true,
            width: '100%',
            ajax: {
                url: "{{ route('principal.institute.seat-plans.search-students', [$school, $seatPlan]) }}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term || '',
                        class_id: classId,
                        seat_plan_id: {{ $seatPlan->id }}
                    };
                },
                processResults: function (students) {
                    if (!students || students.length === 0) {
                        return {
                            results: [{
                                id: '',
                                text: 'কোনো শিক্ষার্থী পাওয়া যায়নি',
                                disabled: true
                            }]
                        };
                    }
                    return {
                        results: students.map(function(student) {
                            return {
                                id: student.id,
                                text: (student.roll || student.student_id) + ' - ' + student.student_name_en,
                                student: student
                            };
                        })
                    };
                },
                cache: true
            },
            minimumInputLength: 0,
            language: {
                searching: function() {
                    return 'খুঁজছি...';
                },
                noResults: function() {
                    return 'কোনো শিক্ষার্থী পাওয়া যায়নি';
                }
            }
        });
        
        // Trigger initial load
        $('#studentSelect').select2('open');
        $('#studentSelect').select2('close');
        
        // Auto-allocate when student is selected
        $('#studentSelect').off('select2:select').on('select2:select', function (e) {
            const studentId = e.params.data.id;
            if (studentId) {
                allocateSeatWithStudent(studentId);
            }
        });
        
        // Open dropdown automatically
        setTimeout(function() {
            $('#studentSelect').select2('open');
        }, 200);
    }

    function allocateSeatWithStudent(studentId) {
        if (!studentId) {
            return;
        }
        
        const col = $(currentSeat).data('col');
        const bench = $(currentSeat).data('bench');
        const position = $(currentSeat).data('position');
        
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
                    $('#seatModal').modal('hide');
                    location.reload();
                } else {
                    alert(response.message || 'Failed to allocate seat');
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Failed to allocate seat');
            }
        });
    }
})();
</script>
@endpush
@endsection
