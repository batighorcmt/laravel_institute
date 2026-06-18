@extends('layouts.admin')

@section('title', 'কক্ষ পরিদর্শক')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">
    <style>
        .select2-container .select2-selection--single {
            height: calc(2.25rem + 2px) !important;
            border: 1px solid #ced4da !important;
            border-radius: .25rem !important;
            padding: .375rem .75rem;
            line-height: 1.5 !important;
            color: #495057;
            background-color: #fff;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            padding-left: 0 !important;
            color: #495057;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(2.25rem + 2px) !important;
        }
        .custom-dropdown-list {
            max-height: 250px !important;
            overflow-y: auto !important;
        }
        /* Ensure dropdown options are visible */
        .select2-results__option {
            color: #212529 !important;
        }
        .select2-results__option--highlighted {
            background-color: #e9ecef !important;
            color: #212529 !important;
        }
        /* Raise z-index to appear above other elements */
        .select2-container { z-index: 1050; }
        .form-inline .select2-container { width: 100% !important; min-width: 100% !important; }
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
                <form method="post" action="{{ route(request()->routeIs('teacher.*') ? 'teacher.institute.exams.invigilations.controller.set' : 'principal.institute.exams.invigilations.controller.set', $school) }}" class="form-group mb-2" style="width: 350px;">
                    @csrf
                    <div class="form-group mr-2 mb-2" style="width: 350px;">
                        <select name="user_id" class="form-control js-teacher-controller" required>
                            <option value="">-- শিক্ষক নির্বাচন করুন --</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" {{ ($currentController && $currentController->user_id === $t->id) ? 'selected' : '' }}>
                                    {{ $t->teacher_full_name ?? $t->name }} {{ $t->teacher_initials ? '(' . $t->teacher_initials . ')' : '' }}
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
                <form id="dutiesForm" method="GET" action="{{ route(request()->routeIs('teacher.*') ? 'teacher.institute.exams.invigilations.index' : 'principal.institute.exams.invigilations.index', $school) }}">
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
                                <select name="duty_date_disabled" class="form-control" disabled>
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
                        <form id="saveAssignmentsForm" method="POST" action="{{ route(request()->routeIs('teacher.*') ? 'teacher.institute.exams.invigilations.store' : 'principal.institute.exams.invigilations.store', $school) }}">
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
                                                                {{ $t->teacher_full_name ?? $t->name }} {{ $t->teacher_initials ? '(' . $t->teacher_initials . ')' : '' }}
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

        // Wait for jQuery AND Select2
        function waitForSelect2(cb, tries) {
            tries = tries || 50;
            if (typeof window.$ !== 'undefined' && $.fn && $.fn.select2) {
                return cb(window.$);
            }
            if (tries <= 0) {
                console.error('Select2 failed to load');
                return;
            }
            setTimeout(function(){ waitForSelect2(cb, tries - 1); }, 150);
        }

        waitForSelect2(function($) {
            // Ensure any previous Select2 instances are destroyed before initializing
            if ($('.js-teacher-controller').data('select2')) {
                $('.js-teacher-controller').select2('destroy');
            }
            $('.js-teacher-controller').select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: '-- শিক্ষক নির্বাচন করুন --',
                allowClear: true
            }).on('select2:open', function() {
                $('.select2-results__options').addClass('custom-dropdown-list');
            });

// Disabled Select2 for room teacher dropdowns – using native <select> elements
// The following block was removed to show a plain dropdown.
// $('.js-teacher-select').select2({
//     theme: 'bootstrap4',
//     width: '100%',
//     placeholder: '-- Select Teacher --',
//     allowClear: true
// }).on('select2:open', function() {
//     $('.select2-results__options').addClass('custom-dropdown-list');
// });

            // Room teacher uniqueness logic
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
                        this.disabled = (val !== keep && !!selectedVals[val]);
                    });
                });
            }

            $(document).on('change', '.js-teacher-select', function() {
                var v = $(this).val();
                if (!v) { updateDisabledOptions(); return; }
                $('.js-teacher-select').not(this).each(function() {
                    if ($(this).val() === v) {
                        $(this).val(null).trigger('change');
                    }
                });
                updateDisabledOptions();
            });

            updateDisabledOptions();
        });
})();
</script>
@endpush
