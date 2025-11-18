@extends('layouts.admin')
@section('title','Class Attendance')
@section('content')
<div class="d-flex justify-content-between mb-3">
  <h1 class="m-0">Class Attendance</h1>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">উপস্থিতি রেকর্ড/দেখুন</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('principal.institute.attendance.class.take', $school) }}" class="mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="class_id" class="required-field">ক্লাস নির্বাচন করুন</label>
                                <select class="form-control" id="class_id" name="class_id" required>
                                    <option value="">নির্বাচন করুন</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="section_id" class="required-field">শাখা নির্বাচন করুন</label>
                                <select class="form-control" id="section_id" name="section_id" required>
                                    <option value="">শাখা নির্বাচন করুন</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="date_display" class="required-field">তারিখ</label>
                                <input type="text" class="form-control" id="date_display" value="{{ date('d/m/Y') }}" readonly>
                                <input type="hidden" id="date" name="date" value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-eye"></i> উপস্থিতি নিন/দেখুন
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');
    const sectionsUrl = '{{ route("principal.institute.meta.sections", $school) }}';

    function resetSections() {
        sectionSelect.innerHTML = '<option value="">শাখা নির্বাচন করুন</option>';
    }

    async function loadSections(classId) {
        resetSections();
        sectionSelect.disabled = true;
        sectionSelect.innerHTML = '<option value="">লোড হচ্ছে...</option>';
        try {
            const resp = await fetch(sectionsUrl + '?class_id=' + encodeURIComponent(classId));
            if (!resp.ok) throw new Error('Network response was not ok');
            const data = await resp.json();
            resetSections();
            if (Array.isArray(data) && data.length) {
                data.forEach(sec => {
                    const opt = document.createElement('option');
                    opt.value = sec.id;
                    opt.textContent = sec.name;
                    sectionSelect.appendChild(opt);
                });
            } else {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'কোন শাখা নেই';
                sectionSelect.appendChild(opt);
            }
        } catch (e) {
            resetSections();
            const opt = document.createElement('option');
            opt.value='';
            opt.textContent='লোড হতে ব্যর্থ';
            sectionSelect.appendChild(opt);
            console.error('Section load failed:', e);
        } finally {
            sectionSelect.disabled = false;
        }
    }

    classSelect.addEventListener('change', function() {
        const val = this.value;
        if (val) {
            loadSections(val);
        } else {
            resetSections();
        }
    });
});
</script>
@endsection