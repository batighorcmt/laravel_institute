<template>
  <div v-if="loaded">
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white font-weight-bold">ডাটা মোড</div>
      <div class="card-body">
        <div class="d-flex flex-wrap">
          <div class="custom-control custom-radio mr-4 mb-2">
            <input id="mode-dynamic" v-model="form.mode" type="radio" value="dynamic" class="custom-control-input">
            <label class="custom-control-label" for="mode-dynamic">
              <strong>ডাইনামিক</strong> — সফটওয়্যারের সংশ্লিষ্ট টেবিল থেকে সয়ংক্রিয়ভাবে গণনা করবে
            </label>
          </div>
          <div class="custom-control custom-radio mb-2">
            <input id="mode-static" v-model="form.mode" type="radio" value="static" class="custom-control-input">
            <label class="custom-control-label" for="mode-static">
              <strong>স্ট্যাটিক</strong> — নিজে সংখ্যা টাইপ করে সংরক্ষণ করবেন
            </label>
          </div>
        </div>
      </div>
    </div>

    <div v-if="form.mode === 'dynamic'" class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white font-weight-bold">লাইভ প্রিভিউ (সফটওয়্যার থেকে গণনাকৃত)</div>
      <div class="card-body">
        <div class="row text-center">
          <div class="col-6 col-md-3 mb-3">
            <div class="h2 mb-0 text-primary">{{ dynamicPreview.students }}</div>
            <div class="text-muted small">শিক্ষার্থী</div>
          </div>
          <div class="col-6 col-md-3 mb-3">
            <div class="h2 mb-0 text-primary">{{ dynamicPreview.teachers }}</div>
            <div class="text-muted small">শিক্ষক</div>
          </div>
          <div class="col-6 col-md-3 mb-3">
            <div class="h2 mb-0 text-primary">{{ dynamicPreview.classes }}</div>
            <div class="text-muted small">শ্রেণি</div>
          </div>
          <div class="col-6 col-md-3 mb-3">
            <div class="h2 mb-0 text-primary">{{ dynamicPreview.founding_year || '—' }}</div>
            <div class="text-muted small">প্রতিষ্ঠাকাল</div>
          </div>
        </div>
        <p class="text-muted small mb-0">
          <i class="fas fa-info-circle mr-1"></i>
          "কর্মচারী" (নন-টিচিং স্টাফ) এর জন্য সফটওয়্যারে এখনও কোনো পৃথক তথ্য নেই, তাই ডাইনামিক মোডে এই ঘরটি দেখানো হবে না। প্রয়োজনে স্ট্যাটিক মোডে টাইপ করে দিন।
        </p>
      </div>
    </div>

    <div v-else class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white font-weight-bold">স্ট্যাটিক তথ্য</div>
      <div class="card-body">
        <div class="form-row">
          <div class="col-md-4 col-6 form-group">
            <label>শিক্ষার্থী সংখ্যা</label>
            <input v-model.number="form.static_students_count" type="number" min="0" class="form-control">
          </div>
          <div class="col-md-4 col-6 form-group">
            <label>শিক্ষক সংখ্যা</label>
            <input v-model.number="form.static_teachers_count" type="number" min="0" class="form-control">
          </div>
          <div class="col-md-4 col-6 form-group">
            <label>কর্মচারী সংখ্যা</label>
            <input v-model.number="form.static_staff_count" type="number" min="0" class="form-control">
          </div>
          <div class="col-md-4 col-6 form-group">
            <label>শ্রেণি সংখ্যা</label>
            <input v-model.number="form.static_classes_count" type="number" min="0" class="form-control">
          </div>
          <div class="col-md-4 col-6 form-group">
            <label>প্রতিষ্ঠাকাল (সন)</label>
            <input v-model.number="form.static_founding_year" type="number" min="1800" :max="currentYear" class="form-control">
          </div>
        </div>
      </div>
    </div>

    <div v-if="serverErrors.length" class="alert alert-danger">
      <ul class="mb-0 pl-3"><li v-for="(e, i) in serverErrors" :key="i">{{ e }}</li></ul>
    </div>

    <button type="button" class="btn btn-primary btn-lg" :disabled="saving" @click="save">
      <span v-if="saving" class="spinner-border spinner-border-sm mr-1"></span>
      সংরক্ষণ করুন
    </button>
  </div>
  <div v-else class="text-center py-5 text-muted">
    <span class="spinner-border"></span> লোড হচ্ছে...
  </div>
</template>

<script>
export default {
  props: {
    schoolId: { type: Number, required: true },
  },
  data() {
    return {
      loaded: false,
      saving: false,
      form: {
        mode: 'dynamic',
        static_students_count: null,
        static_teachers_count: null,
        static_staff_count: null,
        static_classes_count: null,
        static_founding_year: null,
      },
      dynamicPreview: { students: 0, teachers: 0, classes: 0, founding_year: null },
      serverErrors: [],
      currentYear: new Date().getFullYear(),
    };
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    async fetchData() {
      try {
        const resp = await axios.get(`/principal/institute/${this.schoolId}/frontend/stats/data`);
        const s = resp.data.settings || {};
        this.form = {
          mode: s.mode || 'dynamic',
          static_students_count: s.static_students_count,
          static_teachers_count: s.static_teachers_count,
          static_staff_count: s.static_staff_count,
          static_classes_count: s.static_classes_count,
          static_founding_year: s.static_founding_year,
        };
        this.dynamicPreview = resp.data.dynamicPreview || this.dynamicPreview;
      } catch (e) {
        if (window.toastr) window.toastr.error('ডেটা লোড করতে সমস্যা হয়েছে');
      } finally {
        this.loaded = true;
      }
    },
    async save() {
      this.saving = true;
      this.serverErrors = [];
      try {
        const resp = await axios.post(`/principal/institute/${this.schoolId}/frontend/stats/data`, this.form);
        if (window.toastr) window.toastr.success(resp.data.message);
        this.dynamicPreview = resp.data.dynamicPreview || this.dynamicPreview;
      } catch (e) {
        if (e.response?.status === 422 && e.response.data.errors) {
          this.serverErrors = Object.values(e.response.data.errors).flat();
        } else if (window.toastr) {
          window.toastr.error('সংরক্ষণ করতে সমস্যা হয়েছে');
        }
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>
