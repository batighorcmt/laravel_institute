@extends('layouts.admin')
@section('title','Documents: Prottayon')

@section('content')
<div class="row">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">প্রত্যয়নপত্র তৈরি</h5>
        <a href="{{ route('principal.institute.documents.prottayon.history', $school) }}" class="btn btn-sm btn-outline-secondary">তালিকা</a>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('principal.institute.documents.prottayon.generate', $school) }}">
          @csrf
          <div class="form-row">
            <div class="form-group col-md-4">
              <label>শ্রেণি</label>
              <select class="form-control" name="class_id" id="docClass" required>
                <option value="">-- নির্বাচন করুন --</option>
                @if($classes instanceof \Illuminate\Support\Collection)
                  @foreach($classes as $c)
                    <option value="{{ $c->id }}">{{ $c->name ?? ('Class '.$c->numeric_value) }}</option>
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
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                  @endforeach
                @endif
              </select>
            </div>
            <div class="form-group col-md-4">
              <label>শিক্ষার্থী</label>
              <select class="form-control" name="student_id" id="docStudent" required>
                <option value="">-- নির্বাচন করুন --</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>প্রত্যয়নের ধরন</label>
            <select class="form-control" name="attestation_type" required>
              <option value="study">অধ্যয়নরত</option>
              <option value="character">চারিত্রিক</option>
            </select>
          </div>
          <button class="btn btn-primary">জেনারেট করুন</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="alert alert-info">শ্রেণি নির্বাচন করলে শাখা ও শিক্ষার্থীদের তালিকা স্বয়ংক্রিয়ভাবে লোড হবে।</div>
  </div>
</div>
@push('scripts')
<script>
  (function(){
    const sectionsUrl = @json(route('principal.institute.meta.sections', $school));
    const studentsUrl = @json(route('principal.institute.meta.students', $school));
    const classSel = document.getElementById('docClass');
    const sectionSel = document.getElementById('docSection');
    const studentSel = document.getElementById('docStudent');

    function clearOptions(sel, placeholder){
      if(!sel) return;
      sel.innerHTML = '';
      const opt = document.createElement('option');
      opt.value = '';
      opt.textContent = placeholder;
      sel.appendChild(opt);
    }

    classSel && classSel.addEventListener('change', function(){
      const classId = this.value;
      clearOptions(sectionSel, '-- (ঐচ্ছিক) --');
      clearOptions(studentSel, '-- নির্বাচন করুন --');
      if(!classId) return;
      fetch(sectionsUrl + '?class_id=' + encodeURIComponent(classId))
        .then(r=>r.json())
        .then(rows => {
          rows.forEach(r => {
            const opt = document.createElement('option');
            opt.value = r.id; opt.textContent = r.name; sectionSel.appendChild(opt);
          });
        }).catch(()=>{});
      // Preload students by class (no section filter)
      fetch(studentsUrl + '?class_id=' + encodeURIComponent(classId))
        .then(r=>r.json())
        .then(rows => {
          clearOptions(studentSel, '-- নির্বাচন করুন --');
          rows.forEach(r => {
            const opt = document.createElement('option');
            opt.value = r.student_id; opt.textContent = r.name + ' (' + (r.roll_no||'-') + ')'; studentSel.appendChild(opt);
          });
        }).catch(()=>{});
    });

    sectionSel && sectionSel.addEventListener('change', function(){
      const classId = classSel ? classSel.value : '';
      const sectionId = this.value;
      clearOptions(studentSel, '-- নির্বাচন করুন --');
      if(!classId) return;
      let url = studentsUrl + '?class_id=' + encodeURIComponent(classId);
      if(sectionId) url += '&section_id=' + encodeURIComponent(sectionId);
      fetch(url).then(r=>r.json()).then(rows => {
        rows.forEach(r => {
          const opt = document.createElement('option');
          opt.value = r.student_id; opt.textContent = r.name + ' (' + (r.roll_no||'-') + ')'; studentSel.appendChild(opt);
        });
      }).catch(()=>{});
    });
  })();
</script>
@endpush
@endsection
