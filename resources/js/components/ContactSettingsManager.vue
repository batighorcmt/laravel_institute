<template>
  <div class="contact-settings-wrapper min-h-[600px]">

    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight">যোগাযোগ সেটিংস</h2>
        <p class="text-slate-500 text-sm mt-1">ওয়েবসাইটের যোগাযোগ পৃষ্ঠার তথ্য ও ভিজিটরদের পাঠানো বার্তা এখান থেকে নিয়ন্ত্রণ করুন।</p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 mb-6 bg-white rounded-2xl border border-slate-100 shadow-sm p-2 w-fit">
      <button @click="activeTab = 'settings'"
        class="px-6 py-3 rounded-xl font-bold text-sm transition-colors"
        :class="activeTab === 'settings' ? 'bg-indigo-600 text-white shadow' : 'text-slate-600 hover:bg-slate-50'">
        <i class="fas fa-address-book mr-2"></i> যোগাযোগের তথ্য
      </button>
      <button @click="activeTab = 'messages'"
        class="relative px-6 py-3 rounded-xl font-bold text-sm transition-colors"
        :class="activeTab === 'messages' ? 'bg-indigo-600 text-white shadow' : 'text-slate-600 hover:bg-slate-50'">
        <i class="fas fa-envelope mr-2"></i> বার্তাসমূহ
        <span v-if="unreadCount > 0" class="ml-2 inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-500 text-white text-[11px] font-black align-middle">{{ unreadCount }}</span>
      </button>
    </div>

    <div v-if="loading" class="text-center py-20 text-slate-400">
      <i class="fas fa-spinner fa-spin text-3xl"></i>
    </div>

    <!-- Settings Tab -->
    <div v-else-if="activeTab === 'settings'" class="section-card">
      <div class="section-header">
        <i class="fas fa-address-book text-indigo-500"></i>
        <h3 class="font-bold text-slate-800 ml-3 text-lg">যোগাযোগের তথ্য</h3>
      </div>
      <div class="section-body p-8 space-y-10">

        <div>
          <h5 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-4">সাধারণ তথ্য</h5>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
              <label class="input-label">ঠিকানা</label>
              <textarea v-model="form.contact_address" class="input-field" rows="2"></textarea>
            </div>
            <div><label class="input-label">ফোন (ল্যান্ডলাইন)</label><input type="text" v-model="form.contact_phone" class="input-field"></div>
            <div><label class="input-label">মোবাইল নম্বর</label><input type="text" v-model="form.contact_mobile" class="input-field"></div>
            <div><label class="input-label">ইমেইল</label><input type="email" v-model="form.contact_email" class="input-field"></div>
            <div><label class="input-label">ওয়েবসাইট</label><input type="text" v-model="form.contact_website" class="input-field" placeholder="https://..."></div>
          </div>
        </div>

        <div>
          <h5 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-4">DSHE ২০২৫ — বাধ্যতামূলক তথ্য</h5>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div><label class="input-label">তথ্যসেবা কেন্দ্র</label><input type="text" v-model="form.dshe_info_center" class="input-field"></div>
            <div><label class="input-label">তথ্যসেবা মোবাইল</label><input type="text" v-model="form.dshe_info_mobile" class="input-field"></div>
            <div><label class="input-label">অভিযোগ প্রতিকার কর্মকর্তার (GRO) নাম</label><input type="text" v-model="form.gro_name" class="input-field"></div>
            <div><label class="input-label">GRO এর পদবী</label><input type="text" v-model="form.gro_designation" class="input-field"></div>
            <div><label class="input-label">GRO মোবাইল</label><input type="text" v-model="form.gro_mobile" class="input-field"></div>
            <div><label class="input-label">অফিস সময়</label><input type="text" v-model="form.office_hours" class="input-field" placeholder="সকাল ৯টা - বিকাল ৫টা"></div>
          </div>
        </div>

        <div>
          <h5 class="text-sm font-black text-slate-400 uppercase tracking-widest mb-4">গুগল ম্যাপ</h5>
          <label class="input-label">ম্যাপ এম্বেড URL (Google Maps Embed src)</label>
          <textarea v-model="form.map_embed_url" class="input-field" rows="2" placeholder="https://www.google.com/maps/embed?..."></textarea>
          <p class="text-xs text-slate-400 mt-2">গুগল ম্যাপে প্রতিষ্ঠানের অবস্থান খুঁজে Share &gt; Embed a map থেকে iframe src URL কপি করে এখানে বসান।</p>
        </div>

      </div>
      <div class="section-footer">
        <button @click="saveSettings" class="save-btn" :disabled="saving">{{ saving ? 'সংরক্ষণ হচ্ছে...' : 'সেভ করুন' }}</button>
      </div>
    </div>

    <!-- Messages Tab -->
    <div v-else class="section-card">
      <div class="section-header">
        <i class="fas fa-envelope text-indigo-500"></i>
        <h3 class="font-bold text-slate-800 ml-3 text-lg">ভিজিটরদের পাঠানো বার্তা ({{ messages.length }})</h3>
      </div>
      <div class="section-body p-6">
        <div v-if="messages.length === 0" class="text-center py-16 text-slate-400">
          <i class="fas fa-inbox text-4xl mb-3"></i>
          <p>এখনো কোনো বার্তা আসেনি।</p>
        </div>
        <div v-else class="space-y-4">
          <div v-for="msg in messages" :key="msg.id"
            class="border rounded-2xl p-5 transition-colors"
            :class="msg.status === 'unread' ? 'bg-indigo-50/50 border-indigo-100' : 'bg-white border-slate-100'">
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-3">
              <div class="flex-grow">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-bold text-slate-800">{{ msg.name }}</span>
                  <span v-if="msg.status === 'unread'" class="text-[10px] font-black uppercase bg-red-500 text-white px-2 py-0.5 rounded-full">নতুন</span>
                  <span v-if="msg.subject" class="text-xs font-bold text-indigo-600 bg-indigo-100 px-2 py-0.5 rounded-full">{{ msg.subject }}</span>
                </div>
                <div class="text-xs text-slate-500 mt-1 flex gap-4 flex-wrap">
                  <span><i class="fas fa-envelope mr-1"></i>{{ msg.email }}</span>
                  <span v-if="msg.phone"><i class="fas fa-phone mr-1"></i>{{ msg.phone }}</span>
                  <span><i class="fas fa-clock mr-1"></i>{{ formatDate(msg.created_at) }}</span>
                </div>
                <p class="text-sm text-slate-700 mt-3 whitespace-pre-line">{{ msg.message }}</p>
              </div>
              <div class="flex md:flex-col gap-2 shrink-0">
                <button v-if="msg.status === 'unread'" @click="markRead(msg)" class="text-xs font-bold px-3 py-2 rounded-xl bg-emerald-100 text-emerald-700 hover:bg-emerald-200 transition-colors">
                  <i class="fas fa-check mr-1"></i> পঠিত করুন
                </button>
                <button @click="deleteMessage(msg)" class="text-xs font-bold px-3 py-2 rounded-xl bg-red-100 text-red-700 hover:bg-red-200 transition-colors">
                  <i class="fas fa-trash mr-1"></i> মুছুন
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
export default {
  name: 'ContactSettingsManager',
  props: { schoolId: Number },
  data() {
    return {
      activeTab: 'settings',
      loading: false,
      saving: false,
      messages: [],
      unreadCount: 0,
      form: {
        contact_address: '', contact_email: '', contact_phone: '', contact_mobile: '', contact_website: '',
        dshe_info_center: '', dshe_info_mobile: '', gro_name: '', gro_designation: '', gro_mobile: '',
        office_hours: '', map_embed_url: ''
      }
    };
  },
  async mounted() {
    await this.fetchData();
  },
  methods: {
    async fetchData() {
      this.loading = true;
      try {
        const res = await axios.get(`/principal/institute/${this.schoolId}/frontend/contact-settings/data`);
        const settings = res.data.settings || {};
        Object.keys(this.form).forEach(k => { this.form[k] = settings[k] || ''; });
        this.messages = res.data.messages || [];
        this.unreadCount = res.data.unread_count || 0;
      } catch (e) {
        toastr.error('তথ্য লোড করতে সমস্যা হয়েছে');
      } finally {
        this.loading = false;
      }
    },
    async saveSettings() {
      this.saving = true;
      try {
        const res = await axios.post(`/principal/institute/${this.schoolId}/frontend/contact-settings/data`, this.form);
        toastr.success(res.data.message || 'সেভ হয়েছে');
      } catch (e) {
        toastr.error('সংরক্ষণে সমস্যা হয়েছে');
      } finally {
        this.saving = false;
      }
    },
    async markRead(msg) {
      try {
        await axios.post(`/principal/institute/${this.schoolId}/frontend/contact-settings/messages/${msg.id}/read`);
        msg.status = 'read';
        this.unreadCount = Math.max(0, this.unreadCount - 1);
      } catch (e) {
        toastr.error('আপডেট করতে সমস্যা হয়েছে');
      }
    },
    async deleteMessage(msg) {
      if (!confirm('এই বার্তাটি মুছে ফেলতে চান?')) return;
      try {
        await axios.delete(`/principal/institute/${this.schoolId}/frontend/contact-settings/messages/${msg.id}`);
        if (msg.status === 'unread') this.unreadCount = Math.max(0, this.unreadCount - 1);
        this.messages = this.messages.filter(m => m.id !== msg.id);
        toastr.success('মুছে ফেলা হয়েছে');
      } catch (e) {
        toastr.error('মুছতে সমস্যা হয়েছে');
      }
    },
    formatDate(d) {
      if (!d) return '';
      const date = new Date(d);
      return date.toLocaleDateString('bn-BD', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }
  }
};
</script>

<style scoped>
.section-card { background: white; border-radius: 2.5rem; border: 1px solid #f1f5f9; box-shadow: 0 20px 40px -15px rgba(0,0,0,0.05); overflow: hidden; }
.section-header { padding: 1.5rem 2.5rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; background: #f8fafc; }
.input-label { display: block; font-size: 0.8rem; font-weight: 800; color: #475569; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em; }
.input-field { width: 100%; padding: 1rem 1.5rem; border-radius: 1.5rem; background: #f8fafc; border: 1px solid #e2e8f0; outline: none; transition: 0.3s; }
.input-field:focus { background: white; border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
.section-footer { padding: 1.5rem 2.5rem; border-top: 1px solid #f1f5f9; background: #f8fafc; display: flex; justify-content: flex-end; }
.save-btn { padding: 1rem 3rem; background: #4f46e5; color: white; border-radius: 1.5rem; font-weight: 800; transition: 0.3s; box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4); }
.save-btn:hover { background: #4338ca; transform: translateY(-2px); box-shadow: 0 15px 25px -5px rgba(79, 70, 229, 0.5); }
.save-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
</style>
