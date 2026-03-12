@extends('layouts.admin')
@section('title','প্রত্যয়নপত্র সংশোধন')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">প্রত্যয়নপত্র সংশোধন <small class="text-muted">#{{ $document->memo_no }}</small></h4>
  <div>
    <a target="_blank" href="{{ route('principal.institute.documents.prottayon.print', [$school, $document->id]) }}" class="btn btn-outline-success btn-sm mr-1">
      <i class="fas fa-print"></i> প্রিন্ট
    </a>
    <a href="{{ route('principal.institute.documents.prottayon.history', $school) }}" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-list"></i> ইতিহাস
    </a>
  </div>
</div>

<form method="POST" action="{{ route('principal.institute.documents.prottayon.update', [$school, $document->id]) }}">
  @csrf
  @method('PUT')

  <div class="row">
    {{-- Left: Main form --}}
    <div class="col-md-8">

      {{-- Step 1: শিক্ষার্থী তথ্য --}}
      <div class="card card-outline card-primary mb-3">
        <div class="card-header"><h6 class="card-title mb-0"><i class="fas fa-user-graduate mr-2"></i>শিক্ষার্থী তথ্য</h6></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group col-md-4">
              <label>শ্রেণি</label>
              <select class="form-control" name="class_id" id="docClass" required>
                <option value="">-- নির্বাচন করুন --</option>
                @foreach($classes as $c)
                  <option value="{{ $c->id }}" {{ ($document->data['class_id'] ?? null) == $c->id ? 'selected' : '' }}>
                    {{ $c->bangla_name ?: $c->name }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-4">
              <label>শাখা</label>
              <select class="form-control" name="section_id" id="docSection">
                <option value="">-- (ঐচ্ছিক) --</option>
                @foreach($sections as $s)
                  <option value="{{ $s->id }}" {{ ($document->data['section_id'] ?? null) == $s->id ? 'selected' : '' }}>
                    {{ $s->bangla_name ?: $s->name }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-4">
              <label>শিক্ষার্থী</label>
              <select class="form-control" name="student_id" id="docStudent" required>
                <option value="{{ $document->student_id }}" selected>
                  {{ $document->student?->student_name_bn ?: $document->student?->student_name_en }}
                </option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-4">
              <label>প্রত্যয়নের ধরন</label>
              <select class="form-control" name="attestation_type" required>
                <option value="study"     {{ ($document->data['attestation_type'] ?? '') === 'study'     ? 'selected' : '' }}>অধ্যয়নরত</option>
                <option value="character" {{ ($document->data['attestation_type'] ?? '') === 'character' ? 'selected' : '' }}>চারিত্রিক</option>
              </select>
            </div>
            <div class="form-group col-md-4">
              <label>টেম্পলেট</label>
              <select class="form-control" name="template_id" id="docTemplate">
                <option value="">-- কাস্টম কনটেন্ট --</option>
                @foreach($templates as $t)
                  <option value="{{ $t->id }}" {{ ($document->data['template_id'] ?? null) == $t->id ? 'selected' : '' }}
                    data-lang="{{ $t->language }}">
                    {{ $t->name }} ({{ $t->language === 'bn' ? 'বাংলা' : 'English' }})
                  </option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-md-4">
              <label>মুদ্রণ লেআউট</label>
              <select class="form-control" name="layout">
                <option value="standard" {{ ($document->data['layout'] ?? 'standard') === 'standard' ? 'selected' : '' }}>Standard (হেডার সহ)</option>
                <option value="pad"      {{ ($document->data['layout'] ?? '') === 'pad' ? 'selected' : '' }}>Pad/Letterhead (হেডার ছাড়া)</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      {{-- Step 2: প্রত্যয়ন বিষয়বস্তু --}}
      <div class="card card-outline card-warning mb-3">
        <div class="card-header"><h6 class="card-title mb-0"><i class="fas fa-file-alt mr-2"></i>প্রত্যয়নের বিষয়বস্তু</h6></div>
        <div class="card-body">
          <textarea class="form-control" name="content" id="docContent" rows="10"
            placeholder="খালি রাখলে বিদ্যমান কনটেন্ট রাখা হবে। টেম্পলেট নির্বাচন করলে সে অনুযায়ী কনটেন্ট আসবে।">{{ $document->data['custom_content'] ?? '' }}</textarea>
          <small class="text-muted">টেম্পলেট নির্বাচন করলে তার কনটেন্ট এখানে লোড হবে। আপনি সরাসরি সম্পাদনাও করতে পারবেন।</small>
        </div>
      </div>

      {{-- Step 3: শিক্ষার্থীর ব্যক্তিগত তথ্য সংশোধন --}}
      <div class="card card-outline card-secondary mb-3">
        <div class="card-header">
          <h6 class="card-title mb-0">
            <a data-toggle="collapse" href="#studentInfoCollapse" class="text-dark">
              <i class="fas fa-user-edit mr-2"></i>শিক্ষার্থীর তথ্য সংশোধন <small class="text-muted">(ঐচ্ছিক — এই পরিবর্তন ডাটাবেজে সংরক্ষিত হবে)</small>
            </a>
          </h6>
        </div>
        <div id="studentInfoCollapse" class="collapse">
          <div class="card-body">
            <div class="form-row">
              <div class="form-group col-md-6">
                <label>নাম (বাংলা)</label>
                <input class="form-control" name="student_name_bn" value="{{ $document->student?->student_name_bn }}">
              </div>
              <div class="form-group col-md-6">
                <label>নাম (ইংরেজি)</label>
                <input class="form-control" name="student_name_en" value="{{ $document->student?->student_name_en }}">
              </div>
              <div class="form-group col-md-6">
                <label>পিতার নাম (বাংলা)</label>
                <input class="form-control" name="father_name_bn" value="{{ $document->student?->father_name_bn }}">
              </div>
              <div class="form-group col-md-6">
                <label>পিতার নাম (ইংরেজি)</label>
                <input class="form-control" name="father_name" value="{{ $document->student?->father_name }}">
              </div>
              <div class="form-group col-md-6">
                <label>মাতার নাম (বাংলা)</label>
                <input class="form-control" name="mother_name_bn" value="{{ $document->student?->mother_name_bn }}">
              </div>
              <div class="form-group col-md-6">
                <label>মাতার নাম (ইংরেজি)</label>
                <input class="form-control" name="mother_name" value="{{ $document->student?->mother_name }}">
              </div>
              <div class="form-group col-md-3">
                <label>জন্ম তারিখ</label>
                <input type="date" class="form-control" name="date_of_birth" value="{{ $document->student?->date_of_birth?->format('Y-m-d') }}">
              </div>
              <div class="form-group col-md-3">
                <label>গ্রাম</label>
                <input class="form-control" name="present_village" value="{{ $document->student?->present_village }}">
              </div>
              <div class="form-group col-md-3">
                <label>ডাকঘর</label>
                <input class="form-control" name="present_post_office" value="{{ $document->student?->present_post_office }}">
              </div>
              <div class="form-group col-md-3">
                <label>উপজেলা</label>
                <input class="form-control" name="present_upazilla" value="{{ $document->student?->present_upazilla }}">
              </div>
              <div class="form-group col-md-4">
                <label>জেলা</label>
                <input class="form-control" name="present_district" value="{{ $document->student?->present_district }}">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="form-group">
        <button type="submit" class="btn btn-primary btn-lg px-5">
          <i class="fas fa-save mr-2"></i>সংরক্ষণ করুন
        </button>
        <a target="_blank" href="{{ route('principal.institute.documents.prottayon.print', [$school, $document->id]) }}" class="btn btn-outline-success btn-lg ml-2">
          <i class="fas fa-print mr-2"></i>প্রিন্ট
        </a>
      </div>

    </div>

    {{-- Right: Template content quick load --}}
    <div class="col-md-4">
      <div class="card card-outline card-info">
        <div class="card-header"><h6 class="card-title mb-0">তথ্য</h6></div>
        <div class="card-body small">
          <dl>
            <dt>স্মারক নং</dt><dd>{{ $document->memo_no }}</dd>
            <dt>ইস্যু তারিখ</dt><dd>{{ $document->issued_at?->format('d/m/Y') }}</dd>
            <dt>শিক্ষার্থী</dt><dd>{{ $document->student?->student_name_bn }}</dd>
            <dt>আইডি</dt><dd>{{ $document->student?->student_id }}</dd>
          </dl>
          <div class="alert alert-warning small mt-2">
            <i class="fas fa-info-circle"></i> টেম্পলেট নির্বাচন করলে কনটেন্ট বক্সে তার টেক্সট আসবে। আপনি চাইলে সে টেক্সট সম্পাদনা করতে পারবেন।
          </div>
        </div>
      </div>
    </div>

  </div>
</form>
@endsection

@push('scripts')
<script>
(function(){
    const sectionsUrl = @json(route('principal.institute.meta.sections', $school));
    const studentsUrl = @json(route('principal.institute.meta.students', $school));
    const classSel   = document.getElementById('docClass');
    const sectionSel = document.getElementById('docSection');
    const studentSel = document.getElementById('docStudent');
    const contentTA  = document.getElementById('docContent');
    const templateSel = document.getElementById('docTemplate');

    function clearOptions(sel, placeholder){
        if(!sel) return;
        sel.innerHTML = '';
        const opt = document.createElement('option');
        opt.value = ''; opt.textContent = placeholder;
        sel.appendChild(opt);
    }

    classSel && classSel.addEventListener('change', function(){
        const classId = this.value;
        clearOptions(sectionSel,'-- (ঐচ্ছিক) --');
        clearOptions(studentSel,'-- নির্বাচন করুন --');
        if(!classId) return;
        fetch(sectionsUrl + '?class_id=' + encodeURIComponent(classId))
            .then(r => r.json())
            .then(rows => {
                rows.forEach(r => {
                    const o = document.createElement('option');
                    o.value = r.id;
                    o.textContent = r.bangla_name || r.name;
                    sectionSel.appendChild(o);
                });
            });
        fetch(studentsUrl + '?class_id=' + encodeURIComponent(classId))
            .then(r => r.json())
            .then(rows => {
                clearOptions(studentSel,'-- নির্বাচন করুন --');
                rows.forEach(r => {
                    const o = document.createElement('option');
                    o.value = r.record_id;
                    o.textContent = (r.student_name_bn || r.name) + ' (রোল: ' + (r.roll_no||'-') + ')';
                    studentSel.appendChild(o);
                });
            });
    });

    sectionSel && sectionSel.addEventListener('change', function(){
        const classId = classSel ? classSel.value : '';
        const sectionId = this.value;
        if(!classId) return;
        let url = studentsUrl + '?class_id=' + encodeURIComponent(classId);
        if(sectionId) url += '&section_id=' + encodeURIComponent(sectionId);
        fetch(url).then(r => r.json()).then(rows => {
            clearOptions(studentSel,'-- নির্বাচন করুন --');
            rows.forEach(r => {
                const o = document.createElement('option');
                o.value = r.record_id;
                o.textContent = (r.student_name_bn || r.name) + ' (রোল: ' + (r.roll_no||'-') + ')';
                studentSel.appendChild(o);
            });
        });
    });

    // Load template content when template selected
    templateSel && templateSel.addEventListener('change', function(){
        const selectedOption = this.options[this.selectedIndex];
        const templateId = this.value;
        if(!templateId) return;
        // Fetch templates list to find content
        fetch('/principal/institute/{{ $school->id }}/documents/settings/templates')
            .then(r => r.json())
            .then(templates => {
                const t = templates.find(t => t.id == templateId);
                if(t && contentTA) {
                    contentTA.value = t.content;
                }
            });
    });
})();
</script>
@endpush
