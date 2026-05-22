@extends('layouts.admin')
@section('title','Documents: Testimonial')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Testimonial (SSC/HSC) তৈরি</h5>
    <a href="{{ route('principal.institute.documents.testimonial.history', $school) }}" class="btn btn-sm btn-outline-secondary">তালিকা</a>
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('principal.institute.documents.testimonial.generate', $school) }}">
      @csrf
      <div class="form-row">
        <div class="form-group col-md-3">
          <label>শিক্ষাবর্ষ</label>
          <select class="form-control" name="academic_year" id="tstAcademicYear" required>
            <option value="">-- নির্বাচন করুন --</option>
            @foreach($academicYears as $year)
              <option value="{{ $year->id }}">{{ $year->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-3">
          <label>শ্রেণি</label>
          <select class="form-control" name="class_id" id="tstClass" required>
            <option value="">-- নির্বাচন করুন --</option>
            @foreach(\App\Models\SchoolClass::where('school_id',$school->id)->orderBy('numeric_value')->get() as $c)
              <option value="{{ $c->id }}">{{ $c->name ?? ('Class '.$c->numeric_value) }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-2">
          <label>শাখা</label>
          <select class="form-control" name="section_id" id="tstSection">
            <option value="">-- (ঐচ্ছিক) --</option>
          </select>
        </div>
        <div class="form-group col-md-2">
          <label>পরীক্ষার নাম</label>
          <select class="form-control" name="exam_name" id="tstExamName" required>
            <option value="">-- নির্বাচন করুন --</option>
            @foreach($publicExams as $exam)
              <option value="{{ $exam->short_name }}">{{ $exam->short_name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-2">
          <label>শিক্ষার্থী</label>
          <select class="form-control" name="student_id" id="tstStudent" required>
            <option value="">-- নির্বাচন করুন --</option>
          </select>
        </div>
      </div>

      <!-- Dynamic Status Container -->
      <div id="testimonialStatusContainer" class="my-4">
        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-0" style="border-radius: 8px;">
          <div class="mr-3 text-info"><i class="fas fa-info-circle" style="font-size: 1.5rem;"></i></div>
          <div>
            <strong>নির্দেশনা:</strong> প্রশংসাপত্র তৈরির জন্য অনুগ্রহ করে শিক্ষাবর্ষ, শ্রেণি, পরীক্ষার নাম এবং শিক্ষার্থী নির্বাচন করুন।
          </div>
        </div>
      </div>

      <!-- Manual Entry Fields (Hidden by Default) -->
      <div id="manualFormFields" style="display: none;">
        <h5 class="border-bottom pb-2 mb-3 mt-4 text-secondary">প্রশংসাপত্রের ম্যানুয়াল তথ্য</h5>
        <div class="form-row">
          <div class="form-group col-md-4">
            <label>বোর্ড</label>
            <select class="form-control" name="board" id="tstBoard" data-required="true">
              <option value="">-- নির্বাচন করুন --</option>
              <option value="Dhaka">Dhaka</option>
              <option value="Rajshahi">Rajshahi</option>
              <option value="Comilla">Comilla</option>
              <option value="Jashore">Jashore</option>
              <option value="Chittagong">Chittagong</option>
              <option value="Barisal">Barisal</option>
              <option value="Sylhet">Sylhet</option>
              <option value="Dinajpur">Dinajpur</option>
              <option value="Madrasah">Madrasah</option>
              <option value="Technical">Technical</option>
            </select>
          </div>
          <div class="form-group col-md-4">
            <label>সেশন</label>
            <input type="text" class="form-control" name="session" id="tstSession" placeholder="2023-2024" data-required="true">
          </div>
          <div class="form-group col-md-4">
            <label>পাশের বছর</label>
            <input type="number" class="form-control" name="passing_year" id="tstPassingYear" data-required="true">
          </div>
          <div class="form-group col-md-4">
            <label>ফলাফল</label>
            <input type="text" class="form-control" name="result" id="tstResult" placeholder="e.g. GPA 5.00">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-4"><label>Roll</label><input type="text" class="form-control" name="roll" id="tstRoll"></div>
          <div class="form-group col-md-4"><label>Registration</label><input type="text" class="form-control" name="registration" id="tstRegistration"></div>
          <div class="form-group col-md-4"><label>Center</label><input type="text" class="form-control" name="center" id="tstCenter"></div>
        </div>
        <button class="btn btn-primary px-4 mt-2">জেনারেট করুন</button>
      </div>
    </form>
  </div>
</div>
@push('scripts')
<script>
  (function(){
    const sectionsUrl = @json(route('principal.institute.meta.sections', $school));
    const studentsUrl = @json(route('principal.institute.meta.students', $school));
    const loadStatusUrl = @json(route('principal.institute.documents.testimonial.load-students', $school));
    const quickGenerateUrl = @json(route('principal.institute.documents.testimonial.quick-generate', $school));
    const printUrlTemplate = @json(route('principal.institute.documents.testimonial.print', [$school, 'PLACEHOLDER']));
    const editUrlTemplate = @json(route('principal.institute.documents.testimonial.edit', [$school, 'PLACEHOLDER']));

    const academicYearSel = document.getElementById('tstAcademicYear');
    const classSel = document.getElementById('tstClass');
    const sectionSel = document.getElementById('tstSection');
    const studentSel = document.getElementById('tstStudent');
    const examNameSel = document.getElementById('tstExamName');
    
    const statusContainer = document.getElementById('testimonialStatusContainer');
    const manualFields = document.getElementById('manualFormFields');

    function clearOptions(sel, placeholder){ 
      if(!sel) return; 
      sel.innerHTML=''; 
      const opt=document.createElement('option'); 
      opt.value=''; 
      opt.textContent=placeholder; 
      sel.appendChild(opt);
    }

    function clearStatus() {
      statusContainer.innerHTML = `
        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-0" style="border-radius: 8px;">
          <div class="mr-3 text-info"><i class="fas fa-info-circle" style="font-size: 1.5rem;"></i></div>
          <div>
            <strong>নির্দেশনা:</strong> প্রশংসাপত্র তৈরির জন্য অনুগ্রহ করে শিক্ষাবর্ষ, শ্রেণি, পরীক্ষার নাম এবং শিক্ষার্থী নির্বাচন করুন।
          </div>
        </div>
      `;
      hideManualFields();
    }

    function resetManualFields() {
      document.getElementById('tstBoard').value = '';
      document.getElementById('tstSession').value = '';
      document.getElementById('tstPassingYear').value = '';
      document.getElementById('tstResult').value = '';
      document.getElementById('tstRoll').value = '';
      document.getElementById('tstRegistration').value = '';
      document.getElementById('tstCenter').value = '';
    }

    function hideManualFields() {
      manualFields.style.display = 'none';
      manualFields.querySelectorAll('[data-required="true"]').forEach(el => {
        el.removeAttribute('required');
      });
    }

    function showManualFields(prefill = null) {
      resetManualFields();
      manualFields.style.display = 'block';
      manualFields.querySelectorAll('[data-required="true"]').forEach(el => {
        el.setAttribute('required', 'required');
      });
      if (prefill) {
        if (prefill.board) document.getElementById('tstBoard').value = prefill.board;
        if (prefill.session) document.getElementById('tstSession').value = prefill.session;
        if (prefill.passing_year) document.getElementById('tstPassingYear').value = prefill.passing_year;
        if (prefill.roll_no_pub) document.getElementById('tstRoll').value = prefill.roll_no_pub;
        if (prefill.reg_no) document.getElementById('tstRegistration').value = prefill.reg_no;
        if (prefill.center_name) document.getElementById('tstCenter').value = prefill.center_name;
        if (prefill.result) document.getElementById('tstResult').value = prefill.result;
      }
    }

    function checkTestimonialStatus() {
      const academicYearId = academicYearSel.value;
      const classId = classSel.value;
      const studentId = studentSel.value;
      const examName = examNameSel.value;

      if (!academicYearId || !classId || !studentId || !examName) {
        clearStatus();
        return;
      }

      // Show loading spinner
      statusContainer.innerHTML = `
        <div class="d-flex align-items-center justify-content-center p-4">
          <div class="spinner-border text-primary mr-2" role="status"></div>
          <span>তথ্য লোড করা হচ্ছে, অনুগ্রহ করে অপেক্ষা করুন...</span>
        </div>
      `;
      hideManualFields();

      const url = loadStatusUrl + 
        '?academic_year_id=' + encodeURIComponent(academicYearId) + 
        '&class_id=' + encodeURIComponent(classId) + 
        '&public_exam_name=' + encodeURIComponent(examName) + 
        '&student_id=' + encodeURIComponent(studentId);

      fetch(url)
        .then(r => r.json())
        .then(data => {
          if (data.students && data.students.length > 0) {
            const student = data.students[0];
            if (student.has_testimonial) {
              const printUrl = printUrlTemplate.replace('PLACEHOLDER', student.testimonial_id);
              const editUrl = editUrlTemplate.replace('PLACEHOLDER', student.testimonial_id);
              statusContainer.innerHTML = `
                <div class="card border-success shadow-sm mb-3" style="border-radius: 8px;">
                  <div class="card-body">
                    <div class="d-flex align-items-center">
                      <div class="mr-3 text-success"><i class="fas fa-check-circle" style="font-size: 2.5rem;"></i></div>
                      <div>
                        <h6 class="card-title mb-1 text-success font-weight-bold">প্রশংসাপত্র ইতিমধ্যে তৈরি করা হয়েছে</h6>
                        <p class="card-text text-muted mb-0">শিক্ষার্থীর নাম: <strong>${student.name}</strong>, পরীক্ষা: <strong>${student.exam_name}</strong></p>
                      </div>
                      <div class="ml-auto">
                        <a href="${printUrl}" class="btn btn-success px-3 mr-2" target="_blank"><i class="fas fa-print mr-1"></i> প্রিন্ট / দেখুন</a>
                        <a href="${editUrl}" class="btn btn-warning px-3"><i class="fas fa-edit mr-1"></i> সংশোধন</a>
                      </div>
                    </div>
                  </div>
                </div>
              `;
              hideManualFields();
            } else if (student.has_public_exam) {
              statusContainer.innerHTML = `
                <div class="card border-info shadow-sm mb-3" style="border-radius: 8px;">
                  <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                      <div class="mr-3 text-info"><i class="fas fa-database" style="font-size: 2.2rem;"></i></div>
                      <div>
                        <h6 class="card-title mb-1 text-info font-weight-bold">পাবলিক পরীক্ষার তথ্য পাওয়া গেছে</h6>
                        <p class="card-text text-muted mb-0">শিক্ষার্থীর নাম: <strong>${student.name}</strong>, রোল: <strong>${student.roll_no}</strong></p>
                      </div>
                    </div>
                    <div class="table-responsive mb-3">
                      <table class="table table-sm table-bordered text-center mb-0" style="font-size: 0.9rem;">
                        <thead class="bg-light">
                          <tr>
                            <th>বোর্ড</th>
                            <th>সেশন</th>
                            <th>পাশের বছর</th>
                            <th>Roll (পাবলিক)</th>
                            <th>Registration</th>
                            <th>কেন্দ্র</th>
                            <th>ফলাফল</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <td>${student.board || '-'}</td>
                            <td>${student.session || '-'}</td>
                            <td>${student.exam_year || '-'}</td>
                            <td>${student.roll_no_pub || '-'}</td>
                            <td>${student.reg_no || '-'}</td>
                            <td>${student.center_name || '-'}</td>
                            <td><strong>${student.result || '-'}</strong></td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                    <div class="d-flex">
                      <button type="button" class="btn btn-primary px-4 mr-2" id="btnQuickGen"><i class="fas fa-bolt mr-1"></i> দ্রুত তৈরি করুন</button>
                      <button type="button" class="btn btn-outline-secondary px-3" id="btnManualGen"><i class="fas fa-edit mr-1"></i> পরিবর্তন করে ম্যানুয়ালি তৈরি করুন</button>
                    </div>
                  </div>
                </div>
              `;
              hideManualFields();

              // Bind event listeners
              document.getElementById('btnQuickGen').addEventListener('click', function() {
                const btn = this;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> তৈরি হচ্ছে...';

                fetch(quickGenerateUrl, {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                  },
                  body: JSON.stringify({
                    student_id: student.id,
                    academic_year_id: academicYearId,
                    exam_name: examName
                  })
                })
                .then(r => r.json())
                .then(res => {
                  if (res.success && res.print_url) {
                    window.location.href = res.print_url;
                  } else {
                    alert('প্রশংসাপত্র তৈরি করতে ব্যর্থ হয়েছে।');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-bolt mr-1"></i> দ্রুত তৈরি করুন';
                  }
                })
                .catch(err => {
                  console.error(err);
                  alert('একটি ত্রুটি ঘটেছে।');
                  btn.disabled = false;
                  btn.innerHTML = '<i class="fas fa-bolt mr-1"></i> দ্রুত তৈরি করুন';
                });
              });

              document.getElementById('btnManualGen').addEventListener('click', function() {
                showManualFields(student);
              });

            } else {
              statusContainer.innerHTML = `
                <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-3" style="border-radius: 8px;">
                  <div class="mr-3 text-warning"><i class="fas fa-exclamation-triangle" style="font-size: 2rem;"></i></div>
                  <div>
                    <strong>দুঃখিত!</strong> এই শিক্ষার্থীর জন্য কোনো পাবলিক পরীক্ষার তথ্য পাওয়া যায়নি। প্রশংসাপত্র তৈরির জন্য অনুগ্রহ করে নিচের ম্যানুয়াল তথ্যগুলো পূরণ করুন।
                  </div>
                </div>
              `;
              showManualFields(student);
            }
          } else {
            statusContainer.innerHTML = `
              <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-3" style="border-radius: 8px;">
                <div class="mr-3 text-danger"><i class="fas fa-times-circle" style="font-size: 2rem;"></i></div>
                <div>
                  শিক্ষার্থীর তথ্য পাওয়া যায়নি।
                </div>
              </div>
            `;
            hideManualFields();
          }
        })
        .catch(err => {
          console.error(err);
          statusContainer.innerHTML = `
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-3" style="border-radius: 8px;">
              <div class="mr-3 text-danger"><i class="fas fa-times-circle" style="font-size: 2rem;"></i></div>
              <div>
                সার্ভার থেকে তথ্য লোড করতে সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।
              </div>
            </div>
          `;
          hideManualFields();
        });
    }

    function loadStudentsList() {
      const classId = classSel.value;
      const yearId = academicYearSel.value;
      const sectionId = sectionSel.value;
      
      clearOptions(studentSel, '-- নির্বাচন করুন --');
      clearStatus();
      
      if (!classId || !yearId) return;
      
      let url = studentsUrl + '?class_id=' + encodeURIComponent(classId) + '&year_id=' + encodeURIComponent(yearId);
      if (sectionId) url += '&section_id=' + encodeURIComponent(sectionId);
      
      fetch(url)
        .then(r => r.json())
        .then(rows => {
          clearOptions(studentSel, '-- নির্বাচন করুন --');
          rows.forEach(r => {
            const o = document.createElement('option');
            o.value = r.record_id;
            o.textContent = r.name + ' (' + (r.roll_no || '-') + ')';
            studentSel.appendChild(o);
          });
        });
    }

    classSel && classSel.addEventListener('change', function(){
      const classId=this.value; 
      clearOptions(sectionSel,'-- (ঐচ্ছিক) --'); 
      if(classId) {
        fetch(sectionsUrl+'?class_id='+encodeURIComponent(classId))
          .then(r=>r.json())
          .then(rows=>{ 
            rows.forEach(r=>{ 
              const o=document.createElement('option'); 
              o.value=r.id; 
              o.textContent=r.name; 
              sectionSel.appendChild(o); 
            }); 
          });
      }
      loadStudentsList();
    });

    academicYearSel && academicYearSel.addEventListener('change', loadStudentsList);
    sectionSel && sectionSel.addEventListener('change', loadStudentsList);

    studentSel && studentSel.addEventListener('change', checkTestimonialStatus);
    examNameSel && examNameSel.addEventListener('change', checkTestimonialStatus);

  })();
</script>
@endpush
@endsection
