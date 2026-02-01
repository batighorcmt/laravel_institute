@extends('layouts.admin')

@section('title','Edit Section')

@section('content')
@push('styles')
<style>
/* Show ~5 items in Select2 dropdown, scroll for additional results */
.select2-container .select2-results__options { max-height: 180px; overflow-y: auto; }
.select2-container--bootstrap4 .select2-results__option { white-space: nowrap; }
</style>
@endpush
<div class="row mb-2">
    <div class="col">
        <h1 class="m-0">Edit Section</h1>
    </div>
    <div class="col text-right">
        <a href="{{ route('principal.institute.sections.index',$school) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> List
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="post" action="{{ route('principal.institute.sections.update',[$school,$section]) }}">
            @csrf
            @method('put')

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Class *</label>
                    <select name="class_id" class="form-control" required>
                        @foreach($classList as $cls)
                            <option value="{{ $cls->id }}"
                                {{ old('class_id',$section->class_id)==$cls->id?'selected':'' }}>
                                {{ $cls->name }} ({{ $cls->numeric_value }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group col-md-4">
                    <label>Section Name *</label>
                    <input type="text" name="name" class="form-control"
                           required value="{{ old('name',$section->name) }}">
                </div>

                <div class="form-group col-md-4">
                    <label>Class Teacher (optional)</label>

                    {{-- IMPORTANT: options are rendered server-side --}}
                    <select name="class_teacher_id" class="form-control" id="class_teacher_id">
                        <option value="">-- Select --</option>
                        @foreach($activeTeachers as $t)
                            <option value="{{ $t->id }}"
                                {{ old('class_teacher_id',$section->class_teacher_id)==$t->id?'selected':'' }}>
                                {{ $t->user->name ?? ('Teacher #'.$t->id) }}
                                ({{ $t->user->username ?? $t->user->email ?? '' }})
                            </option>
                        @endforeach
                    </select>

                    <small class="text-muted d-block mt-1">
                        A teacher can be class teacher for only one section. You may leave this blank.
                    </small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="active" {{ $section->status=='active'?'selected':'' }}>Active</option>
                        <option value="inactive" {{ $section->status=='inactive'?'selected':'' }}>Inactive</option>
                    </select>
                </div>
            </div>

            <button class="btn btn-primary">
                <i class="fas fa-save mr-1"></i> Update
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {

    function loadSelect2Assets(callback) {
        if (window.jQuery && jQuery.fn && jQuery.fn.select2) {
            callback();
            return;
        }

        // CSS
        var css1 = document.createElement('link');
        css1.rel = 'stylesheet';
        css1.href = 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css';
        document.head.appendChild(css1);

        var css2 = document.createElement('link');
        css2.rel = 'stylesheet';
        css2.href = 'https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.3.1/dist/select2-bootstrap4.min.css';
        document.head.appendChild(css2);

        // JS
        var js = document.createElement('script');
        js.src = 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js';
        js.onload = callback;
        document.head.appendChild(js);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var select = document.getElementById('class_teacher_id');
        if (!select) return;

        loadSelect2Assets(function () {

            // 🚫 NEVER destroy
            // ✅ init once, after options are guaranteed to exist
            $(select).select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: '-- নির্বাচন করুন --',
                allowClear: true
            });

            // 🔒 force selected value (important for edit page)
            var current = select.value;
            if (current) {
                $(select).val(String(current)).trigger('change');
            }
        });
    });

})();
</script>
@endpush
