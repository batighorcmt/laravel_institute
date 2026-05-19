@extends('layouts.admin')

@section('title', 'আন্তঃস্কুল প্রতিযোগিতা')

@section('content_header')
<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0">আন্তঃস্কুল প্রতিযোগিতা</h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item active">আন্তঃস্কুল প্রতিযোগিতা</li>
        </ol>
    </div>
</div>
@endsection

@section('content')
<!-- Vue Component will mount here -->
<div id="app">
    <interschool-competition :school-id="{{ $school->id }}"></interschool-competition>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css">
<link href="https://fonts.maateen.me/nikosh/font.css" rel="stylesheet">
<style>
    body, .content-wrapper, .content, .container-fluid, h1, h2, h3, h4, h5, h6, p, span, a, div, label, input, select, button, table {
        font-family: 'Nikosh', sans-serif !important;
    }
    .select2-container--bootstrap4 .select2-selection--single {
        height: calc(2.25rem + 2px) !important;
        padding-top: 5px !important;
    }
    .select2-container--bootstrap4 .select2-selection--multiple {
        min-height: calc(2.25rem + 2px) !important;
    }
    /* Student select box in modal */
    #studentSelectBox {
        height: 200px;
        overflow-y: auto;
        font-size: 14px;
    }
</style>
@endpush

@push('scripts')
<script>
window.select2InitTimer = null;
window.initStudentSelect2 = function(options, selectedId) {
    if (window.select2InitTimer) {
        clearTimeout(window.select2InitTimer);
        window.select2InitTimer = null;
    }
    const tryInit = function() {
        const $el = $('#interschool-student-select');
        if ($el.length === 0) {
            window.select2InitTimer = setTimeout(tryInit, 100);
            return;
        }
        if (typeof $.fn.select2 === 'undefined') {
            window.select2InitTimer = setTimeout(tryInit, 100);
            return;
        }

        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }

        $el.empty().append('<option value="">-- শিক্ষার্থী নির্বাচন করুন --</option>');
        options.forEach(function(s) {
            const sel = (s.id == selectedId) ? 'selected' : '';
            $el.append(`<option value="${s.id}" ${sel}>${s.roll_no} - ${s.name}</option>`);
        });

        $el.select2({
            theme: 'bootstrap4',
            placeholder: '-- শিক্ষার্থী নির্বাচন করুন --',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#addPlayerModal')
        });

        $el.off('change.interschool').on('change.interschool', function() {
            const val = $(this).val();
            if (window.interschoolVue) {
                window.interschoolVue.newPlayer.student_id = val ? parseInt(val) : null;
            }
        });
    };
    tryInit();
};
</script>
@endpush
