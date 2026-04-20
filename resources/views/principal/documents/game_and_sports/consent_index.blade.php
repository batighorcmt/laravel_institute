@extends('layouts.admin')
@section('title', 'অনুমতিপত্র - গেম এন্ড স্পোর্টস')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark font-weight-bold"><i class="fas fa-running mr-2 text-primary"></i> অনুমতিপত্র
                    </h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card card-primary card-outline shadow-lg border-0">
                        <div class="card-header bg-white py-3">
                            <h3 class="card-title font-weight-bold text-primary">
                                <i class="fas fa-file-alt mr-1"></i> সম্মতিপত্র তৈরির জন্য শিক্ষার্থী নির্বাচন করুন
                            </h3>
                        </div>
                        <div class="card-body p-4">
                            <form id="consentForm"
                                action="{{ route('principal.institute.game-and-sports.consent.print', $school) }}"
                                target="_blank">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-4">
                                            <label class="font-weight-bold text-muted small uppercase mb-1">শিক্ষাবর্ষ <span
                                                    class="text-danger">*</span></label>
                                            <select name="academic_year_id" id="academic_year_id"
                                                class="form-control select2bs4" required>
                                                <option value="">-- নির্বাচন করুন --</option>
                                                @foreach($academicYears as $year)
                                                    <option value="{{ $year->id }}" {{ $year->is_current ? 'selected' : '' }}>
                                                        {{ $year->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-4">
                                            <label class="font-weight-bold text-muted small uppercase mb-1">শ্রেণি <span
                                                    class="text-danger">*</span></label>
                                            <select name="class_id" id="class_id" class="form-control select2bs4" required>
                                                <option value="">-- নির্বাচন করুন --</option>
                                                @foreach($classes as $class)
                                                    <option value="{{ $class->id }}">{{ $class->bangla_name ?: $class->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-4">
                                            <label class="font-weight-bold text-muted small uppercase mb-1">শাখা
                                                (ঐচ্ছিক)</label>
                                            <select name="section_id" id="section_id" class="form-control select2bs4">
                                                <option value="">-- সকল শাখা --</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-4">
                                            <label class="font-weight-bold text-muted small uppercase mb-1">খেলার ইভেন্ট
                                                <span class="text-danger">*</span></label>
                                            <select name="game_name" id="game_name" class="form-control select2bs4"
                                                required>
                                                <option value="">-- নির্বাচন করুন --</option>
                                                @foreach($sports as $sport)
                                                    <option value="{{ $sport }}">{{ $sport }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-4">
                                    <label class="font-weight-bold text-muted small uppercase mb-1">শিক্ষার্থী (রোল - নাম)
                                        <span class="text-danger">*</span></label>
                                    <select name="student_id" id="student_id" class="form-control select2bs4" required
                                        disabled>
                                        <option value="">-- প্রথমে শ্রেণি নির্বাচন করুন --</option>
                                    </select>
                                    <div id="studentLoader" class="mt-1 small text-info" style="display:none;">
                                        <i class="fas fa-spinner fa-spin mr-1"></i> শিক্ষার্থী তালিকা লোড হচ্ছে...
                                    </div>
                                </div>

                                <div class="text-center mt-5">
                                    <button type="submit"
                                        class="btn btn-primary btn-lg px-5 shadow-sm rounded-pill font-weight-bold">
                                        <i class="fas fa-print mr-2"></i> প্রিন্ট ভিউ দেখুন
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Information Box --}}
                    <div class="alert alert-info border-0 shadow-sm mt-4">
                        <h5><i class="icon fas fa-info-circle"></i> বিঃদ্রঃ</h5>
                        <p class="mb-0">
                            সঠিক শিক্ষার্থী এবং খেলার নাম নির্বাচন করে প্রিন্ট বাটনে ক্লিক করলে পূর্ণাঙ্গ অনুমতিপত্র
                            প্রদর্শিত হবে। এটি প্রিন্ট করে অভিভাবকের স্বাক্ষর গ্রহণ করতে পারবেন।
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css">
    <style>
        .select2-container--bootstrap4 .select2-selection--single {
            height: calc(2.25rem + 10px) !important;
            padding-top: 8px !important;
            border-radius: 8px !important;
        }

        .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
            top: 12px !important;
        }

        .card {
            border-radius: 15px !important;
        }

        .card-header {
            border-radius: 15px 15px 0 0 !important;
        }

        .uppercase {
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            // Vite bundles jQuery and loads as deferred module. Wait for it if needed.
            const startScript = function () {
                if (typeof $ === 'undefined') {
                    setTimeout(startScript, 50);
                    return;
                }

                $(document).ready(function () {
                    // Initialize Select2
                    $('.select2bs4').select2({
                        theme: 'bootstrap4',
                        width: '100%'
                    });

                    const schoolId = "{{ $school->id }}";
                    const $classSelect = $('#class_id');
                    const $sectionSelect = $('#section_id');
                    const $studentSelect = $('#student_id');
                    const $gameSelect = $('#game_name');
                    const $yearSelect = $('#academic_year_id');
                    const $loader = $('#studentLoader');

                    function loadStudents() {
                        const classId = $classSelect.val();
                        const sectionId = $sectionSelect.val();
                        const yearId = $yearSelect.val();

                        if (!classId || !yearId) {
                            $studentSelect.prop('disabled', true).trigger('change');
                            return;
                        }

                        $loader.show();
                        $studentSelect.prop('disabled', true).trigger('change');

                        const studentUrl = "{{ route('principal.institute.meta.students', [$school]) }}";
                        console.log('Fetching students from:', studentUrl, { classId, sectionId, yearId });

                        $.get(studentUrl, {
                            class_id: classId,
                            section_id: sectionId,
                            year_id: yearId
                        }, function (data) {
                            let options = '<option value="">-- শিক্ষার্থী নির্বাচন করুন --</option>';
                            data.forEach(function (student) {
                                options += `<option value="${student.record_id}">${student.roll_no} - ${student.name}</option>`;
                            });
                            $studentSelect.html(options).prop('disabled', false).trigger('change');
                            $loader.hide();
                        }).fail(function (xhr) {
                            $loader.hide();
                            console.error('Students fetch failed:', xhr);
                            toastr.error('শিক্ষার্থী তালিকা লোড করতে ব্যর্থ হয়েছে।');
                        });
                    }

                    // Load Sections when Class changes
                    $classSelect.on('change', function () {
                        const classId = $(this).val();

                        // Reset dependent selects
                        $sectionSelect.html('<option value="">-- লোড হচ্ছে... --</option>').trigger('change');
                        $studentSelect.html('<option value="">-- প্রথমে শ্রেণি নির্বাচন করুন --</option>').prop('disabled', true).trigger('change');

                        if (!classId) {
                            $sectionSelect.html('<option value="">-- সকল শাখা --</option>').trigger('change');
                            return;
                        }

                        // Fetch Sections
                        const sectionUrl = "{{ route('principal.institute.meta.sections', [$school]) }}";
                        $.get(sectionUrl, { class_id: classId }, function (data) {
                            let options = '<option value="">-- সকল শাখা --</option>';
                            data.forEach(function (section) {
                                options += `<option value="${section.id}">${section.name}</option>`;
                            });
                            $sectionSelect.html(options).trigger('change');
                            // If no sections, trigger manual student load because sectionId will be empty
                            if (data.length === 0) {
                                loadStudents();
                            }
                        }).fail(function (xhr) {
                            console.error('Sections fetch failed:', xhr);
                            $sectionSelect.html('<option value="">-- সকল শাখা --</option>').trigger('change');
                            toastr.error('শাখা তালিকা লোড করতে ব্যর্থ হয়েছে।');
                        });
                    });

                    // Load Students when Section or Year changes
                    $sectionSelect.on('change', loadStudents);
                    $yearSelect.on('change', loadStudents);

                    // Initial load if Class is already selected
                    if ($classSelect.val()) {
                        $classSelect.trigger('change');
                    }

                    // Ensure form validation
                    $('#consentForm').on('submit', function (e) {
                        if (!$studentSelect.val()) {
                            toastr.warning('দয়া করে শিক্ষার্থী নির্বাচন করুন।');
                            e.preventDefault();
                            return false;
                        }
                        if (!$gameSelect.val()) {
                            toastr.warning('দয়া করে খেলার নাম নির্বাচন করুন।');
                            e.preventDefault();
                            return false;
                        }
                    });
                });
            };

            startScript();
        });
    </script>
@endpush