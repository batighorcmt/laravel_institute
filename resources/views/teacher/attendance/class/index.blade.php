@extends('layouts.admin')

@section('title', 'ছাত্র-ছাত্রী হাজিরা')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h1 class="m-0">ছাত্র-ছাত্রী হাজিরা নিন</h1>
    <a href="{{ route('teacher.dashboard') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-1"></i> ড্যাশবোর্ড
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<div class="card">
    <div class="card-header bg-primary text-white">
        <h3 class="card-title mb-0">
            <i class="fas fa-calendar-check"></i> ক্লাস ও শাখা নির্বাচন করুন
        </h3>
    </div>
    <div class="card-body">
        @if($classes->count() > 0)
            <form method="GET" action="{{ route('teacher.institute.attendance.class.take', $school) }}" id="attendanceForm">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="class_id">ক্লাস <span class="text-danger">*</span></label>
                            <select name="class_id" id="class_id" class="form-control" required>
                                <option value="">-- ক্লাস নির্বাচন করুন --</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" {{ old('class_id', request('class_id')) == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="section_id">শাখা <span class="text-danger">*</span></label>
                            <select name="section_id" id="section_id" class="form-control" required>
                                <option value="">-- প্রথমে ক্লাস নির্বাচন করুন --</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="date">তারিখ</label>
                            <input type="date" name="date" id="date" class="form-control" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-arrow-right"></i> পরবর্তী
                    </button>
                </div>
            </form>
        @else
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle"></i> আপনার কোনো ক্লাস/শাখা বরাদ্দ করা হয়নি। দয়া করে প্রশাসকের সাথে যোগাযোগ করুন।
            </div>
        @endif
    </div>
</div>

<style>
.card-header.bg-primary {
    background: linear-gradient(45deg, #4e73df, #224abe) !important;
}
</style>

<script>
// Sections data from server
const sectionsByClass = @json($sectionsByClass);

document.getElementById('class_id').addEventListener('change', function() {
    const classId = this.value;
    const sectionSelect = document.getElementById('section_id');
    
    // Clear existing options
    sectionSelect.innerHTML = '<option value="">-- শাখা নির্বাচন করুন --</option>';
    
    if (classId && sectionsByClass[classId]) {
        sectionsByClass[classId].forEach(section => {
            const option = document.createElement('option');
            option.value = section.id;
            option.textContent = section.name;
            sectionSelect.appendChild(option);
        });
    }
});

// Trigger change event if class already selected
if (document.getElementById('class_id').value) {
    document.getElementById('class_id').dispatchEvent(new Event('change'));
}
</script>
@endsection
