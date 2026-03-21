<template>
  <div>
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-extrabold">ফি ওয়েভার</h1>
        <p class="text-sm text-slate-500">শিক্ষার্থীর ফি ওয়েভার যোগ/সম্পাদনা করুন (পূর্ণ/পরিমাণ/শতাংশ)</p>
      </div>
      <div>
        <button @click="openForm()" class="px-4 py-2 bg-indigo-600 text-white rounded">নতুন ওয়েভার</button>
      </div>
    </div>

    <div class="bg-white p-4 rounded shadow-sm mb-6">
      <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
        <select v-model="filters.academic_year_id" class="form-input">
          <option value="">সকল শিক্ষাবর্ষ</option>
          <option v-for="ay in (metaSchool?.academic_years||[])" :key="ay.id" :value="ay.id">{{ ay.name_bn || ay.name }}</option>
        </select>
        <select v-model="filters.class_id" class="form-input" @change="onFilterClassChange">
          <option value="">সকল শ্রেণি</option>
          <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
        <select v-model="filters.section_id" class="form-input" @change="onFilterSectionChange">
          <option value="">সকল শাখা</option>
          <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
        <select v-model="filters.student_id" class="form-input student-select">
          <option value="">সকল শিক্ষার্থী</option>
          <option v-for="st in students" :key="st.id" :value="st.id">{{ st.name_bn || st.name_en || st.full_name }} ({{ st.roll_no || st.student_id || st.id }})</option>
        </select>
        <div class="text-right">
          <button @click="fetchList()" class="px-3 py-2 bg-slate-100 rounded">খুঁজুন</button>
        </div>
      </div>
    </div>

    <div class="bg-white rounded shadow-sm overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left">
          <tr>
              <th class="px-4 py-3">#</th>
              <th class="px-4 py-3">শিক্ষার্থী</th>
              <th class="px-4 py-3">শ্রেণি</th>
              <th class="px-4 py-3">শাখা</th>
              <th class="px-4 py-3">রোল নং</th>
              <th class="px-4 py-3">টাইপ</th>
              <th class="px-4 py-3">মান</th>
              <th class="px-4 py-3">পুনরাবৃত্তি</th>
              <th class="px-4 py-3">সময়কাল</th>
              <th class="px-4 py-3">কর্মকলাপ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(w, idx) in waivers.data || []" :key="w.id" class="border-t">
            <td class="px-4 py-3">{{ idx+1 }}</td>
            <td class="px-4 py-3">{{ w.student_name || w.student_id }}</td>
            <td class="px-4 py-3">{{ w.class_name || '-' }}</td>
            <td class="px-4 py-3">{{ w.section_name || '-' }}</td>
            <td class="px-4 py-3">{{ w.roll_no || '-' }}</td>
            <td class="px-4 py-3">{{ w.waiver_type == 'full' ? 'পূর্ণ' : (w.waiver_type=='amount' ? 'পরিমাণ' : 'শতাংশ') }}</td>
            <td class="px-4 py-3">{{ w.waiver_value ?? '-' }}</td>
            <td class="px-4 py-3">{{ w.is_recurring ? 'হ্যাঁ' : 'না' }}</td>
            <td class="px-4 py-3">{{ formatDate(w.start_date) }} - {{ formatDate(w.end_date) }}</td>
            <td class="px-4 py-3">
                <button @click="viewDetails(w)" class="px-2 py-1 bg-teal-600 text-white rounded text-xs">দেখুন</button>
                <button @click="edit(w)" class="px-2 py-1 bg-indigo-600 text-white rounded text-xs ml-1">সম্পাদনা</button>
                <button @click="remove(w)" class="px-2 py-1 bg-red-500 text-white rounded text-xs ml-1">মুছুন</button>
            </td>
          </tr>
          <tr v-if="!(waivers.data||[]).length">
            <td colspan="7" class="px-4 py-8 text-center text-slate-400">কোনও ওয়েভার পাওয়া যায়নি</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Simple modal form -->
    <div v-if="showForm" class="fixed inset-0 bg-black/30 flex items-center justify-center">
      <div class="bg-white p-6 rounded shadow-lg w-96">
        <h3 class="font-bold mb-3">{{ editing ? 'ওয়েভার সম্পাদনা' : 'নতুন ওয়েভার' }}</h3>
        <div class="space-y-2">
              <select v-model="form.academic_year_id" class="form-input">
              <option value="">সিলেক্ট শিক্ষাবর্ষ</option>
              <option v-for="ay in (metaSchool?.academic_years||[])" :key="ay.id" :value="ay.id">{{ ay.name_bn || ay.name }}</option>
            </select>
              <select v-model="form.fee_category_id" class="form-input" @change="onCategoryChange">
                <option value="">ফি ক্যাটেগরি (ঐচ্ছিক)</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name_bn || cat.name }}</option>
              </select>
              <select v-model="form.fee_structure_id" class="form-input">
                <option value="">ফি স্ট্রাকচার (ঐচ্ছিক)</option>
                <option v-for="fs in feeStructuresForForm" :key="fs.id" :value="fs.id">{{ fs.name }}</option>
              </select>
              <label class="flex items-center gap-2"><input type="checkbox" v-model="form.apply_to_all" /> সব ফিতে প্রয়োগ করুন</label>
          <select v-model="form.class_id" class="form-input" @change="onFormClassChange">
            <option value="">শ্রেণি নির্বাচন করুন</option>
            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <select v-model="form.section_id" class="form-input" @change="onFormSectionChange">
            <option value="">শাখা নির্বাচন করুন</option>
            <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
          <select v-model="form.student_id" class="form-input student-select">
            <option value="">নির্বাচন করুন</option>
            <option v-for="st in students" :key="st.id" :value="st.id">{{ st.name_bn || st.name_en || st.full_name }} ({{ st.roll_no || st.student_id || st.id }})</option>
          </select>
          <select v-model="form.waiver_type" class="form-input">
            <option value="full">পূর্ণ</option>
            <option value="amount">পরিমাণ</option>
            <option value="percent">শতাংশ</option>
          </select>
            <input v-model.number="form.waiver_value" placeholder="মান (যদি প্রযোজ্য)" class="form-input" />
            <label class="flex items-center gap-2"><input type="checkbox" v-model="form.is_recurring" /> পুনরাবৃত্তি</label>
          <div class="grid grid-cols-2 gap-2">
            <input v-model="form.start_date" type="date" class="form-input" />
            <input v-model="form.end_date" type="date" class="form-input" />
          </div>
            <textarea v-model="form.notes" placeholder="নোট (ঐচ্ছিক)" class="form-input"></textarea>
            <label class="flex items-center gap-2"><input type="checkbox" checked="checked" v-model="form.apply_to_existing" /> বিদ্যমান ডিউসে রেট্রোঅ্যাকটিভ প্রয়োগ</label>
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <button @click="closeForm" class="px-3 py-2 border rounded">বাতিল</button>
            <button @click="save" class="px-3 py-2 bg-indigo-600 text-white rounded">সংরক্ষণ</button>
        </div>
      </div>
    </div>

    <!-- View Details Modal -->
    <div v-if="showViewModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" @click.self="showViewModal=false">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b bg-teal-600 rounded-t-xl">
          <h3 class="text-white font-bold text-lg">ওয়েভার বিস্তারিত</h3>
          <button @click="showViewModal=false" class="text-white hover:text-teal-100 text-2xl font-bold leading-none">&times;</button>
        </div>
        <div class="px-6 py-5 text-sm">
          <div class="grid grid-cols-2 gap-x-4 gap-y-3">
            <div class="text-slate-500 font-medium">শিক্ষার্থী</div>
            <div class="font-semibold">{{ viewWaiver.student_name || viewWaiver.student_id || '-' }}</div>

            <div class="text-slate-500 font-medium">শ্রেণি</div>
            <div>{{ viewWaiver.class_name || '-' }}</div>

            <div class="text-slate-500 font-medium">শাখা</div>
            <div>{{ viewWaiver.section_name || '-' }}</div>

            <div class="text-slate-500 font-medium">রোল নং</div>
            <div>{{ viewWaiver.roll_no || '-' }}</div>

            <div class="text-slate-500 font-medium">শিক্ষাবর্ষ</div>
            <div>{{ viewWaiver.academic_year_name || viewWaiver.academic_year_id || '-' }}</div>

            <div class="text-slate-500 font-medium">ওয়েভার ধরন</div>
            <div>
              <span v-if="viewWaiver.waiver_type === 'full'" class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs font-semibold">পূর্ণ</span>
              <span v-else-if="viewWaiver.waiver_type === 'amount'" class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs font-semibold">পরিমাণ</span>
              <span v-else class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-xs font-semibold">শতাংশ</span>
            </div>

            <div class="text-slate-500 font-medium">মান</div>
            <div>{{ viewWaiver.waiver_value ?? '-' }}</div>

            <div class="text-slate-500 font-medium">ফি ক্যাটেগরি</div>
            <div>{{ viewWaiver.category_name || viewWaiver.fee_category_id || '-' }}</div>

            <div class="text-slate-500 font-medium">ফি স্ট্রাকচার</div>
            <div>{{ viewWaiver.structure_name || viewWaiver.fee_structure_id || '-' }}</div>

            <div class="text-slate-500 font-medium">সব ফিতে প্রয়োগ</div>
            <div>
              <span :class="viewWaiver.apply_to_all ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'" class="px-2 py-0.5 rounded text-xs font-semibold">{{ viewWaiver.apply_to_all ? 'হ্যাঁ' : 'না' }}</span>
            </div>

            <div class="text-slate-500 font-medium">পুনরাবৃত্তি</div>
            <div>
              <span :class="viewWaiver.is_recurring ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-500'" class="px-2 py-0.5 rounded text-xs font-semibold">{{ viewWaiver.is_recurring ? 'হ্যাঁ' : 'না' }}</span>
            </div>

            <div class="text-slate-500 font-medium">শুরুর তারিখ</div>
            <div>{{ viewWaiver.start_date || '-' }}</div>

            <div class="text-slate-500 font-medium">শেষের তারিখ</div>
            <div>{{ viewWaiver.end_date || '-' }}</div>

            <div class="text-slate-500 font-medium">নোট</div>
            <div class="whitespace-pre-line">{{ viewWaiver.notes || '-' }}</div>
          </div>
        </div>
        <div class="px-6 py-4 border-t flex justify-end">
          <button @click="showViewModal=false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded text-sm">বন্ধ করুন</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
export default {
  data() {
    return {
      waivers: {},
      filters: { student_id: '', fee_category_id: '', academic_year_id: '', class_id: '', section_id: '' },
      categories: [],
      metaSchool: null,
      classes: [],
      sections: [],
      students: [],
      showForm: false,
      showViewModal: false,
      viewWaiver: {},
      editing: false,
      form: {
        academic_year_id: '', fee_category_id: '', fee_structure_id: '', apply_to_all: false, apply_to_existing: true, student_id: '', class_id: '', section_id: '', waiver_type: 'full', waiver_value: null, is_recurring: true, start_date: '', end_date: '', notes: ''
      }
    };
  },
  computed: {
    feeStructuresForForm() {
      const cat = this.categories.find(c=> c.id == this.form.fee_category_id);
      if (!cat) return [];
      return cat.fee_structures || cat.structures || cat.items || [];
    }
  },
  mounted() { this.fetchList(); this.loadCategories(); this.loadMetaSchool(); this.loadClasses(); this.initSelect2(); },
  methods: {
    onCategoryChange() {
      // clear selected structure when category changes
      this.form.fee_structure_id = '';
    },
    loadMetaSchool() {
      axios.get('/api/v1/meta/school').then(r => {
        this.metaSchool = r.data || null;
        const ay = this.metaSchool?.current_academic_year?.id || this.metaSchool?.current_academic_year_id || null;
        if (ay) { this.form.academic_year_id = ay; this.filters.academic_year_id = ay; }
      }).catch(()=>{});
    },
    loadClasses() { axios.get('/api/v1/meta/classes').then(r=> this.classes = r.data||[]).catch(()=> this.classes=[]); },
    loadSections(classId) { axios.get('/api/v1/meta/sections', { params: { class_id: classId } }).then(r=> this.sections = r.data||[]).catch(()=> this.sections=[]); },
    loadStudents(params={}) { axios.get('/api/v1/principal/students/search', { params: { ...params, limit: 200, q: params.q || params.q === '' ? params.q : undefined } }).then(r=> { this.students = r.data||[]; this.$nextTick(()=> this.initSelect2()); }).catch(()=> this.students = []); },
    onFilterClassChange() { this.sections = []; this.students = []; if (this.filters.class_id) { this.loadSections(this.filters.class_id); this.loadStudents({ class_id: this.filters.class_id, section_id: this.filters.section_id, academic_year_id: this.filters.academic_year_id }); } },
    onFilterSectionChange() { this.loadStudents({ class_id: this.filters.class_id, section_id: this.filters.section_id, academic_year_id: this.filters.academic_year_id }); },
    onFormClassChange() { this.sections = []; this.students = []; if (this.form.class_id) { this.loadSections(this.form.class_id); this.loadStudents({ class_id: this.form.class_id, section_id: this.form.section_id, academic_year_id: this.form.academic_year_id }); } },
    onFormSectionChange() { this.loadStudents({ class_id: this.form.class_id, section_id: this.form.section_id, academic_year_id: this.form.academic_year_id }); },
    loadCategories() { axios.get('/api/v1/billing/config').then(r=> this.categories = r.data.categories||[]).catch(()=>{}); },
    formatDate(d) {
      if (!d) return '-';
      // if already in ISO-like form, extract YYYY-MM-DD directly to avoid timezone conversion
      if (typeof d === 'string') {
        // common server formats: 'YYYY-MM-DD', 'YYYY-MM-DD HH:MM:SS', 'YYYY-MM-DDTHH:MM:SSZ'
        const m = d.match(/(\d{4}-\d{2}-\d{2})/);
        if (m) return m[1];
        return String(d);
      }
      try {
        const dt = new Date(d);
        if (isNaN(dt)) return String(d);
        // return YYYY-MM-DD using local components (avoid toISOString timezone shift)
        const y = dt.getFullYear();
        const mth = String(dt.getMonth() + 1).padStart(2,'0');
        const day = String(dt.getDate()).padStart(2,'0');
        return `${y}-${mth}-${day}`;
      } catch (e) { return String(d); }
    },

    normalizeDateInput(val) {
      if (!val) return '';
      // if already YYYY-MM-DD, return
      if (/^\d{4}-\d{2}-\d{2}$/.test(val)) return val;
      // if DD-MM-YYYY, convert
      const dmy = val.match(/^(\d{2})-(\d{2})-(\d{4})$/);
      if (dmy) {
        return `${dmy[3]}-${dmy[2]}-${dmy[1]}`;
      }
      // if 'YYYY-MM-DD HH:MM:SS' or similar, extract
      const m = String(val).match(/(\d{4}-\d{2}-\d{2})/);
      if (m) return m[1];
      return val;
    },
    fetchList(page=1) {
      const params = { ...this.filters, page };
      axios.get('/api/v1/billing/waivers', { params }).then(r=> {
        this.waivers = r.data || {};
        if (this.waivers.data && Array.isArray(this.waivers.data)) {
          this.waivers.data = this.waivers.data.map(item => {
            item.start_date = this.formatDate(item.start_date);
            item.end_date = this.formatDate(item.end_date);
            return item;
          });
        }
      }).catch(()=> this.waivers = {});
    },
    initSelect2() {
      // initialize select2 on student-select elements and sync with Vue
      const self = this;
      const init = () => {
        if (!window.$ || !$.fn.select2) return;
        // destroy any existing instance first
        $('.student-select').each(function(){ if ($(this).data('select2')) { $(this).select2('destroy'); } });
        $('.student-select').select2({ width: '100%', placeholder: 'খুঁজুন...' });
        $('.student-select').off('change.fw').on('change.fw', function(){
          const val = $(this).val();
          // determine whether this select is form or filter by checking closest modal
          if ($(this).closest('.fixed').length) {
            self.form.student_id = val;
          } else {
            self.filters.student_id = val;
          }
        });
      };
      // attempt init after DOM ready
      if (document.readyState === 'loading') {
        window.addEventListener('DOMContentLoaded', init);
      } else { init(); }
    },
    openForm() {
      this.showForm = true;
      this.editing = false;
      this.form = { academic_year_id: this.form.academic_year_id || this.filters.academic_year_id, fee_category_id: '', fee_structure_id: '', apply_to_all: false, apply_to_existing: true, student_id: '', class_id: '', section_id: '', waiver_type: 'full', waiver_value: null, is_recurring: true, start_date: '', end_date: '', notes: '' };
      // preload students for selected class/section if available
      const cls = this.form.class_id || this.filters.class_id || null;
      const sec = this.form.section_id || this.filters.section_id || null;
      const ay = this.form.academic_year_id || this.filters.academic_year_id || null;
      if (cls || sec) { this.loadStudents({ class_id: cls, section_id: sec, academic_year_id: ay }); }
    },
    viewDetails(w) {
      this.viewWaiver = { ...w };
      this.showViewModal = true;
    },
    closeForm() { this.showForm = false; },

    edit(w) {
      this.editing = true;
      this.form = {
        id: w.id,
        academic_year_id: w.academic_year_id || this.form.academic_year_id || this.filters.academic_year_id || '',
        fee_category_id: w.fee_category_id || '',
        fee_structure_id: w.fee_structure_id || '',
        apply_to_all: !!w.apply_to_all,
        apply_to_existing: false,
        student_id: w.student_id || '',
        class_id: w.class_id || '',
        section_id: w.section_id || '',
        waiver_type: w.waiver_type || 'full',
        waiver_value: w.waiver_value || null,
        is_recurring: !!w.is_recurring,
        start_date: this.formatDate(w.start_date),
        end_date: this.formatDate(w.end_date),
        notes: w.notes || ''
      };
      this.showForm = true;
      if (this.form.class_id || this.form.section_id) {
        this.loadStudents({ class_id: this.form.class_id, section_id: this.form.section_id, academic_year_id: this.form.academic_year_id });
      }
    },
    save() {
      // prepare payload and normalize date inputs to YYYY-MM-DD to avoid server/client timezone issues
      const payload = { ...this.form };
      payload.start_date = this.normalizeDateInput(this.form.start_date);
      payload.end_date = this.normalizeDateInput(this.form.end_date);

      if (this.editing) {
        axios.put(`/api/v1/billing/waivers/${this.form.id}`, payload).then(()=>{ this.fetchList(); this.closeForm(); }).catch(e=>{ alert('সংরক্ষণ ব্যর্থ হয়েছে'); console.error(e); });
      } else {
        axios.post('/api/v1/billing/waivers', payload).then(()=>{ this.fetchList(); this.closeForm(); }).catch(e=>{ alert('সংরক্ষণ ব্যর্থ হয়েছে'); console.error(e); });
      }
    },

    remove(w) {
      if (!confirm('ওয়েভার মুছে ফেলতে চান?')) return;
      axios.delete(`/api/v1/billing/waivers/${w.id}`).then(()=> this.fetchList()).catch(()=> alert('মুছে ফেলা যায়নি'));
    }
  }
}
</script>

<style scoped>
.form-input { width:100%; padding:0.5rem; border:1px solid #e5e7eb; border-radius:0.375rem }
</style>
