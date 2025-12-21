@extends('layouts.admin')
@section('title','Certificate সংশোধন')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Certificate সংশোধন</h4>
  <a href="{{ route('principal.institute.documents.certificate.history', $school) }}" class="btn btn-outline-secondary">ইতিহাস</a>
</div>
<div class="card">
  <div class="card-body">
    <form method="POST" action="{{ route('principal.institute.documents.certificate.update', [$school, $document->id]) }}">
      @csrf
      @method('PUT')
      <div class="form-row">
        <div class="form-group col-md-4">
          <label>শ্রেণি</label>
          <select class="form-control" name="class_id" id="crtClass">
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
            <option value="{{ $document->student_id }}">{{ $document->student?->full_name }}</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group col-md-4">
          <label>ক্লাস (যেমন ৫, ৮)</label>
          <input type="text" class="form-control" name="class_name" value="{{ $document->data['class_name'] ?? '' }}" required>
        </div>
        <div class="form-group col-md-4">
          <label>বছর</label>
          <input type="number" class="form-control" name="year" value="{{ $document->data['year'] ?? '' }}" required>
        </div>
      </div>
      <div class="form-group">
        <label>সার্টিফিকেট শিরোনাম</label>
        <input type="text" class="form-control" name="certificate_title" value="{{ $document->data['certificate_title'] ?? '' }}" required>
      </div>
      <button class="btn btn-primary">সংরক্ষণ করুন</button>
      <a target="_blank" href="{{ route('principal.institute.documents.certificate.print', [$school,$document->id]) }}" class="btn btn-outline-secondary">Print</a>
    </form>
  </div>
</div>
@endsection

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
