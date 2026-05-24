<template>
  <div class="front-page-elements" v-if="loaded">
    <div class="mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
      <p class="text-muted mb-0 small">প্রতিটি সেকশনের তথ্য আলাদাভাবে এডিট ও সেভ করুন।</p>
      <a :href="blogPostsUrl" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-newspaper mr-1"></i> ব্লগ পোস্ট ম্যানেজ করুন
      </a>
    </div>

    <!-- Mission -->
    <div class="card shadow-sm border-0 mb-4 rounded-lg overflow-hidden">
      <div class="card-header bg-indigo text-white font-weight-bold">
        <i class="fas fa-bullseye mr-2"></i> ১. আমাদের মিশন
      </div>
      <div class="card-body">
        <div class="form-group">
          <label class="font-weight-bold small">শিরোনাম</label>
          <input v-model="form.mission.title" type="text" class="form-control">
        </div>
        <div class="form-group mb-0">
          <label class="font-weight-bold small">বিবরণ</label>
          <textarea v-model="form.mission.body" rows="4" class="form-control"></textarea>
        </div>
      </div>
      <div class="card-footer bg-light">
        <button class="btn btn-primary" :disabled="saving === 'mission'" @click="saveSection('mission')">
          <span v-if="saving === 'mission'" class="spinner-border spinner-border-sm mr-1"></span>
          মিশন সেভ করুন
        </button>
      </div>
    </div>

    <!-- Vision -->
    <div class="card shadow-sm border-0 mb-4 rounded-lg overflow-hidden">
      <div class="card-header bg-success text-white font-weight-bold">
        <i class="fas fa-eye mr-2"></i> ২. আমাদের ভিশন
      </div>
      <div class="card-body">
        <div class="form-group">
          <label class="font-weight-bold small">শিরোনাম</label>
          <input v-model="form.vision.title" type="text" class="form-control">
        </div>
        <div class="form-group mb-0">
          <label class="font-weight-bold small">বিবরণ</label>
          <textarea v-model="form.vision.body" rows="4" class="form-control"></textarea>
        </div>
      </div>
      <div class="card-footer bg-light">
        <button class="btn btn-success" :disabled="saving === 'vision'" @click="saveSection('vision')">
          <span v-if="saving === 'vision'" class="spinner-border spinner-border-sm mr-1"></span>
          ভিশন সেভ করুন
        </button>
      </div>
    </div>

    <!-- Achievements -->
    <div class="card shadow-sm border-0 mb-4 rounded-lg overflow-hidden">
      <div class="card-header bg-warning text-dark font-weight-bold d-flex justify-content-between align-items-center">
        <span><i class="fas fa-trophy mr-2"></i> ৩. গৌরবের অর্জন</span>
        <button type="button" class="btn btn-sm btn-dark" @click="addAchievement"><i class="fas fa-plus"></i> যোগ</button>
      </div>
      <div class="card-body">
        <div v-for="(item, idx) in form.achievements" :key="'ach-'+idx" class="border rounded p-3 mb-3 bg-light">
          <div class="d-flex justify-content-between mb-2">
            <strong class="text-muted small">অর্জন #{{ idx + 1 }}</strong>
            <button type="button" class="btn btn-sm btn-outline-danger" @click="removeAchievement(idx)"><i class="fas fa-trash"></i></button>
          </div>
          <div class="form-row">
            <div class="col-md-2 form-group">
              <label class="small font-weight-bold">বছর</label>
              <input v-model="item.year" type="text" class="form-control form-control-sm" placeholder="২০২৫">
            </div>
            <div class="col-md-4 form-group">
              <label class="small font-weight-bold">শিরোনাম</label>
              <input v-model="item.title" type="text" class="form-control form-control-sm">
            </div>
            <div class="col-md-3 form-group">
              <label class="small font-weight-bold">আইকন</label>
              <select v-model="item.icon" class="form-control form-control-sm">
                <option v-for="ic in iconOptions" :key="ic" :value="ic">{{ ic }}</option>
              </select>
            </div>
            <div class="col-md-3 form-group">
              <label class="small font-weight-bold">রঙ</label>
              <select v-model="item.color" class="form-control form-control-sm">
                <option v-for="c in colorOptions" :key="c" :value="c">{{ c }}</option>
              </select>
            </div>
            <div class="col-12 form-group mb-0">
              <label class="small font-weight-bold">বিবরণ</label>
              <textarea v-model="item.description" rows="2" class="form-control form-control-sm"></textarea>
            </div>
          </div>
        </div>
      </div>
      <div class="card-footer bg-light">
        <button class="btn btn-warning text-dark font-weight-bold" :disabled="saving === 'achievements'" @click="saveSection('achievements')">
          <span v-if="saving === 'achievements'" class="spinner-border spinner-border-sm mr-1"></span>
          অর্জন সেভ করুন
        </button>
      </div>
    </div>

    <!-- Facilities -->
    <div class="card shadow-sm border-0 mb-4 rounded-lg overflow-hidden">
      <div class="card-header bg-info text-white font-weight-bold d-flex justify-content-between align-items-center">
        <span><i class="fas fa-building mr-2"></i> ৫. স্কুলের সুবিধাসমূহ</span>
        <button type="button" class="btn btn-sm btn-light" @click="addFacility"><i class="fas fa-plus"></i> যোগ</button>
      </div>
      <div class="card-body">
        <div v-for="(item, idx) in form.facilities" :key="'fac-'+idx" class="border rounded p-3 mb-3 bg-light">
          <div class="d-flex justify-content-between mb-2">
            <strong class="text-muted small">সুবিধা #{{ idx + 1 }}</strong>
            <button type="button" class="btn btn-sm btn-outline-danger" @click="removeFacility(idx)"><i class="fas fa-trash"></i></button>
          </div>
          <div class="form-row">
            <div class="col-md-4 form-group">
              <label class="small font-weight-bold">শিরোনাম</label>
              <input v-model="item.title" type="text" class="form-control form-control-sm">
            </div>
            <div class="col-md-4 form-group">
              <label class="small font-weight-bold">আইকন</label>
              <select v-model="item.icon" class="form-control form-control-sm">
                <option v-for="ic in iconOptions" :key="'f-'+ic" :value="ic">{{ ic }}</option>
              </select>
            </div>
            <div class="col-md-4 form-group">
              <label class="small font-weight-bold">রঙ</label>
              <select v-model="item.color" class="form-control form-control-sm">
                <option v-for="c in colorOptions" :key="'fc-'+c" :value="c">{{ c }}</option>
              </select>
            </div>
            <div class="col-12 form-group mb-0">
              <label class="small font-weight-bold">বিবরণ</label>
              <textarea v-model="item.description" rows="2" class="form-control form-control-sm"></textarea>
            </div>
          </div>
        </div>
      </div>
      <div class="card-footer bg-light">
        <button class="btn btn-info" :disabled="saving === 'facilities'" @click="saveSection('facilities')">
          <span v-if="saving === 'facilities'" class="spinner-border spinner-border-sm mr-1"></span>
          সুবিধা সেভ করুন
        </button>
      </div>
    </div>

    <!-- Blog section labels -->
    <div class="card shadow-sm border-0 mb-4 rounded-lg overflow-hidden">
      <div class="card-header bg-purple text-white font-weight-bold" style="background: linear-gradient(135deg,#6366f1,#a855f7);">
        <i class="fas fa-newspaper mr-2"></i> ৬. ব্লগ ও সংবাদ (সেকশন শিরোনাম)
      </div>
      <div class="card-body">
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
      <div class="card-footer bg-light">
        <button class="btn btn-primary" :disabled="saving === 'blog'" @click="saveSection('blog')">
          <span v-if="saving === 'blog'" class="spinner-border spinner-border-sm mr-1"></span>
          ব্লগ সেকশন সেভ করুন
        </button>
      </div>
    </div>

    <!-- Gallery -->
    <div class="card shadow-sm border-0 mb-4 rounded-lg overflow-hidden">
      <div class="card-header bg-dark text-white font-weight-bold">
        <i class="fas fa-images mr-2"></i> ৭. ফটো গ্যালারী
      </div>
      <div class="card-body">
        <div class="mb-4 p-4 border border-dashed rounded text-center bg-light">
          <input type="file" multiple accept="image/*" class="form-control-file" @change="onGallerySelected">
          <small class="text-muted d-block mt-2">একাধিক ছবি নির্বাচন করতে পারবেন (JPG, PNG, WEBP)</small>
        </div>
        <div v-if="form.gallery.length" class="row">
          <div v-for="(path, idx) in form.gallery" :key="'gal-'+idx" class="col-6 col-md-3 mb-3">
            <div class="position-relative rounded overflow-hidden shadow-sm border">
              <img :src="galleryPreview(path)" class="w-100" style="height:140px;object-fit:cover" alt="">
              <button type="button" class="btn btn-danger btn-sm position-absolute" style="top:8px;right:8px" @click="removeGalleryImage(path)">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
        </div>
        <p v-else class="text-muted small mb-0">কোনো গ্যালারি ছবি নেই।</p>
      </div>
      <div class="card-footer bg-light">
        <button class="btn btn-dark" :disabled="saving === 'gallery'" @click="saveSection('gallery')">
          <span v-if="saving === 'gallery'" class="spinner-border spinner-border-sm mr-1"></span>
          গ্যালারি সেভ করুন
        </button>
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

export default {
  props: {
    schoolId: { type: Number, required: true },
    blogPostsUrl: { type: String, required: true },
  },
  data() {
    return {
      loaded: false,
      saving: null,
      galleryFiles: [],
      form: {
        mission: { title: '', body: '' },
        vision: { title: '', body: '' },
        blog_section: { title: '', subtitle: '' },
        achievements: [],
        facilities: [],
        gallery: [],
      },
      iconOptions: [
        'fa-trophy', 'fa-medal', 'fa-flask', 'fa-music', 'fa-star', 'fa-award',
        'fa-chalkboard', 'fa-microscope', 'fa-book-open', 'fa-laptop', 'fa-futbol', 'fa-shield-alt',
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
        this.form.gallery = [...(res.data.gallery_paths || [])];
        this.galleryFiles = [];
      } catch (e) {
        if (window.toastr) window.toastr.error('ডেটা লোড করতে সমস্যা হয়েছে');
      } finally {
        this.loaded = true;
      }
    },
    galleryPreview(path) {
      if (!path) return '';
      if (path.startsWith('http')) return path;
      return `/storage/${path.replace(/^\/+/, '').replace(/^storage\//, '')}`;
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
    onGallerySelected(e) {
      this.galleryFiles = Array.from(e.target.files || []);
    },
    async removeGalleryImage(path) {
      if (!confirm('এই ছবি মুছে ফেলবেন?')) return;
      try {
        await axios.delete(`/principal/institute/${this.schoolId}/frontend/front-page-elements/gallery`, { data: { path } });
        this.form.gallery = this.form.gallery.filter(p => p !== path);
        if (window.toastr) window.toastr.success('ছবি মুছে ফেলা হয়েছে');
      } catch (e) {
        if (window.toastr) window.toastr.error('মুছতে সমস্যা হয়েছে');
      }
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
      this.form.gallery.forEach((path, i) => fd.append(`gallery_existing[${i}]`, path));
      this.galleryFiles.forEach((file, i) => fd.append(`gallery_images[${i}]`, file));
      return fd;
    },
    async saveSection(section) {
      this.saving = section;
      try {
        const fd = this.buildPayload();
        await axios.post(`/principal/institute/${this.schoolId}/frontend/front-page-elements/data`, fd);
        if (window.toastr) window.toastr.success('সেভ হয়েছে');
        this.galleryFiles = [];
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
.front-page-elements .card-header { border-bottom: none; }
</style>
