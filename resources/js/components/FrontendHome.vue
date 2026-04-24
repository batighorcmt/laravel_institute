<template>
  <div class="min-h-screen bg-[#f8fafc] text-[#1e2a32] font-sans overflow-x-hidden relative">

    <!-- Top Header & Contact Info -->
    <header class="bg-white border-b-4 border-indigo-600 shadow-md relative z-40">
      <div class="h-2 w-full bg-gradient-to-r from-green-500 via-indigo-500 to-purple-600"></div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
          <div class="flex items-center gap-5">
            <div class="relative group">
              <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl blur opacity-25 group-hover:opacity-75 transition duration-500"></div>
              <img v-if="school.logo" :src="'/storage/' + school.logo" alt="Logo" class="relative h-20 w-auto rounded-2xl p-1 bg-white border border-gray-100 shadow-lg object-contain" />
              <div v-else class="relative h-20 w-20 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-black text-4xl shadow-lg ring-2 ring-white">
                {{ schoolNameBn.charAt(0) }}
              </div>
            </div>
            <div>
              <h1 class="text-3xl font-black text-[#1e1b4b] leading-tight flex items-center gap-3">
                 {{ schoolNameBn }}
                 <span v-if="school.eiin" class="bg-indigo-50 text-indigo-600 text-[10px] uppercase tracking-widest px-2 py-1 rounded-md border border-indigo-100 hidden sm:inline-block">EIIN: {{ school.eiin }}</span>
              </h1>
              <h2 class="text-sm font-bold tracking-[0.2em] text-slate-400 uppercase">{{ school.name || 'School Name' }}</h2>
            </div>
          </div>
          <div class="flex flex-col items-center md:items-end gap-1 text-slate-600 font-bold relative z-10 w-full md:w-auto text-sm">
            <a v-if="school.phone" :href="'tel:'+school.phone" class="hover:text-indigo-600 transition-colors flex items-center gap-2"><i class="fas fa-phone-alt opacity-50"></i> {{ school.phone }}</a>
            <a v-if="school.email" :href="'mailto:'+school.email" class="hover:text-purple-600 transition-colors flex items-center gap-2"><i class="fas fa-envelope opacity-50"></i> {{ school.email }}</a>
          </div>
        </div>
      </div>
    </header>

    <!-- Navigation -->
    <nav class="sticky top-0 z-50 bg-[#0f172a]/95 backdrop-blur-md shadow-2xl">
      <div class="max-w-7xl mx-auto px-4">
        <ul class="flex flex-col md:flex-row w-full justify-between items-center">
          <li v-for="link in navLinks" :key="link.id" class="flex-grow text-center group">
            <a :href="link.href" class="block text-slate-200 py-4 text-xs font-black uppercase tracking-[0.2em] hover:text-white transition-all relative">
              {{ link.name }}
              <span class="absolute bottom-0 left-0 w-full h-1 bg-indigo-500 scale-x-0 group-hover:scale-x-100 transition-transform"></span>
            </a>
          </li>
          <li class="md:ml-6 py-2">
             <a :href="'/admission/' + school.code" class="px-6 py-2 bg-green-600 text-white font-black text-xs uppercase tracking-widest rounded-full shadow-lg hover:bg-green-500 hover:scale-105 transition-all flex items-center gap-2">ভর্তি চলছে</a>
          </li>
        </ul>
      </div>
    </nav>

    <!-- Notice Update Marquee -->
    <div class="bg-[#1e1b4b] text-white py-2 border-b border-white/5 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 flex items-center">
           <div class="bg-rose-600 text-[10px] font-black uppercase px-3 py-1 rounded tracking-[0.3em] flex items-center gap-2 mr-6 shrink-0 shadow-lg">
              <i class="fas fa-bolt animate-pulse"></i> আপডেট
           </div>
           <div class="marquee-container relative overflow-hidden flex-grow h-6">
              <div class="absolute whitespace-nowrap animate-marquee flex gap-16 font-bold text-sm text-slate-300">
                 <span v-if="settings.marquee_text"><i class="fas fa-star text-indigo-400 mr-2"></i> {{ settings.marquee_text }}</span>
                 <span v-else><i class="fas fa-star text-indigo-400 mr-2"></i> অনলাইন ভর্তি ফরম পূরণ শুরু হয়েছে। ডিজিটাল হাজিরা সফটওয়্যারে সকল শিক্ষার্থীদের স্বাগতম।</span>
                 <span v-if="settings.marquee_text"><i class="fas fa-star text-indigo-400 mr-2"></i> {{ settings.marquee_text }}</span>
              </div>
           </div>
        </div>
    </div>

    <!-- Hero & Notice Section (Matching Heights) -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-10 mb-20">
      <div class="flex flex-col lg:flex-row gap-6 auto-rows-fr h-auto lg:h-[480px]">
        
        <!-- Slider Section (Left) -->
        <div class="lg:w-8/12 h-[400px] lg:h-full relative rounded-[40px] overflow-hidden shadow-2xl ring-8 ring-white group border border-slate-100">
           <transition name="fade">
              <div :key="activeSlide" class="absolute inset-0">
                 <img :src="currentSlide.image ? (currentSlide.image.startsWith('http') ? currentSlide.image : '/storage/' + currentSlide.image) : 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=1200'" class="w-full h-full object-cover">
              </div>
           </transition>
           
           <!-- Overlay Content -->
           <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent flex flex-col justify-end p-10 md:p-14">
              <transition name="slide-up">
                 <div :key="activeSlide">
                    <span class="bg-indigo-600 text-white text-[10px] font-black uppercase tracking-[0.3em] px-4 py-1.5 rounded-full mb-4 inline-block shadow-lg">Welcome</span>
                    <h2 class="text-4xl md:text-6xl font-black text-white mb-3 drop-shadow-2xl leading-tight">{{ currentSlide.title || settings.hero_title || schoolNameBn }}</h2>
                    <p class="text-slate-200 text-base md:text-xl font-medium max-w-2xl border-l-4 border-indigo-500 pl-4 py-1">{{ currentSlide.subtitle || settings.hero_subtitle || 'শ্রেষ্ঠত্বের পথে একটি গৌরবময় যাত্রা।' }}</p>
                 </div>
              </transition>
           </div>

           <!-- Slider Arrows -->
           <div v-if="activeSlides.length > 1" class="absolute inset-x-0 top-1/2 -translate-y-1/2 flex justify-between px-6 opacity-0 group-hover:opacity-100 transition-opacity">
              <button @click="prevSlide" class="w-12 h-12 bg-white/20 backdrop-blur-xl text-white rounded-full flex items-center justify-center hover:bg-white hover:text-indigo-600 transition-all"><i class="fas fa-chevron-left"></i></button>
              <button @click="nextSlide" class="w-12 h-12 bg-white/20 backdrop-blur-xl text-white rounded-full flex items-center justify-center hover:bg-white hover:text-indigo-600 transition-all"><i class="fas fa-chevron-right"></i></button>
           </div>
        </div>

        <!-- Notice Board (Right - Matching Height) -->
        <div class="lg:w-4/12 h-[450px] lg:h-full bg-white rounded-[40px] shadow-2xl flex flex-col overflow-hidden border border-slate-100 relative group">
           <div class="bg-[#1e1b4b] text-white p-5 flex items-center justify-between border-b-4 border-indigo-500 shadow-inner">
              <div class="flex items-center gap-4">
                 <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center animate-pulse"><i class="fas fa-bullhorn text-sm"></i></div>
                 <h3 class="text-xl font-black tracking-widest">নোটিশ বোর্ড</h3>
              </div>
              <span class="text-[10px] font-black bg-indigo-600 px-3 py-1 rounded-full uppercase">{{ notices.length }} New</span>
           </div>
           
           <!-- Content - Scrollable notice area -->
           <div class="flex-grow overflow-y-auto overflow-x-hidden custom-scrollbar bg-indigo-50/20 p-4 space-y-3">
              <div v-for="(notice, idx) in notices" :key="idx" class="p-4 bg-white rounded-3xl shadow-sm border border-slate-100 hover:shadow-lg hover:border-indigo-100 transition-all cursor-pointer group/item hover:-translate-y-1">
                 <div class="flex gap-4">
                    <div class="flex flex-col items-center justify-center bg-indigo-50 rounded-2xl p-2 text-indigo-700 min-w-[55px] border border-indigo-100 group-hover/item:bg-indigo-600 group-hover/item:text-white transition-colors">
                       <span class="text-sm font-black">{{ notice.date.split(' ')[0] }}</span>
                       <span class="text-[10px] font-bold opacity-80">{{ notice.date.split(' ')[1] }}</span>
                    </div>
                    <div class="flex-grow py-1">
                       <h4 class="text-sm font-black text-slate-800 leading-snug group-hover/item:text-indigo-600 transition-colors mb-2">{{ notice.title }}</h4>
                       <div class="flex items-center justify-between">
                          <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">ডাউনলোড</span>
                          <i class="fas fa-arrow-right text-indigo-600 text-xs group-hover/item:translate-x-1 transition-transform"></i>
                       </div>
                    </div>
                 </div>
              </div>
           </div>

           <!-- Footer action -->
           <div class="p-4 bg-white border-t border-slate-50">
              <a href="/login" class="w-full py-3 bg-indigo-50 text-indigo-700 rounded-2xl font-black text-xs uppercase tracking-widest flex items-center justify-center gap-2 hover:bg-indigo-600 hover:text-white transition-all shadow-inner">
                 সব নোটিশ দেখুন <i class="fas fa-chevron-right ml-1"></i>
              </a>
           </div>
        </div>

      </div>
    </section>

    <!-- Stats Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-24 relative -mt-10 lg:-mt-14 z-20">
       <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
          <div v-for="stat in stats" :key="stat.label" class="p-8 rounded-[40px] shadow-xl text-center text-white transform hover:-translate-y-2 transition-all" :class="stat.bg">
             <div class="text-4xl font-black mb-1 drop-shadow-md">{{ stat.value }}</div>
             <div class="text-[10px] font-black uppercase tracking-[0.3em] opacity-80">{{ stat.label }}</div>
          </div>
       </div>
    </div>

    <!-- About Section -->
    <section id="about" class="py-24 bg-white overflow-hidden" data-aos="fade-up">
       <div class="max-w-7xl mx-auto px-4 flex flex-col lg:flex-row items-center gap-16">
          <div class="lg:w-1/2 relative">
             <div class="absolute -inset-4 bg-indigo-500 rounded-[60px] transform rotate-3 scale-95 opacity-5"></div>
             <img :src="settings.about_image ? '/storage/' + settings.about_image : 'https://images.unsplash.com/photo-1546410531-bea5aad13914?w=800'" class="rounded-[60px] shadow-2xl border-8 border-white relative z-10 w-full aspect-video object-cover">
          </div>
          <div class="lg:w-2/3">
             <h4 class="text-indigo-600 font-black uppercase text-xs tracking-[0.4em] mb-4">About School</h4>
             <h2 class="text-4xl md:text-5xl font-black text-[#1e1b4b] mb-8 leading-tight">জ্ঞানের আলোয় আলোকিত <br><span class="text-indigo-500">আগামীর সুন্দর ভবিষ্যৎ</span></h2>
             <div v-if="settings.about_text" class="prose prose-lg text-slate-600 max-w-none leading-relaxed prose-indigo mb-10" v-html="settings.about_text"></div>
             <p v-else class="text-lg text-slate-500">প্রতিষ্ঠার পর থেকে এটি একটি আধুনিক প্রগতিশীল শিক্ষাপ্রতিষ্ঠান হিসেবে দীর্ঘকাল ধরে তার শ্রেষ্ঠত্ব প্রমাণ করে আসছে।</p>
          </div>
       </div>
    </section>

    <!-- Principal & Administration -->
    <section id="teachers" class="py-24 bg-slate-50 border-y border-slate-100">
       <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row gap-16 items-center">
          <div class="md:w-1/3 text-center">
             <div class="relative inline-block border-8 border-white shadow-2xl rounded-[40px] overflow-hidden group aspect-[3/4] max-w-[320px]">
                <img v-if="settings.principal_image" :src="'/storage/' + settings.principal_image" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                <div v-else class="w-full h-full bg-slate-200 flex items-center justify-center text-slate-400 text-8xl"><i class="fas fa-user-tie"></i></div>
                <div class="absolute bottom-0 inset-x-0 p-6 bg-gradient-to-t from-indigo-900 to-transparent text-white pt-10">
                   <h4 class="text-xl font-black">{{ settings.principal_name || 'Principal Name' }}</h4>
                </div>
             </div>
          </div>
          <div class="md:w-2/3 relative">
             <i class="fas fa-quote-left text-9xl text-indigo-500/10 absolute -top-10 -left-10"></i>
             <div class="px-6 py-2 bg-indigo-600 text-white rounded-full text-[10px] font-black uppercase tracking-widest inline-block mb-8">অধ্যক্ষের বাণী</div>
             <div v-if="settings.principal_message" class="text-2xl md:text-3xl text-slate-700 font-bold leading-relaxed prose max-w-none" v-html="settings.principal_message"></div>
             <p v-else class="text-xl text-slate-500 italic leading-relaxed">"শিক্ষাই জাতির মেরুদণ্ড। আদর্শ সুনাগরিক হিসেবে শিক্ষার্থীদের গড়ে তোলাই আমাদের মূল লক্ষ্য।"</p>
             <div class="mt-12 h-1 w-20 bg-indigo-600 rounded-full"></div>
          </div>
       </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="bg-[#020617] text-white pt-24 pb-10 px-4">
       <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-20 border-b border-white/5 pb-20 mb-10">
          <div>
             <h4 class="text-3xl font-black mb-6 bg-clip-text text-transparent bg-gradient-to-r from-indigo-400 to-purple-400">{{ schoolNameBn }}</h4>
             <p class="text-slate-400 leading-relaxed font-medium">{{ settings.contact_address || school.address_bn || school.address }}</p>
          </div>
          <div class="space-y-4">
             <h5 class="text-xs font-black uppercase tracking-[0.5em] text-slate-500 mb-8">Contacts</h5>
             <p class="flex items-center gap-4 text-slate-300 font-bold"><i class="fas fa-phone-alt text-indigo-500"></i> {{ settings.contact_phone || school.phone }}</p>
             <p class="flex items-center gap-4 text-slate-300 font-bold"><i class="fas fa-envelope text-indigo-500"></i> {{ settings.contact_email || school.email }}</p>
          </div>
          <div class="bg-indigo-900/40 p-10 rounded-[40px] border border-indigo-500/20 text-center flex flex-col justify-center">
             <span class="text-[9px] font-black uppercase tracking-[0.8em] text-indigo-400 block mb-4">Institution EIIN</span>
             <div class="text-6xl font-black text-white mix-blend-screen mix-blend-plus-lighter">{{ school.eiin }}</div>
          </div>
       </div>
       <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center text-[10px] font-black uppercase tracking-widest text-slate-600">
          <p>&copy; {{ new Date().getFullYear() }} {{ school.name }}. All rights reserved.</p>
          <div class="flex items-center gap-3 mt-4 md:mt-0">
             <span>Platform by</span>
             <span class="bg-white text-black px-2 py-0.5 rounded">BATIGHOR EIMS</span>
          </div>
       </div>
    </footer>

  </div>
</template>

<script>
export default {
  name: 'FrontendHome',
  props: { school: Object, settings: Object },
  data() {
    return {
      activeSlide: 0,
      slideInterval: null,
      navLinks: [
        { id: 1, name: 'Home', href: '#home' },
        { id: 2, name: 'About', href: '#about' },
        { id: 3, name: 'Administration', href: '#teachers' },
        { id: 4, name: 'Facilities', href: '#about' },
        { id: 5, name: 'Contact', href: '#contact' }
      ],
      stats: [
        { label: 'Students', value: '৮৫০+', bg: 'bg-emerald-600 shadow-emerald-500/20' },
        { label: 'Teachers', value: '৩৫', bg: 'bg-indigo-600 shadow-indigo-500/20' },
        { label: 'Awards', value: '৫+', bg: 'bg-purple-600 shadow-purple-500/20' },
        { label: 'Pass Rate', value: '১০০%', bg: 'bg-rose-600 shadow-rose-500/20' }
      ],
      notices: [
        { title: 'এসএসসি পরীক্ষা ২০২৬ সংক্রান্ত বিজ্ঞপ্তি ও রুটিন', date: '৩০ মার্চ' },
        { title: '৬ষ্ঠ শ্রেণিতে ভর্তি আবেদন শুরু ১০ এপ্রিল থেকে', date: '২৫ মার্চ' },
        { title: 'বার্ষিক ক্রীড়া প্রতিযোগিতা ২০২৫-এর তারিখ ঘোষণা', date: '২০ মার্চ' },
        { title: 'অভিভাবক সভা আগামী ১৫ মে তারিখে অনুষ্ঠিত হবে', date: '১৫ মার্চ' },
        { title: 'বার্ষিক পরীক্ষার ফলাফল ও মার্কশীট বিতরণ', date: '১০ মার্চ' },
        { title: 'নতুন সেশনের ক্লাস রুটিন প্রকাশ করা হয়েছে', date: '০৫ মার্চ' },
        { title: 'লাইব্রেরি কার্ড বিতরণ কর্মসূচি', date: '০১ মার্চ' }
      ]
    };
  },
  computed: {
    schoolNameBn() { return this.school.name_bn || this.school.name || "বিদ্যালয়"; },
    activeSlides() {
       let images = [];
       if (typeof this.settings.hero_images === 'string') {
          try { images = JSON.parse(this.settings.hero_images); } catch(e) { images = []; }
       } else { 
          images = this.settings.hero_images || [];
       }
       
       // Fallback to singular hero_image if slider is empty
       if (images.length === 0 && this.settings.hero_image) {
          images = [{ image: this.settings.hero_image, active: true }];
       }
       
       return images.filter(i => i && (i.active === true || i.active === undefined)).map(i => typeof i === 'string' ? { image: i, active: true } : i);
    },
    currentSlide() {
       return this.activeSlides[this.activeSlide] || { image: null, title: '', subtitle: '' };
    }
  },
  mounted() {
    if (window.AOS) window.AOS.init();
    this.startSlider();
  },
  beforeUnmount() { clearInterval(this.slideInterval); },
  methods: {
    startSlider() { if (this.activeSlides.length > 1) this.slideInterval = setInterval(() => this.nextSlide(), 6000); },
    nextSlide() { this.activeSlide = (this.activeSlide + 1) % this.activeSlides.length; },
    prevSlide() { this.activeSlide = (this.activeSlide - 1 + this.activeSlides.length) % this.activeSlides.length; }
  }
}
</script>

<style scoped>
.animate-marquee { animation: marquee 30s linear infinite; }
@keyframes marquee { 0% { transform: translateX(10%); } 100% { transform: translateX(-100%); } }
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.2); border-radius: 10px; }
.fade-enter-active, .fade-leave-active { transition: opacity 1s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-up-enter-active { transition: opacity 0.8s ease, transform 0.8s ease; }
.slide-up-enter-from { opacity: 0; transform: translateY(30px); }
</style>
