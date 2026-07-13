<template>
  <div class="card shadow border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
      <h3 class="card-title m-0 font-weight-bold text-primary">
        <i class="fas fa-palette mr-2"></i> ওয়েবসাইট থিম
      </h3>
      <button class="btn btn-primary" @click="openModal()">
        <i class="fas fa-plus mr-1"></i> নতুন থিম
      </button>
    </div>

    <div class="card-body p-0">
      <div v-if="loading" class="text-center p-5">
        <div class="spinner-border text-primary"></div>
      </div>

      <div v-else-if="themes.length === 0" class="text-center p-5 text-muted">
        কোনো থিম পাওয়া যায়নি। নতুন থিম যুক্ত করুন।
      </div>

      <div class="table-responsive" v-else>
        <table class="table table-hover table-striped mb-0 align-middle">
          <thead class="thead-light">
            <tr>
              <th>নাম</th>
              <th>টেমপ্লেট (প্যাটার্ন)</th>
              <th>কালার প্রিভিউ</th>
              <th class="text-center">ডিফল্ট</th>
              <th class="text-center">স্ট্যাটাস</th>
              <th class="text-center" width="180">অ্যাকশন</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="theme in themes" :key="theme.id">
              <td class="font-weight-bold">{{ theme.name }}</td>
              <td>
                <span class="badge" :class="theme.template_key === 'theme-2' ? 'badge-warning' : 'badge-secondary'">
                  {{ templateLabel(theme.template_key) }}
                </span>
              </td>
              <td>
                <span class="swatch" :style="{ background: theme.colors.primary }" title="Primary"></span>
                <span class="swatch" :style="{ background: theme.colors.secondary }" title="Secondary"></span>
                <span class="swatch" :style="{ background: theme.colors.accent }" title="Accent"></span>
              </td>
              <td class="text-center">
                <span v-if="theme.is_default" class="badge badge-info">ডিফল্ট</span>
              </td>
              <td class="text-center">
                <button
                  class="btn btn-sm"
                  :class="theme.is_active ? 'btn-success' : 'btn-secondary'"
                  @click="toggle(theme)"
                >
                  {{ theme.is_active ? 'চালু' : 'বাতিল' }}
                </button>
              </td>
              <td class="text-center">
                <button class="btn btn-sm btn-light text-primary border shadow-sm mr-2" @click="openModal(theme)">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-light text-danger border shadow-sm" @click="remove(theme)">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="modal fade" id="themeModal" tabindex="-1" ref="modal">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-light">
            <h5 class="modal-title font-weight-bold">{{ isEditing ? 'থিম আপডেট করুন' : 'নতুন থিম' }}</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <form @submit.prevent="submitForm">
            <div class="modal-body">
              <div v-if="serverErrors.length" class="alert alert-danger">
                <ul class="mb-0 pl-3"><li v-for="(e, i) in serverErrors" :key="i">{{ e }}</li></ul>
              </div>

              <div class="form-group">
                <label>থিমের নাম <span class="text-danger">*</span></label>
                <input v-model="form.name" type="text" class="form-control" required placeholder="যেমন: ক্লাসিক গোল্ড">
              </div>
              <div class="form-group">
                <label>বিবরণ</label>
                <input v-model="form.description" type="text" class="form-control">
              </div>
              <div class="form-group">
                <label>টেমপ্লেট (প্যাটার্ন/লেআউট) <span class="text-danger">*</span></label>
                <select v-model="form.template_key" class="form-control">
                  <option value="theme-1">থিম ১ — বিদ্যমান লেআউট (ইন্ডিগো হোমপেজ)</option>
                  <option value="theme-2">থিম ২ — ঐতিহ্যবাহী লেআউট (পাইন/মেরুন/গোল্ড)</option>
                </select>
                <small class="text-muted">টেমপ্লেট বদলালে পুরো পৃষ্ঠার গঠন/প্যাটার্ন বদলে যাবে, শুধু রং নয়।</small>
              </div>

              <div class="form-row">
                <div class="col-6 form-group">
                  <label>প্রাইমারি কালার</label>
                  <input v-model="form.colors.primary" type="color" class="form-control form-control-color">
                </div>
                <div class="col-6 form-group">
                  <label>সেকেন্ডারি কালার</label>
                  <input v-model="form.colors.secondary" type="color" class="form-control form-control-color">
                </div>
                <div class="col-6 form-group">
                  <label>অ্যাকসেন্ট কালার</label>
                  <input v-model="form.colors.accent" type="color" class="form-control form-control-color">
                </div>
                <div class="col-6 form-group">
                  <label>ব্যাকগ্রাউন্ড কালার</label>
                  <input v-model="form.colors.bg" type="color" class="form-control form-control-color">
                </div>
                <div class="col-6 form-group">
                  <label>টেক্সট কালার</label>
                  <input v-model="form.colors.text" type="color" class="form-control form-control-color">
                </div>
                <div class="col-6 form-group">
                  <label>ফন্ট</label>
                  <input v-model="form.font_family" type="text" class="form-control" placeholder="'Hind Siliguri', sans-serif">
                </div>
              </div>

              <div class="custom-control custom-checkbox">
                <input id="theme-default" v-model="form.is_default" type="checkbox" class="custom-control-input">
                <label class="custom-control-label" for="theme-default">এটি ডিফল্ট থিম হিসেবে সেট করুন</label>
              </div>
            </div>
            <div class="modal-footer bg-light">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">বন্ধ করুন</button>
              <button type="submit" class="btn btn-primary" :disabled="saving">
                {{ saving ? 'সেভ হচ্ছে...' : 'সেভ করুন' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
function emptyColors() {
  return { primary: '#d97706', secondary: '#92400e', accent: '#f59e0b', bg: '#fefcf5', text: '#1f2937' };
}

export default {
  props: {
    apiUrl: { type: String, required: true },
    uploadUrl: { type: String, required: true },
    csrfToken: { type: String, required: true },
  },
  data() {
    return {
      themes: [],
      loading: true,
      saving: false,
      isEditing: false,
      currentId: null,
      form: { name: '', description: '', template_key: 'theme-1', colors: emptyColors(), font_family: '', is_default: false },
      serverErrors: [],
    };
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    templateLabel(key) {
      return key === 'theme-2' ? 'থিম ২ (ঐতিহ্যবাহী)' : 'থিম ১ (বিদ্যমান)';
    },
    async fetchData() {
      this.loading = true;
      try {
        const resp = await fetch(this.apiUrl, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await resp.json();
        this.themes = data.themes || [];
      } finally {
        this.loading = false;
      }
    },
    openModal(theme = null) {
      this.serverErrors = [];
      if (theme) {
        this.isEditing = true;
        this.currentId = theme.id;
        this.form = {
          name: theme.name,
          description: theme.description || '',
          template_key: theme.template_key || 'theme-1',
          colors: { ...emptyColors(), ...theme.colors },
          font_family: theme.font_family || '',
          is_default: !!theme.is_default,
        };
      } else {
        this.isEditing = false;
        this.currentId = null;
        this.form = { name: '', description: '', template_key: 'theme-1', colors: emptyColors(), font_family: '', is_default: false };
      }
      $(this.$refs.modal).modal('show');
    },
    async submitForm() {
      this.saving = true;
      this.serverErrors = [];
      const url = this.isEditing ? `${this.apiUrl}/${this.currentId}` : this.apiUrl;
      const method = this.isEditing ? 'PUT' : 'POST';
      try {
        const resp = await fetch(url, {
          method,
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': this.csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify(this.form),
        });
        const data = await resp.json();
        if (!resp.ok) {
          this.serverErrors = resp.status === 422 && data.errors ? Object.values(data.errors).flat() : [data.message || 'সমস্যা হয়েছে'];
        } else {
          $(this.$refs.modal).modal('hide');
          this.fetchData();
          if (window.toastr) window.toastr.success(data.message);
        }
      } catch (e) {
        this.serverErrors = ['সার্ভারে সমস্যা হয়েছে।'];
      } finally {
        this.saving = false;
      }
    },
    async toggle(theme) {
      const resp = await fetch(`${this.apiUrl}/${theme.id}/toggle`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': this.csrfToken, Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await resp.json();
      if (resp.ok) {
        if (window.toastr) window.toastr.success(data.message);
        this.fetchData();
      }
    },
    async remove(theme) {
      if (!confirm(`"${theme.name}" থিমটি মুছে ফেলবেন?`)) return;
      const resp = await fetch(`${this.apiUrl}/${theme.id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': this.csrfToken, Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await resp.json();
      if (resp.ok) {
        if (window.toastr) window.toastr.success(data.message);
        this.fetchData();
      } else {
        alert(data.message || 'মুছে ফেলা সম্ভব হয়নি।');
      }
    },
  },
};
</script>

<style scoped>
.swatch {
  display: inline-block;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  margin-right: 4px;
  border: 1px solid rgba(0, 0, 0, 0.15);
  vertical-align: middle;
}
.form-control-color {
  width: 100%;
  height: 42px;
}
</style>
