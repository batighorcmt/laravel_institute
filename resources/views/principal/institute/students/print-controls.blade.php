@extends('layouts.admin')
@section('title','শিক্ষার্থী তালিকা প্রিন্ট কন্ট্রোলস')

@push('styles')
<style>
  .sortable-columns {
    list-style: none;
    padding: 0;
    margin: 0;
  }
  .sortable-columns .column-item {
    padding: 8px 12px;
    margin-bottom: 4px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    cursor: move;
    display: flex;
    align-items: center;
    transition: all 0.2s;
  }
  .sortable-columns .column-item:hover {
    background: #e9ecef;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  .sortable-columns .column-item.sortable-ghost {
    opacity: 0.4;
    background: #007bff;
    color: white;
  }
  .sortable-columns .column-item .drag-handle {
    margin-right: 8px;
    color: #6c757d;
  }
  .sortable-columns .column-item label {
    margin: 0;
    flex: 1;
    cursor: move;
  }
</style>
@endpush

@section('content')
@php
  $years = $years ?? collect();
  $currentYear = $currentYear ?? null;
  $selectedYear = $selectedYear ?? null;
  $selectedYearId = $selectedYearId ?? 0;
  $cols = is_array($cols ?? null) ? $cols : [];
  $lang = $lang ?? 'bn';
  $u = auth()->user();
  $classes = $classes ?? collect();
  $sections = $sections ?? collect();
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="m-0">প্রিন্ট কন্ট্রোলস - {{ $school->name }}</h1>
  <div>
    <a href="{{ route('principal.institute.students.index', $school) }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> তালিকায় ফিরে যান</a>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h5 class="m-0">প্রিন্ট সেটিংস</h5>
  </div>
  <div class="card-body">
    <form method="get" action="{{ route('principal.institute.students.print-preview', $school) }}" id="printControlsForm" class="needs-validation" novalidate>
      <div class="row">
        <div class="col-12 col-lg-5 mb-3">
          <div class="border rounded p-3 h-100">
            <h6 class="mb-2">কলাম নির্বাচন ও সাজান (টেনে সাজাতে পারবেন)</h6>
            @php
              $allColumns = [
                'serial' => 'ক্রমিক',
                'student_id' => 'আইডি নং',
                'name' => 'নাম (বাংলা/ইংরেজি)',
                'father' => 'পিতার নাম',
                'mother' => 'মাতার নাম',
                'date_of_birth' => 'জন্ম তারিখ',
                'gender' => 'লিঙ্গ',
                'religion' => 'ধর্ম',
                'blood_group' => 'রক্তের গ্রুপ',
                'class' => 'শ্রেণি',
                'section' => 'শাখা',
                'roll' => 'রোল',
                'group' => 'গ্রুপ',
                'guardian_name' => 'অভিভাবকের নাম',
                'guardian_relation' => 'অভিভাবকের সম্পর্ক',
                'mobile' => 'মোবাইল নং',
                'present_village' => 'বর্তমান গ্রাম',
                'present_para_moholla' => 'বর্তমান পাড়া/মহল্লা',
                'present_post_office' => 'বর্তমান ডাকঘর',
                'present_upazilla' => 'বর্তমান উপজেলা',
                'present_district' => 'বর্তমান জেলা',
                'permanent_village' => 'স্থায়ী গ্রাম',
                'permanent_para_moholla' => 'স্থায়ী পাড়া/মহল্লা',
                'permanent_post_office' => 'স্থায়ী ডাকঘর',
                'permanent_upazilla' => 'স্থায়ী উপজেলা',
                'permanent_district' => 'স্থায়ী জেলা',
                'admission_date' => 'ভর্তির তারিখ',
                'previous_school' => 'পূর্ববর্তী স্কুল',
                'pass_year' => 'পাসের বছর',
                'previous_result' => 'পূর্ববর্তী ফলাফল',
                'status' => 'স্ট্যাটাস',
                'subjects' => 'বিষয়সমূহ',
                'photo' => 'ছবি',
              ];
              
              // Reorder columns based on current selection
              $orderedColumns = [];
              foreach($cols as $colKey) {
                if (isset($allColumns[$colKey])) {
                  $orderedColumns[$colKey] = $allColumns[$colKey];
                  unset($allColumns[$colKey]);
                }
              }
              // Add remaining columns
              $orderedColumns = array_merge($orderedColumns, $allColumns);
            @endphp
            
            <div class="mb-2">
              <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllColumns">সব নির্বাচন করুন</button>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllColumns">সব বাতিল করুন</button>
            </div>
            
            <div style="max-height: 400px; overflow-y: auto;">
              <ul class="sortable-columns" id="columnsList">
                @foreach($orderedColumns as $key => $label)
                  <li class="column-item" data-column="{{ $key }}">
                    <span class="drag-handle"><i class="fas fa-grip-vertical"></i></span>
                    <div class="custom-control custom-checkbox flex-grow-1">
                      <input type="checkbox" class="custom-control-input column-checkbox" id="col-{{ $key }}" name="cols[]" value="{{ $key }}" {{ in_array($key, $cols) ? 'checked' : '' }}>
                      <label class="custom-control-label" for="col-{{ $key }}">{{ $label }}</label>
                    </div>
                  </li>
                @endforeach
              </ul>
            </div>
            <small class="text-muted d-block mt-2">
              <i class="fas fa-info-circle"></i> কলামগুলো টেনে সাজাতে পারবেন। ছবি যুক্ত করলে পেজ বড় হতে পারে।
            </small>
          </div>
        </div>

        <div class="col-12 col-lg-3 mb-3">
          <div class="border rounded p-3 h-100">
            <h6 class="mb-2">সাজানোর নিয়ম</h6>
            <div class="form-group">
              <label class="small mb-1">সাজান</label>
              <select name="sort_by" class="form-control form-control-sm">
                <option value="student_id" {{ request('sort_by') == 'student_id' ? 'selected' : '' }}>আইডি নং অনুসারে</option>
                <option value="class" {{ request('sort_by') == 'class' ? 'selected' : '' }}>শ্রেণি অনুসারে</option>
                <option value="section" {{ request('sort_by') == 'section' ? 'selected' : '' }}>শাখা অনুসারে</option>
                <option value="roll" {{ request('sort_by') == 'roll' ? 'selected' : '' }}>রোল নং অনুসারে</option>
                <option value="village" {{ request('sort_by') == 'village' ? 'selected' : '' }}>গ্রাম অনুসারে</option>
              </select>
            </div>
            
            <div class="form-group">
              <label class="small mb-1">ক্রম</label>
              <select name="sort_order" class="form-control form-control-sm">
                <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>ছোট থেকে বড়</option>
                <option value="desc" {{ request('sort_order') == 'desc' ? 'selected' : '' }}>বড় থেকে ছোট</option>
              </select>
            </div>

            <hr>
            <h6 class="mb-2">ভাষা</h6>
            <div class="custom-control custom-radio">
              <input class="custom-control-input" type="radio" id="lang-bn" name="lang" value="bn" {{ $lang==='bn' ? 'checked' : '' }}>
              <label class="custom-control-label" for="lang-bn">বাংলা</label>
            </div>
            <div class="custom-control custom-radio">
              <input class="custom-control-input" type="radio" id="lang-en" name="lang" value="en" {{ $lang==='en' ? 'checked' : '' }}>
              <label class="custom-control-label" for="lang-en">English</label>
            </div>

            <hr>
            <h6 class="mb-2">পেজ ওরিয়েন্টেশন</h6>
            <div class="custom-control custom-radio">
              <input class="custom-control-input" type="radio" id="orientation-portrait" name="orientation" value="portrait" {{ request('orientation', 'portrait') === 'portrait' ? 'checked' : '' }}>
              <label class="custom-control-label" for="orientation-portrait">Portrait (লম্বা)</label>
            </div>
            <div class="custom-control custom-radio">
              <input class="custom-control-input" type="radio" id="orientation-landscape" name="orientation" value="landscape" {{ request('orientation') === 'landscape' ? 'checked' : '' }}>
              <label class="custom-control-label" for="orientation-landscape">Landscape (চওড়া)</label>
            </div>

            <hr>
            <h6 class="mb-2">ইয়ার নির্বাচন</h6>
            <select name="year_id" class="form-control form-control-sm">
              <option value="">-- বছর নির্বাচন --</option>
              @foreach($years as $y)
                <option value="{{ $y->id }}" {{ (int)$selectedYearId===$y->id?'selected':'' }}>{{ $y->name }}</option>
              @endforeach
            </select>

            <div class="form-group mt-3 mb-0">
              <label class="small mb-1">ফল সীমা (max 5000)</label>
              <input type="number" class="form-control form-control-sm" name="limit" min="1" max="5000" value="{{ request('limit', 1000) }}">
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-4 mb-3">
          <div class="border rounded p-3 h-100">
            <h6 class="mb-2">ফিল্টারসমূহ</h6>

            <div class="form-group mb-2">
              <input type="text" name="q" class="form-control form-control-sm" placeholder="নাম/আইডি সার্চ..." value="{{ request('q') }}">
            </div>

            <div class="form-row">
              <div class="form-group col-6">
                <label class="small mb-1">শ্রেণি</label>
                <select name="class_id" id="classFilter" class="form-control form-control-sm">
                  <option value="">-- শ্রেণি নির্বাচন --</option>
                  @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group col-6">
                <label class="small mb-1">শাখা</label>
                <select name="section_id" id="sectionFilter" class="form-control form-control-sm">
                  <option value="">-- শাখা নির্বাচন --</option>
                  @foreach($sections as $section)
                    <option value="{{ $section->id }}" 
                            data-class-id="{{ $section->class_id }}"
                            {{ request('section_id') == $section->id ? 'selected' : '' }}>
                      {{ $section->name }}
                    </option>
                  @endforeach
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-6">
                <label class="small mb-1">গ্রুপ</label>
                <select name="group_id" class="form-control form-control-sm">
                  <option value="">-- গ্রুপ নির্বাচন --</option>
                  @foreach($school->groups ?? [] as $group)
                    <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-group col-6">
                <label class="small mb-1">স্ট্যাটাস</label>
                <select name="status" class="form-control form-control-sm">
                  <option value="">-- স্ট্যাটাস নির্বাচন --</option>
                  <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>সক্রিয়</option>
                  <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>নিষ্ক্রিয়</option>
                  <option value="graduated" {{ request('status') == 'graduated' ? 'selected' : '' }}>গ্র্যাজুয়েট</option>
                  <option value="transferred" {{ request('status') == 'transferred' ? 'selected' : '' }}>ট্রান্সফার্ড</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-6">
                <label class="small mb-1">লিঙ্গ</label>
                <select name="gender" class="form-control form-control-sm">
                  <option value="">-- লিঙ্গ নির্বাচন --</option>
                  <option value="male" {{ request('gender') == 'male' ? 'selected' : '' }}>পুরুষ</option>
                  <option value="female" {{ request('gender') == 'female' ? 'selected' : '' }}>মহিলা</option>
                </select>
              </div>
              <div class="form-group col-6">
                <label class="small mb-1">ধর্ম</label>
                <select name="religion" class="form-control form-control-sm">
                  <option value="">-- ধর্ম নির্বাচন --</option>
                  <option value="Islam" {{ request('religion') == 'Islam' ? 'selected' : '' }}>ইসলাম</option>
                  <option value="Hindu" {{ request('religion') == 'Hindu' ? 'selected' : '' }}>হিন্দু</option>
                  <option value="Buddhist" {{ request('religion') == 'Buddhist' ? 'selected' : '' }}>বৌদ্ধ</option>
                  <option value="Christian" {{ request('religion') == 'Christian' ? 'selected' : '' }}>খ্রিস্টান</option>
                  <option value="Other" {{ request('religion') == 'Other' ? 'selected' : '' }}>অন্যান্য</option>
                </select>
              </div>
            </div>

            @php
              $villages = \App\Models\Student::forSchool($school->id)->whereNotNull('present_village')->distinct()->pluck('present_village')->sort()->unique();
            @endphp
            <div class="form-group mb-0">
              <label class="small mb-1">গ্রাম</label>
              <select name="village" class="form-control form-control-sm">
                <option value="">-- গ্রাম নির্বাচন --</option>
                @foreach($villages as $village)
                  <option value="{{ $village }}" {{ request('village') == $village ? 'selected' : '' }}>{{ $village }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center">
        <div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-eye mr-1"></i> প্রিভিউ</button>
          <a class="btn btn-outline-danger ml-2" href="{{ route('principal.institute.students.print-controls', $school) }}"><i class="fas fa-undo mr-1"></i> রিসেট</a>
        </div>
        <div class="text-muted small">
          প্রিভিউ পেজে গিয়ে Print চাপুন
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<!-- Sortable.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Initialize Sortable for column reordering
  const columnsList = document.getElementById('columnsList');
  if (columnsList) {
    new Sortable(columnsList, {
      animation: 150,
      handle: '.column-item',
      ghostClass: 'sortable-ghost',
      onEnd: function(evt) {
        // Update the order of checkboxes in the form
        updateColumnOrder();
      }
    });
  }

  // Update column order in form submission
  function updateColumnOrder() {
    const items = columnsList.querySelectorAll('.column-item');
    items.forEach((item, index) => {
      const checkbox = item.querySelector('input[type="checkbox"]');
      if (checkbox) {
        // Update the order by moving the input in DOM
        // This ensures the form submission order matches visual order
      }
    });
  }

  // Select all columns
  document.getElementById('selectAllColumns')?.addEventListener('click', function() {
    document.querySelectorAll('.column-checkbox').forEach(cb => cb.checked = true);
  });

  // Deselect all columns
  document.getElementById('deselectAllColumns')?.addEventListener('click', function() {
    document.querySelectorAll('.column-checkbox').forEach(cb => cb.checked = false);
  });

  // Filter sections by selected class
  const classFilter = document.getElementById('classFilter');
  const sectionFilter = document.getElementById('sectionFilter');
  
  if (classFilter && sectionFilter) {
    classFilter.addEventListener('change', function() {
      const selectedClassId = this.value;
      const sectionOptions = sectionFilter.querySelectorAll('option');
      
      sectionOptions.forEach(option => {
        if (option.value === '') {
          // Keep the default "-- শাখা নির্বাচন --" option
          option.style.display = '';
          return;
        }
        
        const optionClassId = option.getAttribute('data-class-id');
        
        if (!selectedClassId || optionClassId === selectedClassId) {
          option.style.display = '';
        } else {
          option.style.display = 'none';
        }
      });
      
      // Reset section selection if current selection is not visible
      const currentSelection = sectionFilter.value;
      if (currentSelection) {
        const currentOption = sectionFilter.querySelector(`option[value="${currentSelection}"]`);
        if (currentOption && currentOption.style.display === 'none') {
          sectionFilter.value = '';
        }
      }
    });
    
    // Trigger on page load to filter sections based on pre-selected class
    if (classFilter.value) {
      classFilter.dispatchEvent(new Event('change'));
    }
  }
});
</script>
@endpush
