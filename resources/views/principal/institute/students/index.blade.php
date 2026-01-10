@extends('layouts.admin')
@section('title','শিক্ষার্থী তালিকা')
@section('content')
@php
  $years = $years ?? collect();
  $currentYear = $currentYear ?? null;
  $selectedYear = $selectedYear ?? null;
  $selectedYearId = $selectedYearId ?? 0;
  $yearLabel = $selectedYear ? $selectedYear->name : ($currentYear ? $currentYear->name : 'বর্ষ নির্ধারিত নয়');
@endphp
<div class="d-flex flex-column flex-md-row justify-content-between mb-3">
  <h1 class="m-0 mb-2 mb-md-0">শিক্ষার্থী তালিকা - {{ $school->name }}</h1>
  <div class="d-flex flex-column flex-sm-row">
    <a href="{{ route('principal.institute.students.create',$school) }}" class="btn btn-success mb-1 mb-sm-0 mr-sm-2"><i class="fas fa-user-plus mr-1"></i> নতুন শিক্ষার্থী</a>
    <a href="{{ route('principal.institute.students.bulk',$school) }}" class="btn btn-outline-primary mb-1 mb-sm-0 mr-sm-2"><i class="fas fa-file-import mr-1"></i> Bulk student add</a>
    <a href="{{ route('principal.institute.students.print-controls',$school) }}" class="btn btn-outline-secondary"><i class="fas fa-print mr-1"></i> প্রিন্ট</a>
  </div>
</div>
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
  <div class="d-flex align-items-center mb-2 mb-sm-0">
    <label class="mr-2 mb-0">প্রতি পৃষ্ঠায়:</label>
    <select name="per_page" class="form-control form-control-sm" style="width: auto;" onchange="changePerPage(this.value)">
      <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
      <option value="20" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20</option>
      <option value="50" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50</option>
      <option value="100" {{ request('per_page', 10) == 100 ? 'selected' : '' }}>100</option>
      <option value="500" {{ request('per_page', 10) == 500 ? 'selected' : '' }}>500</option>
    </select>
  </div>
</div>
<button class="btn btn-outline-secondary d-md-none mb-2" type="button" data-toggle="collapse" data-target="#filtersCollapse" aria-expanded="false" aria-controls="filtersCollapse">
  ফিল্টার দেখুন/লুকান
</button>
<form id="filtersCollapse" class="form-inline mb-3 collapse" method="get">
  <div class="position-relative mr-2" style="min-width: 250px;">
    <input type="text" id="student-search" name="q" value="{{ $q }}" class="form-control" placeholder="নাম / আইডি সার্চ..." autocomplete="off">
    <div id="search-results" class="position-absolute bg-white border rounded shadow-sm" style="top: 100%; left: 0; right: 0; z-index: 1000; display: none; max-height: 300px; overflow-y: auto;"></div>
  </div>
  <select name="year_id" class="form-control mr-2">
    <option value="">-- বছর নির্বাচন --</option>
    @foreach(($years ?? []) as $y)
      <option value="{{ $y->id }}" {{ (int)($selectedYearId ?? 0)===$y->id?'selected':'' }}>{{ $y->name }}</option>
    @endforeach
  </select>
  <select name="class_id" class="form-control mr-2">
    <option value="">-- শ্রেণি নির্বাচন --</option>
    @foreach($school->classes ?? [] as $class)
      <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
    @endforeach
  </select>
  <select name="section_id" class="form-control mr-2">
    <option value="">-- শাখা নির্বাচন --</option>
    @foreach($school->sections ?? [] as $section)
      <option value="{{ $section->id }}" {{ request('section_id') == $section->id ? 'selected' : '' }}>{{ $section->name }}</option>
    @endforeach
  </select>
  <select name="group_id" class="form-control mr-2">
    <option value="">-- গ্রুপ নির্বাচন --</option>
    @foreach($school->groups ?? [] as $group)
      <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
    @endforeach
  </select>
  <select name="status" class="form-control mr-2">
    <option value="">-- স্ট্যাটাস নির্বাচন --</option>
    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>সক্রিয়</option>
    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>নিষ্ক্রিয়</option>
    <option value="graduated" {{ request('status') == 'graduated' ? 'selected' : '' }}>গ্র্যাজুয়েট</option>
    <option value="transferred" {{ request('status') == 'transferred' ? 'selected' : '' }}>ট্রান্সফার্ড</option>
  </select>
  <select name="gender" class="form-control mr-2">
    <option value="">-- লিঙ্গ নির্বাচন --</option>
    <option value="male" {{ request('gender') == 'male' ? 'selected' : '' }}>পুরুষ</option>
    <option value="female" {{ request('gender') == 'female' ? 'selected' : '' }}>মহিলা</option>
  </select>
  <select name="religion" class="form-control mr-2">
    <option value="">-- ধর্ম নির্বাচন --</option>
    <option value="Islam" {{ request('religion') == 'Islam' ? 'selected' : '' }}>ইসলাম</option>
    <option value="Hindu" {{ request('religion') == 'Hindu' ? 'selected' : '' }}>হিন্দু</option>
    <option value="Buddhist" {{ request('religion') == 'Buddhist' ? 'selected' : '' }}>বৌদ্ধ</option>
    <option value="Christian" {{ request('religion') == 'Christian' ? 'selected' : '' }}>খ্রিস্টান</option>
    <option value="Other" {{ request('religion') == 'Other' ? 'selected' : '' }}>অন্যান্য</option>
  </select>
  <select name="village" class="form-control mr-2">
    <option value="">-- গ্রাম নির্বাচন --</option>
    @php
      $villages = \App\Models\Student::forSchool($school->id)->whereNotNull('present_village')->distinct()->pluck('present_village')->sort()->unique();
    @endphp
    @foreach($villages as $village)
      <option value="{{ $village }}" {{ request('village') == $village ? 'selected' : '' }}>{{ $village }}</option>
    @endforeach
  </select>
  <button class="btn btn-outline-secondary mr-2">ফিল্টার</button>
  <a href="{{ route('principal.institute.students.index', $school) }}" class="btn btn-outline-danger">রিসেট</a>
</form>
<div class="table-responsive">
  <table class="table table-bordered table-sm">
    <thead class="thead-light">
      <tr>
        <th style="width:60px">ক্রমিক</th>
        <th>আইডি নং</th>
        <th>নাম</th>
        <th>পিতার নাম</th>
        <th>শ্রেণি</th>
        <th>শাখা</th>
        <th>রোল</th>
        <th class="d-none d-md-table-cell">গ্রুপ</th>
        <th class="d-none d-lg-table-cell">মোবাইল নং</th>
        <th>স্ট্যাটাস</th>
        <th style="width:80px" class="d-none d-sm-table-cell">ছবি</th>
        <th>বিষয়সমূহ ({{ $yearLabel }})</th>
        <th style="width:120px">অ্যাকশন</th>
      </tr>
    </thead>
    <tbody>
    @foreach($students as $idx=>$stu)
      @php
        $en = $stu->enrollments->first();
        $subsHtml = '';
        if ($en) {
          $subs = collect($en->subjects);
          $subsSorted = $subs->sortBy(function($ss){
            $code = optional($ss->subject)->code;
            $num  = $code ? intval(preg_replace('/\D+/', '', $code)) : PHP_INT_MAX;
            return [ $ss->is_optional ? 1 : 0, $num, $code ]; // optional last, then by numeric code
          })->values();
          $parts = [];
          foreach ($subsSorted as $ss) {
            $code = optional($ss->subject)->code;
            if (!$code) { continue; }
            if ($ss->is_optional) {
              $parts[] = '<span class="text-primary">'.e($code).'</span>';
            } else {
              $parts[] = e($code);
            }
          }
          $subsHtml = implode(', ', $parts);
        }
      @endphp
      <tr>
        <td>{{ $students->firstItem() + $idx }}</td>
        <td>{{ $stu->student_id }}</td>
        <td>{{ $stu->full_name }}</td>
        <td>{{ $stu->father_name_bn ?: $stu->father_name }}</td>
        <td>{{ $en? $en->class?->name : '-' }}</td>
        <td>{{ $en? $en->section?->name : '-' }}</td>
        <td>{{ $en? $en->roll_no : '-' }}</td>
        <td class="d-none d-md-table-cell">{{ $en? $en->group?->name : '-' }}</td>
        <td class="d-none d-lg-table-cell">{{ $stu->guardian_phone }}</td>
        <td>
          @php
            $st = $stu->status;
            $badgeClass = 'badge ' . ($st === 'active' ? 'badge-success' : ($st === 'inactive' ? 'badge-secondary' : ($st === 'graduated' ? 'badge-info' : 'badge-warning')));
          @endphp
          <span class="{{ $badgeClass }}">{{ $st }}</span>
        </td>
        <td class="text-center d-none d-sm-table-cell">
          <img src="{{ $stu->photo_url }}" alt="photo" style="width:40px;height:40px;object-fit:cover;border-radius:10%;">
        </td>
        <td class="small d-none d-xl-table-cell">{!! $subsHtml ?: '-' !!}</td>
        <td class="text-nowrap">
          <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle student-action-dd" type="button" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">
              Action
            </button>
            <div class="dropdown-menu dropdown-menu-right">
              <a class="dropdown-item" href="{{ route('principal.institute.students.show',[$school,$stu]) }}"><i class="fas fa-id-card mr-1"></i> প্রোফাইল</a>
              <a class="dropdown-item" href="{{ route('principal.institute.students.edit',[$school,$stu]) }}"><i class="fas fa-edit mr-1"></i> সম্পাদনা</a>
              @if($en)
                <a class="dropdown-item" href="{{ route('principal.institute.enrollments.subjects.edit',[$school,$en]) }}"><i class="fas fa-book mr-1"></i> বিষয় নির্বাচন</a>
              @endif
              <div class="dropdown-divider"></div>
              <form action="{{ route('principal.institute.students.toggle-status',[$school,$stu]) }}" method="post" class="px-3 py-1">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-link p-0 m-0 align-baseline">
                  <i class="fas fa-toggle-{{ $stu->status==='active'?'on':'off' }} mr-1"></i>
                  {{ $stu->status==='active' ? 'নিষ্ক্রিয় করুন' : 'সক্রিয় করুন' }}
                </button>
              </form>
            </div>
          </div>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-2">
  <div class="mb-2 mb-md-0">মোট {{ $students->total() }}টির মধ্যে {{ $students->firstItem() }}–{{ $students->lastItem() }}</div>
  <div class="pagination-mobile">
    @if($students->hasPages())
      <nav aria-label="Student pagination">
        <ul class="pagination pagination-sm justify-content-center justify-content-md-end mb-0">
          {{-- Previous Page Link --}}
          @if ($students->onFirstPage())
            <li class="page-item disabled">
              <span class="page-link">&laquo;</span>
            </li>
          @else
            <li class="page-item">
              <a class="page-link" href="{{ $students->previousPageUrl() }}" rel="prev">&laquo;</a>
            </li>
          @endif

          {{-- Compact Pagination Logic --}}
          @php
            $currentPage = $students->currentPage();
            $lastPage = $students->lastPage();
            $showRange = 2; // Show 2 pages on each side of current page
            $pages = []; // Initialize pages array

            // Always show first page
            if ($currentPage > $showRange + 2) {
              $pages[] = 1;
              if ($currentPage > $showRange + 3) {
                $pages[] = '...';
              }
            }

            // Show pages around current page
            for ($i = max(1, $currentPage - $showRange); $i <= min($lastPage, $currentPage + $showRange); $i++) {
              $pages[] = $i;
            }

            // Always show last page
            if ($currentPage < $lastPage - $showRange - 1) {
              if ($currentPage < $lastPage - $showRange - 2) {
                $pages[] = '...';
              }
              $pages[] = $lastPage;
            }
          @endphp

          @foreach($pages as $page)
            @if($page === '...')
              <li class="page-item disabled">
                <span class="page-link">{{ $page }}</span>
              </li>
            @elseif($page == $currentPage)
              <li class="page-item active">
                <span class="page-link">{{ $page }}</span>
              </li>
            @else
              <li class="page-item">
                <a class="page-link" href="{{ $students->url($page) }}">{{ $page }}</a>
              </li>
            @endif
          @endforeach

          {{-- Next Page Link --}}
          @if ($students->hasMorePages())
            <li class="page-item">
              <a class="page-link" href="{{ $students->nextPageUrl() }}" rel="next">&raquo;</a>
            </li>
          @else
            <li class="page-item disabled">
              <span class="page-link">&raquo;</span>
            </li>
          @endif
        </ul>
      </nav>
    @endif
  </div>
</div>
@endsection
@push('styles')
<style>
/* Mobile-friendly pagination styles */
.pagination-mobile .pagination {
  margin: 0;
}

.pagination-mobile .page-link {
  padding: 0.375rem 0.5rem;
  font-size: 0.875rem;
  line-height: 1.25;
  border-radius: 0.2rem;
}

@media (max-width: 576px) {
  .pagination-mobile .pagination {
    flex-wrap: wrap;
  }

  .pagination-mobile .page-item {
    flex: 0 0 auto;
    margin: 0.125rem;
  }

  .pagination-mobile .page-link {
    padding: 0.25rem 0.375rem;
    font-size: 0.75rem;
    min-width: 2rem;
  }

  /* Compact pagination already handles ellipsis, no need for additional hiding */
}

/* Responsive filter form */
@media (max-width: 768px) {
  .form-inline .form-control {
    margin-bottom: 0.5rem;
    width: 100%;
  }

  .form-inline .btn {
    margin-bottom: 0.5rem;
  }

  .form-inline {
    flex-direction: column;
    align-items: stretch;
  }

  .form-inline .mr-2 {
    margin-right: 0 !important;
  }
}
</style>
@endpush
@push('scripts')
<script>
(function(){
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

  // Ensure filters form is collapsed on mobile and visible on md+ screens
  const filtersCollapse = document.getElementById('filtersCollapse');
  let resizeTO;
  function syncFiltersCollapse() {
    if (!filtersCollapse) return;
    if (window.innerWidth >= 768) {
      if (!filtersCollapse.classList.contains('show')) {
        filtersCollapse.classList.add('show');
        filtersCollapse.style.height = 'auto';
      }
    } else {
      if (filtersCollapse.classList.contains('show')) {
        filtersCollapse.classList.remove('show');
        filtersCollapse.style.height = '';
      }
    }
  }
  syncFiltersCollapse();
  window.addEventListener('resize', function(){
    clearTimeout(resizeTO);
    resizeTO = setTimeout(syncFiltersCollapse, 150);
  });

  // AJAX helper function
  function ajaxRequest(url, data = {}) {
    const urlWithParams = new URL(url, window.location.origin);
    Object.keys(data).forEach(key => {
      if (data[key] !== null && data[key] !== undefined && data[key] !== '') {
        urlWithParams.searchParams.append(key, data[key]);
      }
    });

    return fetch(urlWithParams.toString(), {
      method: 'GET',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      }
    }).then(response => response.json());
  }

  // Student search with autocomplete
  const searchInput = document.getElementById('student-search');
  const searchResults = document.getElementById('search-results');
  let searchTimeout;

  searchInput.addEventListener('input', function() {
    const query = this.value.trim();
    clearTimeout(searchTimeout);

    if (query.length < 2) {
      searchResults.style.display = 'none';
      return;
    }

    searchTimeout = setTimeout(() => {
      ajaxRequest(`{{ route('principal.institute.meta.students', $school) }}?q=${encodeURIComponent(query)}`)
        .then(data => {
          if (data.length > 0) {
            const html = data.map(student => `
              <div class="p-2 border-bottom hover-bg-light cursor-pointer" onclick="selectStudent('${student.student_id}', '${student.name}')">
                <div class="font-weight-bold">${student.name}</div>
                <small class="text-muted">ID: ${student.student_id} | Class: ${student.class_name || 'N/A'} | Phone: ${student.phone || 'N/A'}</small>
              </div>
            `).join('');
            searchResults.innerHTML = html;
            searchResults.style.display = 'block';
          } else {
            searchResults.innerHTML = '<div class="p-2 text-muted">No students found</div>';
            searchResults.style.display = 'block';
          }
        })
        .catch(error => {
          console.error('Search error:', error);
          searchResults.style.display = 'none';
        });
    }, 300);
  });

  // Hide search results when clicking outside
  document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
      searchResults.style.display = 'none';
    }
  });

  // Function to select a student from search results
  window.selectStudent = function(studentId, fullName) {
    searchInput.value = fullName + ' (' + studentId + ')';
    searchResults.style.display = 'none';
  };

  // Function to change per page
  window.changePerPage = function(perPage) {
    const url = new URL(window.location);
    url.searchParams.set('per_page', perPage);
    window.location.href = url.toString();
  };

  // Dependent dropdowns for class -> section and class -> group
  const classSelect = document.querySelector('select[name="class_id"]');
  const sectionSelect = document.querySelector('select[name="section_id"]');
  const groupSelect = document.querySelector('select[name="group_id"]');

  classSelect.addEventListener('change', function() {
    const classId = this.value;

    // Reset section and group
    sectionSelect.innerHTML = '<option value="">-- শাখা নির্বাচন --</option>';
    groupSelect.innerHTML = '<option value="">-- গ্রুপ নির্বাচন --</option>';

    if (classId) {
      // Load sections for this class
      ajaxRequest(`{{ route('principal.institute.meta.sections', $school) }}?class_id=${classId}`)
        .then(sections => {
          sections.forEach(section => {
            const option = document.createElement('option');
            option.value = section.id;
            option.textContent = section.name;
            sectionSelect.appendChild(option);
          });
        })
        .catch(error => console.error('Error loading sections:', error));

      // Load groups for this class
      ajaxRequest(`{{ route('principal.institute.meta.groups', $school) }}?class_id=${classId}`)
        .then(groups => {
          groups.forEach(group => {
            const option = document.createElement('option');
            option.value = group.id;
            option.textContent = group.name;
            groupSelect.appendChild(option);
          });
        })
        .catch(error => console.error('Error loading groups:', error));
    }
  });

  // Detach dropdown menus to body so table/layout overflow/transform doesn't misplace them
  function positionMenu(button, menu){
    const rect = button.getBoundingClientRect();
    const top = rect.bottom + window.scrollY;
    menu.style.position = 'absolute';
    menu.style.top = top + 'px';
    menu.style.minWidth = rect.width + 'px';
    menu.style.zIndex = 2000;
    // Align by direction
    // Measure after show to get correct width
    const menuWidth = menu.offsetWidth || rect.width;
    const rightAligned = menu.classList.contains('dropdown-menu-right');
    const left = rightAligned
      ? (rect.right + window.scrollX - menuWidth)
      : (rect.left + window.scrollX);
    menu.style.left = Math.max(0, left) + 'px';
  }

  document.addEventListener('shown.bs.dropdown', function(e){
    const btn = e.target; // button
    if(!btn.classList.contains('student-action-dd')) return;
    const menu = btn.parentElement.querySelector('.dropdown-menu');
    if(!menu) return;
    // Move to body
    document.body.appendChild(menu);
    menu.classList.add('show'); // ensure visible after move
    positionMenu(btn, menu);
    // Reposition on resize/scroll
    function realign(){ if(menu.classList.contains('show')) positionMenu(btn, menu); }
    window.addEventListener('scroll', realign, {passive:true});
    window.addEventListener('resize', realign);
    menu._realignHandler = realign;
    menu._originButton = btn;
  });

  document.addEventListener('hide.bs.dropdown', function(e){
    const btn = e.target;
    if(!btn.classList.contains('student-action-dd')) return;
    const menu = document.querySelector('.dropdown-menu.show');
    if(menu && menu._originButton === btn){
      menu.classList.remove('show');
      // Put back inside dropdown container to keep DOM tidy
      btn.parentElement.appendChild(menu);
      window.removeEventListener('scroll', menu._realignHandler);
      window.removeEventListener('resize', menu._realignHandler);
      delete menu._realignHandler;
      delete menu._originButton;
    }
  });
})();
</script>
@endpush
