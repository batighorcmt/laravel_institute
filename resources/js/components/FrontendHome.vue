<template>
  <div class="min-h-screen bg-[#f8fafc] text-[#1e2a32] font-sans overflow-x-hidden relative">

    <frontend-site-header
      :school="school"
      :settings="settings"
      :menu-items="headerMenuItems"
      :marquee-notices="marqueeNotices"
      :storage-base="storageBase"
    />

    <!-- Hero & Notice Section (Matching Heights) -->
    <section id="home" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-10 mb-20">
      <div class="flex flex-col lg:flex-row gap-6 lg:items-stretch">
        
        <!-- Slider Section (Left) — explicit height; lg:w-2/3 (invalid w-8/12 breaks layout in production CSS) -->
        <div class="w-full lg:w-2/3 h-[400px] lg:h-[480px] shrink-0 relative rounded-[40px] overflow-hidden shadow-2xl ring-8 ring-white group border border-slate-100 bg-gradient-to-br from-indigo-900 via-indigo-700 to-slate-800">
           <img
             v-if="heroImageSrc"
             :key="'hero-' + activeSlide"
             :src="heroImageSrc"
             @error="onHeroImageError"
             alt=""
             class="absolute inset-0 z-0 h-full w-full object-cover"
           />
           
           <!-- Overlay Content -->
           <div class="absolute inset-0 z-10 bg-gradient-to-t from-black/90 via-black/20 to-transparent flex flex-col justify-end p-10 md:p-14 pointer-events-none">
              <div>
                 <h2
                   class="text-2xl md:text-4xl font-black text-white drop-shadow-2xl leading-tight"
                   :class="hasSlideSubtitle ? 'mb-3' : 'mb-0'"
                 >{{ slideTitle }}</h2>
                 <p
                   v-if="hasSlideSubtitle"
                   class="text-slate-200 text-base md:text-xl font-medium max-w-2xl border-l-4 border-indigo-500 pl-4 py-1"
                 >{{ slideSubtitle }}</p>
              </div>
           </div>

           <!-- Slider Arrows -->
           <div v-if="activeSlides.length > 1" class="absolute inset-x-0 top-1/2 z-20 -translate-y-1/2 flex justify-between px-6 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-auto">
              <button @click="prevSlide" class="w-12 h-12 bg-white/20 backdrop-blur-xl text-white rounded-full flex items-center justify-center hover:bg-white hover:text-[var(--theme-primary)] transition-all"><i class="fas fa-chevron-left"></i></button>
              <button @click="nextSlide" class="w-12 h-12 bg-white/20 backdrop-blur-xl text-white rounded-full flex items-center justify-center hover:bg-white hover:text-[var(--theme-primary)] transition-all"><i class="fas fa-chevron-right"></i></button>
           </div>
        </div>

        <!-- Notice Board (Right - Matching Height) -->
        <div class="w-full lg:w-1/3 h-[400px] lg:h-[480px] shrink-0 bg-white rounded-[40px] shadow-2xl flex flex-col overflow-hidden border border-slate-100 relative group">
           <div class="bg-[#1e1b4b] text-white p-5 flex items-center justify-between border-b-4 border-indigo-500 shadow-inner">
              <div class="flex items-center gap-4">
                 <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center animate-pulse"><i class="fas fa-bullhorn text-sm"></i></div>
                 <h3 class="text-xl font-black tracking-widest">নোটিশ বোর্ড</h3>
              </div>
              <span v-if="boardNotices.length" class="text-[10px] font-black bg-indigo-600 px-3 py-1 rounded-full uppercase">{{ boardNotices.length }} New</span>
           </div>
           
           <!-- Content - Compact title-only scrollable list -->
           <div class="flex-grow overflow-y-auto overflow-x-hidden custom-scrollbar bg-indigo-50/20 p-3">
              <ul class="space-y-1">
                 <li
                   v-for="notice in boardNotices"
                   :key="notice.id"
                   class="flex items-start gap-2 px-3 py-2.5 rounded-xl hover:bg-indigo-100 cursor-pointer transition-colors group/item"
                   @click="openNoticeDetail(notice)"
                 >
                    <i class="fas fa-caret-right text-indigo-500 mt-1 text-xs shrink-0"></i>
                    <span class="text-sm font-bold text-slate-700 leading-snug flex-grow group-hover/item:text-indigo-700">{{ notice.title }}</span>
                    <i v-if="notice.download_url" class="fas fa-paperclip text-indigo-400 text-xs mt-1 opacity-60"></i>
                 </li>
                 <li v-if="!boardNotices.length" class="px-3 py-6 text-center text-sm text-slate-400 font-medium">
                    কোনো নোটিশ নেই
                 </li>
              </ul>
           </div>

           <!-- Footer action -->
           <div class="p-4 bg-white border-t border-slate-50">
              <button
                type="button"
                @click="openNoticesModal"
                class="w-full py-3 bg-indigo-50 text-indigo-700 rounded-2xl font-black text-xs uppercase tracking-widest flex items-center justify-center gap-2 hover:bg-indigo-600 hover:text-white transition-all shadow-inner"
              >
                 সব নোটিশ দেখুন <i class="fas fa-chevron-right ml-1"></i>
              </button>
           </div>
        </div>

      </div>
    </section>

    <!-- Stats Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-24 relative -mt-10 lg:-mt-14 z-20">
       <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
          <div v-for="stat in statsCards" :key="stat.label" class="p-8 rounded-[40px] shadow-xl text-center text-white transform hover:-translate-y-2 transition-all" :class="stat.bg">
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
             <div class="rounded-[60px] shadow-2xl border-8 border-white relative z-10 w-full aspect-video overflow-hidden bg-gradient-to-br from-indigo-100 to-slate-200">
               <img
                 v-if="aboutImageSrc"
                 :src="aboutImageSrc"
                 @error="aboutImageBroken = true"
                 alt=""
                 class="w-full h-full object-cover"
               />
               <div v-else class="w-full h-full flex items-center justify-center text-indigo-300 text-6xl"><i class="fas fa-school"></i></div>
             </div>
          </div>
          <div class="lg:w-2/3">
             <h4 class="text-[var(--theme-primary)] font-black uppercase text-xs tracking-[0.4em] mb-4">About School</h4>
             <h2 class="text-4xl md:text-5xl font-black text-[#1e1b4b] mb-8 leading-tight">জ্ঞানের আলোয় আলোকিত <br><span class="text-indigo-500">আগামীর সুন্দর ভবিষ্যৎ</span></h2>
             <div v-if="settings.about_text" class="prose prose-lg text-slate-600 max-w-none leading-relaxed prose-indigo mb-10" v-html="settings.about_text"></div>
             <p v-else class="text-lg text-slate-500">প্রতিষ্ঠার পর থেকে এটি একটি আধুনিক প্রগতিশীল শিক্ষাপ্রতিষ্ঠান হিসেবে দীর্ঘকাল ধরে তার শ্রেষ্ঠত্ব প্রমাণ করে আসছে।</p>
          </div>
       </div>
    </section>

    <!-- Mission & Vision -->
    <section id="mission" class="py-20 relative overflow-hidden">
      <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-white to-purple-50"></div>
      <div class="max-w-7xl mx-auto px-4 relative z-10">
        <div class="grid md:grid-cols-2 gap-8">
          <div
            class="group p-10 rounded-[40px] bg-gradient-to-br from-[var(--theme-primary)] to-[var(--theme-secondary)] text-white shadow-2xl hover:-translate-y-2 transition-all duration-500"
            data-aos="fade-right"
          >
            <div class="w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
              <i class="fas fa-bullseye text-3xl"></i>
            </div>
            <h3 class="text-3xl font-black mb-4">{{ homepage.mission.title }}</h3>
            <p class="text-indigo-100 leading-relaxed text-lg">{{ homepage.mission.body }}</p>
          </div>
          <div
            id="vision"
            class="group p-10 rounded-[40px] bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-2xl hover:-translate-y-2 transition-all duration-500"
            data-aos="fade-left"
          >
            <div class="w-16 h-16 rounded-2xl bg-white/20 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
              <i class="fas fa-eye text-3xl"></i>
            </div>
            <h3 class="text-3xl font-black mb-4">{{ homepage.vision.title }}</h3>
            <p class="text-emerald-50 leading-relaxed text-lg">{{ homepage.vision.body }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Achievements -->
    <section id="achievements" class="py-24 bg-[#0f172a] text-white relative overflow-hidden">
      <div class="absolute top-0 right-0 w-96 h-96 bg-[var(--theme-accent)]/20 rounded-full blur-3xl animate-pulse-slow"></div>
      <div class="absolute bottom-0 left-0 w-80 h-80 bg-rose-500/20 rounded-full blur-3xl animate-pulse-slow"></div>
      <div class="max-w-7xl mx-auto px-4 relative z-10">
        <div class="text-center mb-16" data-aos="fade-up">
          <span class="text-amber-400 font-black uppercase tracking-[0.4em] text-xs">Hall of Fame</span>
          <h2 class="text-4xl md:text-5xl font-black mt-3">গৌরবের অর্জন</h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div
            v-for="(item, idx) in homepage.achievements"
            :key="'ach-'+idx"
            class="p-6 rounded-[32px] bg-white/5 border border-white/10 backdrop-blur-sm hover:bg-white/10 hover:-translate-y-2 transition-all duration-300"
            data-aos="zoom-in"
            :data-aos-delay="idx * 80"
          >
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br flex items-center justify-center mb-5 shadow-lg" :class="item.color">
              <i class="fas text-white text-xl" :class="item.icon"></i>
            </div>
            <span class="text-xs font-black text-amber-400 uppercase tracking-widest">{{ item.year }}</span>
            <h4 class="text-xl font-black mt-2 mb-2">{{ item.title }}</h4>
            <p class="text-slate-400 text-sm leading-relaxed">{{ item.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Teachers -->
    <section id="faculty" class="py-24 bg-gradient-to-b from-slate-50 to-white">
      <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-14" data-aos="fade-up">
          <span class="text-[var(--theme-primary)] font-black uppercase tracking-[0.4em] text-xs">Our Faculty</span>
          <h2 class="text-4xl md:text-5xl font-black text-[#1e1b4b] mt-3">আমাদের শিক্ষকমণ্ডলী</h2>
        </div>
        <div v-if="teachers.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
          <div
            v-for="(teacher, idx) in displayedTeachers"
            :key="'t-'+teacher.id"
            class="group text-center"
            data-aos="fade-up"
            :data-aos-delay="idx * 60"
          >
            <div class="relative mx-auto w-36 h-36 mb-4">
              <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 rounded-[32px] blur opacity-40 group-hover:opacity-80 transition-opacity"></div>
              <button
                type="button"
                @click="openTeacherModal(teacher)"
                class="relative w-full h-full rounded-[32px] overflow-hidden border-4 border-white shadow-xl bg-slate-100 block focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <img v-if="teacher.photo" :src="teacher.photo" :alt="teacher.name" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                <div v-else class="w-full h-full flex items-center justify-center text-4xl text-indigo-300"><i class="fas fa-user-tie"></i></div>
              </button>
            </div>
            <button
              type="button"
              @click="openTeacherModal(teacher)"
              class="font-black text-slate-800 hover:text-[var(--theme-primary)] transition-colors focus:outline-none"
            >{{ teacher.name }}</button>
            <p class="text-sm text-[var(--theme-primary)] font-bold mt-1">{{ teacher.designation }}</p>
          </div>
        </div>
        <div v-if="teachers.length > 12 && !showAllTeachers" class="text-center mt-12" data-aos="fade-up">
          <button
            type="button"
            @click="showAllTeachers = true"
            class="inline-flex items-center gap-2 px-8 py-4 rounded-full bg-[var(--theme-primary)] text-white font-black text-sm uppercase tracking-widest shadow-xl hover:brightness-90 hover:scale-105 transition-all"
          >
            সকল শিক্ষক দেখুন
            <i class="fas fa-chevron-down"></i>
          </button>
        </div>
        <p v-else-if="!teachers.length" class="text-center text-slate-400 font-medium">শিক্ষকমণ্ডলীর তথ্য শীঘ্রই যুক্ত হবে।</p>
      </div>
    </section>

    <!-- Facilities -->
    <section id="facilities" class="py-24 bg-white">
      <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-14" data-aos="fade-up">
          <span class="text-emerald-600 font-black uppercase tracking-[0.4em] text-xs">Campus Life</span>
          <h2 class="text-4xl md:text-5xl font-black text-[#1e1b4b] mt-3">স্কুলের সুবিধাসমূহ</h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="(facility, idx) in homepage.facilities"
            :key="'fac-'+idx"
            class="group p-8 rounded-[36px] border border-slate-100 bg-slate-50 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300"
            data-aos="flip-left"
            :data-aos-delay="idx * 70"
          >
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br flex items-center justify-center mb-6 text-white shadow-lg group-hover:scale-110 transition-transform" :class="facility.color">
              <i class="fas text-2xl" :class="facility.icon"></i>
            </div>
            <h4 class="text-xl font-black text-slate-800 mb-2">{{ facility.title }}</h4>
            <p class="text-slate-500 leading-relaxed">{{ facility.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Blog -->
    <section id="blog" class="py-24 bg-gradient-to-br from-indigo-900 via-purple-900 to-slate-900 text-white">
      <div class="max-w-7xl mx-auto px-4">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12" data-aos="fade-up">
          <div>
            <span class="text-indigo-300 font-black uppercase tracking-[0.4em] text-xs">News & Updates</span>
            <h2 class="text-4xl md:text-5xl font-black mt-3">{{ blogSectionTitle }}</h2>
            <p v-if="blogSectionSubtitle" class="text-indigo-200 mt-2 font-medium">{{ blogSectionSubtitle }}</p>
          </div>
          <a href="/blog" class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-white text-indigo-900 font-black text-sm hover:scale-105 transition-transform">
            সব পোস্ট দেখুন <i class="fas fa-arrow-right"></i>
          </a>
        </div>
        <div v-if="blogPosts.length" class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          <a
            v-for="(post, idx) in blogPosts"
            :key="'blog-'+post.id"
            :href="post.url"
            class="group block rounded-[32px] overflow-hidden bg-white/10 border border-white/10 hover:bg-white/15 hover:-translate-y-2 transition-all duration-300"
            data-aos="fade-up"
            :data-aos-delay="idx * 100"
          >
            <div class="aspect-video overflow-hidden bg-slate-800">
              <img v-if="post.image" :src="post.image" :alt="post.title" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
              <div v-else class="w-full h-full flex items-center justify-center text-5xl text-indigo-400/50"><i class="fas fa-newspaper"></i></div>
            </div>
            <div class="p-6">
              <span v-if="post.date" class="text-xs font-bold text-indigo-300">{{ post.date }}</span>
              <h4 class="text-xl font-black mt-2 mb-2 group-hover:text-amber-300 transition-colors">{{ post.title }}</h4>
              <p class="text-slate-300 text-sm line-clamp-3">{{ post.excerpt }}</p>
            </div>
          </a>
        </div>
        <p v-else class="text-center text-indigo-200 font-medium">এখনো কোনো ব্লগ পোস্ট প্রকাশিত হয়নি।</p>
      </div>
    </section>

    <!-- Gallery -->
    <section id="gallery" class="py-24 bg-slate-50">
      <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-14" data-aos="fade-up">
          <span class="text-rose-500 font-black uppercase tracking-[0.4em] text-xs">Memories</span>
          <h2 class="text-4xl md:text-5xl font-black text-[#1e1b4b] mt-3">ফটো গ্যালারী</h2>
        </div>
        <div v-if="galleryImages.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <div
            v-for="(img, idx) in galleryImages"
            :key="'gal-'+idx"
            class="group relative aspect-square rounded-[28px] overflow-hidden shadow-lg cursor-pointer"
            data-aos="zoom-in"
            :data-aos-delay="idx * 50"
          >
            <img :src="img" alt="" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
            <div class="absolute inset-0 bg-indigo-900/0 group-hover:bg-indigo-900/30 transition-colors flex items-center justify-center">
              <i class="fas fa-search-plus text-white text-2xl opacity-0 group-hover:opacity-100 transition-opacity"></i>
            </div>
          </div>
        </div>
        <p v-else class="text-center text-slate-400">গ্যালারীতে ছবি যুক্ত করুন (ফ্রন্টএন্ড সেটিংস থেকে)।</p>
      </div>
    </section>

    <!-- Principal & Chairman Messages -->
    <section id="principal" class="py-24 bg-slate-50 border-y border-slate-100">
       <div class="max-w-7xl mx-auto px-4">
          <div class="grid grid-cols-1 gap-16" :class="messagePersons.length > 1 ? 'lg:grid-cols-2' : ''">
             <div v-for="person in messagePersons" :key="person.key" class="flex flex-col md:flex-row gap-10 items-center">
                <div class="md:w-2/5 text-center shrink-0">
                   <div class="relative inline-block border-8 border-white shadow-2xl rounded-[40px] overflow-hidden group aspect-[3/4] max-w-[280px]">
                      <img v-if="personImageUrl(person)" :src="personImageUrl(person)" @error="markImageFailed(person.key)" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                      <div v-else class="w-full h-full bg-slate-200 flex items-center justify-center text-slate-400 text-7xl"><i :class="person.icon"></i></div>
                      <div class="absolute bottom-0 inset-x-0 p-6 bg-gradient-to-t from-indigo-900 to-transparent text-white pt-10">
                         <h4 class="text-xl font-black">{{ person.name || person.fallbackName }}</h4>
                      </div>
                   </div>
                </div>
                <div class="md:w-3/5 relative">
                   <i class="fas fa-quote-left text-7xl text-indigo-500/10 absolute -top-8 -left-8"></i>
                   <div class="px-6 py-2 bg-[var(--theme-primary)] text-white rounded-full text-[10px] font-black uppercase tracking-widest inline-block mb-6">{{ person.label }}</div>
                   <div v-if="person.message" class="text-xl md:text-2xl text-slate-700 font-bold leading-relaxed prose max-w-none message-clamp" v-html="person.message"></div>
                   <p v-else-if="person.fallbackQuote" class="text-lg text-slate-500 italic leading-relaxed">{{ person.fallbackQuote }}</p>
                   <button
                     v-if="person.message"
                     type="button"
                     @click="openMessageModal(person)"
                     class="mt-8 inline-flex items-center gap-2 px-7 py-3.5 rounded-full bg-[#1e1b4b] text-white font-black text-sm hover:bg-indigo-900 hover:gap-3 transition-all duration-300 shadow-lg shadow-indigo-900/20"
                   >
                     সম্পূর্ণ বাণী পড়ুন <i class="fas fa-arrow-right text-xs"></i>
                   </button>
                   <div class="mt-10 h-1 w-20 bg-indigo-600 rounded-full"></div>
                </div>
             </div>
          </div>
       </div>
    </section>

    <frontend-site-footer
      :school="school"
      :settings="settings"
      :menu-items="footerMenuItems"
    />

    <!-- Full Message Modal (Principal / Chairman) -->
    <Teleport to="body">
      <div
        v-if="activeMessagePerson"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm"
        @click.self="closeMessageModal"
      >
        <div class="bg-white rounded-[32px] shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden border border-slate-100">
          <div class="bg-[#1e1b4b] text-white px-6 py-5 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-4 min-w-0">
              <div class="w-14 h-14 rounded-full overflow-hidden bg-white/10 shrink-0 border-2 border-white/20">
                <img v-if="personImageUrl(activeMessagePerson)" :src="personImageUrl(activeMessagePerson)" class="w-full h-full object-cover" alt="">
                <div v-else class="w-full h-full flex items-center justify-center text-xl"><i :class="activeMessagePerson.icon"></i></div>
              </div>
              <div class="min-w-0">
                <h3 class="text-lg font-black truncate">{{ activeMessagePerson.name || activeMessagePerson.fallbackName }}</h3>
                <p class="text-indigo-200 text-xs mt-0.5">{{ activeMessagePerson.label }}</p>
              </div>
            </div>
            <button type="button" @click="closeMessageModal" class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors shrink-0">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="p-8 overflow-y-auto prose max-w-none text-slate-700 text-lg leading-relaxed" v-html="activeMessagePerson.message"></div>
        </div>
      </div>
    </Teleport>

    <!-- All Notices Modal -->
    <Teleport to="body">
      <div
        v-if="showNoticesModal"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm"
        @click.self="closeNoticesModal"
      >
        <div class="bg-white rounded-[32px] shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden border border-slate-100">
          <div class="bg-[#1e1b4b] text-white px-6 py-5 flex items-center justify-between shrink-0">
            <div>
              <h3 class="text-xl font-black tracking-wide">সকল নোটিশ</h3>
              <p class="text-indigo-200 text-xs mt-1">মোট {{ filteredAllNotices.length }}টি নোটিশ</p>
            </div>
            <button type="button" @click="closeNoticesModal" class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <div class="px-6 py-4 border-b border-slate-100 shrink-0">
            <div class="relative">
              <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
              <input
                v-model="noticeTitleFilter"
                type="text"
                placeholder="নোটিশের শিরোনাম দিয়ে খুঁজুন..."
                class="w-full pl-11 pr-4 py-3 rounded-2xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none text-sm font-medium"
              />
            </div>
          </div>

          <div class="flex-grow overflow-auto custom-scrollbar">
            <table class="w-full text-sm">
              <thead class="bg-slate-50 sticky top-0 z-10">
                <tr class="text-left text-[10px] font-black uppercase tracking-widest text-slate-500">
                  <th class="px-6 py-3 w-16">ক্রমিক</th>
                  <th class="px-4 py-3">নোটিশের শিরোনাম</th>
                  <th class="px-4 py-3 w-36">প্রকাশের তারিখ</th>
                  <th class="px-6 py-3 w-28 text-center">ডাউনলোড</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(notice, index) in paginatedNotices"
                  :key="notice.id"
                  class="border-t border-slate-50 hover:bg-indigo-50/50 transition-colors"
                >
                  <td class="px-6 py-4 font-bold text-slate-400">{{ noticeSerialStart + index }}</td>
                  <td class="px-4 py-4">
                    <button
                      type="button"
                      @click="openNoticeDetail(notice)"
                      class="font-bold text-slate-800 hover:text-[var(--theme-primary)] text-left"
                    >{{ notice.title }}</button>
                  </td>
                  <td class="px-4 py-4 text-slate-500 font-medium whitespace-nowrap">{{ notice.publish_at_label || '—' }}</td>
                  <td class="px-6 py-4 text-center">
                    <button
                      v-if="notice.download_url"
                      type="button"
                      @click="downloadNotice(notice)"
                      class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-indigo-100 text-indigo-700 hover:bg-indigo-600 hover:text-white transition-colors"
                      :title="notice.attachment_name || 'ডাউনলোড'"
                    >
                      <i class="fas fa-download"></i>
                    </button>
                    <span v-else class="text-slate-300 text-xs">—</span>
                  </td>
                </tr>
                <tr v-if="!paginatedNotices.length">
                  <td colspan="4" class="px-6 py-12 text-center text-slate-400 font-medium">
                    {{ noticeTitleFilter ? 'খুঁজে পাওয়া যায়নি' : 'কোনো নোটিশ নেই' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="filteredAllNotices.length > noticesPerPage" class="px-6 py-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 shrink-0 bg-slate-50/80">
            <p class="text-xs font-bold text-slate-500">
              দেখানো হচ্ছে {{ noticeSerialStart }}–{{ noticeRangeEnd }} / {{ filteredAllNotices.length }}
            </p>
            <div class="flex items-center gap-1">
              <button
                type="button"
                :disabled="noticePage <= 1"
                @click="noticePage--"
                class="px-3 py-2 rounded-xl text-xs font-black uppercase tracking-wider border border-slate-200 bg-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition-colors"
              >
                <i class="fas fa-chevron-left"></i>
              </button>
              <button
                v-for="page in noticeTotalPages"
                :key="'np-' + page"
                type="button"
                @click="noticePage = page"
                class="min-w-[2.25rem] px-3 py-2 rounded-xl text-xs font-black transition-colors"
                :class="noticePage === page ? 'bg-[var(--theme-primary)] text-white shadow-lg' : 'bg-white border border-slate-200 text-slate-600 hover:bg-indigo-50'"
              >
                {{ page }}
              </button>
              <button
                type="button"
                :disabled="noticePage >= noticeTotalPages"
                @click="noticePage++"
                class="px-3 py-2 rounded-xl text-xs font-black uppercase tracking-wider border border-slate-200 bg-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition-colors"
              >
                <i class="fas fa-chevron-right"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Single Notice Detail Modal -->
    <Teleport to="body">
      <div
        v-if="selectedNotice"
        class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/75 backdrop-blur-sm"
        @click.self="closeNoticeDetail"
      >
        <div class="bg-white rounded-[28px] shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden border border-slate-100">
          <div class="bg-[#1e1b4b] text-white px-6 py-5 flex items-start justify-between gap-4 shrink-0">
            <div class="min-w-0">
              <p class="text-indigo-300 text-xs font-bold uppercase tracking-widest mb-1">নোটিশ বিস্তারিত</p>
              <h3 class="text-xl font-black leading-snug">{{ selectedNotice.title }}</h3>
              <p v-if="selectedNotice.publish_at_label" class="text-indigo-200 text-sm mt-2">
                <i class="far fa-calendar-alt mr-1"></i> প্রকাশ: {{ selectedNotice.publish_at_label }}
              </p>
            </div>
            <button type="button" @click="closeNoticeDetail" class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center shrink-0">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="flex-grow overflow-y-auto custom-scrollbar px-6 py-6">
            <div
              class="prose prose-slate max-w-none text-slate-700 leading-relaxed whitespace-pre-wrap"
              v-html="formatNoticeBody(selectedNotice.body)"
            ></div>
          </div>
          <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex flex-wrap items-center justify-between gap-3 shrink-0">
            <button
              type="button"
              @click="closeNoticeDetail"
              class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-100 transition-colors"
            >
              বন্ধ করুন
            </button>
            <a
              v-if="selectedNotice.download_url"
              :href="selectedNotice.download_url"
              class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-black text-white bg-indigo-600 hover:brightness-90 shadow-lg transition-colors"
            >
              <i class="fas fa-download"></i>
              ডাউনলোড করুন
            </a>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Teacher Profile Modal -->
    <Teleport to="body">
      <div
        v-if="selectedTeacher"
        class="fixed inset-0 z-[120] flex items-center justify-center p-4 bg-slate-900/75 backdrop-blur-sm"
        @click.self="closeTeacherModal"
      >
        <div class="bg-white rounded-[32px] shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
          <div class="relative h-40 bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500">
            <button
              type="button"
              @click="closeTeacherModal"
              class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition-colors"
            >
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="px-8 pb-8 -mt-16 relative">
            <div class="mx-auto w-32 h-32 rounded-[28px] overflow-hidden border-4 border-white shadow-2xl bg-slate-100">
              <img v-if="selectedTeacher.photo" :src="selectedTeacher.photo" :alt="selectedTeacher.name" class="w-full h-full object-cover">
              <div v-else class="w-full h-full flex items-center justify-center text-4xl text-indigo-300"><i class="fas fa-user-tie"></i></div>
            </div>
            <div class="text-center mt-5">
              <h3 class="text-2xl font-black text-slate-800">{{ selectedTeacher.name }}</h3>
              <p class="text-[var(--theme-primary)] font-bold mt-1">{{ selectedTeacher.designation }}</p>
            </div>
            <div class="mt-6 space-y-3">
              <a
                v-if="selectedTeacher.phone"
                :href="'tel:' + selectedTeacher.phone"
                class="flex items-center gap-3 p-4 rounded-2xl bg-slate-50 hover:bg-indigo-50 transition-colors text-slate-700 font-bold"
              >
                <span class="w-10 h-10 rounded-xl bg-indigo-100 text-[var(--theme-primary)] flex items-center justify-center shrink-0"><i class="fas fa-phone-alt"></i></span>
                <span>{{ selectedTeacher.phone }}</span>
              </a>
              <a
                v-if="selectedTeacher.email"
                :href="'mailto:' + selectedTeacher.email"
                class="flex items-center gap-3 p-4 rounded-2xl bg-slate-50 hover:bg-purple-50 transition-colors text-slate-700 font-bold"
              >
                <span class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center shrink-0"><i class="fas fa-envelope"></i></span>
                <span class="break-all">{{ selectedTeacher.email }}</span>
              </a>
            </div>
            <button
              type="button"
              @click="closeTeacherModal"
              class="mt-6 w-full py-3 rounded-2xl text-sm font-black text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors"
            >
              বন্ধ করুন
            </button>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script>
export default {
  name: 'FrontendHome',
  props: {
    school: Object,
    settings: Object,
    storageBase: { type: String, default: '/storage' },
    boardNotices: { type: Array, default: () => [] },
    allBoardNotices: { type: Array, default: () => [] },
    marqueeNotices: { type: Array, default: () => [] },
    homepage: { type: Object, default: () => ({ mission: {}, vision: {}, achievements: [], facilities: [], gallery: [], blog_section: {} }) },
    headerMenu: { type: Array, default: () => [] },
    footerMenu: { type: Array, default: () => [] },
    teachers: { type: Array, default: () => [] },
    blogPosts: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      showNoticesModal: false,
      selectedNotice: null,
      selectedTeacher: null,
      activeMessagePerson: null,
      showAllTeachers: false,
      noticeTitleFilter: '',
      noticePage: 1,
      noticesPerPage: 10,
      activeSlide: 0,
      slideInterval: null,
      imageFailedMap: {},
      heroImageBroken: {},
      aboutImageBroken: false,
    };
  },
  computed: {
    schoolNameBn() { return this.school.name_bn || this.school.name || "বিদ্যালয়"; },
    statsCards() {
      const s = this.stats || {};
      const fmt = (n) => (n === null || n === undefined) ? '—' : Number(n).toLocaleString('bn-BD');
      const cards = [
        { label: 'শিক্ষার্থী', value: fmt(s.students), bg: 'bg-emerald-600 shadow-emerald-500/20' },
        { label: 'শিক্ষক', value: fmt(s.teachers), bg: 'bg-indigo-600 shadow-indigo-500/20' },
      ];
      if (s.staff) {
        cards.push({ label: 'কর্মচারী', value: fmt(s.staff), bg: 'bg-purple-600 shadow-purple-500/20' });
      } else {
        cards.push({ label: 'শ্রেণি', value: fmt(s.classes), bg: 'bg-purple-600 shadow-purple-500/20' });
      }
      cards.push({ label: 'অভিজ্ঞতা (বছর)', value: s.experience_years ? fmt(s.experience_years) : '—', bg: 'bg-rose-600 shadow-rose-500/20' });
      return cards;
    },
    messagePersons() {
      const list = [{
        key: 'principal',
        name: this.settings.principal_name,
        fallbackName: 'Principal Name',
        message: this.settings.principal_message,
        imagePath: this.settings.principal_image,
        icon: 'fas fa-user-tie',
        label: 'অধ্যক্ষের বাণী',
        fallbackQuote: '"শিক্ষাই জাতির মেরুদণ্ড। আদর্শ সুনাগরিক হিসেবে শিক্ষার্থীদের গড়ে তোলাই আমাদের মূল লক্ষ্য।"',
      }];
      if (this.settings.chairman_message || this.settings.chairman_name || this.settings.chairman_image) {
        list.push({
          key: 'chairman',
          name: this.settings.chairman_name,
          fallbackName: 'Chairman Name',
          message: this.settings.chairman_message,
          imagePath: this.settings.chairman_image,
          icon: 'fas fa-user-shield',
          label: 'সভাপতির বাণী',
          fallbackQuote: '',
        });
      }
      return list;
    },
    slideTitle() {
       return (this.currentSlide.title || '').trim()
         || (this.settings.hero_title || '').trim()
         || this.schoolNameBn;
    },
    slideSubtitle() {
       return (this.currentSlide.subtitle || '').trim();
    },
    hasSlideSubtitle() {
       return this.slideSubtitle.length > 0;
    },
    headerMenuItems() {
       return this.headerMenu?.length ? this.headerMenu : this.fallbackNavMenu;
    },
    footerMenuItems() {
       return this.footerMenu || [];
    },
    fallbackNavMenu() {
       return [
         { id: 'home', label: 'হোম', url: '/#home', target: '_self', children: [] },
         { id: 'about', label: 'পরিচিতি', url: '/#about', target: '_self', children: [] },
         { id: 'mission', label: 'মিশন', url: '/#mission', target: '_self', children: [] },
         { id: 'achievements', label: 'অর্জন', url: '/#achievements', target: '_self', children: [] },
         { id: 'faculty', label: 'শিক্ষক', url: '/#faculty', target: '_self', children: [] },
         { id: 'facilities', label: 'সুবিধা', url: '/#facilities', target: '_self', children: [] },
         { id: 'blog', label: 'ব্লগ', url: '/blog', target: '_self', children: [] },
         { id: 'gallery', label: 'গ্যালারি', url: '/#gallery', target: '_self', children: [] },
         { id: 'contact', label: 'যোগাযোগ', url: '/#contact', target: '_self', children: [] },
       ];
    },
    activeSlides() {
       let images = [];
       const settingsImages = this.settings.hero_images;
       
       if (Array.isArray(settingsImages)) {
          images = settingsImages;
       } else if (typeof settingsImages === 'string' && settingsImages.trim()) {
          try { 
             images = JSON.parse(settingsImages); 
          } catch(e) { 
             images = []; 
          }
       }
       
       // Fallback to singular hero_image if slider is empty
       if ((!Array.isArray(images) || images.length === 0) && this.settings.hero_image) {
          images = [{ image: this.settings.hero_image, active: true }];
       }

       if (!Array.isArray(images)) images = [];
       
       // Map to standard object format and filter active
       return images
         .filter(i => i && (i.active === true || i.active === undefined))
         .map(i => {
            if (typeof i === 'string') {
              return { image: this.resolveImageUrl(i), active: true, title: '', subtitle: '' };
            }
            return {
               image: i.image ? this.resolveImageUrl(i.image) : null,
               title: i.title || '',
               subtitle: i.subtitle || '',
               active: i.active !== false
            };
         });
    },
    heroImageSrc() {
       if (this.heroImageBroken[this.activeSlide]) {
          return null;
       }
       return this.currentSlide.image || null;
    },
    aboutImageSrc() {
       if (this.aboutImageBroken || !this.settings.about_image) {
          return null;
       }
       return this.resolveImageUrl(this.settings.about_image);
    },
    currentSlide() {
       const slides = this.activeSlides;
       if (slides.length > 0) {
          return slides[this.activeSlide % slides.length];
       }
       return { image: null, title: '', subtitle: '' };
    },
    filteredAllNotices() {
       const query = (this.noticeTitleFilter || '').trim().toLowerCase();
       const source = this.allBoardNotices.length ? this.allBoardNotices : this.boardNotices;
       if (!query) {
          return source;
       }
       return source.filter(n => (n.title || '').toLowerCase().includes(query));
    },
    noticeTotalPages() {
       return Math.max(1, Math.ceil(this.filteredAllNotices.length / this.noticesPerPage));
    },
    paginatedNotices() {
       const start = (this.noticePage - 1) * this.noticesPerPage;
       return this.filteredAllNotices.slice(start, start + this.noticesPerPage);
    },
    noticeSerialStart() {
       return (this.noticePage - 1) * this.noticesPerPage + 1;
    },
    noticeRangeEnd() {
       if (!this.paginatedNotices.length) {
          return 0;
       }
       return Math.min(
          this.noticeSerialStart + this.paginatedNotices.length - 1,
          this.filteredAllNotices.length
       );
    },
    galleryImages() {
       return this.homepage?.gallery || [];
    },
    blogSectionTitle() {
       return this.homepage?.blog_section?.title || 'ব্লগ ও সংবাদ';
    },
    blogSectionSubtitle() {
       return (this.homepage?.blog_section?.subtitle || '').trim();
    },
    displayedTeachers() {
       if (this.showAllTeachers || this.teachers.length <= 12) {
          return this.teachers;
       }
       return this.teachers.slice(0, 12);
    },
  },
  watch: {
    noticeTitleFilter() {
       this.noticePage = 1;
    },
    filteredAllNotices() {
       if (this.noticePage > this.noticeTotalPages) {
          this.noticePage = this.noticeTotalPages;
       }
    },
  },
  mounted() {
    if (window.AOS) window.AOS.init();
    this.startSlider();
  },
  beforeUnmount() { clearInterval(this.slideInterval); },
  methods: {
    resolveImageUrl(path) {
      if (!path) return null;
      if (path.startsWith('http://') || path.startsWith('https://')) return path;
      const base = (this.storageBase || '/storage').replace(/\/$/, '');
      const clean = String(path).replace(/^\/+/, '').replace(/^\/?storage\//, '');
      return `${base}/${clean}`;
    },
    storageUrl(path) {
      return this.resolveImageUrl(path) || '';
    },
    personImageUrl(person) {
      if (!person.imagePath || this.imageFailedMap[person.key]) return null;
      return this.storageUrl(person.imagePath);
    },
    markImageFailed(key) {
      this.imageFailedMap = { ...this.imageFailedMap, [key]: true };
    },
    onHeroImageError() {
       this.heroImageBroken = { ...this.heroImageBroken, [this.activeSlide]: true };
    },
    startSlider() { 
       if (this.activeSlides.length > 1) {
          this.slideInterval = setInterval(() => this.nextSlide(), 6000); 
       }
    },
    nextSlide() { 
       if (this.activeSlides.length > 0) {
          this.activeSlide = (this.activeSlide + 1) % this.activeSlides.length; 
       }
    },
    prevSlide() { 
       if (this.activeSlides.length > 0) {
          this.activeSlide = (this.activeSlide - 1 + this.activeSlides.length) % this.activeSlides.length; 
       }
    },
    openNoticesModal() {
       this.noticeTitleFilter = '';
       this.noticePage = 1;
       this.showNoticesModal = true;
       document.body.style.overflow = 'hidden';
    },
    closeNoticesModal() {
       this.showNoticesModal = false;
       if (!this.selectedNotice && !this.selectedTeacher && !this.activeMessagePerson) {
          document.body.style.overflow = '';
       }
    },
    openMessageModal(person) {
       this.activeMessagePerson = person;
       document.body.style.overflow = 'hidden';
    },
    closeMessageModal() {
       this.activeMessagePerson = null;
       if (!this.showNoticesModal && !this.selectedNotice && !this.selectedTeacher) {
          document.body.style.overflow = '';
       }
    },
    openNoticeDetail(notice) {
       if (!notice) {
          return;
       }
       this.selectedNotice = notice;
       document.body.style.overflow = 'hidden';
    },
    closeNoticeDetail() {
       this.selectedNotice = null;
       if (!this.showNoticesModal && !this.selectedTeacher) {
          document.body.style.overflow = '';
       }
    },
    formatNoticeBody(body) {
       if (!body) {
          return '<p class="text-slate-400 italic">বিস্তারিত বিবরণ নেই।</p>';
       }
       const escaped = String(body)
         .replace(/&/g, '&amp;')
         .replace(/</g, '&lt;')
         .replace(/>/g, '&gt;')
         .replace(/"/g, '&quot;');
       return escaped.replace(/\n/g, '<br>');
    },
    downloadNotice(notice) {
       if (!notice?.download_url) {
          return;
       }
       window.location.href = notice.download_url;
    },
    openTeacherModal(teacher) {
       if (!teacher) {
          return;
       }
       this.selectedTeacher = teacher;
       document.body.style.overflow = 'hidden';
    },
    closeTeacherModal() {
       this.selectedTeacher = null;
       if (!this.showNoticesModal && !this.selectedNotice) {
          document.body.style.overflow = '';
       }
    },
  }
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.2); border-radius: 10px; }
.fade-enter-active, .fade-leave-active { transition: opacity 1s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-up-enter-active { transition: opacity 0.8s ease, transform 0.8s ease; }
.slide-up-enter-from { opacity: 0; transform: translateY(30px); }
@keyframes pulse-slow {
  0%, 100% { opacity: 0.4; transform: scale(1); }
  50% { opacity: 0.7; transform: scale(1.05); }
}
.animate-pulse-slow { animation: pulse-slow 6s ease-in-out infinite; }
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.message-clamp {
  display: -webkit-box;
  -webkit-line-clamp: 5;
  line-clamp: 5;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
