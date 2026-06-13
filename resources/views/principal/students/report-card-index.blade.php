@extends('layouts.admin')

@section('title', 'Report Cards')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Report Card</h3>
    </div>
    <div class="card-body">
        @php
            $schoolId = is_object($school) ? $school->id : $school;
        @endphp
        <div class="row mb-3">
            <div class="col-md-3 mb-2">
                <label class="small">শিক্ষাবর্ষ</label>
                <select id="year_select" class="form-control form-control-sm select2"></select>
            </div>
            <div class="col-md-3 mb-2">
                <label class="small">শ্রেণি</label>
                <select id="class_select" class="form-control form-control-sm select2"></select>
            </div>
            <div class="col-md-3 mb-2">
                <label class="small">শাখা</label>
                <select id="section_select" class="form-control form-control-sm select2"><option value="">সকল শাখা</option></select>
            </div>
            <div class="col-md-3 mb-2">
                <label class="small">শিক্ষার্থী</label>
                <select id="student_select" class="form-control form-control-sm select2">
                    <option value="">সকল শিক্ষার্থী</option>
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <label class="small">তারিখ হতে (ঐচ্ছিক)</label>
                <input type="date" id="start_date" class="form-control form-control-sm">
            </div>
            <div class="col-md-3 mb-2">
                <label class="small">তারিখ পর্যন্ত (ঐচ্ছিক)</label>
                <input type="date" id="end_date" class="form-control form-control-sm">
            </div>
        </div>

        <div class="mb-3">
            <button id="list_btn" class="btn btn-sm btn-primary">তালিকা দেখুন</button>
            <button id="print_summary_btn" class="btn btn-sm btn-info ml-2"><i class="fas fa-table"></i> রিপোর্ট কার্ড সামারি প্রিন্ট</button>
            <button id="print_all_btn" class="btn btn-sm btn-success ml-2"><i class="fas fa-print"></i> সকল রিপোর্ট কার্ড প্রিন্ট</button>
            <a id="reset_btn" href="{{ route('principal.institute.students.report-cards.index', $schoolId) }}" class="btn btn-sm btn-outline-secondary ml-2">রিসেট</a>
        </div>

        <div id="students_list">
            <!-- populated via AJAX -->
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function(){
                const schoolId = '{{ $schoolId }}';
                const classesUrl = '{{ route("principal.institute.meta.classes", [$schoolId]) }}';
                const yearsUrl = '{{ route("principal.institute.meta.academic-years", [$schoolId]) }}';
                const sectionsUrl = '{{ route("principal.institute.meta.sections", [$schoolId]) }}';
                const studentsUrl = '{{ route("principal.institute.meta.students", [$schoolId]) }}';
                const showTemplate = @json(route('principal.institute.students.report-cards.show', [$schoolId, '__STUDENT__']));
                const printAllUrl = @json(route('principal.institute.students.report-cards.print-all', [$schoolId]));
                const printSummaryUrl = @json(route('principal.institute.students.report-cards.print-summary', [$schoolId]));

                const $class = jQuery('#class_select');
                const $section = jQuery('#section_select');
                const $year = jQuery('#year_select');
                const $student = jQuery('#student_select');

                // Load academic years
                fetch(yearsUrl).then(r=>r.json()).then(data=>{
                    $year.empty();
                    data.forEach(d=>{ $year.append(new Option(d.text,d.id)); });
                    $year.val($year.find('option:first').val());
                    $year.trigger('change');
                }).catch(()=>{});

                // Load classes
                fetch(classesUrl).then(r=>r.json()).then(data=>{
                    $class.empty();
                    $class.append(new Option('সকল শ্রেণি',''));
                    data.forEach(d=>{ $class.append(new Option(d.text,d.id)); });
                    $class.trigger('change');
                }).catch(()=>{});

                // When class changes, load sections
                $class.on('change', function(){
                    const classId = this.value;
                    $section.prop('disabled',true).empty().append(new Option('লোড হচ্ছে...',''));
                    fetch(sectionsUrl + '?class_id=' + encodeURIComponent(classId)).then(r=>r.json()).then(data=>{
                        $section.empty();
                        $section.append(new Option('সকল শাখা',''));
                        data.forEach(s=>{ $section.append(new Option(s.name,s.id)); });
                        $section.prop('disabled',false).trigger('change');
                        // preload students for selected class (and default section)
                        loadStudentsOptions();
                    }).catch(()=>{ $section.prop('disabled',false); });
                });

                // When section changes, reload students options
                $section.on('change', function(){
                    loadStudentsOptions();
                });

                // Student select2
                $student.select2({
                    placeholder: 'শিক্ষার্থীর নাম/আইডি অনুসন্ধান করুন',
                    allowClear: true,
                    width: '100%'
                });

                // Preload students into the select so users can open dropdown without typing
                function loadStudentsOptions(){
                    const params = new URLSearchParams();
                    if ($year.val()) params.append('year_id', $year.val());
                    if ($class.val()) params.append('class_id', $class.val());
                    if ($section.val()) params.append('section_id', $section.val());
                    params.append('status', 'active');
                    const url = studentsUrl + '?' + params.toString();
                    $student.prop('disabled', true);
                    fetch(url).then(r=>r.json()).then(data=>{
                        // clear existing options and add default
                        $student.empty();
                        $student.append(new Option('সকল শিক্ষার্থী',''));
                        data.forEach(d=>{ $student.append(new Option((d.roll_no?d.roll_no+' - ':'') + d.name + ' ( ' + (d.student_id||'') + ' )', d.record_id)); });
                        $student.prop('disabled', false).trigger('change');
                    }).catch(()=>{ $student.prop('disabled', false); });
                }

                // Handle print summary button
                document.getElementById('print_summary_btn').addEventListener('click', function(e){
                    e.preventDefault();
                    if (!$class.val()) {
                        alert('অনুগ্রহ করে শ্রেণি নির্বাচন করুন।');
                        return;
                    }
                    const params = new URLSearchParams();
                    params.append('class_id', $class.val());
                    if ($section.val()) params.append('section_id', $section.val());
                    
                    const startDate = document.getElementById('start_date').value;
                    const endDate = document.getElementById('end_date').value;
                    if (startDate && endDate) {
                        params.append('start_date', startDate);
                        params.append('end_date', endDate);
                    }
                    window.open(printSummaryUrl + '?' + params.toString(), '_blank');
                });

                // Handle print all button
                document.getElementById('print_all_btn').addEventListener('click', function(e){
                    e.preventDefault();
                    if (!$class.val()) {
                        alert('অনুগ্রহ করে শ্রেণি নির্বাচন করুন।');
                        return;
                    }
                    const params = new URLSearchParams();
                    params.append('class_id', $class.val());
                    if ($section.val()) params.append('section_id', $section.val());
                    
                    const startDate = document.getElementById('start_date').value;
                    const endDate = document.getElementById('end_date').value;
                    if (startDate && endDate) {
                        params.append('start_date', startDate);
                        params.append('end_date', endDate);
                    }
                    window.open(printAllUrl + '?' + params.toString(), '_blank');
                });

                // Handle list button
                document.getElementById('list_btn').addEventListener('click', function(e){
                    e.preventDefault();
                    loadList();
                });

                function loadList(){
                    const params = new URLSearchParams();
                    if ($year.val()) params.append('year_id', $year.val());
                    if ($class.val()) params.append('class_id', $class.val());
                    if ($section.val()) params.append('section_id', $section.val());
                    if ($student.val()) params.append('student_id', $student.val());
                    params.append('status', 'active');

                    fetch(studentsUrl + '?' + params.toString()).then(r=>r.json()).then(data=>{
                        renderTable(data);
                    }).catch(err=>{
                        jQuery('#students_list').html('<div class="alert alert-danger">তথ্য লোড করতে সমস্যা হয়েছে</div>');
                    });
                }

                function renderTable(rows){
                    if (!rows.length) { jQuery('#students_list').html('<div class="alert alert-info">কোন তথ্য পাওয়া যায়নি</div>'); return; }
                    // determine whether to show section column
                    const showSection = !Boolean($section.val());
                    let html = '<div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th>#</th><th>ছবি</th><th>নাম</th>' + (showSection?'<th>শাখা</th>':'') + '<th>রোল নং</th><th></th></tr></thead><tbody>';
                    const startDate = document.getElementById('start_date').value;
                    const endDate = document.getElementById('end_date').value;
                    let urlParams = '';
                    if (startDate && endDate) {
                        urlParams = '?start_date=' + encodeURIComponent(startDate) + '&end_date=' + encodeURIComponent(endDate);
                    }

                    rows.forEach((r, i)=>{
                        const idx = i+1;
                        const photo = r.photo_url || '{{ asset('images/default-avatar.svg') }}';
                        const sectionHtml = showSection ? ('<td>' + (r.section_name_bn||r.section_name||'-') + '</td>') : '';
                        const showUrl = showTemplate.replace('__STUDENT__', r.record_id) + urlParams;

                        html += '<tr>'+
                            '<td>' + idx + '</td>'+
                            '<td><img src="' + photo + '" style="width:40px;height:40px;object-fit:cover;border-radius:50%;"/></td>'+
                            '<td>' + (r.name||'') + '<br><small class="text-muted">' + (r.student_id||'') + '</small></td>'+
                            sectionHtml +
                            '<td>' + (r.roll_no || '-') + '</td>'+
                            '<td><a class="btn btn-sm btn-primary" target="_blank" href="' + showUrl + '">Open</a></td>'+
                            '</tr>';
                    });
                    html += '</tbody></table></div>';
                    jQuery('#students_list').html(html);
                }

                // Initialize select2 for selects other than student
                $class.select2({ width: '100%' });
                $section.select2({ width: '100%' });
                $year.select2({ width: '100%' });
            });
        </script>
    </div>
</div>
@endsection
