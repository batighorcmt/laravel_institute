@extends('layouts.admin')

@section('title', 'কক্ষ পরিদর্শক')

@push('styles')
    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.6.2/dist/select2-bootstrap4.min.css">
    <style>
        .select2-container--bootstrap4 .select2-selection--single {
            height: calc(2.25rem + 2px) !important;
            border: 1px solid #ced4da;
        }
        .select2-container--bootstrap4.select2-container--focus .select2-selection--single {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .select2-selection__arrow {
            top: 5px !important;
        }
        /* Limit dropdown to 5 items (approx 5 * 36px) */
        .select2-results__options.custom-dropdown-list {
            max-height: 180px !important;
            overflow-y: auto !important;
        }
    </style>
@endpush

@section('content')
<div class="content">
    <div class="container-fluid pt-4">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('principal'))
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0"><strong>Set Exam Controller</strong></h5>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('principal.institute.exams.invigilations.controller.set', $school) }}" class="form-inline">
                    @csrf
                    <div class="form-group mr-2 mb-2">
                        <select name="user_id" class="form-control js-teacher-controller" style="width: 250px;" required>
                            <option value="">-- Select Teacher --</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" {{ ($currentController && $currentController->user_id === $t->id) ? 'selected' : '' }}>
                                    {{ $t->name }} {{ $t->teacher && $t->teacher->initials ? '(' . $t->teacher->initials . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary mb-2">Save</button>
                    <div class="ml-3 mb-2 text-muted">
                        Current: <strong>{{ $currentController && $currentController->user ? $currentController->user->name : 'None' }}</strong>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><strong>Assign Room Duties</strong></h5>
            </div>
            <div class="card-body">
                <form id="dutiesForm" method="GET" action="{{ route('principal.institute.exams.invigilations.index', $school) }}">
                    <div class="row">
                        <div class="form-group col-md-4">
                            <label>Seat Plan</label>
                            <select id="filterPlan" name="plan_id" class="form-control" onchange="this.form.submit()" required>
                                <option value="">-- Select Plan --</option>
                                @foreach($plans as $p)
                                    <option value="{{ $p->id }}" {{ $sel_plan_id == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }} ({{ $p->shift }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="form-group col-md-3">
                            <label>Date</label>
                            @if($examDates->isNotEmpty())
                                <select id="filterDate" name="duty_date" class="form-control" onchange="this.form.submit()" required>
                                    <option value="" {{ !$sel_date ? 'selected' : '' }}>-- Select Date --</option>
                                    @foreach($examDates as $d)
                                        <option value="{{ $d }}" {{ $sel_date === $d ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::parse($d)->format('d/m/Y') }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <select id="filterDate" class="form-control" disabled>
                                    <option value="">No dates found</option>
                                </select>
                                <small class="form-text text-muted">Select an active seat plan mapped to an exam date.</small>
                            @endif
                        </div>
                    </div>
                </form>

                <hr>

                @if($sel_plan_id && $sel_date)
                    @if($rooms->isNotEmpty())
                        <form id="saveAssignmentsForm" method="POST" action="{{ route('principal.institute.exams.invigilations.store', $school) }}">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $sel_plan_id }}">
                            <input type="hidden" name="duty_date" value="{{ $sel_date }}">

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Room</th>
                                            <th>Assign Teacher</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($rooms as $r)
                                            <tr>
                                                <td class="align-middle">
                                                    {{ $r->room_no }} {!! $r->title ? ' &mdash; ' . $r->title : '' !!}
                                                </td>
                                                <td>
                                                    <select name="room_teacher[{{ $r->id }}]" class="form-control js-teacher-select">
                                                        <option value="">-- Select --</option>
                                                        @foreach($teachers as $t)
                                                            <option value="{{ $t->id }}" 
                                                                {{ isset($dutyMap[$r->id]) && $dutyMap[$r->id] == $t->id ? 'selected' : '' }}>
                                                                {{ $t->name }} {{ $t->teacher && $t->teacher->initials ? '(' . $t->teacher->initials . ')' : '' }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" class="btn btn-success mt-2">
                                <i class="fas fa-save mr-1"></i> Save Duties
                            </button>
                        </form>
                    @else
                        <div class="text-muted">No rooms found for the selected plan.</div>
                    @endif
                @else
                    <div class="text-muted">Select Seat Plan and Date to load rooms.</div>
                @endif

            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    'use strict';

    // Wait for jQuery
    function withJQ(cb, tries) {
        tries = tries || 20;
        if (typeof window.$ !== 'undefined') return cb(window.$);
        if (tries <= 0) { console.error('jQuery not loaded'); return; }
        setTimeout(function(){ withJQ(cb, tries-1); }, 100);
    }

    withJQ(function($) {
        // Load Select2 dynamically after jQuery is there
        if (!$.fn.select2) {
            var s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js';
            s.onload = initApp;
            document.head.appendChild(s);
        } else {
            initApp();
        }

        function initApp() {
            // Initialize standard select2
            $('.js-teacher-controller').select2({
                theme: 'bootstrap4'
            }).on('select2:open', function() {
                $('.select2-results__options').addClass('custom-dropdown-list');
            });

            // Initialize room assigns select2 with uniqueness checks
            $('.js-teacher-select').select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: '-- Select Teacher --',
                allowClear: true
            }).on('select2:open', function() {
                $('.select2-results__options').addClass('custom-dropdown-list');
            });

            function updateDisabledOptions() {
                var selectedVals = {};
                $('.js-teacher-select').each(function() {
                    var v = $(this).val();
                    if (v) selectedVals[v] = true;
                });

                $('.js-teacher-select').each(function() {
                    var $sel = $(this);
                    var keep = $sel.val();
                    
                    $sel.find('option').each(function() {
                        var val = this.value;
                        if (!val) return;
                        
                        if (val === keep) {
                            this.disabled = false;
                        } else {
                            this.disabled = !!selectedVals[val];
                        }
                    });
                });

                // Trigger chosen logic to refresh disabled display
                $('.js-teacher-select').select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    placeholder: '-- Select Teacher --',
                    allowClear: true
                }).on('select2:open', function() {
                    $('.select2-results__options').addClass('custom-dropdown-list');
                });
            }

            $('.js-teacher-select').on('change', function() {
                var v = $(this).val();
                if (!v) {
                    updateDisabledOptions();
                    return;
                }

                // Clear same teacher from other selects (if they forcibly matched it)
                $('.js-teacher-select').not(this).each(function() {
                    if ($(this).val() === v) {
                        $(this).val(null).trigger('change');
                    }
                });

                updateDisabledOptions();
            });

            // Initialize disabled state based on page load (e.g. edit mode)
            updateDisabledOptions();
        }
    });
})();
</script>
@endpush
