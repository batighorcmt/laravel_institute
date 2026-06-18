<template>
  <div class="card shadow border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
      <h3 class="card-title m-0 font-weight-bold text-primary">
        <i class="fas fa-user-tag mr-2"></i> পদবীসমূহ (Designations)
      </h3>
      <button class="btn btn-primary" @click="openModal()">
        <i class="fas fa-plus mr-1"></i> নতুন পদবী
      </button>
    </div>

    <div class="card-body p-0">
      <div v-if="loading" class="text-center p-5">
        <div class="spinner-border text-primary" role="status">
          <span class="sr-only">লোড হচ্ছে...</span>
        </div>
      </div>
      
      <div v-else-if="designations.length === 0" class="text-center p-5 text-muted">
        <i class="fas fa-info-circle fa-3x mb-3 text-light"></i>
        <p>কোনো পদবী পাওয়া যায়নি। নতুন পদবী যুক্ত করুন।</p>
      </div>

      <div class="table-responsive" v-else>
        <table class="table table-hover table-striped table-vcenter mb-0 text-nowrap">
          <thead class="thead-light">
            <tr>
              <th width="50" class="text-center">#</th>
              <th>পদবী (বাংলা)</th>
              <th>Designation (English)</th>
              <th width="150" class="text-center">অ্যাকশন</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in designations" :key="item.id">
              <td class="text-center">{{ index + 1 }}</td>
              <td class="font-weight-bold">{{ item.name_bn || '-' }}</td>
              <td>{{ item.name_en }}</td>
              <td class="text-center">
                <button class="btn btn-sm btn-light text-primary border shadow-sm mr-2" @click="openModal(item)" title="এডিট করুন">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-light text-danger border shadow-sm" @click="confirmDelete(item.id)" title="ডিলিট করুন">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="designationModal" tabindex="-1" role="dialog" aria-hidden="true" ref="modal">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-light">
            <h5 class="modal-title font-weight-bold">
              <i class="fas" :class="isEditing ? 'fa-edit text-primary' : 'fa-plus-circle text-success'"></i>
              {{ isEditing ? 'পদবী আপডেট করুন' : 'নতুন পদবী যুক্ত করুন' }}
            </h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form @submit.prevent="submitForm">
            <div class="modal-body">
              <div v-if="serverErrors.length > 0" class="alert alert-danger">
                <ul class="mb-0 pl-3">
                  <li v-for="(err, idx) in serverErrors" :key="idx">{{ err }}</li>
                </ul>
              </div>

              <div class="form-group">
                <label>পদবী (বাংলা)</label>
                <input type="text" class="form-control form-control-lg shadow-sm" v-model="form.name_bn" placeholder="যেমন: প্রধান শিক্ষক">
              </div>

              <div class="form-group">
                <label>Designation (English) <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-lg shadow-sm" v-model="form.name_en" required placeholder="e.g. Head Teacher">
              </div>
            </div>
            <div class="modal-footer bg-light">
              <button type="button" class="btn btn-secondary shadow-sm" data-dismiss="modal">বন্ধ করুন</button>
              <button type="submit" class="btn btn-primary shadow-sm" :disabled="saving">
                <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-save'"></i> 
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
    apiUrl: {
      type: String,
      required: true
    },
    csrfToken: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      designations: [],
      loading: true,
      saving: false,
      isEditing: false,
      currentId: null,
      form: {
        name_en: '',
        name_bn: ''
      },
      serverErrors: []
    }
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    async fetchData() {
      this.loading = true;
      try {
        const resp = await fetch(this.apiUrl, {
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await resp.json();
        this.designations = data.designations || [];
      } catch (e) {
        console.error('Fetch error:', e);
      } finally {
        this.loading = false;
      }
    },
    openModal(item = null) {
      this.serverErrors = [];
      if (item) {
        this.isEditing = true;
        this.currentId = item.id;
        this.form.name_en = item.name_en;
        this.form.name_bn = item.name_bn;
      } else {
        this.isEditing = false;
        this.currentId = null;
        this.form.name_en = '';
        this.form.name_bn = '';
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
          method: method,
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': this.csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify(this.form)
        });

        const data = await resp.json();

        if (!resp.ok) {
          if (resp.status === 422 && data.errors) {
            this.serverErrors = Object.values(data.errors).flat();
          } else {
            this.serverErrors = [data.message || 'Error occurred'];
          }
        } else {
          $(this.$refs.modal).modal('hide');
          this.fetchData();
          // Optionally show success toast here if Toastr/SweetAlert is available globally
          if (window.toastr) {
            window.toastr.success(data.message);
          }
        }
      } catch (e) {
        this.serverErrors = ['সার্ভারে সমস্যা হয়েছে।'];
      } finally {
        this.saving = false;
      }
    },
    async confirmDelete(id) {
      if (confirm('আপনি কি নিশ্চিত যে এই পদবীটি ডিলিট করতে চান?')) {
        try {
          const resp = await fetch(`${this.apiUrl}/${id}`, {
            method: 'DELETE',
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': this.csrfToken,
              'X-Requested-With': 'XMLHttpRequest'
            }
          });
          const data = await resp.json();
          if (resp.ok) {
            if (window.toastr) {
              window.toastr.success(data.message);
            }
            this.fetchData();
          } else {
            alert(data.message || 'ডিলিট করা সম্ভব হয়নি।');
          }
        } catch (e) {
          alert('সার্ভারে সমস্যা হয়েছে।');
        }
      }
    }
  }
}
</script>

<style scoped>
.table-vcenter td, .table-vcenter th {
  vertical-align: middle;
}
.form-control-lg {
  font-size: 1rem;
  border-radius: 0.5rem;
}
</style>
