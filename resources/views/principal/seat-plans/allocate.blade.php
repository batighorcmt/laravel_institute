@extends('layouts.admin')

@section('title', 'Assign Seats - ' . $seatPlan->name)

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.6.2/dist/select2-bootstrap4.min.css">
<style>
    /* Select2 styling */
    .select2-container{ width:100% !important; }
    .select2-container--bootstrap4 .select2-selection--single{ height: calc(2.25rem + 2px); padding:.375rem .75rem; }
    .select2-results__option { font-size: 14px; }
    .select2-search__field{ outline: none; }
    .select2-dropdown { z-index: 2050; }
    #assignModal .select2-results__options { max-height: 360px; overflow-y: auto; }

    /* Grid layout - 3 columns side by side */
    #grid { 
        display: flex; 
        gap: 16px; 
        flex-wrap: nowrap;
        overflow-x: auto;
    }
    .seat-column { 
        flex: 1; 
        min-width: 280px; 
    }
    
    /* Bench row - L and R side by side with bench number in middle */
    .bench-row { 
        display: flex; 
        align-items: stretch; 
        margin-bottom: 8px; 
        border: 1px dashed #bbb; 
        padding: 6px; 
        border-radius: 4px;
        background: #fff;
    }
    
    /* Seat cells */
    .seat-cell { 
        cursor: pointer; 
        background: #f8f9fa; 
        transition: all 0.2s;
        border: 1px solid #ddd;
        padding: 8px;
        border-radius: 3px;
        min-height: 80px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
    }
    .seat-cell:hover{ 
        background: #fff3cd !important; 
        box-shadow: 0 2px 8px rgba(0,0,0,0.15); 
    }
    .seat-cell.bg-warning{ 
        background: #ffe8a1 !important; 
    }
    
    /* Assigned seat styling - exactly like PHP version */
    .seat-roll{ 
        font-size: 28px; 
        font-weight: 900; 
        color: #b00020; 
        line-height: 1; 
        margin: 4px 0;
    }
    .seat-name{ 
        font-size: 13px; 
        font-weight: 700; 
        line-height: 1.1; 
        margin: 4px 0;
        color: #000;
    }
    .seat-class{ 
        font-size: 16px; 
        color: #666;
        margin: 2px 0;
    }
    
    /* Bench number in middle */
    .bench-number {
        width: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 16px;
        color: #000;
        margin: 0 4px;
    }
    
    /* Position label (L/R) */
    .position-label {
        font-size: 11px;
        color: #999;
        font-weight: normal;
        margin-bottom: 4px;
    }
    
    /* Empty seat */
    .seat-cell em { 
        color: #999; 
        font-style: italic;
        font-size: 13px;
    }
    
    /* Clear button */
    .btn-xs {
        padding: 2px 8px;
        font-size: 11px;
        margin-top: 4px;
    }

    /* Toast notifications - top right */
    .app-toast-container{ 
        position: fixed; 
        top: 80px; 
        right: 16px; 
        z-index: 9999; 
        display: flex; 
        flex-direction: column; 
        gap: 10px; 
    }
    .app-toast{ 
        min-width: 260px; 
        max-width: 380px; 
        background: #343a40; 
        color: #fff; 
        padding: 12px 16px; 
        border-radius: 6px; 
        box-shadow: 0 6px 20px rgba(0,0,0,.3); 
        opacity: 0; 
        transform: translateX(20px); 
        transition: opacity .3s ease, transform .3s ease; 
        font-weight: 600; 
    }
    .app-toast.show{ 
        opacity: 1; 
        transform: translateX(0); 
    }
    .app-toast--success{ background: #28a745; }
    .app-toast--danger{ background: #dc3545; }
    .app-toast--warning{ background: #ffc107; color: #212529; }
    .app-toast--info{ background: #17a2b8; }

    /* Responsive */
    @media (max-width: 991.98px){
        #grid { 
            flex-wrap: wrap; 
        }
        .seat-column{ 
            min-width: 100%; 
        }
    }
</style>
@endpush

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-12">
                <h1 class="m-0">Assign Seats</h1>
                <p class="text-muted mb-0">
                    Plan: <strong>{{ $seatPlan->name }}</strong> — 
                    Room: <strong>{{ $room ? $room->room_no : 'Select Room' }}</strong>
                    @if($room) (Shift: {{ $seatPlan->shift ?? 'N/A' }}) @endif
                </p>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Action Buttons -->
        <div class="mb-3 d-flex justify-content-between flex-wrap">
            <div>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('principal.institute.seat-plans.show', [$school, $seatPlan]) }}">
                    <i class="fas fa-arrow-left"></i> Back to Rooms
                </a>
                @if($room)
                <a class="btn btn-sm btn-outline-primary" target="_blank" href="{{ route('principal.institute.seat-plans.rooms.print', [$school, $seatPlan, $room]) }}">
                    <i class="fas fa-print"></i> Print This Room
                </a>
                @endif
            </div>
            <div>
                <a class="btn btn-sm btn-outline-dark" href="{{ route('principal.institute.seat-plans.index', $school) }}">
                    <i class="fas fa-th-large"></i> All Seat Plans
                </a>
            </div>
        </div>

        <!-- Room Selection -->
        @if($room)
        <!-- Room Layout -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <strong>Room Layout</strong>
                    </div>
                    <div class="card-body">
                        <div id="grid">
                            @for($col = 1; $col <= $room->columns_count; $col++)
                                @php
                                    $benches = $col == 1 ? $room->col1_benches : ($col == 2 ? $room->col2_benches : $room->col3_benches);
                                @endphp
                                <div class="seat-column">
                                    <div class="text-center font-weight-bold mb-2">
                                        Column {{ $col }} ({{ $benches }} benches)
                                    </div>
                                    
                                    @for($bench = 1; $bench <= $benches; $bench++)
                                        @php
                                            $leftAllocation = $allocations->where('col_no', $col)->where('bench_no', $bench)->where('position', 'Left')->first();
                                            $rightAllocation = $allocations->where('col_no', $col)->where('bench_no', $bench)->where('position', 'Right')->first();
                                        @endphp
                                        <div class="bench-row">
                                            <!-- Left Seat -->
                                            <div class="seat-cell flex-fill" data-c="{{ $col }}" data-b="{{ $bench }}" data-p="L">
                                                <div class="position-label">L</div>
                                                @if($leftAllocation && $leftAllocation->student)
                                                    <div class="seat-roll"><strong>{{ $leftAllocation->student->roll ?? $leftAllocation->student->student_id }}</strong></div>
                                                    <div class="seat-name">{{ $leftAllocation->student->student_name_en }}</div>
                                                    <div class="seat-class">Class: {{ $leftAllocation->student->class->name ?? 'N/A' }}</div>
                                                    <button type="button" class="btn btn-xs btn-outline-danger js-clear-seat" data-c="{{ $col }}" data-b="{{ $bench }}" data-p="L">Clear</button>
                                                @else
                                                    <em>Empty</em>
                                                @endif
                                            </div>
                                            
                                            <!-- Bench Number -->
                                            <div class="bench-number">{{ $bench }}</div>
                                            
                                            <!-- Right Seat -->
                                            <div class="seat-cell flex-fill" data-c="{{ $col }}" data-b="{{ $bench }}" data-p="R">
                                                <div class="position-label">R</div>
                                                @if($rightAllocation && $rightAllocation->student)
                                                    <div class="seat-roll"><strong>{{ $rightAllocation->student->roll ?? $rightAllocation->student->student_id }}</strong></div>
                                                    <div class="seat-name">{{ $rightAllocation->student->student_name_en }}</div>
                                                    <div class="seat-class">Class: {{ $rightAllocation->student->class->name ?? 'N/A' }}</div>
                                                    <button type="button" class="btn btn-xs btn-outline-danger js-clear-seat" data-c="{{ $col }}" data-b="{{ $bench }}" data-p="R">Clear</button>
                                                @else
                                                    <em>Empty</em>
                                                @endif
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                            @endfor
                        </div>
                        <div class="mt-3 text-muted">
                            <i class="fas fa-info-circle"></i> <strong>Tip:</strong> Click a seat, choose class, then click a student name to assign instantly.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="card">
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> Please select a room to view seat layout.
                </div>
            </div>
        </div>
        @endif
    </div>
</section>

<!-- Assign Modal -->
<div class="modal fade" id="assignModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Student to Seat</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="assignForm">
                    <input type="hidden" id="m_col_no">
                    <input type="hidden" id="m_bench_no">
                    <input type="hidden" id="m_position">

                    <div class="form-group">
                        <label>Class</label>
                        <select id="modal_class_id" class="form-control" required>
                            <option value="">-- Select Class --</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Student</label>
                        <select id="student_select" class="form-control" style="width:100%" data-placeholder="-- Select Student --" required disabled>
                        </select>
                        <small class="form-text text-muted">Type roll number or name to search</small>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="app-toast-container" class="app-toast-container"></div>
@endsection

@push('scripts')
<script>
(function() {
    'use strict';
    
    // Wait for jQuery and document ready
    function withJQ(cb, tries){
        tries = tries || 20;
        if (typeof window.$ !== 'undefined') return cb(window.$);
        if (tries <= 0) { console.error('jQuery not loaded'); return; }
        setTimeout(function(){ withJQ(cb, tries-1); }, 100);
    }

    withJQ(function($){
        let selectedSeat = null;

        // Room selection removed (navigated from room list)

    // Mark selected seat
    function markSelection(el) {
        $('.seat-cell').removeClass('bg-warning');
        $(el).addClass('bg-warning');
    }

    // Seat click handler
    $(document).on('click', '.seat-cell', function(e) {
        // Don't open modal if Clear button was clicked
        if ($(e.target).closest('.js-clear-seat').length) return;
        
        selectedSeat = {
            c: $(this).data('c'),
            b: $(this).data('b'),
            p: $(this).data('p')
        };
        
        markSelection(this);
        
        // Fill modal inputs
        $('#m_col_no').val(selectedSeat.c);
        $('#m_bench_no').val(selectedSeat.b);
        $('#m_position').val(selectedSeat.p);
        
        // Reset selects
        $('#modal_class_id').val('').trigger('change');
        $('#student_select').val(null).trigger('change').prop('disabled', true);
        
        $('#assignModal').modal('show');
    });

    // Initialize Select2 (wait until plugin is available)
    var $student = $('#student_select');
    function initSelect2(tries){
        tries = tries || 30;
        if ($.fn && $.fn.select2) {
            $student.select2({
        theme: 'bootstrap4',
        width: '100%',
        dropdownParent: $('#assignModal'),
        placeholder: 'Select student',
        allowClear: true,
        minimumInputLength: 0,
        ajax: {
            delay: 250,
            url: "{{ route('principal.institute.seat-plans.search-students', [$school, $seatPlan]) }}",
            dataType: 'json',
            data: function(params) {
                return {
                    plan_id: {{ $seatPlan->id }},
                    class_id: $('#modal_class_id').val() || '',
                    search: params.term || ''
                };
            },
            processResults: function(data) {
                var results = (Array.isArray(data) ? data : []).map(function(s) {
                    var roll = s.roll || s.student_id || '';
                    var name = s.student_name_en || '';
                    return {
                        id: s.id,
                        text: (roll ? roll + ' - ' : '') + name,
                        data: s
                    };
                });
                return { results: results };
            }
        }
        });
        // Force initial query when opened
        $student.on('select2:open', function(){
            var el = document.querySelector('.select2-container--bootstrap4 .select2-search__field');
            if (el) {
                el.value = ' ';
                el.dispatchEvent(new Event('input', { bubbles: true }));
                setTimeout(function(){ el.value = ''; el.dispatchEvent(new Event('input', { bubbles: true })); }, 30);
            }
        });
        } else if (tries > 0) {
            setTimeout(function(){ initSelect2(tries-1); }, 100);
        } else {
            console.error('Select2 failed to load');
        }
    }
    initSelect2();

    // Class change handler
    $('#modal_class_id').on('change', function() {
        $student.val(null).trigger('change');
        
        if (this.value) {
            $student.prop('disabled', false);
            setTimeout(function() {
                if ($.fn && $.fn.select2 && $student.select2) {
                    try { $student.select2('open'); } catch(e){}
                    var search = document.querySelector('.select2-container--bootstrap4 .select2-search__field');
                    if (search) {
                        // Force Select2 to issue an initial AJAX query
                        search.focus();
                        search.value = ' ';
                        search.dispatchEvent(new Event('input', { bubbles: true }));
                        setTimeout(function(){
                            search.value = '';
                            search.dispatchEvent(new Event('input', { bubbles: true }));
                        }, 30);
                    }
                }
            }, 150);
        } else {
            $student.prop('disabled', true);
        }
    });

    // Auto-assign on student select
    $student.on('select2:select', function(e) {
        var studentId = e.params.data.id;
        var studentData = e.params.data.data;
        var c = $('#m_col_no').val();
        var b = $('#m_bench_no').val();
        var p = $('#m_position').val();
        
        if (!studentId || !c || !b || !p) return;
        
        // AJAX assign
        $.ajax({
            url: "{{ route('principal.institute.seat-plans.allocate.store', [$school, $seatPlan]) }}",
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                student_id: studentId,
                room_id: {{ $room ? $room->id : 0 }},
                col_no: c,
                bench_no: b,
                position: p
            },
            success: function(res) {
                if (!res.success) {
                    showToast('danger', res.message || 'Assignment failed');
                    return;
                }
                
                // Update seat cell
                var $cell = $('.seat-cell[data-c="'+c+'"][data-b="'+b+'"][data-p="'+p+'"]');
                if ($cell.length) {
                    renderSeatAssigned($cell[0], res.data);
                }
                
                $('#assignModal').modal('hide');
                showToast('success', 'Seat assigned');
            },
            error: function(xhr) {
                showToast('danger', xhr.responseJSON?.message || 'Assignment failed');
            }
        });
    });

    // Modal shown handler
    $('#assignModal').on('shown.bs.modal', function(){
        if ($('#modal_class_id').val()) {
            if ($.fn && $.fn.select2 && $student.select2) {
                try { $student.select2('open'); } catch(e){}
                let search = document.querySelector('.select2-container--bootstrap4 .select2-search__field');
                if (search) search.focus();
            }
        } else {
            $('#modal_class_id').focus();
        }
    });

    // Clear seat handler
    $(document).on('click', '.js-clear-seat', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var c = $(this).data('c');
        var b = $(this).data('b');
        var p = $(this).data('p');
        var $btn = $(this);
        
        var prevHtml = $btn.html();
        $btn.prop('disabled', true).html('Clearing…');
        
        $.ajax({
            url: "{{ route('principal.institute.seat-plans.allocate.store', [$school, $seatPlan]) }}",
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                _method: 'DELETE',
                room_id: {{ $room ? $room->id : 0 }},
                col_no: c,
                bench_no: b,
                position: p
            },
            success: function(res) {
                if (!res.success) {
                    showToast('danger', res.message || 'Clear failed');
                    $btn.prop('disabled', false).html(prevHtml);
                    return;
                }
                
                var $cell = $('.seat-cell[data-c="'+c+'"][data-b="'+b+'"][data-p="'+p+'"]');
                if ($cell.length) {
                    renderSeatEmpty($cell[0]);
                }
                
                showToast('success', 'Seat cleared');
            },
            error: function(xhr) {
                showToast('danger', xhr.responseJSON?.message || 'Clear failed');
                $btn.prop('disabled', false).html(prevHtml);
            }
        });
    });

    // Helper: Render assigned seat
    function renderSeatAssigned(cell, data) {
        var roll = data.roll || data.student_id || '';
        var name = data.student_name_en || '';
        var className = data.class_name || 'N/A';
        var c = $(cell).data('c');
        var b = $(cell).data('b');
        var p = $(cell).data('p');
        
        var html = '<div class="position-label">' + p + '</div>' +
                   '<div class="seat-roll"><strong>' + escapeHtml(roll) + '</strong></div>' +
                   '<div class="seat-name">' + escapeHtml(name) + '</div>' +
                   '<div class="seat-class">Class: ' + escapeHtml(className) + '</div>' +
                   '<button type="button" class="btn btn-xs btn-outline-danger js-clear-seat" data-c="'+c+'" data-b="'+b+'" data-p="'+p+'">Clear</button>';
        
        $(cell).html(html);
    }

    // Helper: Render empty seat
    function renderSeatEmpty(cell) {
        var p = $(cell).data('p');
        var html = '<div class="position-label">' + p + '</div>' +
                   '<em>Empty</em>';
        $(cell).html(html);
    }

    // Helper: Escape HTML
    function escapeHtml(s) {
        return String(s || '').replace(/[&<>"']/g, function(c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c];
        });
    }

    // Toast notification
    window.showToast = function(type, message, duration) {
        duration = duration || 2500;
        var $container = $('#app-toast-container');
        if (!$container.length) {
            $container = $('<div id="app-toast-container" class="app-toast-container"></div>');
            $('body').append($container);
        }
        
        var $toast = $('<div class="app-toast app-toast--' + type + '" role="alert">' + message + '</div>');
        $container.append($toast);
        
        setTimeout(function() {
            $toast.addClass('show');
        }, 10);
        
        setTimeout(function() {
            $toast.removeClass('show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, duration);
    };

    @if(session('success'))
        showToast('success', '{{ session('success') }}');
    @endif

    @if(session('error'))
        showToast('danger', '{{ session('error') }}');
    @endif
    
    }); // End document.ready
})(); // End IIFE
</script>
@endpush
