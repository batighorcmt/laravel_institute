@extends('layouts.admin')
@section('title','Documents: Certificate')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">Certificate তৈরি</h5>
    <a href="{{ route('principal.institute.documents.certificate.history', $school) }}" class="btn btn-sm btn-outline-secondary">তালিকা</a>
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('principal.institute.documents.certificate.generate', $school) }}">
      @csrf
      <div class="form-row">
        <div class="form-group col-md-4">
          <label>শ্রেণি</label>
          <select class="form-control" name="class_id" id="crtClass" required>
            <option value="">-- নির্বাচন করুন --</option>
            @foreach(\App\Models\SchoolClass::where('school_id',$school->id)->orderBy('numeric_value')->get() as $c)
              <option value="{{ $c->id }}">{{ $c->name ?? ('Class '.$c->numeric_value) }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-4">
          <label>শাখা</label>
          <select class="form-control" name="section_id" id="crtSection">
            <option value="">-- (ঐচ্ছিক) --</option>
          </select>
        </div>
        <div class="form-group col-md-4">
          <label>শিক্ষার্থী</label>
          <select class="form-control" name="student_id" id="crtStudent" required>
            <option value="">-- নির্বাচন করুন --</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group col-md-4">
          <label>ক্লাস (যেমন ৫, ৮)</label>
          <input type="text" class="form-control" name="class_name" required>
        </div>
        <div class="form-group col-md-4">
          <label>বছর</label>
          <input type="number" class="form-control" name="year" required>
        </div>
      </div>
      <div class="form-group">
        <label>সার্টিফিকেট শিরোনাম</label>
        <input type="text" class="form-control" name="certificate_title" placeholder="যেমন: ক্লাস ৮ পাস সার্টিফিকেট" required>
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
    const classSel = document.getElementById('crtClass');
    const sectionSel = document.getElementById('crtSection');
    const studentSel = document.getElementById('crtStudent');
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
