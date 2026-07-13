<template>
  <div>
    <header class="bg-white border-b-4 border-[var(--theme-primary)] shadow-md relative z-40">
      <div class="h-2 w-full bg-gradient-to-r from-[var(--theme-primary)] via-[var(--theme-accent)] to-[var(--theme-secondary)]"></div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
          <a :href="homeUrl" class="flex items-center gap-5 text-inherit no-underline">
            <div class="relative group">
              <div class="absolute -inset-1 bg-gradient-to-r from-[var(--theme-primary)] to-[var(--theme-accent)] rounded-2xl blur opacity-25 group-hover:opacity-75 transition duration-500"></div>
              <img
                v-if="school.logo && !logoFailed"
                :src="storageUrl(school.logo)"
                @error="logoFailed = true"
                alt="Logo"
                class="relative h-20 w-auto rounded-2xl p-1 bg-white border border-gray-100 shadow-lg object-contain"
              />
              <div v-else class="relative h-20 w-20 rounded-2xl bg-gradient-to-br from-[var(--theme-primary)] to-[var(--theme-accent)] flex items-center justify-center text-white font-black text-4xl shadow-lg ring-2 ring-white">
                {{ schoolNameBn.charAt(0) }}
              </div>
            </div>
            <div>
              <h1 class="text-3xl font-black text-[var(--theme-secondary)] leading-tight flex items-center gap-3">
                {{ schoolNameBn }}
                <span v-if="school.eiin" class="bg-indigo-50 text-[var(--theme-primary)] text-[10px] uppercase tracking-widest px-2 py-1 rounded-md border border-indigo-100 hidden sm:inline-block">EIIN: {{ school.eiin }}</span>
              </h1>
              <h2 class="text-sm font-bold tracking-[0.2em] text-slate-400 uppercase">{{ school.name || 'School Name' }}</h2>
            </div>
          </a>
          <div class="flex flex-col items-center md:items-end gap-1 text-slate-600 font-bold relative z-10 w-full md:w-auto text-sm">
            <a v-if="school.phone" :href="'tel:'+school.phone" class="hover:text-[var(--theme-primary)] transition-colors flex items-center gap-2"><i class="fas fa-phone-alt opacity-50"></i> {{ school.phone }}</a>
            <a v-if="school.email" :href="'mailto:'+school.email" class="hover:text-[var(--theme-accent)] transition-colors flex items-center gap-2"><i class="fas fa-envelope opacity-50"></i> {{ school.email }}</a>
          </div>
        </div>
      </div>
    </header>

    <nav class="sticky top-0 z-50 bg-[#0f172a]/95 backdrop-blur-md shadow-2xl">
      <div class="max-w-7xl mx-auto px-4 flex items-center justify-between gap-2">
        <button
          type="button"
          class="md:hidden text-white p-3 -ml-1 shrink-0"
          :aria-expanded="mobileNavOpen ? 'true' : 'false'"
          aria-label="মেনু খুলুন/বন্ধ করুন"
          @click="mobileNavOpen = !mobileNavOpen"
        >
          <i class="fas text-lg" :class="mobileNavOpen ? 'fa-times' : 'fa-bars'"></i>
        </button>

        <div
          class="w-full md:flex-1 md:min-w-0 py-1 md:py-0"
          :class="mobileNavOpen ? 'block' : 'hidden md:block'"
          @click="onNavWrapperClick"
        >
          <frontend-site-nav
            v-if="displayMenuItems.length"
            :items="displayMenuItems"
            :open-menus="navOpenMenus"
            @toggle="navOpenMenus = $event"
          />
        </div>

        <div v-if="showAdmissionCta && school.code" class="py-2 shrink-0">
          <a :href="admissionUrl" class="px-6 py-2 bg-green-600 text-white font-black text-xs uppercase tracking-widest rounded-full shadow-lg hover:bg-green-500 hover:scale-105 transition-all flex items-center gap-2">ভর্তি চলছে</a>
        </div>
      </div>
    </nav>

    <div v-if="showMarquee" class="bg-[#1e1b4b] text-white py-2 border-b border-white/5 overflow-hidden">
      <div class="max-w-7xl mx-auto px-4 flex items-center">
        <div class="bg-rose-600 text-[10px] font-black uppercase px-3 py-1 rounded tracking-[0.3em] flex items-center gap-2 mr-6 shrink-0 shadow-lg">
          <i class="fas fa-bolt animate-pulse"></i> আপডেট
        </div>
        <div v-if="marqueeItems.length" class="marquee-container relative overflow-hidden flex-grow h-6">
          <div class="absolute whitespace-nowrap animate-marquee flex gap-8 font-bold text-sm text-slate-300">
            <span v-for="(text, idx) in marqueeItems" :key="'mq-' + idx">
              <i class="fas fa-star text-indigo-400 mr-2"></i> {{ text }}
            </span>
          </div>
        </div>
        <div v-else-if="settings.marquee_text" class="flex-grow text-sm font-bold text-slate-300 truncate">
          <i class="fas fa-star text-indigo-400 mr-2"></i> {{ settings.marquee_text }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'FrontendSiteHeader',
  props: {
    school: { type: Object, required: true },
    settings: { type: Object, default: () => ({}) },
    menuItems: { type: Array, default: () => [] },
    marqueeNotices: { type: Array, default: () => [] },
    storageBase: { type: String, default: '/storage' },
    showMarquee: { type: Boolean, default: true },
    showAdmissionCta: { type: Boolean, default: true },
  },
  data() {
    return {
      logoFailed: false,
      navOpenMenus: {},
      mobileNavOpen: false,
    };
  },
  computed: {
    displayMenuItems() {
      if (this.menuItems?.length) {
        return this.menuItems;
      }

      return [
        { id: 'home', label: 'হোম', url: '/', target: '_self', children: [] },
        { id: 'blog', label: 'ব্লগ', url: '/blog', target: '_self', children: [] },
      ];
    },
    schoolNameBn() {
      return this.school.name_bn || this.school.name || 'বিদ্যালয়';
    },
    homeUrl() {
      return '/';
    },
    admissionUrl() {
      return '/admission/' + (this.school.code || '');
    },
    marqueeItems() {
      const fromNotices = (this.marqueeNotices || []).map(n => (n.title || '').trim()).filter(Boolean);
      if (fromNotices.length) {
        return [...fromNotices, ...fromNotices];
      }
      const fallback = (this.settings.marquee_text || '').trim();
      return fallback ? [fallback, fallback] : [];
    },
  },
  methods: {
    storageUrl(path) {
      if (!path) return '';
      if (path.startsWith('http://') || path.startsWith('https://')) return path;
      const base = (this.storageBase || '/storage').replace(/\/$/, '');
      const clean = String(path).replace(/^\/+/, '').replace(/^\/?storage\//, '');
      return `${base}/${clean}`;
    },
    onNavWrapperClick(e) {
      // Close the mobile panel when a real link is followed, but keep it open
      // when a submenu-toggle button (no href) is clicked.
      if (e.target.closest('a')) {
        this.mobileNavOpen = false;
      }
    },
  },
};
</script>

<style scoped>
.animate-marquee { animation: marquee 9s linear infinite; }
@keyframes marquee { 0% { transform: translateX(5%); } 100% { transform: translateX(-100%); } }
</style>
