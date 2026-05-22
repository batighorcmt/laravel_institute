@extends('layouts.admin')
@section('title','প্রত্যয়নপত্রের তালিকা')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">প্রত্যয়নপত্রের তালিকা</h4>
  <a href="{{ route('principal.institute.documents.prottayon.index', $school) }}" class="btn btn-primary">নতুন তৈরি</a>
</div>

<div id="filtersBar" class="card card-body p-3 mb-3" style="background:#f8fafc;">
  <form class="form-inline" method="get" style="display:flex; flex-wrap:wrap; gap:.5rem; align-items:flex-end;">
    <div class="form-group mr-2 mb-2" style="min-width: 200px;">
      <label class="small d-block mb-1">অনুসন্ধান (নাম / আইডি / স্মারক নং)</label>
      <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm w-100" placeholder="অনুসন্ধান...">
    </div>
    
    <div class="form-group mr-2 mb-2" style="min-width: 150px;">
      <label class="small d-block mb-1">শ্রেণি</label>
      <select name="class_id" class="form-control form-control-sm w-100">
        <option value="">সকল শ্রেণি</option>
        @foreach($classes as $cls)
          <option value="{{ $cls->id }}" {{ request('class_id') == $cls->id ? 'selected' : '' }}>{{ $cls->bangla_name ?: $cls->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group mr-2 mb-2" style="min-width: 150px;">
      <label class="small d-block mb-1">শাখা</label>
      <select name="section_id" class="form-control form-control-sm w-100">
        <option value="">সকল শাখা</option>
        @foreach($sections as $sec)
          <option value="{{ $sec->id }}" {{ request('section_id') == $sec->id ? 'selected' : '' }}>{{ $sec->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="form-group mr-2 mb-2">
      <label class="small d-block mb-1">তারিখ হতে</label>
      <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control form-control-sm">
    </div>

    <div class="form-group mr-2 mb-2">
      <label class="small d-block mb-1">তারিখ পর্যন্ত</label>
      <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control form-control-sm">
    </div>

    <div class="form-group mb-2">
      <button type="submit" class="btn btn-sm btn-primary">ফিল্টার করুন</button>
      <a href="{{ route('principal.institute.documents.prottayon.history', $school) }}" class="btn btn-sm btn-outline-danger ml-1">রিসেট</a>
    </div>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-striped mb-0">
      <thead>
        <tr>
          <th>স্মারক নং</th>
          <th>তারিখ</th>
          <th>শিক্ষার্থী</th>
          <th>শ্রেণি</th>
          <th>রোল নং</th>
          <th>ধরন</th>
          <th>কর্ম</th>
        </tr>
      </thead>
      <tbody>
        @forelse($records as $rec)
          @php
              $classId = $rec->data['class_id'] ?? null;
              $className = '-';
              if ($classId && isset($allClasses[$classId])) {
                  $classObj = $allClasses[$classId];
                  $className = $classObj->bangla_name ?: $classObj->name;
              } else {
                  // Fallback to enrollment class
                  $enrollment = $rec->student?->enrollments->where('status', 'active')->first() ?? $rec->student?->enrollments->first();
                  if ($enrollment && $enrollment->class) {
                      $className = $enrollment->class->bangla_name ?: $enrollment->class->name;
                  }
              }
              
              $rollNo = '-';
              if ($rec->student) {
                  $enrollment = null;
                  if ($classId) {
                      $enrollment = $rec->student->enrollments->firstWhere('class_id', $classId);
                  }
                  if (!$enrollment) {
                      $enrollment = $rec->student->enrollments->where('status', 'active')->first() ?? $rec->student->enrollments->first();
                  }
                  if ($enrollment) {
                      $rollNo = $enrollment->roll_no;
                  }
              }
          @endphp
          <tr>
            <td>{{ $rec->memo_no }}</td>
            <td>{{ $rec->issued_at?->format('d-m-Y') }}</td>
            <td>
              {{ $rec->student?->full_name }}
              @if($rec->student?->student_id)
                <br><small class="text-muted">{{ $rec->student->student_id }}</small>
              @endif
            </td>
            <td>{{ $className }}</td>
            <td>{{ $rollNo }}</td>
            <td>{{ $rec->data['attestation_type'] ?? '-' }}</td>
            <td>
              <a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ route('principal.institute.documents.prottayon.print', [$school, $rec->id]) }}">প্রিন্ট</a>
              <a class="btn btn-sm btn-outline-primary" href="{{ route('principal.institute.documents.prottayon.edit', [$school, $rec->id]) }}">সম্পাদনা</a>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-center text-muted">কোনো রেকর্ড নেই</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $records->links() }}</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.querySelector('select[name="class_id"]');
    const sectionSelect = document.querySelector('select[name="section_id"]');

    if (classSelect && sectionSelect) {
        classSelect.addEventListener('change', function() {
            const classId = this.value;
            sectionSelect.innerHTML = '<option value="">সকল শাখা</option>';

            if (classId) {
                const url = `{{ route('principal.institute.meta.sections', $school) }}?class_id=${classId}`;
                fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(sections => {
                    sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section.id;
                        option.textContent = section.name;
                        sectionSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error loading sections:', error));
            }
        });
    }
});
</script>
@endpush
