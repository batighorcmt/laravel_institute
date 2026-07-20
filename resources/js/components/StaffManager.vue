<template>
  <div v-if="loaded">
    <div class="row mb-4">
      <div class="col-6 col-md-3 mb-3">
        <div class="card shadow-sm border-0 text-center py-3">
          <div class="h3 mb-0 text-primary">{{ staff.length }}</div>
          <div class="text-muted small">মোট কর্মচারী</div>
        </div>
      </div>
      <div class="col-6 col-md-3 mb-3">
        <div class="card shadow-sm border-0 text-center py-3">
          <div class="h3 mb-0 text-success">{{ activeCount }}</div>
          <div class="text-muted small">সক্রিয়</div>
        </div>
      </div>
      <div class="col-6 col-md-3 mb-3">
        <div class="card shadow-sm border-0 text-center py-3">
          <div class="h3 mb-0 text-info">{{ visibleCount }}</div>
          <div class="text-muted small">ওয়েবসাইটে দৃশ্যমান</div>
        </div>
      </div>
      <div class="col-6 col-md-3 mb-3 d-flex flex-column gap-2">
        <button type="button" class="btn btn-primary btn-block mb-2" @click="openModal()">
          <i class="fas fa-plus mr-1"></i> নতুন কর্মচারী
        </button>
        <button type="button" class="btn btn-outline-secondary btn-block" @click="openPrintModal">
          <i class="fas fa-print mr-1"></i> প্রিন্ট
        </button>
      </div>
    </div>

    <div class="card shadow-sm border-0">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <input v-model="search" type="text" class="form-control form-control-sm w-auto" placeholder="খুঁজুন (নাম/পদবী/ফোন)...">
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="thead-light">
            <tr>
              <th width="50">#</th>
              <th width="70">ছবি</th>
              <th>নাম</th>
              <th>পদবী</th>
              <th>যোগাযোগ</th>
              <th class="text-center">অবস্থা</th>
              <th class="text-center">লগইন</th>
              <th class="text-center" width="150">অ্যাকশন</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(s, idx) in filteredStaff" :key="s.id">
              <td>{{ idx + 1 }}</td>
              <td>
                <img v-if="s.photo_url" :src="s.photo_url" class="staff-avatar" alt="">
                <div v-else class="staff-avatar staff-avatar--placeholder">{{ (s.full_name_bn || s.first_name || '?').charAt(0) }}</div>
              </td>
              <td>
                <strong>{{ s.full_name_bn || s.full_name }}</strong>
                <div class="text-muted small" v-if="s.full_name_bn">{{ s.full_name }}</div>
              </td>
              <td>{{ s.designation_label || '—' }}</td>
              <td>
                <div v-if="s.phone" class="small"><i class="fas fa-phone-alt text-muted mr-1"></i>{{ s.phone }}</div>
                <div v-if="s.email" class="small"><i class="fas fa-envelope text-muted mr-1"></i>{{ s.email }}</div>
              </td>
              <td class="text-center">
                <span class="badge" :class="s.status === 'active' ? 'badge-success' : 'badge-secondary'">
                  {{ s.status === 'active' ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                </span>
              </td>
              <td class="text-center">
                <template v-if="s.has_login">
                  <div class="small"><span class="badge badge-success">{{ s.username }}</span></div>
                  <div class="small text-muted mt-1">পাস: <code>{{ s.plain_password || '—' }}</code></div>
                  <button class="btn btn-sm btn-outline-warning mt-1" :disabled="resettingFor === s.id" @click="resetPassword(s)">
                    <span v-if="resettingFor === s.id" class="spinner-border spinner-border-sm"></span>
                    <template v-else><i class="fas fa-sync-alt mr-1"></i>রিসেট</template>
                  </button>
                </template>
                <button v-else class="btn btn-sm btn-outline-primary" :disabled="creatingLoginFor === s.id" @click="createLogin(s)">
                  <span v-if="creatingLoginFor === s.id" class="spinner-border spinner-border-sm"></span>
                  <template v-else><i class="fas fa-key mr-1"></i>লগইন তৈরি করুন</template>
                </button>
              </td>
              <td class="text-center">
                <button class="btn btn-sm btn-light text-primary border shadow-sm mr-2" @click="openModal(s)"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-light text-danger border shadow-sm" @click="remove(s)"><i class="fas fa-trash-alt"></i></button>
              </td>
            </tr>
            <tr v-if="!filteredStaff.length">
              <td colspan="8" class="text-center text-muted py-5">কোনো কর্মচারী পাওয়া যায়নি। "নতুন কর্মচারী" থেকে যুক্ত করুন।</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="staffModal" tabindex="-1" ref="modal">
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-light">
            <h5 class="modal-title font-weight-bold">{{ isEditing ? 'কর্মচারীর তথ্য সম্পাদনা' : 'নতুন কর্মচারী যুক্ত করুন' }}</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <form @submit.prevent="submitForm">
            <div class="modal-body">
              <div v-if="serverErrors.length" class="alert alert-danger">
                <ul class="mb-0 pl-3"><li v-for="(e, i) in serverErrors" :key="i">{{ e }}</li></ul>
              </div>
              <div class="form-row">
                <div class="col-md-6 form-group">
                  <label>নাম (English) <span class="text-danger">*</span></label>
                  <input v-model="form.first_name" type="text" class="form-control" required>
                </div>
                <div class="col-md-6 form-group">
                  <label>পদবি (Last Name, English)</label>
                  <input v-model="form.last_name" type="text" class="form-control">
                </div>
                <div class="col-md-6 form-group">
                  <label>নাম (বাংলা)</label>
                  <input v-model="form.first_name_bn" type="text" class="form-control">
                </div>
                <div class="col-md-6 form-group">
                  <label>পদবি (বাংলা)</label>
                  <input v-model="form.last_name_bn" type="text" class="form-control">
                </div>
                <div class="col-md-6 form-group">
                  <label>পদবী (Designation)</label>
                  <select v-model="form.designation_id" class="form-control">
                    <option :value="null">-- নির্বাচন করুন --</option>
                    <option v-for="d in designations" :key="d.id" :value="d.id">
                      {{ d.name_bn || d.name_en }}<template v-if="d.name_bn && d.name_en"> ({{ d.name_en }})</template>
                    </option>
                  </select>
                </div>
                <div class="col-md-6 form-group">
                  <label>মোবাইল</label>
                  <input v-model="form.phone" type="text" class="form-control">
                </div>
                <div class="col-md-6 form-group">
                  <label>ইমেইল</label>
                  <input v-model="form.email" type="email" class="form-control">
                </div>
                <div class="col-md-6 form-group">
                  <label>যোগদানের তারিখ</label>
                  <input v-model="form.joining_date" type="date" class="form-control">
                </div>
                <div class="col-md-6 form-group">
                  <label>জন্ম তারিখ</label>
                  <input v-model="form.date_of_birth" type="date" class="form-control">
                </div>
                <div class="col-md-6 form-group">
                  <label>ক্রম (সিরিয়াল)</label>
                  <input v-model="form.serial_number" type="number" min="1" class="form-control">
                </div>
                <div class="col-12 form-group">
                  <label>ঠিকানা</label>
                  <textarea v-model="form.address" rows="2" class="form-control"></textarea>
                </div>
                <div class="col-md-6 form-group">
                  <label>ছবি</label>
                  <input type="file" accept="image/*" class="form-control-file" @change="onPhotoSelected">
                  <img v-if="isEditing && currentPhoto" :src="currentPhoto" style="max-height:70px;margin-top:8px;" class="rounded">
                </div>
                <div class="col-md-3 form-group">
                  <label>অবস্থা</label>
                  <select v-model="form.status" class="form-control">
                    <option value="active">সক্রিয়</option>
                    <option value="inactive">নিষ্ক্রিয়</option>
                  </select>
                </div>
                <div class="col-md-3 form-group d-flex align-items-center mt-4">
                  <div class="custom-control custom-checkbox">
                    <input id="staff_show_on_website" v-model="form.show_on_website" type="checkbox" class="custom-control-input">
                    <label class="custom-control-label" for="staff_show_on_website">ওয়েবসাইটে দেখাবে</label>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer bg-light">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">বন্ধ করুন</button>
              <button type="submit" class="btn btn-primary" :disabled="saving">
                {{ saving ? 'সংরক্ষণ হচ্ছে...' : 'সংরক্ষণ করুন' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Print Options Modal -->
    <div class="modal fade" id="staffPrintModal" tabindex="-1" ref="printModal">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-light">
            <h5 class="modal-title font-weight-bold">প্রিন্ট অপশন</h5>
            <button type="button" class="close" data-dismiss="modal">&times;</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>পদবী অনুযায়ী ফিল্টার</label>
              <select v-model="printOptions.designation_id" class="form-control">
                <option value="">-- সকল পদবী --</option>
                <option v-for="d in designations" :key="d.id" :value="d.id">{{ d.name_bn || d.name_en }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>অবস্থা</label>
              <select v-model="printOptions.status" class="form-control">
                <option value="">-- সকল --</option>
                <option value="active">সক্রিয়</option>
                <option value="inactive">নিষ্ক্রিয়</option>
              </select>
            </div>
            <div class="form-group">
              <label>কলাম নির্বাচন করুন</label>
              <div class="custom-control custom-checkbox" v-for="c in columnOptions" :key="c.value">
                <input :id="'col-'+c.value" v-model="printOptions.columns" type="checkbox" class="custom-control-input" :value="c.value">
                <label class="custom-control-label" :for="'col-'+c.value">{{ c.label }}</label>
              </div>
            </div>
          </div>
          <div class="modal-footer bg-light">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">বন্ধ করুন</button>
            <button type="button" class="btn btn-primary" @click="doPrint"><i class="fas fa-print mr-1"></i> প্রিন্ট করুন</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div v-else class="text-center py-5 text-muted">
    <span class="spinner-border"></span> লোড হচ্ছে...
  </div>
</template>

<script>
export default {
  props: {
    schoolId: { type: Number, required: true },
    printUrl: { type: String, required: true },
  },
  data() {
    return {
      loaded: false,
      saving: false,
      staff: [],
      designations: [],
      search: '',
      isEditing: false,
      currentId: null,
      currentPhoto: null,
      photoFile: null,
      form: this.emptyForm(),
      serverErrors: [],
      creatingLoginFor: null,
      resettingFor: null,
      printOptions: {
        designation_id: '',
        status: '',
        columns: ['col-photo', 'col-name-bn', 'col-designation', 'col-mobile'],
      },
      columnOptions: [
        { value: 'col-photo', label: 'ছবি' },
        { value: 'col-name-bn', label: 'নাম (বাংলা)' },
        { value: 'col-name-en', label: 'নাম (English)' },
        { value: 'col-designation', label: 'পদবী' },
        { value: 'col-mobile', label: 'মোবাইল' },
        { value: 'col-joining', label: 'যোগদানের তারিখ' },
        { value: 'col-address', label: 'ঠিকানা' },
      ],
    };
  },
  computed: {
    activeCount() {
      return this.staff.filter((s) => s.status === 'active').length;
    },
    visibleCount() {
      return this.staff.filter((s) => s.show_on_website).length;
    },
    filteredStaff() {
      const q = this.search.trim().toLowerCase();
      if (!q) return this.staff;
      return this.staff.filter((s) => [s.full_name, s.full_name_bn, s.designation_label, s.phone]
        .filter(Boolean).some((v) => v.toLowerCase().includes(q)));
    },
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    emptyForm() {
      return {
        first_name: '', last_name: '', first_name_bn: '', last_name_bn: '',
        designation_id: null, phone: '', email: '', address: '',
        date_of_birth: '', joining_date: '', serial_number: '',
        status: 'active', show_on_website: true,
      };
    },
    async fetchData() {
      try {
        const resp = await axios.get(`/principal/institute/${this.schoolId}/staff/data`);
        this.staff = resp.data.staff || [];
        this.designations = resp.data.designations || [];
      } catch (e) {
        if (window.toastr) window.toastr.error('ডেটা লোড করতে সমস্যা হয়েছে');
      } finally {
        this.loaded = true;
      }
    },
    openModal(s = null) {
      this.serverErrors = [];
      this.photoFile = null;
      if (s) {
        this.isEditing = true;
        this.currentId = s.id;
        this.currentPhoto = s.photo_url;
        this.form = {
          first_name: s.first_name || '', last_name: s.last_name || '',
          first_name_bn: s.first_name_bn || '', last_name_bn: s.last_name_bn || '',
          designation_id: s.designation_id, phone: s.phone || '', email: s.email || '',
          address: s.address || '', date_of_birth: s.date_of_birth || '', joining_date: s.joining_date || '',
          serial_number: s.serial_number || '', status: s.status || 'active', show_on_website: !!s.show_on_website,
        };
      } else {
        this.isEditing = false;
        this.currentId = null;
        this.currentPhoto = null;
        this.form = this.emptyForm();
      }
      $(this.$refs.modal).modal('show');
    },
    onPhotoSelected(e) {
      this.photoFile = e.target.files[0] || null;
    },
    buildFormData() {
      const fd = new FormData();
      Object.keys(this.form).forEach((key) => {
        const val = this.form[key];
        if (key === 'show_on_website') {
          fd.append(key, val ? '1' : '0');
        } else if (val !== null && val !== undefined) {
          fd.append(key, val);
        }
      });
      if (this.photoFile) fd.append('photo', this.photoFile);
      return fd;
    },
    async submitForm() {
      this.saving = true;
      this.serverErrors = [];
      try {
        const fd = this.buildFormData();
        const url = this.isEditing
          ? `/principal/institute/${this.schoolId}/staff/${this.currentId}`
          : `/principal/institute/${this.schoolId}/staff`;
        const resp = await axios.post(url, fd);
        if (!this.isEditing) {
          // New staff get a login created automatically — the message
          // carries the one-time username/password, so make sure it's
          // actually seen (toastr auto-dismisses too fast for this).
          alert(resp.data.message);
        } else if (window.toastr) {
          window.toastr.success(resp.data.message);
        }
        $(this.$refs.modal).modal('hide');
        await this.fetchData();
      } catch (e) {
        if (e.response?.status === 422 && e.response.data.errors) {
          this.serverErrors = Object.values(e.response.data.errors).flat();
        } else if (window.toastr) {
          window.toastr.error(e.response?.data?.message || 'সংরক্ষণ করতে সমস্যা হয়েছে');
        }
      } finally {
        this.saving = false;
      }
    },
    async createLogin(s) {
      this.creatingLoginFor = s.id;
      try {
        const resp = await axios.post(`/principal/institute/${this.schoolId}/staff/${s.id}/create-login`);
        alert(resp.data.message);
        await this.fetchData();
      } catch (e) {
        if (window.toastr) window.toastr.error(e.response?.data?.message || 'লগইন তৈরি করতে সমস্যা হয়েছে');
      } finally {
        this.creatingLoginFor = null;
      }
    },
    async resetPassword(s) {
      if (!confirm(`"${s.full_name_bn || s.full_name}"-এর পাসওয়ার্ড রিসেট করবেন? পুরনো পাসওয়ার্ড আর কাজ করবে না।`)) return;
      this.resettingFor = s.id;
      try {
        const resp = await axios.post(`/principal/institute/${this.schoolId}/staff/${s.id}/reset-password`);
        alert(resp.data.message);
        await this.fetchData();
      } catch (e) {
        if (window.toastr) window.toastr.error(e.response?.data?.message || 'রিসেট করতে সমস্যা হয়েছে');
      } finally {
        this.resettingFor = null;
      }
    },
    async remove(s) {
      if (!confirm(`"${s.full_name_bn || s.full_name}" কে মুছে ফেলবেন?`)) return;
      try {
        const resp = await axios.delete(`/principal/institute/${this.schoolId}/staff/${s.id}`);
        if (window.toastr) window.toastr.success(resp.data.message);
        await this.fetchData();
      } catch (e) {
        if (window.toastr) window.toastr.error('মুছতে সমস্যা হয়েছে');
      }
    },
    openPrintModal() {
      $(this.$refs.printModal).modal('show');
    },
    doPrint() {
      const params = new URLSearchParams();
      if (this.printOptions.designation_id) params.append('designation_id', this.printOptions.designation_id);
      if (this.printOptions.status) params.append('status', this.printOptions.status);
      this.printOptions.columns.forEach((c) => params.append('columns[]', c));
      window.open(`${this.printUrl}?${params.toString()}`, '_blank');
      $(this.$refs.printModal).modal('hide');
    },
  },
};
</script>

<style scoped>
.staff-avatar {
  width: 42px; height: 42px; border-radius: 50%; object-fit: cover;
}
.staff-avatar--placeholder {
  display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #6366f1, #a855f7);
  color: #fff; font-weight: 700;
}
</style>
