@extends('layouts.admin')
@section('title','প্রত্যয়নপত্র সংশোধন')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">প্রত্যয়নপত্র সংশোধন</h4>
  <a href="{{ route('principal.institute.documents.prottayon.history', $school) }}" class="btn btn-outline-secondary">ইতিহাস</a>
</div>
<div class="card">
  <div class="card-body">
    <form method="POST" action="{{ route('principal.institute.documents.prottayon.update', [$school, $document->id]) }}">
      @csrf
      @method('PUT')
      <div class="form-row">
        <div class="form-group col-md-4">
          <label>শ্রেণি</label>
          <select class="form-control" name="class_id" id="docClass" required>
            <option value="">-- নির্বাচন করুন --</option>
            @if($classes instanceof \Illuminate\Support\Collection)
              @foreach($classes as $c)
                <option value="{{ $c->id }}" {{ ($document->data['class_id'] ?? null) == $c->id ? 'selected' : '' }}>{{ $c->name ?? ('Class '.$c->numeric_value) }}</option>
              @endforeach
            @endif
          </select>
        </div>
        <div class="form-group col-md-4">
          <label>শাখা</label>
          <select class="form-control" name="section_id" id="docSection">
            <option value="">-- (ঐচ্ছিক) --</option>
            @if($sections instanceof \Illuminate\Support\Collection)
              @foreach($sections as $s)
                <option value="{{ $s->id }}" {{ ($document->data['section_id'] ?? null) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
              @endforeach
            @endif
          </select>
        </div>
        <div class="form-group col-md-4">
          <label>শিক্ষার্থী</label>
          <select class="form-control" name="student_id" id="docStudent" required>
            <option value="{{ $document->student_id }}">{{ $document->student?->full_name }}</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>প্রত্যয়নের ধরন</label>
        <select class="form-control" name="attestation_type" required>
          <option value="study" {{ ($document->data['attestation_type'] ?? '')==='study' ? 'selected' : '' }}>অধ্যয়নরত</option>
          <option value="character" {{ ($document->data['attestation_type'] ?? '')==='character' ? 'selected' : '' }}>চারিত্রিক</option>
        </select>
      </div>
      <button class="btn btn-primary">সংরক্ষণ করুন</button>
      <a target="_blank" href="{{ route('principal.institute.documents.prottayon.print', [$school,$document->id]) }}" class="btn btn-outline-secondary">Print</a>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  (function(){
    const sectionsUrl = @json(route('principal.institute.meta.sections', $school));
    const studentsUrl = @json(route('principal.institute.meta.students', $school));
    const classSel = document.getElementById('docClass');
    const sectionSel = document.getElementById('docSection');
    const studentSel = document.getElementById('docStudent');
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
