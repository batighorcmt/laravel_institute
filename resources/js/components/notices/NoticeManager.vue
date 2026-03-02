<template>
  <div class="notice-manager p-4 bg-white rounded shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="h4 mb-0 font-weight-bold text-primary">নোটিশ বোর্ড</h2>
      <button @click="openCreator()" class="btn btn-primary shadow-sm">
        <i class="fas fa-plus-circle mr-1"></i> নতুন নোটিশ
      </button>
    </div>

    <!-- Notice List -->
    <div v-if="!showCreator" class="notice-list">
      <div v-for="notice in notices" :key="notice.id" class="card mb-3 border-left-primary shadow-sm hover-shadow transition">
        <div class="card-body d-flex justify-content-between align-items-center py-3">
          <div @click="viewSingle(notice)" class="cursor-pointer flex-grow-1">
            <h5 class="card-title text-dark mb-1 font-weight-bold">{{ notice.title }}</h5>
            <p class="card-text text-muted small mb-0">
              <i class="fas fa-user-friends mr-1"></i> {{ audienceLabel(notice.audience_type) }} |
              <i class="fas fa-calendar-alt mr-1"></i> {{ notice.publish_at }}
            </p>
          </div>
          <div class="text-right">
            <div class="mb-2">
                <span class="badge badge-info mr-1">দেখা হয়েছে: {{ notice.read_count }}</span>
                <span v-if="notice.reply_required" class="badge badge-success">রিপ্লাই: {{ notice.reply_count }}</span>
            </div>
            <div class="btn-group btn-group-sm">
                <button @click="viewStats(notice)" class="btn btn-outline-info" title="পরিসংখ্যান">
                    <i class="fas fa-chart-bar"></i>
                </button>
                <button @click="editNotice(notice)" class="btn btn-outline-primary" title="এডিট">
                    <i class="fas fa-edit"></i>
                </button>
                <button @click="deleteNotice(notice)" class="btn btn-outline-danger" title="মুছে ফেলুন">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
          </div>
        </div>
      </div>
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
      </div>
      <div v-if="!loading && !notices.length" class="text-center py-5 text-muted">
         কোনো নোটিশ রেকর্ড পাওয়া যায়নি।
      </div>
    </div>

    <!-- Creator Modal / View -->
    <div v-else class="notice-creator card border-0">
       <div class="card-header bg-transparent d-flex justify-content-between align-items-center px-0">
         <div class="d-flex align-items-center">
            <button @click="showCreator = false" class="btn btn-sm btn-outline-secondary rounded-circle mr-3">
              <i class="fas fa-arrow-left"></i>
            </button>
            <h3 class="h5 font-weight-bold mb-0">{{ editingId ? 'নোটিশ এডিট করুন' : 'নতুন নোটিশ তৈরি' }}</h3>
         </div>
         <button @click="showCreator = false" class="btn btn-sm btn-light rounded-circle">&times;</button>
       </div>
       <div class="card-body px-0 pl-md-4">
         <div class="row">
           <div class="col-md-7">
             <div class="form-group">
               <label class="font-weight-bold">শিরোনাম *</label>
               <input v-model="form.title" type="text" class="form-control form-control-lg" placeholder="নোটিশের শিরোনাম লিখুন">
             </div>
             <div class="form-group">
               <label class="font-weight-bold">বিস্তারিত বিবরণ *</label>
               <textarea v-model="form.body" class="form-control" rows="12" placeholder="নোটিশের বিস্তারিত বার্তা এখানে লিখুন..."></textarea>
             </div>
           </div>
           
           <div class="col-md-5">
             <div class="bg-light p-3 rounded border">
                <h6 class="mb-3 border-bottom pb-2 font-weight-bold"><i class="fas fa-bullseye mr-2"></i>প্রাপক নির্বাচন</h6>
                
                <div class="form-group">
                  <label class="small text-muted">কাদের জন্য প্রযোজ্য?</label>
                  <select v-model="form.audience_type" class="form-control" @change="resetTargets">
                    <option value="all">সবাইকে (শিক্ষক ও শিক্ষার্থী)</option>
                    <option value="teachers">শুধু শিক্ষকদের জন্য</option>
                    <option value="students">শুধু শিক্ষার্থীদের জন্য</option>
                  </select>
                </div>

                <!-- Teacher Targeting -->
                <div v-if="form.audience_type === 'teachers'" class="mt-3">
                   <label class="small font-weight-bold">শিক্ষক নির্বাচন করুন:</label>
                   <div class="targeting-list border rounded bg-white p-2">
                      <div v-for="teacher in teachers" :key="teacher.id" class="custom-control custom-checkbox mb-1">
                        <input type="checkbox" :id="'t-'+teacher.id" :value="{id: teacher.id, type: 'Teacher', name: teacher.name}" v-model="form.targets" class="custom-control-input">
                        <label class="custom-control-label small" :for="'t-'+teacher.id">{{ teacher.name }} ({{ teacher.designation }})</label>
                      </div>
                   </div>
                   <small class="text-muted">কোনো শিক্ষক সিলেক্ট না করলে সকল শিক্ষকের কাছে যাবে।</small>
                </div>

                <!-- Student Hierarchical Targeting -->
                <div v-if="form.audience_type === 'students'" class="mt-3">
                   <!-- 1. Class Selection -->
                   <div class="mb-3">
                      <label class="small font-weight-bold">১. শ্রেণি নির্বাচন করুন:</label>
                      <div class="d-flex flex-wrap border rounded bg-white p-2 max-h-150">
                         <div v-for="cls in classes" :key="cls.id" class="custom-control custom-checkbox mr-3 mb-1">
                           <input type="checkbox" :id="'c-'+cls.id" :value="cls.id" v-model="selectedClasses" class="custom-control-input">
                           <label class="custom-control-label small" :for="'c-'+cls.id">{{ cls.name }}</label>
                         </div>
                      </div>
                   </div>

                   <!-- 2. Section & Group Selection (Filtered) -->
                   <div v-if="selectedClasses.length" class="mb-3">
                      <label class="small font-weight-bold">২. শাখা ও বিভাগ (ঐচ্ছিক):</label>
                      <div class="border rounded bg-white p-2 max-h-200">
                         <!-- Sections -->
                         <div v-if="filteredSections.length" class="mb-2">
                           <label class="x-small text-muted font-weight-bold text-uppercase">শাখা:</label>
                           <div v-for="sec in filteredSections" :key="'s'+sec.id" class="custom-control custom-checkbox mb-1">
                             <input type="checkbox" :id="'s-'+sec.id" :value="{id: sec.id, type: 'Section', name: sec.class_name + ' - ' + sec.name}" v-model="form.targets" class="custom-control-input">
                             <label class="custom-control-label small font-italic" :for="'s-'+sec.id">{{ sec.class_name }} - {{ sec.name }}</label>
                           </div>
                         </div>
                         <!-- Groups -->
                         <div v-if="groups.length" class="mb-1">
                           <label class="x-small text-muted font-weight-bold text-uppercase">বিভাগ:</label>
                           <div v-for="grp in groups" :key="'g'+grp.id" class="custom-control custom-checkbox mb-1">
                             <input type="checkbox" :id="'g-'+grp.id" :value="{id: grp.id, type: 'Group', name: grp.name}" v-model="form.targets" class="custom-control-input">
                             <label class="custom-control-label small" :for="'g-'+grp.id">{{ grp.name }}</label>
                           </div>
                         </div>
                      </div>
                      <small class="text-muted">শাখা/বিভাগ সিলেক্ট না করলে নির্বাচিত শ্রেণির সকল শিক্ষার্থীর কাছে যাবে।</small>
                   </div>

                   <!-- 3. Student Search/Selection -->
                   <div v-if="selectedClasses.length" class="mb-3">
                      <label class="small font-weight-bold">৩. নির্দিষ্ট শিক্ষার্থী (ঐচ্ছিক):</label>
                      <div class="input-group input-group-sm mb-2">
                        <input type="text" v-model="studentSearch" @input="searchStudents" class="form-control" placeholder="নাম বা আইডি দিয়ে খুঁজুন">
                        <div class="input-group-append">
                           <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                      </div>
                      
                      <div v-if="foundStudents.length" class="found-students border rounded bg-white shadow-sm p-1 max-h-150 overflow-auto">
                        <div v-for="std in foundStudents" :key="std.id" @click="addTarget(std, 'Student')" class="p-2 small border-bottom cursor-pointer hover-bg-light">
                           <div class="font-weight-bold text-primary">{{ std.name_bn }}</div>
                           <div class="x-small text-muted">ID: {{ std.student_id }} | {{ std.class_name }} {{ std.section_name }}</div>
                        </div>
                      </div>

                      <div class="selected-targets mt-2">
                         <div v-for="(target, idx) in selectedStudentTargets" :key="'std'+idx" class="badge badge-primary mr-1 mb-1 p-2">
                            {{ target.name }} <span @click="removeTarget(target)" class="cursor-pointer ml-1">&times;</span>
                         </div>
                      </div>
                      <small class="text-muted">শিক্ষার্থী সিলেক্ট না করলে স্বয়ংক্রিয়ভাবে উপরের সিলেকশন অনুযায়ী সকলের কাছে যাবে।</small>
                   </div>
                </div>

                <hr>
                
                <div class="form-row">
                   <div class="col-12 mb-3">
                     <div class="custom-control custom-switch">
                       <input v-model="form.reply_required" type="checkbox" class="custom-control-input" id="replySwitch">
                       <label class="custom-control-label font-weight-bold" for="replySwitch">ভয়েস রিপ্লাই প্রয়োজন?</label>
                     </div>
                   </div>
                   <div class="col-md-6 mb-2">
                     <label class="small">প্রকাশের সময়</label>
                     <input v-model="form.publish_at" type="datetime-local" class="form-control form-control-sm">
                   </div>
                   <div class="col-md-6 mb-2">
                     <label class="small">মেয়াদ শেষ</label>
                     <input v-model="form.expiry_at" type="datetime-local" class="form-control form-control-sm">
                   </div>
                </div>

                <button @click="saveNotice" :disabled="saving" class="btn btn-success btn-block mt-4 py-2 font-weight-bold shadow-sm">
                  <span v-if="saving" class="spinner-border spinner-border-sm mr-1"></span>
                  {{ editingId ? 'আপডেট করুন' : 'তৈরি ও প্রচার করুন' }}
                </button>
             </div>
           </div>
         </div>
       </div>
    </div>

    <!-- Stats Modal -->
    <div v-if="statsNotice" class="modal d-block" style="background: rgba(0,0,0,0.5)">
      <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-info text-white">
            <h5 class="modal-title"><i class="fas fa-chart-pie mr-2"></i>পরিসংখ্যান: {{ statsNotice.title }}</h5>
            <button @click="statsNotice = null" class="btn text-white">&times;</button>
          </div>
          <div class="modal-body">
            <div class="row text-center mb-4">
               <div class="col border-right">
                 <h2 class="text-primary font-weight-bold">{{ statsData.read_count }}</h2>
                 <p class="text-muted mb-0">পড়া হয়েছে</p>
               </div>
               <div v-if="statsNotice.reply_required" class="col">
                 <h2 class="text-success font-weight-bold">{{ statsData.reply_count }}</h2>
                 <p class="text-muted mb-0">ভয়েস রিপ্লাই</p>
               </div>
            </div>

            <div class="read-replies-tabs">
               <ul class="nav nav-tabs mb-3">
                 <li class="nav-item">
                   <a class="nav-link" :class="{active: activeTab === 'reads'}" @click="activeTab = 'reads'" href="javascript:void(0)">পাঠক তালিকা ({{ statsData.read_count }})</a>
                 </li>
                 <li v-if="statsNotice.reply_required" class="nav-item">
                   <a class="nav-link" :class="{active: activeTab === 'replies'}" @click="activeTab = 'replies'" href="javascript:void(0)">রিপ্লাই তালিকা ({{ statsData.reply_count }})</a>
                 </li>
               </ul>

               <div v-if="activeTab === 'reads'" class="max-h-300 overflow-auto">
                  <div v-for="read in statsData.reads" :key="read.id" class="p-2 border-bottom small d-flex justify-content-between">
                    <span>{{ read.user?.name }}</span>
                    <span class="text-muted">{{ read.created_at }}</span>
                  </div>
                  <div v-if="!statsData.read_count" class="text-center py-3 text-muted">কেউ এখনো পড়েনি।</div>
               </div>

               <div v-if="activeTab === 'replies'" class="max-h-400 overflow-auto">
                  <div v-for="reply in statsData.replies" :key="reply.id" class="d-flex align-items-center p-3 border rounded mb-2 bg-light shadow-sm">
                    <div class="mr-3">
                        <img v-if="reply.student && reply.student.photo" :src="'/storage/' + reply.student.photo" class="rounded-circle border" width="45" height="45">
                        <div v-else class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:45px;height:45px">
                           {{ reply.student ? (reply.student.name_bn ? reply.student.name_bn[0] : 'S') : 'U' }}
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="font-weight-bold text-dark">{{ reply.student ? reply.student.name_bn : 'অজ্ঞাত শিক্ষার্থী' }}</div>
                        <div class="x-small text-muted mb-2">শ্রেণি: {{ reply.student?.class_name }}, আইডি: {{ reply.student?.student_id }}</div>
                        <audio controls style="height: 30px; width: 100%" class="shadow-sm">
                          <source :src="'/storage/' + reply.voice_path" type="audio/webm">
                        </audio>
                    </div>
                  </div>
                  <div v-if="!statsData.reply_count" class="text-center py-3 text-muted">কোনো রিপ্লাই নেই।</div>
               </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Single View Modal -->
    <div v-if="viewingNotice" class="modal d-block" style="background: rgba(0,0,0,0.5)">
      <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title font-weight-bold"><i class="fas fa-eye mr-2"></i>নোটিশ বিস্তারিত</h5>
            <button @click="viewingNotice = null" class="btn text-white">&times;</button>
          </div>
          <div class="modal-body p-4">
             <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                <div>
                   <h3 class="h4 text-primary font-weight-bold mb-1">{{ viewingNotice.title }}</h3>
                   <div class="small text-muted">
                      <i class="fas fa-calendar-alt mr-1"></i> প্রকাশিত: {{ viewingNotice.publish_at }}
                   </div>
                </div>
                <div class="text-right">
                   <div class="badge badge-light p-2">{{ audienceLabel(viewingNotice.audience_type) }}</div>
                </div>
             </div>
             
             <div class="notice-content py-3 mb-4" v-html="formattedBody(viewingNotice.body)"></div>

             <div class="notice-targets bg-light p-3 rounded">
                <h6 class="small font-weight-bold text-uppercase text-muted border-bottom pb-1 mb-2">প্রাপক সিলেকশন:</h6>
                <div v-if="viewingNotice.targets && viewingNotice.targets.length">
                   <div v-for="t in viewingNotice.targets" :key="t.id" class="badge badge-info mr-1 mb-1 p-2">
                      {{ targetTypeLabel(t.type) }}: {{ t.name || t.id }}
                   </div>
                </div>
                <div v-else class="text-muted small">
                   {{ viewingNotice.audience_type === 'all' ? 'সকল শিক্ষক এবং শিক্ষার্থী' : 
                      viewingNotice.audience_type === 'teachers' ? 'সকল শিক্ষক' : 'সকল শিক্ষার্থী' }}
                </div>
             </div>
          </div>
          <div class="modal-footer bg-light">
             <button @click="editNotice(viewingNotice); viewingNotice = null" class="btn btn-primary"><i class="fas fa-edit mr-1"></i> পরিবর্তন করুন</button>
             <button @click="viewingNotice = null" class="btn btn-secondary">বন্ধ করুন</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

// Simple debounce function to avoid lodash dependency
function debounce(fn, delay) {
  let timeoutId;
  return function(...args) {
    if (timeoutId) clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
      fn.apply(this, args);
    }, delay);
  };
}

export default {
  props: {
    schoolId: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      notices: [],
      loading: false,
      saving: false,
      showCreator: false,
      editingId: null,
      statsNotice: null,
      statsData: {},
      activeTab: 'reads',
      viewingNotice: null,
      
      teachers: [],
      classes: [],
      sections: [],
      groups: [],
      selectedClasses: [], 
      
      studentSearch: '',
      foundStudents: [],
      
      form: {
        title: '',
        body: '',
        audience_type: 'all',
        targets: [], // {id, type, name}
        reply_required: false,
        publish_at: '',
        expiry_at: '',
      }
    }
  },
  computed: {
    filteredSections() {
      if (this.selectedClasses.length === 0) return [];
      return this.sections.filter(s => this.selectedClasses.includes(s.class_id));
    },
    selectedStudentTargets() {
      return this.form.targets.filter(t => t.type === 'Student');
    }
  },
  watch: {
    selectedClasses(newVal) {
      // Sync form.targets: if a class is unselected, remove associated sections
      this.form.targets = this.form.targets.filter(t => {
        if (t.type === 'Section') {
           const sec = this.sections.find(s => s.id === t.id);
           return sec && newVal.includes(sec.class_id);
        }
        return true;
      });
      
      // Update form.targets with Class targets
      // First remove all existing Class targets
      this.form.targets = this.form.targets.filter(t => t.type !== 'Class');
      // Adding Class targets is actually implicit in many systems but we store it as targetable
      newVal.forEach(id => {
         const cls = this.classes.find(c => c.id === id);
         this.form.targets.push({ id, type: 'Class', name: cls?.name });
      });
    }
  },
  mounted() {
    this.fetchNotices();
    this.fetchMeta();
  },
  methods: {
    async fetchNotices() {
      this.loading = true;
      try {
        const res = await axios.get('/api/v1/notices', {
          params: { school_id: this.schoolId }
        });
        this.notices = res.data.data;
      } catch (err) {
        console.error(err);
      } finally {
        this.loading = false;
      }
    },
    async fetchMeta() {
      try {
        const [tRes, cRes, sRes, gRes] = await Promise.all([
          axios.get('/api/v1/meta/teachers', { params: { school_id: this.schoolId } }),
          axios.get('/api/v1/meta/classes', { params: { school_id: this.schoolId } }),
          axios.get('/api/v1/meta/sections', { params: { school_id: this.schoolId } }),
          axios.get('/api/v1/meta/groups', { params: { school_id: this.schoolId } })
        ]);
        this.teachers = tRes.data;
        this.classes = cRes.data;
        this.sections = sRes.data;
        this.groups = gRes.data;
      } catch (err) {
        console.error(err);
      }
    },
    searchStudents: debounce(async function() {
      if (this.studentSearch.length < 2) {
        this.foundStudents = [];
        return;
      }
      try {
        // Use either existing sections or selected classes as filter
        const params = { q: this.studentSearch, school_id: this.schoolId };
        if (this.selectedClasses.length === 1) params.class_id = this.selectedClasses[0];
        
        const res = await axios.get('/api/v1/principal/students/search', { params });
        this.foundStudents = res.data;
      } catch (err) {
        console.error(err);
      }
    }, 500),
    addTarget(item, type) {
      if (!this.form.targets.find(t => t.id === item.id && t.type === type)) {
        this.form.targets.push({ 
          id: item.id, 
          type: type, 
          name: item.name_bn || item.student_name_bn || item.name || item.student_id 
        });
      }
      this.studentSearch = '';
      this.foundStudents = [];
    },
    removeTarget(target) {
      if (target.type === 'Class') {
          this.selectedClasses = this.selectedClasses.filter(id => id !== target.id);
      } else {
          this.form.targets = this.form.targets.filter(t => !(t.id === target.id && t.type === target.type));
      }
    },
    resetTargets() {
       this.form.targets = [];
       this.selectedClasses = [];
    },
    openCreator() {
      this.editingId = null;
      this.resetTargets();
      this.form.title = '';
      this.form.body = '';
      this.form.reply_required = false;
      this.form.publish_at = '';
      this.form.expiry_at = '';
      this.showCreator = true;
    },
    viewSingle(notice) {
       this.viewingNotice = notice;
    },
    editNotice(notice) {
      this.editingId = notice.id;
      this.form = {
        title: notice.title,
        body: notice.body,
        audience_type: notice.audience_type,
        targets: notice.targets || [],
        reply_required: notice.reply_required,
        publish_at: notice.publish_at ? notice.publish_at.replace(' ', 'T').slice(0, 16) : '',
        expiry_at: notice.expiry_at ? notice.expiry_at.replace(' ', 'T').slice(0, 16) : '',
      };
      
      // Sync selectedClasses from targets
      this.selectedClasses = this.form.targets
        .filter(t => t.type === 'Class')
        .map(t => t.id);
        
      this.showCreator = true;
      this.viewingNotice = null;
    },
    async deleteNotice(notice) {
      if (!confirm('আপনি কি নিশ্চিত যে এই নোটিশটি মুছে ফেলতে চান?')) return;
      try {
        await axios.delete(`/api/v1/notices/${notice.id}`);
        this.fetchNotices();
      } catch (err) {
        alert('মুছে ফেলতে সমস্যা হয়েছে।');
      }
    },
    async saveNotice() {
      if (!this.form.title || !this.form.body) {
        return alert('শিরোনাম এবং বিবরণ আবশ্যক');
      }
      
      this.saving = true;
      try {
        const url = this.editingId ? `/api/v1/notices/${this.editingId}` : '/api/v1/notices';
        const method = this.editingId ? 'put' : 'post';
        
        // Prepare smart targets based on hierararchy: Student > Section/Group > Class
        let finalTargets = [...this.form.targets];
        
        if (this.form.audience_type === 'students') {
           const studentTargets = finalTargets.filter(t => t.type === 'Student');
           const sectionTargets = finalTargets.filter(t => t.type === 'Section');
           const groupTargets   = finalTargets.filter(t => t.type === 'Group');
           const classTargets   = finalTargets.filter(t => t.type === 'Class');
           
           // Logic: If any student is selected, and they belong to a class/section that is also selected,
           // we should keep the broad targets only if the user explicitly wanted "Additives".
           // But based on "এক কথায় পরিষ্কার করি", if a specific section is selected, 
           // it means ONLY for that section.
           
           // Filter classes: remove if any of its sections or students are selected
           const filteredTargets = finalTargets.filter(t => {
              if (t.type === 'Class') {
                 // Is any section of this class selected?
                 const hasSection = sectionTargets.some(s => {
                    const secObj = this.sections.find(sec => sec.id === s.id);
                    return secObj && secObj.class_id === t.id;
                 });
                 // Is any student of this class selected?
                 // (Student object in foundStudents has class_id, but in targets it might not.
                 // We rely on the search implementation or a more complete target object).
                 return !hasSection;
              }
              return true;
           });
           
           finalTargets = filteredTargets;
        }

        await axios[method](url, {
           ...this.form,
           targets: finalTargets
        });
        
        this.showCreator = false;
        this.fetchNotices();
      } catch (err) {
        alert('সেভ করতে সমস্যা হয়েছে: ' + (err.response?.data?.message || err.message));
      } finally {
        this.saving = false;
      }
    },
    async viewStats(notice) {
      this.statsNotice = notice;
      this.activeTab = 'reads';
      try {
        const res = await axios.get(`/api/v1/notices/${notice.id}/stats`);
        this.statsData = res.data.stats;
      } catch (err) {
        console.error(err);
      }
    },
    audienceLabel(type) {
      const labels = { 'all': 'সবাই', 'teachers': 'শিক্ষক', 'students': 'শিক্ষার্থী' };
      return labels[type] || type;
    },
    targetTypeLabel(type) {
       const labels = { 'Class': 'শ্রেণি', 'Section': 'শাখা', 'Group': 'বিভাগ', 'Student': 'শিক্ষার্থী', 'Teacher': 'শিক্ষক' };
       return labels[type] || type;
    },
    formattedBody(body) {
      if (!body) return '';
      return body.replace(/\n/g, '<br>');
    }
  }
}
</script>

<style scoped>
.notice-manager { min-height: 500px; }
.transition { transition: all 0.2s ease-in-out; }
.hover-shadow:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
.cursor-pointer { cursor: pointer; }
.cursor-pointer:hover { background-color: rgba(0,0,0,0.02); }
.border-left-primary { border-left: 5px solid #4e73df !important; }
.max-h-150 { max-height: 150px; overflow-y: auto; }
.max-h-200 { max-height: 200px; overflow-y: auto; }
.max-h-300 { max-height: 300px; overflow-y: auto; }
.max-h-400 { max-height: 400px; overflow-y: auto; }
.x-small { font-size: 0.75rem; }
.notice-content { font-size: 1.1rem; line-height: 1.7; white-space: pre-line; }
.nav-link { cursor: pointer; }
.nav-link.active { font-weight: bold; }
</style>
