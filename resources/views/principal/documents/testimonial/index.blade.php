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
        <div class="form-group col-md-4">
          <label>শ্রেণি</label>
          <select class="form-control" name="class_id" id="tstClass" required>
            <option value="">-- নির্বাচন করুন --</option>
            @foreach(\App\Models\SchoolClass::where('school_id',$school->id)->orderBy('numeric_value')->get() as $c)
              <option value="{{ $c->id }}">{{ $c->name ?? ('Class '.$c->numeric_value) }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-4">
          <label>শাখা</label>
          <select class="form-control" name="section_id" id="tstSection">
            <option value="">-- (ঐচ্ছিক) --</option>
          </select>
        </div>
        <div class="form-group col-md-4">
          <label>শিক্ষার্থী</label>
          <select class="form-control" name="student_id" id="tstStudent" required>
            <option value="">-- নির্বাচন করুন --</option>
          </select>
        </div>
        <div class="form-group col-md-4">
          <label>পরীক্ষার নাম</label>
          <select class="form-control" name="exam_name" required>
            <option value="SSC">SSC</option>
            <option value="HSC">HSC</option>
          </select>
        </div>
        <div class="form-group col-md-4">
          <label>সেশন বছর</label>
          <input type="number" class="form-control" name="session_year" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group col-md-4"><label>Roll</label><input type="text" class="form-control" name="roll"></div>
        <div class="form-group col-md-4"><label>Registration</label><input type="text" class="form-control" name="registration"></div>
        <div class="form-group col-md-4"><label>Center</label><input type="text" class="form-control" name="center"></div>
      </div>
      <button class="btn btn-primary">জেনারেট করুন</button>
    </form>
  </div>
</div>
@push('scripts')
<script>
  (function(){
    const sectionsUrl = @json(route('principal.institute.meta.sections', $school));
    const studentsUrl = @json(route('principal.institute.meta.students', $school));
    const classSel = document.getElementById('tstClass');
    const sectionSel = document.getElementById('tstSection');
    const studentSel = document.getElementById('tstStudent');
    function clearOptions(sel, placeholder){ if(!sel) return; sel.innerHTML=''; const opt=document.createElement('option'); opt.value=''; opt.textContent=placeholder; sel.appendChild(opt);}    
    classSel && classSel.addEventListener('change', function(){
      const classId=this.value; clearOptions(sectionSel,'-- (ঐচ্ছিক) --'); clearOptions(studentSel,'-- নির্বাচন করুন --'); if(!classId) return;
      fetch(sectionsUrl+'?class_id='+encodeURIComponent(classId)).then(r=>r.json()).then(rows=>{ rows.forEach(r=>{ const o=document.createElement('option'); o.value=r.id; o.textContent=r.name; sectionSel.appendChild(o); }); });
      fetch(studentsUrl+'?class_id='+encodeURIComponent(classId)).then(r=>r.json()).then(rows=>{ clearOptions(studentSel,'-- নির্বাচন করুন --'); rows.forEach(r=>{ const o=document.createElement('option'); o.value=r.student_id; o.textContent=r.name+' ('+(r.roll_no||'-')+')'; studentSel.appendChild(o); }); });
    });
    sectionSel && sectionSel.addEventListener('change', function(){
      const classId=classSel?classSel.value:''; const sectionId=this.value; clearOptions(studentSel,'-- নির্বাচন করুন --'); if(!classId) return;
      let url=studentsUrl+'?class_id='+encodeURIComponent(classId); if(sectionId) url+='&section_id='+encodeURIComponent(sectionId);
      fetch(url).then(r=>r.json()).then(rows=>{ rows.forEach(r=>{ const o=document.createElement('option'); o.value=r.student_id; o.textContent=r.name+' ('+(r.roll_no||'-')+')'; studentSel.appendChild(o); }); });
    });
  })();
</script>
@endpush
@endsection
