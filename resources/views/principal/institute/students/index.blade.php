@extends('layouts.admin')
@section('title','শিক্ষার্থী তালিকা')
@section('content')
@php
  $years = $years ?? collect();
  $currentYear = $currentYear ?? null;
  $selectedYear = $selectedYear ?? null;
  $selectedYearId = $selectedYearId ?? 0;
  $yearLabel = $selectedYear ? $selectedYear->name : ($currentYear ? $currentYear->name : 'বর্ষ নির্ধারিত নয়');
  
  $initialFilters = [
      'q' => request('q', ''),
      'roll_no' => request('roll_no', ''),
      'year_id' => request('year_id', $selectedYearId),
      'class_id' => request('class_id', ''),
      'section_id' => request('section_id', ''),
      'group_id' => request('group_id', ''),
      'status' => request('status', 'active'),
      'gender' => request('gender', ''),
      'religion' => request('religion', ''),
      'village' => request('village', ''),
      'per_page' => request('per_page', 10),
  ];
@endphp

<div id="student-vue-app" v-cloak class="student-list-container">
    <div class="d-flex flex-column flex-md-row justify-content-end mb-4 align-items-md-center">
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('principal.institute.students.create',$school) }}" class="btn btn-primary shadow-sm rounded-pill px-3 py-2 mr-2 mb-2">
            <i class="fas fa-user-plus mr-1"></i> নতুন শিক্ষার্থী
        </a>
        <a href="{{ route('principal.institute.students.bulk',$school) }}" class="btn btn-outline-info shadow-sm rounded-pill px-3 py-2 mr-2 mb-2">
            <i class="fas fa-file-import mr-1"></i> Bulk Add
        </a>
        <a href="{{ route('principal.institute.students.print-controls',$school) }}" class="btn btn-outline-secondary shadow-sm rounded-pill px-3 py-2 mb-2">
            <i class="fas fa-print mr-1"></i> প্রিন্ট
        </a>
      </div>
    </div>

    <!-- Filters Card -->
    <div class="card glass-card mb-4 shadow-sm border-0">
      <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center pt-3 pb-0">
        <h5 class="mb-0 font-weight-bold text-primary">
          <i class="fas fa-filter mr-2"></i> ফিল্টার করুন
        </h5>
        <button class="btn btn-sm btn-light d-md-none rounded-pill shadow-sm" type="button" @click="toggleFilters">
          <i class="fas" :class="showFilters ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
        </button>
      </div>
      
      <transition name="fade-slide">
        <div class="card-body" v-show="showFilters || isDesktop">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4 col-lg-3">
              <label class="form-label text-muted small mb-1">শিক্ষার্থীর নাম/আইডি/রোল/পিতা/মোবাইল</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-white border-right-0 rounded-left"><i class="fas fa-search text-muted"></i></span>
                </div>
                <input type="text" v-model="filters.q" class="form-control border-left-0 rounded-right shadow-none focus-ring" placeholder="সার্চ করুন..." @input="debouncedFetch">
              </div>
            </div>
            <div class="col-6 col-md-2 col-lg-1">
              <label class="form-label text-muted small mb-1">রোল নং</label>
              <input type="number" v-model="filters.roll_no" class="form-control shadow-none focus-ring" placeholder="রোল" min="1" @input="debouncedFetch">
            </div>
            <div class="col-6 col-md-3 col-lg-2">
              <label class="form-label text-muted small mb-1">বছর</label>
              <select v-model="filters.year_id" class="form-control shadow-none focus-ring custom-select" @change="fetchStudents(1)">
                <option value="">-- সকল --</option>
                <option v-for="y in years" :key="y.id" :value="y.id">@{{ y.name }}</option>
              </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
              <label class="form-label text-muted small mb-1">শ্রেণি</label>
              <select v-model="filters.class_id" class="form-control shadow-none focus-ring custom-select" @change="onClassChange">
                <option value="">-- সকল --</option>
                <option v-for="c in classes" :key="c.id" :value="c.id">@{{ c.name }}</option>
              </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
              <label class="form-label text-muted small mb-1">শাখা</label>
              <select v-model="filters.section_id" class="form-control shadow-none focus-ring custom-select" @change="fetchStudents(1)">
                <option value="">-- সকল --</option>
                <option v-for="s in dynamicSections" :key="s.id" :value="s.id">@{{ s.name }}</option>
              </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
              <label class="form-label text-muted small mb-1">গ্রুপ</label>
              <select v-model="filters.group_id" class="form-control shadow-none focus-ring custom-select" @change="fetchStudents(1)">
                <option value="">-- সকল --</option>
                <option v-for="g in dynamicGroups" :key="g.id" :value="g.id">@{{ g.name }}</option>
              </select>
            </div>
            
            <div class="col-6 col-md-3 col-lg-2">
              <label class="form-label text-muted small mb-1">স্ট্যাটাস</label>
              <select v-model="filters.status" class="form-control shadow-none focus-ring custom-select" @change="fetchStudents(1)">
                <option value="">-- সকল --</option>
                <option value="active">সক্রিয়</option>
                <option value="inactive">নিষ্ক্রিয়</option>
                <option value="graduated">গ্র্যাজুয়েট</option>
                <option value="transferred">ট্রান্সফার্ড</option>
              </select>
            </div>
            
            <div class="col-6 col-md-3 col-lg-2">
              <label class="form-label text-muted small mb-1">প্রতি পৃষ্ঠায়</label>
              <select v-model="filters.per_page" class="form-control shadow-none focus-ring custom-select" @change="fetchStudents(1)">
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
            </div>
            
            <div class="col-12 col-md-12 col-lg-2 text-md-right text-lg-left mt-3 mt-lg-0">
              <button class="btn btn-outline-danger shadow-sm rounded-pill px-4 w-100" @click="resetFilters">
                <i class="fas fa-undo-alt mr-1"></i> রিসেট
              </button>
            </div>
          </div>
        </div>
      </transition>
    </div>

    <!-- Main Content -->
    <div class="card border-0 shadow-sm rounded-lg overflow-hidden position-relative">
      
      <!-- Loading Overlay -->
      <div v-if="loading" class="loading-overlay d-flex justify-content-center align-items-center">
        <div class="spinner-grow text-primary" role="status">
          <span class="sr-only">Loading...</span>
        </div>
        <div class="spinner-grow text-success mx-2" role="status" style="animation-delay: 0.2s"></div>
        <div class="spinner-grow text-info" role="status" style="animation-delay: 0.4s"></div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover table-modern mb-0">
          <thead class="bg-light">
            <tr>
              <th class="border-top-0 text-center text-muted" style="width:60px">ক্রমিক</th>
              <th class="border-top-0 text-muted">আইডি নং</th>
              <th class="border-top-0 text-muted">শিক্ষার্থীর নাম</th>
              <th class="border-top-0 text-muted d-none d-lg-table-cell">পিতার নাম</th>
              <th class="border-top-0 text-muted d-none d-md-table-cell">শ্রেণি</th>
              <th class="border-top-0 text-muted d-none d-sm-table-cell">শাখা</th>
              <th class="border-top-0 text-muted">রোল</th>
              <th class="border-top-0 text-muted d-none d-lg-table-cell">মোবাইল নং</th>
              <th class="border-top-0 text-muted text-center">স্ট্যাটাস</th>
              <th class="border-top-0 text-muted text-center" style="width:120px">বিষয়সমূহ</th>
              <th class="border-top-0 text-muted text-center" style="width:100px">অ্যাকশন</th>
            </tr>
          </thead>
          <tbody>
            <template v-if="studentsData.data && studentsData.data.length > 0">
              <tr v-for="(stu, idx) in studentsData.data" :key="stu.id" class="align-middle">
                <td class="text-center text-muted">@{{ studentsData.from + idx }}</td>
                <td class="font-weight-bold text-primary">@{{ stu.student_id }}</td>
                <td>
                  <div class="d-flex align-items-center" style="cursor: pointer;" @click="openProfileModal(stu)" title="প্রোফাইল দেখুন">
                    <img :src="getPhotoUrl(stu)" class="rounded-circle mr-3 shadow-sm d-none d-sm-block transition-all" style="width: 40px; height: 40px; object-fit: cover; border: 2px solid #fff;" alt="Photo">
                    <div>
                      <div class="font-weight-bold text-primary">@{{ getFullName(stu) }}</div>
                      <div class="d-sm-none small text-muted mt-1">
                        <span class="badge badge-light border">@{{ getEnrollmentClass(stu) }}</span> | 
                        <span class="badge badge-light border">@{{ getEnrollmentSection(stu) }}</span>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="d-none d-lg-table-cell text-muted">@{{ stu.father_name_bn || stu.father_name }}</td>
                <td class="d-none d-md-table-cell"><span class="badge badge-soft-primary">@{{ getEnrollmentClass(stu) }}</span></td>
                <td class="d-none d-sm-table-cell text-muted">@{{ getEnrollmentSection(stu) }}</td>
                <td class="font-weight-bold">@{{ getEnrollmentRoll(stu) }}</td>
                <td class="d-none d-lg-table-cell text-muted">@{{ stu.guardian_phone }}</td>
                <td class="text-center">
                  <span class="badge rounded-pill px-3 py-2" :class="getStatusClass(stu.status)">
                    @{{ stu.status }}
                  </span>
                </td>
                <td class="text-center">
                  <button @click="openSubjectsModal(stu)" class="btn btn-sm btn-light rounded-pill shadow-sm view-btn" title="বিষয়সমূহ দেখুন">
                    <i class="fas fa-book-open text-info mr-1"></i> দেখুন
                  </button>
                </td>
                <td class="text-center">
                  <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle shadow-none student-action-dd" type="button" data-toggle="dropdown">
                      Action
                    </button>
                    <div class="dropdown-menu dropdown-menu-right shadow border-0" style="border-radius: 10px;">
                      <a class="dropdown-item py-2" :href="`/principal/institute/${school.id}/students/${stu.id}`"><i class="fas fa-id-card text-primary mr-2"></i> প্রোফাইল</a>
                      <a class="dropdown-item py-2" :href="`/principal/institute/${school.id}/students/${stu.id}/print-cv`" target="_blank"><i class="fas fa-print text-secondary mr-2"></i> প্রোফাইল প্রিন্ট</a>
                      <a class="dropdown-item py-2" :href="`/principal/institute/${school.id}/students/${stu.id}/edit`"><i class="fas fa-edit text-warning mr-2"></i> সম্পাদনা</a>
                      <div class="dropdown-divider"></div>
                      <form :action="`/principal/institute/${school.id}/students/${stu.id}/status`" method="post" class="px-3 py-1 m-0">
                        <input type="hidden" name="_token" :value="csrfToken">
                        <input type="hidden" name="_method" value="PATCH">
                        <button type="submit" class="btn btn-link text-dark p-0 m-0 w-100 text-left text-decoration-none dropdown-item py-2 px-0">
                          <i class="fas" :class="stu.status === 'active' ? 'fa-toggle-on text-success' : 'fa-toggle-off text-muted'" class="mr-2"></i>
                          @{{ stu.status === 'active' ? 'নিষ্ক্রিয় করুন' : 'সক্রিয় করুন' }}
                        </button>
                      </form>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
            <template v-else>
              <tr>
                <td colspan="11" class="text-center py-5 text-muted">
                  <i class="fas fa-inbox fa-3x mb-3 text-light"></i>
                  <h5>কোনো শিক্ষার্থী পাওয়া যায়নি</h5>
                  <p class="mb-0">অনুগ্রহ করে ফিল্টার পরিবর্তন করে আবার চেষ্টা করুন।</p>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div class="card-footer bg-white border-top d-flex flex-column flex-md-row justify-content-between align-items-center py-3" v-if="studentsData.total > 0">
        <div class="text-muted small mb-2 mb-md-0">
          মোট <strong>@{{ studentsData.total }}</strong> টির মধ্যে <strong>@{{ studentsData.from }}</strong> - <strong>@{{ studentsData.to }}</strong> দেখাচ্ছে
        </div>
        
        <nav aria-label="Pagination" class="ml-auto">
          <ul class="pagination pagination-sm mb-0 justify-content-end">
            <li class="page-item" :class="{ disabled: !studentsData.prev_page_url }">
              <button class="page-link shadow-none rounded-left" @click.prevent="changePage(studentsData.current_page - 1)">&laquo; পূর্ববর্তী</button>
            </li>
            
            <li v-for="page in visiblePages" :key="page" class="page-item" :class="{ active: page === studentsData.current_page, disabled: page === '...' }">
              <button class="page-link shadow-none" @click.prevent="page !== '...' ? changePage(page) : null">@{{ page }}</button>
            </li>
            
            <li class="page-item" :class="{ disabled: !studentsData.next_page_url }">
              <button class="page-link shadow-none rounded-right" @click.prevent="changePage(studentsData.current_page + 1)">পরবর্তী &raquo;</button>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <!-- Subjects Modal -->
    <div class="modal fade" id="subjectsModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-lg">
          <div class="modal-header bg-primary text-white border-bottom-0 rounded-top" style="border-top-left-radius: 0.5rem; border-top-right-radius: 0.5rem;">
            <h5 class="modal-title font-weight-bold">
              <i class="fas fa-layer-group mr-2"></i> নির্বাচিত বিষয়সমূহ
            </h5>
            <button type="button" class="close text-white shadow-none" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body p-4 bg-light">
            <div v-if="selectedStudent" class="mb-4 text-center">
              <img :src="getPhotoUrl(selectedStudent)" class="rounded-circle shadow-sm mb-3 border border-white" style="width: 80px; height: 80px; object-fit: cover;">
              <h5 class="font-weight-bold text-dark mb-1">@{{ getFullName(selectedStudent) }}</h5>
              <div class="text-muted small">আইডি: @{{ selectedStudent.student_id }} | রোল: @{{ getEnrollmentRoll(selectedStudent) }}</div>
            </div>
            
            <div class="card border-0 shadow-sm">
              <div class="card-body p-0">
                <ul class="list-group list-group-flush rounded-lg">
                  <li v-if="!selectedStudentSubjects.length" class="list-group-item text-center text-muted py-4">
                    কোনো বিষয় পাওয়া যায়নি
                  </li>
                  <li v-for="sub in selectedStudentSubjects" :key="sub.id" class="list-group-item d-flex justify-content-between align-items-center py-3 hover-bg-light transition-all">
                    <div>
                      <div class="font-weight-bold text-dark">@{{ sub.name }}</div>
                      <div class="small text-muted">@{{ sub.code || 'No Code' }}</div>
                    </div>
                    <span v-if="sub.is_optional" class="badge badge-pill badge-info px-3 py-1 shadow-sm">ঐচ্ছিক</span>
                    <span v-else class="badge badge-pill badge-primary px-3 py-1 shadow-sm">আবশ্যিক</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          <div class="modal-footer bg-white border-top-0 rounded-bottom" style="border-bottom-left-radius: 0.5rem; border-bottom-right-radius: 0.5rem;">
            <button type="button" class="btn btn-secondary rounded-pill px-4 shadow-sm" data-dismiss="modal">বন্ধ করুন</button>
            <a v-if="selectedStudent" :href="getSubjectSelectUrl(selectedStudent)" class="btn btn-primary rounded-pill px-4 shadow-sm">
              <i class="fas fa-edit mr-1"></i> বিষয় পরিবর্তন
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-lg">
          <div class="modal-header bg-info text-white border-bottom-0 rounded-top" style="border-top-left-radius: 0.5rem; border-top-right-radius: 0.5rem;">
            <h5 class="modal-title font-weight-bold">
              <i class="far fa-id-card mr-2"></i> শিক্ষার্থীর প্রোফাইল
            </h5>
            <button type="button" class="close text-white shadow-none" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body p-4 bg-light text-center" v-if="profileModalStudent">
            <img :src="getPhotoUrl(profileModalStudent)" class="rounded-circle shadow mb-3 border border-white" style="width: 140px; height: 140px; object-fit: cover;">
            <h4 class="font-weight-bold text-dark mb-1">@{{ getFullName(profileModalStudent) }}</h4>
            <div class="text-muted mb-4">আইডি: @{{ profileModalStudent.student_id }} | রোল: @{{ getEnrollmentRoll(profileModalStudent) }}</div>
            
            <div class="row text-left mt-2">
                <div class="col-6 mb-2"><strong>শ্রেণি:</strong> <span class="text-muted">@{{ getEnrollmentClass(profileModalStudent) }}</span></div>
                <div class="col-6 mb-2"><strong>শাখা:</strong> <span class="text-muted">@{{ getEnrollmentSection(profileModalStudent) }}</span></div>
                <div class="col-12 border-top pt-2 mt-1 mb-2"><strong>পিতার নাম:</strong> <span class="text-muted">@{{ profileModalStudent.father_name_bn || profileModalStudent.father_name }}</span></div>
                <div class="col-12 mb-2"><strong>মোবাইল নং:</strong> <span class="text-muted">@{{ profileModalStudent.guardian_phone }}</span></div>
                <div class="col-12 mb-2"><strong>বর্তমান ঠিকানা:</strong> <span class="text-muted">@{{ profileModalStudent.present_village }}</span></div>
            </div>
          </div>
          <div class="modal-footer bg-white border-top-0 rounded-bottom" style="border-bottom-left-radius: 0.5rem; border-bottom-right-radius: 0.5rem;">
            <button type="button" class="btn btn-secondary rounded-pill px-4 shadow-sm" data-dismiss="modal">বন্ধ করুন</button>
            <a v-if="profileModalStudent" :href="`/principal/institute/${school.id}/students/${profileModalStudent.id}`" class="btn btn-info rounded-pill px-4 shadow-sm text-white">
              <i class="fas fa-external-link-alt mr-1"></i> বিস্তারিত প্রোফাইল
            </a>
          </div>
        </div>
      </div>
    </div>
</div>
@endsection

@push('styles')
<style>
[v-cloak] { display: none !important; }

.student-list-container {
  position: relative;
}

/* Glassmorphism card */
.glass-card {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.18) !important;
}

.focus-ring:focus {
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
  border-color: #86b7fe;
}

/* Modern table styling */
.table-modern {
  border-collapse: separate;
  border-spacing: 0;
}
.table-modern th {
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.5px;
  background-color: #f8f9fc;
}
.table-modern td {
  vertical-align: middle;
  padding: 1rem 0.75rem;
  border-top: 1px solid #edf2f9;
}
.table-modern tbody tr {
  transition: all 0.2s ease;
}
.table-modern tbody tr:hover {
  background-color: #fbfdff;
  transform: translateY(-1px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  z-index: 1;
  position: relative;
}

/* Custom Soft Badges */
.bg-success-soft { background-color: rgba(40, 167, 69, 0.1); }
.text-success { color: #28a745 !important; }
.bg-info-soft { background-color: rgba(23, 162, 184, 0.1); }
.bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
.text-warning { color: #ffc107 !important; }
.bg-secondary-soft { background-color: rgba(108, 117, 125, 0.1); }

.badge-soft-primary {
  background-color: rgba(0, 123, 255, 0.1);
  color: #007bff;
  padding: 0.4em 0.8em;
}

.view-btn {
  transition: all 0.3s;
}
.view-btn:hover {
  background-color: #e3f2fd;
  color: #007bff;
  transform: scale(1.05);
}

.hover-bg-light:hover {
  background-color: #f8f9fa !important;
}
.transition-all {
  transition: all 0.2s ease-in-out;
}

/* Transitions */
.fade-slide-enter-active, .fade-slide-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}
.fade-slide-enter-from, .fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
  max-height: 0;
}
.fade-slide-enter-to, .fade-slide-leave-from {
  opacity: 1;
  transform: translateY(0);
  max-height: 500px;
}

.loading-overlay {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(255,255,255,0.7);
  z-index: 10;
  backdrop-filter: blur(2px);
}

.modal-content {
  border-radius: 1rem;
  overflow: hidden;
}
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const { createApp } = Vue;

  createApp({
    data() {
      return {
        school: @json($school),
        studentsData: @json($students),
        filters: @json($initialFilters),
        years: @json($years),
        classes: @json($classes ?? ($school->classes ?? collect())),
        sections: @json($sections ?? collect()),
        groups: @json($groups ?? collect()),
        
        dynamicSections: @json($sections ?? collect()),
        dynamicGroups: @json($groups ?? collect()),
        
        loading: false,
        debounceTimer: null,
        showFilters: false,
        windowWidth: window.innerWidth,
        selectedStudent: null,
        profileModalStudent: null,
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      };
    },
    computed: {
      isDesktop() {
        return this.windowWidth >= 768;
      },
      visiblePages() {
        const current = this.studentsData.current_page;
        const last = this.studentsData.last_page;
        if (!last || last === 1) return [];
        
        const delta = 2;
        const left = current - delta;
        const right = current + delta + 1;
        const range = [];
        const rangeWithDots = [];
        let l;

        for (let i = 1; i <= last; i++) {
          if (i === 1 || i === last || i >= left && i < right) {
            range.push(i);
          }
        }

        for (let i of range) {
          if (l) {
            if (i - l === 2) {
              rangeWithDots.push(l + 1);
            } else if (i - l !== 1) {
              rangeWithDots.push('...');
            }
          }
          rangeWithDots.push(i);
          l = i;
        }

        return rangeWithDots;
      },
      selectedStudentSubjects() {
        if (!this.selectedStudent) return [];
        const en = this.selectedStudent.enrollments && this.selectedStudent.enrollments.length > 0 ? this.selectedStudent.enrollments[0] : null;
        if (!en || !en.subjects) return [];
        
        let subs = [...en.subjects].map(s => ({
          id: s.id,
          is_optional: s.is_optional,
          name: s.subject ? s.subject.name : 'Unknown',
          code: s.subject ? s.subject.code : ''
        }));
        
        return subs.sort((a, b) => {
          if (a.is_optional && !b.is_optional) return 1;
          if (!a.is_optional && b.is_optional) return -1;
          const numA = a.code ? parseInt(a.code.replace(/\\D/g, '')) || 999999 : 999999;
          const numB = b.code ? parseInt(b.code.replace(/\\D/g, '')) || 999999 : 999999;
          return numA - numB;
        });
      }
    },
    mounted() {
      window.addEventListener('resize', this.onResize);
      const urlParams = new URLSearchParams(window.location.search);
      for (let key in this.filters) {
        if (urlParams.has(key)) {
          this.filters[key] = urlParams.get(key);
        }
      }
      
      // Fix AdminLTE dropdown positioning for Vue rendered elements
      this.attachDropdownFix();
    },
    beforeUnmount() {
      window.removeEventListener('resize', this.onResize);
    },
    methods: {
      onResize() {
        this.windowWidth = window.innerWidth;
      },
      toggleFilters() {
        this.showFilters = !this.showFilters;
      },
      debouncedFetch() {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
          this.fetchStudents(1);
        }, 400);
      },
      async onClassChange() {
        this.filters.section_id = '';
        this.filters.group_id = '';
        
        try {
          if (this.filters.class_id) {
            const [secRes, grpRes] = await Promise.all([
              axios.get(`/principal/institute/${this.school.id}/meta/sections?class_id=${this.filters.class_id}`),
              axios.get(`/principal/institute/${this.school.id}/meta/groups?class_id=${this.filters.class_id}`)
            ]);
            this.dynamicSections = secRes.data || [];
            this.dynamicGroups = grpRes.data || [];
          } else {
            this.dynamicSections = this.sections;
            this.dynamicGroups = this.groups;
          }
        } catch (err) {
          console.error('Error fetching class meta:', err);
        }
        
        this.fetchStudents(1);
      },
      async fetchStudents(page = null) {
        this.loading = true;
        const params = { ...this.filters };
        if (page) {
          params.page = page;
        } else {
          params.page = this.studentsData.current_page;
        }
        
        Object.keys(params).forEach(key => {
          if (params[key] === null || params[key] === '') {
            delete params[key];
          }
        });
        
        try {
          const response = await axios.get(`/principal/institute/${this.school.id}/students-data`, {
            params: params
          });

          if (response.data && response.data.students) {
            this.studentsData = response.data.students;

            const url = new URL(window.location.pathname, window.location.origin);
            url.search = new URLSearchParams(params).toString();
            window.history.pushState({}, '', url);
            
            // Re-attach dropdown fixes after data changes
            this.$nextTick(() => {
              this.attachDropdownFix();
            });
          }
        } catch (error) {
          if(window.toastr) {
            window.toastr.error('ডেটা লোড করতে সমস্যা হয়েছে।');
          }
          console.error('Fetch error:', error);
        } finally {
          this.loading = false;
        }
      },
      changePage(page) {
        if (page >= 1 && page <= this.studentsData.last_page) {
          this.fetchStudents(page);
        }
      },
      resetFilters() {
        this.filters = {
          q: '', roll_no: '', year_id: '', class_id: '', section_id: '', group_id: '',
          status: 'active', gender: '', religion: '', village: '', per_page: 10
        };
        this.dynamicSections = this.sections;
        this.dynamicGroups = this.groups;
        this.fetchStudents(1);
      },
      openSubjectsModal(student) {
        this.selectedStudent = student;
        if (window.$) {
          window.$('#subjectsModal').modal('show');
        }
      },
      openProfileModal(student) {
        this.profileModalStudent = student;
        if (window.$) {
          window.$('#profileModal').modal('show');
        }
      },
      getFullName(stu) {
        if (stu.student_name_bn) return stu.student_name_bn;
        if (stu.student_name_en) return stu.student_name_en;
        return 'Unknown';
      },
      getEnrollmentClass(stu) {
        if (!stu.enrollments || stu.enrollments.length === 0) return '-';
        return stu.enrollments[0].class ? stu.enrollments[0].class.name : '-';
      },
      getEnrollmentSection(stu) {
        if (!stu.enrollments || stu.enrollments.length === 0) return '-';
        return stu.enrollments[0].section ? stu.enrollments[0].section.name : '-';
      },
      getEnrollmentRoll(stu) {
        if (!stu.enrollments || stu.enrollments.length === 0) return '-';
        return stu.enrollments[0].roll_no || '-';
      },
      getPhotoUrl(stu) {
        if (stu.photo) {
            return `/storage/${stu.photo}`;
        }
        return '/images/default-avatar.png';
      },
      getStatusClass(status) {
        const map = {
          'active': 'bg-success-soft text-success',
          'inactive': 'bg-secondary-soft text-secondary',
          'graduated': 'bg-info-soft text-info',
          'transferred': 'bg-warning-soft text-warning'
        };
        return map[status] || 'bg-light text-dark';
      },
      getSubjectSelectUrl(student) {
        const en = student.enrollments && student.enrollments.length > 0 ? student.enrollments[0] : null;
        if (en) {
          return `/principal/institute/${this.school.id}/enrollments/${en.id}/subjects`;
        }
        return '#';
      },
      attachDropdownFix() {
        // AdminLTE/Bootstrap dropdown fix for tables with overflow
        setTimeout(() => {
          if (!window.$) return;
          $('.student-action-dd').off('show.bs.dropdown').on('show.bs.dropdown', function () {
            $('.table-responsive').css( "overflow", "inherit" );
          });
          $('.student-action-dd').off('hide.bs.dropdown').on('hide.bs.dropdown', function () {
            $('.table-responsive').css( "overflow", "auto" );
          });
        }, 100);
      }
    }
  }).mount('#student-vue-app');
});
</script>
@endpush
