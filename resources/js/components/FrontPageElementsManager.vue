<template>
  <div class="front-page-elements" v-if="loaded">
    <div class="mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
      <p class="text-muted mb-0 small">প্রতিটি সেকশনের তথ্য আলাদাভাবে এডিট ও সেভ করুন।</p>
      <div class="d-flex gap-2">
        <a :href="galleryUrl" class="btn btn-outline-dark btn-sm">
          <i class="fas fa-images mr-1"></i> গ্যালারি ম্যানেজ করুন
        </a>
        <a :href="blogPostsUrl" class="btn btn-outline-primary btn-sm">
          <i class="fas fa-newspaper mr-1"></i> ব্লগ পোস্ট ম্যানেজ করুন
        </a>
      </div>
    </div>

    <!-- Mission -->
    <div class="fpe-card">
      <div class="fpe-card-header fpe-bg-indigo">
        <i class="fas fa-bullseye"></i> ১. আমাদের মিশন
      </div>
      <div class="fpe-card-body">
        <div class="form-group">
          <label class="font-weight-bold small">শিরোনাম</label>
          <input v-model="form.mission.title" type="text" class="form-control">
        </div>
        <div class="form-group mb-0">
          <label class="font-weight-bold small">বিবরণ</label>
          <textarea v-model="form.mission.body" rows="4" class="form-control"></textarea>
        </div>
      </div>
      <div class="fpe-card-footer">
        <button class="btn btn-primary" :disabled="saving === 'mission'" @click="saveSection('mission')">
          <span v-if="saving === 'mission'" class="spinner-border spinner-border-sm mr-1"></span>
          মিশন সেভ করুন
        </button>
      </div>
    </div>

    <!-- Vision -->
    <div class="fpe-card">
      <div class="fpe-card-header fpe-bg-success">
        <i class="fas fa-eye"></i> ২. আমাদের ভিশন
      </div>
      <div class="fpe-card-body">
        <div class="form-group">
          <label class="font-weight-bold small">শিরোনাম</label>
          <input v-model="form.vision.title" type="text" class="form-control">
        </div>
        <div class="form-group mb-0">
          <label class="font-weight-bold small">বিবরণ</label>
          <textarea v-model="form.vision.body" rows="4" class="form-control"></textarea>
        </div>
      </div>
      <div class="fpe-card-footer">
        <button class="btn btn-success" :disabled="saving === 'vision'" @click="saveSection('vision')">
          <span v-if="saving === 'vision'" class="spinner-border spinner-border-sm mr-1"></span>
          ভিশন সেভ করুন
        </button>
      </div>
    </div>

    <!-- Achievements -->
    <div class="fpe-card">
      <div class="fpe-card-header fpe-bg-warning justify-content-between">
        <span><i class="fas fa-trophy"></i> ৩. গৌরবের অর্জন</span>
        <button type="button" class="btn btn-sm btn-dark" @click="addAchievement"><i class="fas fa-plus"></i> যোগ</button>
      </div>
      <div class="fpe-card-body">
        <div v-for="(item, idx) in form.achievements" :key="'ach-'+idx" class="fpe-item">
          <div class="d-flex justify-content-between mb-3">
            <strong class="text-muted small">অর্জন #{{ idx + 1 }}</strong>
            <button type="button" class="btn btn-sm btn-outline-danger" @click="removeAchievement(idx)"><i class="fas fa-trash"></i></button>
          </div>
          <div class="form-row">
            <div class="col-md-3 form-group">
              <label class="small font-weight-bold">বছর</label>
              <input v-model="item.year" type="text" class="form-control form-control-sm" placeholder="২০২৫">
            </div>
            <div class="col-md-9 form-group">
              <label class="small font-weight-bold">শিরোনাম</label>
              <input v-model="item.title" type="text" class="form-control form-control-sm">
            </div>
          </div>
          <div class="form-group">
            <label class="small font-weight-bold d-block">আইকন নির্বাচন করুন</label>
            <icon-picker v-model="item.icon" :options="iconOptions"></icon-picker>
          </div>
          <div class="form-group">
            <label class="small font-weight-bold d-block">রঙ নির্বাচন করুন</label>
            <color-picker v-model="item.color" :options="colorOptions"></color-picker>
          </div>
          <div class="form-group mb-0">
            <label class="small font-weight-bold">বিবরণ</label>
            <textarea v-model="item.description" rows="2" class="form-control form-control-sm"></textarea>
          </div>
        </div>
        <p v-if="!form.achievements.length" class="text-muted small mb-0">কোনো অর্জন যোগ করা হয়নি।</p>
      </div>
      <div class="fpe-card-footer">
        <button class="btn btn-warning text-dark font-weight-bold" :disabled="saving === 'achievements'" @click="saveSection('achievements')">
          <span v-if="saving === 'achievements'" class="spinner-border spinner-border-sm mr-1"></span>
          অর্জন সেভ করুন
        </button>
      </div>
    </div>

    <!-- Facilities -->
    <div class="fpe-card">
      <div class="fpe-card-header fpe-bg-info justify-content-between">
        <span><i class="fas fa-building"></i> ৪. স্কুলের সুবিধাসমূহ</span>
        <button type="button" class="btn btn-sm btn-light" @click="addFacility"><i class="fas fa-plus"></i> যোগ</button>
      </div>
      <div class="fpe-card-body">
        <div v-for="(item, idx) in form.facilities" :key="'fac-'+idx" class="fpe-item">
          <div class="d-flex justify-content-between mb-3">
            <strong class="text-muted small">সুবিধা #{{ idx + 1 }}</strong>
            <button type="button" class="btn btn-sm btn-outline-danger" @click="removeFacility(idx)"><i class="fas fa-trash"></i></button>
          </div>
          <div class="form-group">
            <label class="small font-weight-bold">শিরোনাম</label>
            <input v-model="item.title" type="text" class="form-control form-control-sm">
          </div>
          <div class="form-group">
            <label class="small font-weight-bold d-block">আইকন নির্বাচন করুন</label>
            <icon-picker v-model="item.icon" :options="iconOptions"></icon-picker>
          </div>
          <div class="form-group">
            <label class="small font-weight-bold d-block">রঙ নির্বাচন করুন</label>
            <color-picker v-model="item.color" :options="colorOptions"></color-picker>
          </div>
          <div class="form-group mb-0">
            <label class="small font-weight-bold">বিবরণ</label>
            <textarea v-model="item.description" rows="2" class="form-control form-control-sm"></textarea>
          </div>
        </div>
        <p v-if="!form.facilities.length" class="text-muted small mb-0">কোনো সুবিধা যোগ করা হয়নি।</p>
      </div>
      <div class="fpe-card-footer">
        <button class="btn btn-info" :disabled="saving === 'facilities'" @click="saveSection('facilities')">
          <span v-if="saving === 'facilities'" class="spinner-border spinner-border-sm mr-1"></span>
          সুবিধা সেভ করুন
        </button>
      </div>
    </div>

    <!-- Blog section labels -->
    <div class="fpe-card">
      <div class="fpe-card-header fpe-bg-purple">
        <i class="fas fa-newspaper"></i> ৫. ব্লগ ও সংবাদ (সেকশন শিরোনাম)
      </div>
      <div class="fpe-card-body">
        <p class="small text-muted">পোস্টের বিষয়বস্তু <a :href="blogPostsUrl">ব্লগ পোস্ট</a> মেনু থেকে ম্যানেজ করুন।</p>
        <div class="form-group">
          <label class="font-weight-bold small">সেকশন শিরোনাম</label>
          <input v-model="form.blog_section.title" type="text" class="form-control">
        </div>
        <div class="form-group mb-0">
          <label class="font-weight-bold small">সাব-টাইটেল (ঐচ্ছিক)</label>
          <input v-model="form.blog_section.subtitle" type="text" class="form-control">
        </div>
      </div>
      <div class="fpe-card-footer">
        <button class="btn btn-primary" :disabled="saving === 'blog'" @click="saveSection('blog')">
          <span v-if="saving === 'blog'" class="spinner-border spinner-border-sm mr-1"></span>
          ব্লগ সেকশন সেভ করুন
        </button>
      </div>
    </div>

    <!-- Gallery moved notice -->
    <div class="fpe-card">
      <div class="fpe-card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
          <div class="font-weight-bold"><i class="fas fa-images mr-2 text-dark"></i> ফটো গ্যালারি এখন আলাদা মেনুতে</div>
          <p class="text-muted small mb-0">একাধিক ছবি আপলোড, আপলোড প্রগ্রেস ও এলবাম তৈরির জন্য নতুন গ্যালারি ম্যানেজমেন্ট পেজ ব্যবহার করুন।</p>
        </div>
        <a :href="galleryUrl" class="btn btn-dark"><i class="fas fa-arrow-right mr-1"></i> গ্যালারি ম্যানেজমেন্ট এ যান</a>
      </div>
    </div>
  </div>
  <div v-else class="text-center py-5">
    <div class="spinner-border text-primary"></div>
  </div>
</template>

<script>
import axios from 'axios';

const defaultAchievement = () => ({
  year: '',
  title: '',
  description: '',
  icon: 'fa-trophy',
  color: 'from-amber-400 to-orange-500',
});

const defaultFacility = () => ({
  title: '',
  description: '',
  icon: 'fa-chalkboard',
  color: 'from-indigo-500 to-blue-600',
});

const gradientStops = {
  'from-amber-400': '#fbbf24', 'to-orange-500': '#f97316',
  'from-emerald-400': '#34d399', 'to-teal-500': '#14b8a6',
  'from-violet-400': '#a78bfa', 'to-purple-600': '#9333ea',
  'from-rose-400': '#fb7185', 'to-pink-500': '#ec4899',
  'from-indigo-500': '#6366f1', 'to-blue-600': '#2563eb',
  'from-cyan-500': '#06b6d4', 'to-teal-600': '#0d9488',
  'from-purple-500': '#a855f7', 'to-fuchsia-600': '#c026d3',
  'from-green-500': '#22c55e', 'to-emerald-600': '#059669',
  'from-slate-600': '#475569', 'to-slate-800': '#1e293b',
};

const gradientCss = (value) => {
  const [from, to] = (value || '').split(' to ').map((s, i) => (i === 1 ? 'to-' + s : s));
  const start = gradientStops[from] || '#6366f1';
  const end = gradientStops[to] || '#4f46e5';
  return `linear-gradient(135deg, ${start}, ${end})`;
};

export default {
  components: {
    IconPicker: {
      props: { modelValue: String, options: Array },
      emits: ['update:modelValue'],
      template: `
        <div class="picker-grid">
          <button
            v-for="ic in options" :key="ic" type="button"
            class="picker-btn" :class="{ active: modelValue === ic }"
            @click="$emit('update:modelValue', ic)" :title="ic"
          ><i class="fas" :class="ic"></i></button>
        </div>
      `,
    },
    ColorPicker: {
      props: { modelValue: String, options: Array },
      emits: ['update:modelValue'],
      methods: { gradientCss },
      template: `
        <div class="picker-grid">
          <button
            v-for="c in options" :key="c" type="button"
            class="picker-swatch" :class="{ active: modelValue === c }"
            :style="{ background: gradientCss(c) }"
            @click="$emit('update:modelValue', c)" :title="c"
          ></button>
        </div>
      `,
    },
  },
  props: {
    schoolId: { type: Number, required: true },
    blogPostsUrl: { type: String, required: true },
    galleryUrl: { type: String, required: true },
  },
  data() {
    return {
      loaded: false,
      saving: null,
      form: {
        mission: { title: '', body: '' },
        vision: { title: '', body: '' },
        blog_section: { title: '', subtitle: '' },
        achievements: [],
        facilities: [],
      },
      iconOptions: [
        'fa-trophy', 'fa-medal', 'fa-flask', 'fa-music', 'fa-star', 'fa-award',
        'fa-chalkboard', 'fa-microscope', 'fa-book-open', 'fa-laptop', 'fa-futbol', 'fa-shield-alt',
        'fa-graduation-cap', 'fa-school', 'fa-globe', 'fa-heart', 'fa-lightbulb', 'fa-palette',
      ],
      colorOptions: [
        'from-amber-400 to-orange-500',
        'from-emerald-400 to-teal-500',
        'from-violet-400 to-purple-600',
        'from-rose-400 to-pink-500',
        'from-indigo-500 to-blue-600',
        'from-cyan-500 to-teal-600',
        'from-purple-500 to-fuchsia-600',
        'from-green-500 to-emerald-600',
        'from-slate-600 to-slate-800',
      ],
    };
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    async fetchData() {
      try {
        const res = await axios.get(`/principal/institute/${this.schoolId}/frontend/front-page-elements/data`);
        const c = res.data.homepage_content;
        this.form.mission = { ...c.mission };
        this.form.vision = { ...c.vision };
        this.form.blog_section = { ...(c.blog_section || { title: 'ব্লগ ও সংবাদ', subtitle: '' }) };
        this.form.achievements = (c.achievements || []).map(a => ({ ...a }));
        this.form.facilities = (c.facilities || []).map(f => ({ ...f }));
      } catch (e) {
        if (window.toastr) window.toastr.error('ডেটা লোড করতে সমস্যা হয়েছে');
      } finally {
        this.loaded = true;
      }
    },
    addAchievement() {
      this.form.achievements.push(defaultAchievement());
    },
    removeAchievement(idx) {
      this.form.achievements.splice(idx, 1);
    },
    addFacility() {
      this.form.facilities.push(defaultFacility());
    },
    removeFacility(idx) {
      this.form.facilities.splice(idx, 1);
    },
    buildPayload() {
      const fd = new FormData();
      fd.append('mission[title]', this.form.mission.title || '');
      fd.append('mission[body]', this.form.mission.body || '');
      fd.append('vision[title]', this.form.vision.title || '');
      fd.append('vision[body]', this.form.vision.body || '');
      fd.append('blog_section[title]', this.form.blog_section.title || '');
      fd.append('blog_section[subtitle]', this.form.blog_section.subtitle || '');
      this.form.achievements.forEach((item, i) => {
        Object.keys(item).forEach(key => fd.append(`achievements[${i}][${key}]`, item[key] ?? ''));
      });
      this.form.facilities.forEach((item, i) => {
        Object.keys(item).forEach(key => fd.append(`facilities[${i}][${key}]`, item[key] ?? ''));
      });
      return fd;
    },
    async saveSection(section) {
      this.saving = section;
      try {
        const fd = this.buildPayload();
        await axios.post(`/principal/institute/${this.schoolId}/frontend/front-page-elements/data`, fd);
        if (window.toastr) window.toastr.success('সেভ হয়েছে');
        await this.fetchData();
      } catch (e) {
        if (window.toastr) window.toastr.error(e.response?.data?.message || 'সেভ করতে সমস্যা হয়েছে');
      } finally {
        this.saving = null;
      }
    },
  },
};
</script>

<style scoped>
.fpe-card { background: #fff; border-radius: 1rem; box-shadow: 0 10px 24px -12px rgba(15,23,42,.15); overflow: hidden; margin-bottom: 1.5rem; }
.fpe-card-header { display: flex; align-items: center; gap: .5rem; padding: 1rem 1.5rem; font-weight: 800; color: #fff; }
.fpe-card-body { padding: 1.5rem; }
.fpe-card-footer { padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid #f1f5f9; }
.fpe-bg-indigo { background: linear-gradient(135deg,#6366f1,#4338ca); }
.fpe-bg-success { background: linear-gradient(135deg,#22c55e,#15803d); }
.fpe-bg-warning { background: linear-gradient(135deg,#fbbf24,#d97706); color: #1e293b !important; }
.fpe-bg-info { background: linear-gradient(135deg,#0ea5e9,#0369a1); }
.fpe-bg-purple { background: linear-gradient(135deg,#8b5cf6,#6d28d9); }
.fpe-item { border: 1px solid #e2e8f0; border-radius: .75rem; padding: 1.25rem; margin-bottom: 1rem; background: #f8fafc; }
.picker-grid { display: flex; flex-wrap: wrap; gap: 8px; }
.picker-btn {
  width: 42px; height: 42px; border-radius: 10px; border: 2px solid #e2e8f0; background: #fff;
  color: #475569; display: flex; align-items: center; justify-content: center; font-size: 1rem; transition: all .15s;
}
.picker-btn:hover { border-color: #a5b4fc; }
.picker-btn.active { border-color: #4f46e5; background: #eef2ff; color: #4338ca; box-shadow: 0 0 0 3px rgba(79,70,229,.15); }
.picker-swatch {
  width: 42px; height: 42px; border-radius: 10px; border: 2px solid transparent; cursor: pointer; transition: all .15s;
}
.picker-swatch:hover { transform: translateY(-2px); }
.picker-swatch.active { border-color: #1e293b; box-shadow: 0 0 0 3px rgba(30,41,59,.2); }
</style>
