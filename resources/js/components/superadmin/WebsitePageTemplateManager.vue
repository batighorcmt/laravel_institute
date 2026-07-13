<template>
  <div class="card shadow border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
      <h3 class="card-title m-0 font-weight-bold text-primary">
        <i class="fas fa-file-alt mr-2"></i> ডিফল্ট পৃষ্ঠা টেমপ্লেট
      </h3>
      <button class="btn btn-primary" @click="openModal()">
        <i class="fas fa-plus mr-1"></i> নতুন টেমপ্লেট
      </button>
    </div>
    <p class="text-muted small px-3 pt-3 mb-0">
      এখানে শুধু পৃষ্ঠার নাম ও স্লাগ নির্ধারণ করুন। পৃষ্ঠার কনটেন্ট ডাইনামিক (সফটওয়্যার থেকে) নাকি স্ট্যাটিক (এডিটরে টাইপ করা) হবে তা প্রতিষ্ঠান প্রধান টেমপ্লেট প্রয়োগ করার পর নিজের "Pages" স্ক্রিন থেকে নির্বাচন করবেন।
    </p>

    <div class="card-body p-0">
      <div v-if="loading" class="text-center p-5">
        <div class="spinner-border text-primary"></div>
      </div>
      <div v-else-if="pageTemplates.length === 0" class="text-center p-5 text-muted">
        কোনো পৃষ্ঠা টেমপ্লেট পাওয়া যায়নি।
      </div>
      <div class="table-responsive" v-else>
        <table class="table table-hover table-striped mb-0 align-middle">
          <thead class="thead-light">
            <tr>
              <th>শিরোনাম</th>
              <th>Key / Slug</th>
              <th class="text-center">স্ট্যাটাস</th>
              <th class="text-center" width="180">অ্যাকশন</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="pt in pageTemplates" :key="pt.id">
              <td class="font-weight-bold">{{ pt.title_bn || pt.title }}</td>
              <td><code>{{ pt.key }}</code> / <code>/{{ pt.default_slug }}</code></td>
              <td class="text-center">
                <button class="btn btn-sm" :class="pt.is_active ? 'btn-success' : 'btn-secondary'" @click="toggle(pt)">
                  {{ pt.is_active ? 'চালু' : 'বাতিল' }}
                </button>
              </td>
              <td class="text-center">
                <button class="btn btn-sm btn-light text-primary border shadow-sm mr-2" @click="openModal(pt)">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-light text-danger border shadow-sm" @click="remove(pt)">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="modal fade" id="pageTemplateModal" tabindex="-1" ref="modal">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-light">
            <h5 class="modal-title font-weight-bold">{{ isEditing ? 'টেমপ্লেট আপডেট করুন' : 'নতুন পৃষ্ঠা টেমপ্লেট' }}</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <form @submit.prevent="submitForm">
            <div class="modal-body">
              <div v-if="serverErrors.length" class="alert alert-danger">
                <ul class="mb-0 pl-3"><li v-for="(e, i) in serverErrors" :key="i">{{ e }}</li></ul>
              </div>

              <div class="form-group">
                <label>Key <span class="text-danger">*</span></label>
                <input v-model="form.key" type="text" class="form-control" required placeholder="history">
              </div>
              <div class="form-group">
                <label>শিরোনাম (English) <span class="text-danger">*</span></label>
                <input v-model="form.title" type="text" class="form-control" required>
              </div>
              <div class="form-group">
                <label>শিরোনাম (বাংলা)</label>
                <input v-model="form.title_bn" type="text" class="form-control">
              </div>
              <div class="form-group">
                <label>ডিফল্ট URL স্লাগ <span class="text-danger">*</span></label>
                <input v-model="form.default_slug" type="text" class="form-control" required placeholder="history">
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
export default {
  props: {
    apiUrl: { type: String, required: true },
    csrfToken: { type: String, required: true },
  },
  data() {
    return {
      pageTemplates: [],
      loading: true,
      saving: false,
      isEditing: false,
      currentId: null,
      form: this.emptyForm(),
      serverErrors: [],
    };
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    emptyForm() {
      return { key: '', title: '', title_bn: '', default_slug: '', is_active: true };
    },
    async fetchData() {
      this.loading = true;
      try {
        const resp = await fetch(this.apiUrl, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await resp.json();
        this.pageTemplates = data.pageTemplates || [];
      } finally {
        this.loading = false;
      }
    },
    openModal(pt = null) {
      this.serverErrors = [];
      if (pt) {
        this.isEditing = true;
        this.currentId = pt.id;
        this.form = {
          key: pt.key,
          title: pt.title,
          title_bn: pt.title_bn || '',
          default_slug: pt.default_slug,
          is_active: !!pt.is_active,
        };
      } else {
        this.isEditing = false;
        this.currentId = null;
        this.form = this.emptyForm();
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
    async toggle(pt) {
      const resp = await fetch(`${this.apiUrl}/${pt.id}/toggle`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': this.csrfToken, Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await resp.json();
      if (resp.ok) {
        if (window.toastr) window.toastr.success(data.message);
        this.fetchData();
      }
    },
    async remove(pt) {
      if (!confirm(`"${pt.title}" টেমপ্লেট মুছে ফেলবেন?`)) return;
      const resp = await fetch(`${this.apiUrl}/${pt.id}`, {
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
