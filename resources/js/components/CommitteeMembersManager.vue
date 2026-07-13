<template>
  <div v-if="loaded">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="font-weight-bold mb-0"><i class="fas fa-users-cog mr-2 text-indigo"></i> কমিটির সদস্য তালিকা</h5>
      <button type="button" class="btn btn-sm btn-primary" @click="addMember"><i class="fas fa-plus mr-1"></i> সদস্য যোগ করুন</button>
    </div>
    <p class="text-muted small">এখানে যুক্ত করা সদস্যরা ফ্রন্টএন্ড ওয়েবসাইটের কমিটি তালিকা পৃষ্ঠায় দেখানো হবে।</p>

    <div v-if="!members.length" class="text-center text-muted py-4 border rounded bg-light">
      কোনো সদস্য যুক্ত করা হয়নি।
    </div>

    <div class="table-responsive" v-else>
      <table class="table table-bordered table-sm align-middle">
        <thead class="thead-light">
          <tr>
            <th width="80">ক্রমিক</th>
            <th>নাম</th>
            <th>পদবী</th>
            <th width="150">মোবাইল নং</th>
            <th>ঠিকানা</th>
            <th width="50"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(m, idx) in members" :key="'cm-'+idx">
            <td><input v-model="m.serial" type="text" class="form-control form-control-sm" placeholder="১"></td>
            <td><input v-model="m.name" type="text" class="form-control form-control-sm" placeholder="নাম"></td>
            <td><input v-model="m.designation" type="text" class="form-control form-control-sm" placeholder="সভাপতি / সদস্য"></td>
            <td><input v-model="m.mobile" type="text" class="form-control form-control-sm" placeholder="01xxxxxxxxx"></td>
            <td><input v-model="m.address" type="text" class="form-control form-control-sm" placeholder="ঠিকানা"></td>
            <td class="text-center">
              <button type="button" class="btn btn-sm btn-outline-danger" @click="removeMember(idx)"><i class="fas fa-trash"></i></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <button type="button" class="btn btn-success" :disabled="saving" @click="save">
      <span v-if="saving" class="spinner-border spinner-border-sm mr-1"></span>
      কমিটির সদস্য তালিকা সংরক্ষণ করুন
    </button>
  </div>
  <div v-else class="text-center py-4 text-muted">
    <span class="spinner-border spinner-border-sm"></span> লোড হচ্ছে...
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
      members: [],
    };
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    async fetchData() {
      try {
        const res = await axios.get(`/principal/institute/${this.schoolId}/frontend/front-page-elements/data`);
        this.members = (res.data.homepage_content?.committee_members || []).map((m) => ({ ...m }));
      } catch (e) {
        if (window.toastr) window.toastr.error('কমিটি তালিকা লোড করতে সমস্যা হয়েছে');
      } finally {
        this.loaded = true;
      }
    },
    addMember() {
      this.members.push({
        serial: String(this.members.length + 1),
        name: '',
        designation: '',
        mobile: '',
        address: '',
      });
    },
    removeMember(idx) {
      this.members.splice(idx, 1);
    },
    async save() {
      this.saving = true;
      try {
        const fd = new FormData();
        this.members.forEach((m, i) => {
          Object.keys(m).forEach((key) => fd.append(`committee_members[${i}][${key}]`, m[key] ?? ''));
        });
        await axios.post(`/principal/institute/${this.schoolId}/frontend/front-page-elements/data`, fd);
        if (window.toastr) window.toastr.success('কমিটি তালিকা সংরক্ষণ হয়েছে');
        await this.fetchData();
      } catch (e) {
        if (window.toastr) window.toastr.error(e.response?.data?.message || 'সংরক্ষণ করতে সমস্যা হয়েছে');
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>
