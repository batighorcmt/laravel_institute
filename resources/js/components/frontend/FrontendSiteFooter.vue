<template>
  <footer id="contact" class="bg-[#020617] text-white pt-24 pb-10 px-4">
    <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-20 border-b border-white/5 pb-20 mb-10">
      <div>
        <h4 class="text-3xl font-black mb-6 bg-clip-text text-transparent bg-gradient-to-r from-[var(--theme-primary)] to-[var(--theme-accent)]">{{ schoolNameBn }}</h4>
        <p class="text-slate-400 leading-relaxed font-medium">{{ settings.contact_address || school.address_bn || school.address }}</p>
      </div>
      <div class="space-y-4">
        <h5 class="text-xs font-black uppercase tracking-[0.5em] text-slate-500 mb-8">Contacts</h5>
        <p class="flex items-center gap-4 text-slate-300 font-bold"><i class="fas fa-phone-alt text-[var(--theme-accent)]"></i> {{ settings.contact_phone || school.phone }}</p>
        <p class="flex items-center gap-4 text-slate-300 font-bold"><i class="fas fa-envelope text-[var(--theme-accent)]"></i> {{ settings.contact_email || school.email }}</p>
      </div>
      <div class="bg-indigo-900/40 p-10 rounded-[40px] border border-indigo-500/20 text-center flex flex-col justify-center">
        <span class="text-[9px] font-black uppercase tracking-[0.8em] text-[var(--theme-accent)] block mb-4">Institution EIIN</span>
        <div class="text-6xl font-black text-white">{{ school.eiin }}</div>
      </div>
    </div>
    <div v-if="displayMenuItems.length" class="max-w-7xl mx-auto mb-10 pb-6 border-b border-white/5">
      <frontend-site-nav :items="displayMenuItems" variant="footer" />
    </div>
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center text-[10px] font-black uppercase tracking-widest text-slate-600">
      <p>&copy; {{ new Date().getFullYear() }} {{ school.name }}. All rights reserved.</p>
      <div class="flex items-center gap-3 mt-4 md:mt-0">
        <span>Platform by</span>
        <span class="bg-white text-black px-2 py-0.5 rounded">BATIGHOR EIMS</span>
      </div>
    </div>
  </footer>
</template>

<script>
export default {
  name: 'FrontendSiteFooter',
  props: {
    school: { type: Object, required: true },
    settings: { type: Object, default: () => ({}) },
    menuItems: { type: Array, default: () => [] },
  },
  computed: {
    displayMenuItems() {
      return this.menuItems?.length ? this.menuItems : [];
    },
    schoolNameBn() {
      return this.school.name_bn || this.school.name || 'বিদ্যালয়';
    },
  },
};
</script>
