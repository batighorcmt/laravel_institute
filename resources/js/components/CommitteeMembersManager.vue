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
            <th width="70">ছবি</th>
            <th width="70">ক্রমিক</th>
            <th>নাম</th>
            <th width="200">পদবী</th>
            <th width="150">মোবাইল নং</th>
            <th>ঠিকানা</th>
            <th width="50"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(m, idx) in members" :key="'cm-'+idx">
            <td class="text-center">
              <label class="member-photo-upload">
                <img :src="memberPhotoPreview(m)" class="member-photo-thumb" alt="">
                <input type="file" accept="image/*" class="d-none" @change="onPhotoSelected(m, $event)">
                <i class="fas fa-camera member-photo-icon"></i>
              </label>
            </td>
            <td><input v-model="m.serial" type="text" class="form-control form-control-sm" placeholder="১"></td>
            <td><input v-model="m.name" type="text" class="form-control form-control-sm" placeholder="নাম"></td>
            <td>
              <select v-model="m.designation" :data-index="idx" class="form-control form-control-sm select2-designation">
                <option value="">-- পদবী নির্বাচন করুন --</option>
                <option v-for="d in designations" :key="d.id" :value="d.id">{{ d.text }}</option>
              </select>
            </td>
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
      designations: [],
    };
  },
  watch: {
    'members.length'() {
      this.$nextTick(this.initSelect2);
    },
  },
  async mounted() {
    // Designations must be loaded before select2 initializes, otherwise it
    // caches an empty option list and never picks up options added later.
    await Promise.all([this.fetchDesignations(), this.fetchData()]);
    this.loaded = true;
    this.$nextTick(this.initSelect2);
  },
  methods: {
    async fetchDesignations() {
      try {
        const res = await axios.get(`/principal/institute/${this.schoolId}/frontend/meta/designations`);
        this.designations = res.data || [];
      } catch (e) {
        // Designation list is a UI nicety; failing silently keeps the form usable as a plain text field.
      }
    },
    initSelect2() {
      if (!window.$ || !window.$.fn || !window.$.fn.select2) return;
      const vm = this;
      const $els = window.$(this.$el).find('.select2-designation');
      $els.each(function () {
        const $el = window.$(this);
        if ($el.data('select2')) $el.select2('destroy');
        $el.select2({
          width: '100%',
          theme: 'bootstrap4',
          placeholder: '-- পদবী নির্বাচন করুন --',
          allowClear: true,
          dropdownParent: $el.closest('.table-responsive').length ? $el.closest('.table-responsive') : window.$('body'),
        });

        // select2 manipulates the underlying <select> directly; Vue's v-model
        // does not reliably pick up that change, so sync it back explicitly.
        $el.off('change.committeeSync').on('change.committeeSync', function () {
          const idx = Number($el.data('index'));
          const member = vm.members[idx];
          if (member) {
            member.designation = $el.val() || '';
          }
        });
      });
    },
    async fetchData() {
      try {
        const res = await axios.get(`/principal/institute/${this.schoolId}/frontend/front-page-elements/data`);
        this.members = (res.data.homepage_content?.committee_members || []).map((m) => ({ ...m, photoFile: null, photoPreview: null }));
      } catch (e) {
        if (window.toastr) window.toastr.error('কমিটি তালিকা লোড করতে সমস্যা হয়েছে');
      }
    },
    memberPhotoPreview(m) {
      if (m.photoPreview) return m.photoPreview;
      if (m.photo) return `/storage/${String(m.photo).replace(/^\/+/, '').replace(/^storage\//, '')}`;
      return '/images/default-avatar.svg';
    },
    onPhotoSelected(m, e) {
      const file = e.target.files?.[0];
      if (!file) return;
      m.photoFile = file;
      m.photoPreview = URL.createObjectURL(file);
    },
    addMember() {
      this.members.push({
        serial: String(this.members.length + 1),
        name: '',
        designation: '',
        mobile: '',
        address: '',
        photo: '',
        photoFile: null,
        photoPreview: null,
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
          fd.append(`committee_members[${i}][serial]`, m.serial ?? '');
          fd.append(`committee_members[${i}][name]`, m.name ?? '');
          fd.append(`committee_members[${i}][designation]`, m.designation ?? '');
          fd.append(`committee_members[${i}][mobile]`, m.mobile ?? '');
          fd.append(`committee_members[${i}][address]`, m.address ?? '');
          fd.append(`committee_members[${i}][photo]`, m.photo ?? '');
          if (m.photoFile) {
            fd.append(`committee_member_photos[${i}]`, m.photoFile);
          }
        });
        await axios.post(`/principal/institute/${this.schoolId}/frontend/front-page-elements/data`, fd);
        if (window.toastr) window.toastr.success('কমিটি তালিকা সংরক্ষণ হয়েছে');
        await this.fetchData();
        this.$nextTick(this.initSelect2);
      } catch (e) {
        if (window.toastr) window.toastr.error(e.response?.data?.message || 'সংরক্ষণ করতে সমস্যা হয়েছে');
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>

<style scoped>
:deep(.select2-results__options) { max-height: 200px; overflow-y: auto; }
.member-photo-upload { position: relative; display: block; width: 44px; height: 44px; margin: 0 auto; cursor: pointer; }
.member-photo-thumb { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 1px solid #dee2e6; display: block; }
.member-photo-icon {
  position: absolute; bottom: -2px; right: -2px; background: #4f46e5; color: #fff; border-radius: 50%;
  width: 16px; height: 16px; font-size: 8px; display: flex; align-items: center; justify-content: center;
}
</style>
