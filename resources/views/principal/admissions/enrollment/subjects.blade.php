@extends('layouts.admin')
@section('title', 'Subject Selection')

@section('content')
<div class="container-fluid">
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="card shadow">
        <div class="card-header bg-primary text-white">
          <h4 class="mb-0">
            <i class="fas fa-book mr-2"></i>বিষয় নির্বাচন করুন
          </h4>
        </div>
        
        <div class="card-body">
          @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
              {{ session('success') }}
              <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
          @endif

          @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
              {{ session('error') }}
              <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
          @endif

          <!-- Student Info Card -->
          <div class="row mb-4">
            <div class="col-md-8">
              <div class="card border-info">
                <div class="card-body">
                  <h5 class="card-title text-info mb-3">
                    <i class="fas fa-user-graduate mr-2"></i>শিক্ষার্থীর তথ্য
                  </h5>
                  <div class="row">
                    <div class="col-md-6">
                      <p class="mb-2"><strong>নাম (বাংলা):</strong> {{ $student->student_name_bn }}</p>
                      <p class="mb-2"><strong>নাম (English):</strong> {{ $student->student_name_en }}</p>
                      <p class="mb-2"><strong>Student ID:</strong> {{ $student->student_id }}</p>
                    </div>
                    <div class="col-md-6">
                      <p class="mb-2"><strong>ক্লাস:</strong> {{ $enrollment->class->name }}</p>
                      <p class="mb-2"><strong>শাখা:</strong> {{ $enrollment->section->name ?? '—' }}</p>
                      <p class="mb-2"><strong>গ্রুপ:</strong> {{ $enrollment->group->name ?? '—' }}</p>
                      <p class="mb-2"><strong>রোল নং:</strong> {{ $enrollment->roll_no }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="text-center">
                <img src="{{ $student->photo ? asset('storage/students/'.$student->photo) : asset('images/default-avatar.png') }}" 
                     alt="Photo" 
                     class="img-thumbnail" 
                     style="width:180px; height:220px; object-fit:cover;">
              </div>
            </div>
          </div>

          <!-- Subject Selection Form -->
          <form method="POST" action="{{ route('principal.institute.admissions.enrollment.subjects.store', [$school, $student]) }}">
            @csrf
            
            <div class="alert alert-info">
              <i class="fas fa-info-circle mr-2"></i>
              <strong>নির্দেশনা:</strong> শিক্ষার্থীর জন্য প্রয়োজনীয় সকল বিষয় নির্বাচন করুন। বাধ্যতামূলক বিষয়গুলো অবশ্যই নির্বাচন করতে হবে।
            </div>

            @if($availableSubjects->isEmpty())
              <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                এই ক্লাসের জন্য কোনো বিষয় সেটআপ করা হয়নি। অনুগ্রহ করে প্রথমে বিষয় যুক্ত করুন।
              </div>
            @else
              <div class="card">
                <div class="card-header bg-light">
                  <h5 class="mb-0">উপলব্ধ বিষয়সমূহ</h5>
                </div>
                <div class="card-body">
                  <!-- Compulsory Subjects -->
                  @php($compulsorySubjects = $availableSubjects->where('offered_mode', 'compulsory'))
                  @if($compulsorySubjects->isNotEmpty())
                    <h6 class="text-danger mb-3">
                      <i class="fas fa-star mr-2"></i>বাধ্যতামূলক বিষয় (Compulsory)
                    </h6>
                    <div class="row mb-4">
                      @foreach($compulsorySubjects as $classSubject)
                        <div class="col-md-4 mb-3">
                          <div class="card border-danger h-100">
                            <div class="card-body">
                              <div class="custom-control custom-checkbox">
                                <input type="checkbox" 
                                       class="custom-control-input" 
                                       name="subjects[]" 
                                       value="{{ $classSubject->subject->id }}"
                                       id="subject_{{ $classSubject->subject->id }}"
                                       {{ in_array($classSubject->subject->id, $selectedSubjects) ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold" for="subject_{{ $classSubject->subject->id }}">
                                  {{ $classSubject->subject->name }}
                                  @if($classSubject->subject->code)
                                    <small class="text-muted">({{ $classSubject->subject->code }})</small>
                                  @endif
                                </label>
                              </div>
                              @if($classSubject->full_mark || $classSubject->pass_mark)
                                <small class="text-muted d-block mt-2">
                                  পূর্ণমান: {{ $classSubject->full_mark ?? '—' }}, 
                                  পাশ নম্বর: {{ $classSubject->pass_mark ?? '—' }}
                                </small>
                              @endif
                            </div>
                          </div>
                        </div>
                      @endforeach
                    </div>
                  @endif

                  <!-- Optional Subjects -->
                  @php($optionalSubjects = $availableSubjects->where('offered_mode', 'optional'))
                  @if($optionalSubjects->isNotEmpty())
                    <h6 class="text-primary mb-3">
                      <i class="fas fa-book-open mr-2"></i>ঐচ্ছিক বিষয় (Optional)
                    </h6>
                    <div class="row mb-4">
                      @foreach($optionalSubjects as $classSubject)
                        <div class="col-md-4 mb-3">
                          <div class="card border-primary h-100">
                            <div class="card-body">
                              <div class="custom-control custom-checkbox">
                                <input type="checkbox" 
                                       class="custom-control-input" 
                                       name="subjects[]" 
                                       value="{{ $classSubject->subject->id }}"
                                       id="subject_{{ $classSubject->subject->id }}"
                                       {{ in_array($classSubject->subject->id, $selectedSubjects) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="subject_{{ $classSubject->subject->id }}">
                                  {{ $classSubject->subject->name }}
                                  @if($classSubject->subject->code)
                                    <small class="text-muted">({{ $classSubject->subject->code }})</small>
                                  @endif
                                </label>
                              </div>
                              @if($classSubject->full_mark || $classSubject->pass_mark)
                                <small class="text-muted d-block mt-2">
                                  পূর্ণমান: {{ $classSubject->full_mark ?? '—' }}, 
                                  পাশ নম্বর: {{ $classSubject->pass_mark ?? '—' }}
                                </small>
                              @endif
                            </div>
                          </div>
                        </div>
                      @endforeach
                    </div>
                  @endif

                  <!-- Elective section omitted: not present in current schema/mappings -->
                </div>
              </div>

              <div class="text-center mt-4">
                <a href="{{ route('principal.institute.admissions.enrollment.index', $school) }}" class="btn btn-secondary btn-lg mr-3">
                  <i class="fas fa-arrow-left mr-2"></i>পরে করব
                </a>
                <button type="submit" class="btn btn-success btn-lg">
                  <i class="fas fa-save mr-2"></i>বিষয় সংরক্ষণ করুন
                </button>
              </div>
            @endif
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
// Count selected subjects
function updateSubjectCount() {
  const checked = document.querySelectorAll('input[name="subjects[]"]:checked').length;
  console.log('Selected subjects:', checked);
}

document.querySelectorAll('input[name="subjects[]"]').forEach(checkbox => {
  checkbox.addEventListener('change', updateSubjectCount);
});
</script>
@endpush

@endsection
