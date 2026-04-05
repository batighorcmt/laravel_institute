<template>
  <div class="min-h-screen bg-[#f3f8fb] text-[#1e2a32] font-sans overflow-x-hidden relative">

    <!-- Top Header & Contact Info -->
    <header class="bg-white border-b-4 border-indigo-600 shadow-md relative z-40">
      <div class="h-2 w-full bg-gradient-to-r from-green-500 via-indigo-500 to-purple-600"></div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex flex-col md:flex-row justify-between items-center gap-6">
          
          <div class="flex items-center gap-5">
            <div class="relative group">
              <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl blur opacity-25 group-hover:opacity-75 transition duration-500"></div>
              <img v-if="school.logo" :src="'/storage/' + school.logo" alt="Logo" class="relative h-20 w-auto rounded-xl p-1 bg-white border border-gray-100 shadow-lg object-contain" />
              <div v-else class="relative h-20 w-20 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-black text-4xl shadow-lg ring-2 ring-white">
                {{ schoolNameBn.charAt(0) }}
              </div>
            </div>
            <div>
              <h1 class="text-3xl font-extrabold text-[#111827] leading-tight mb-1 bg-clip-text text-transparent bg-gradient-to-r from-indigo-800 to-purple-800">{{ schoolNameBn }}</h1>
              <h2 class="text-lg font-bold tracking-wide text-gray-500 uppercase">{{ school.name || 'School Name' }}</h2>
              <div class="flex flex-wrap items-center gap-2 mt-2 text-sm font-semibold text-gray-500">
                <span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-md border border-indigo-100">EIIN: {{ school.eiin || 'N/A' }}</span>
                <span v-if="school.founding_year" class="bg-purple-50 text-purple-700 px-2 py-0.5 rounded-md border border-purple-100">স্থাপিত: {{ school.founding_year }}</span>
              </div>
            </div>
          </div>
          
          <div class="flex flex-col items-center md:items-end gap-2 text-gray-700 font-semibold relative z-10 w-full md:w-auto">
            <a v-if="school.phone" :href="'tel:'+school.phone" class="flex items-center gap-2 bg-white px-4 py-2 rounded-full shadow border border-gray-100 hover:shadow-md hover:text-indigo-600 transition-all group w-full justify-center md:w-auto">
              <span class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                <i class="fas fa-phone-alt text-indigo-600"></i>
              </span>
              {{ school.phone }}
            </a>
            <a v-if="school.email" :href="'mailto:'+school.email" class="flex items-center gap-2 bg-white px-4 py-2 rounded-full shadow border border-gray-100 hover:shadow-md hover:text-purple-600 transition-all group w-full justify-center md:w-auto">
              <span class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                <i class="fas fa-envelope text-purple-600"></i>
              </span>
              {{ school.email }}
            </a>
          </div>
        </div>
      </div>
    </header>

    <!-- Navigation Bar -->
    <nav class="sticky top-0 z-50 bg-[#0a2f3b]/95 backdrop-blur-md border-b border-indigo-500/20 shadow-2xl">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between md:hidden py-4 border-b border-white/10">
          <span class="text-white font-bold tracking-wider flex items-center gap-2"><i class="fas fa-bars text-indigo-400"></i> মেনু</span>
          <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="text-white hover:text-indigo-400 transition transform hover:scale-110">
            <i class="fas fa-ellipsis-v fa-lg"></i>
          </button>
        </div>
        
        <ul :class="{'hidden': !isMobileMenuOpen, 'flex': isMobileMenuOpen}" class="md:flex flex-col md:flex-row w-full justify-center lg:justify-between py-2 md:py-0">
          <li class="flex-1 text-center group">
            <a href="#home" class="block text-gray-100 py-3 md:py-4 text-sm font-bold uppercase tracking-wider relative overflow-hidden group-hover:text-white transition-colors duration-300">
              <span class="relative z-10"><i class="fas fa-home opacity-50 mr-1"></i> হোম</span>
              <span class="absolute bottom-0 left-0 w-full h-1 bg-indigo-500 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left duration-300"></span>
            </a>
          </li>
          <li class="flex-1 text-center group relative">
            <a href="#about" class="block text-gray-100 py-3 md:py-4 text-sm font-bold uppercase tracking-wider relative overflow-hidden group-hover:text-white transition-colors duration-300">
              <span class="relative z-10"><i class="fas fa-info-circle opacity-50 mr-1"></i> পরিচিতি <i class="fas fa-angle-down text-[10px] ml-1"></i></span>
              <span class="absolute bottom-0 left-0 w-full h-1 bg-green-500 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left duration-300"></span>
            </a>
            <div class="md:absolute top-full left-1/2 md:-translate-x-1/2 w-full md:w-64 bg-[#0a2f3b] border border-white/10 shadow-2xl md:rounded-b-2xl overflow-hidden hidden group-hover:block transition-all duration-300 transform origin-top">
              <a href="#about" class="block text-left text-gray-200 py-3 px-6 hover:bg-green-500 hover:text-white hover:pl-8 transition-all duration-300 border-b border-white/5 font-semibold text-sm">
                <i class="fas fa-chevron-right text-xs opacity-50 mr-2"></i> প্রতিষ্ঠান পরিচিতি
              </a>
              <a href="#mission" class="block text-left text-gray-200 py-3 px-6 hover:bg-green-500 hover:text-white hover:pl-8 transition-all duration-300 font-semibold text-sm">
                <i class="fas fa-chevron-right text-xs opacity-50 mr-2"></i> মিশন ও ভিশন
              </a>
            </div>
          </li>
          <li class="flex-1 text-center group relative">
            <a href="#teachers" class="block text-gray-100 py-3 md:py-4 text-sm font-bold uppercase tracking-wider relative overflow-hidden group-hover:text-white transition-colors duration-300">
              <span class="relative z-10"><i class="fas fa-users-cog opacity-50 mr-1"></i> প্রশাসন <i class="fas fa-angle-down text-[10px] ml-1"></i></span>
              <span class="absolute bottom-0 left-0 w-full h-1 bg-purple-500 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left duration-300"></span>
            </a>
            <div class="md:absolute top-full left-1/2 md:-translate-x-1/2 w-full md:w-64 bg-[#0a2f3b] border border-white/10 shadow-2xl md:rounded-b-2xl overflow-hidden hidden group-hover:block transition-all duration-300 transform origin-top">
              <a href="#teachers" class="block text-left text-gray-200 py-3 px-6 hover:bg-purple-500 hover:text-white hover:pl-8 transition-all duration-300 border-b border-white/5 font-semibold text-sm">
                <i class="fas fa-chevron-right text-xs opacity-50 mr-2"></i> শিক্ষকবৃন্দ
              </a>
              <a href="#" class="block text-left text-gray-200 py-3 px-6 hover:bg-purple-500 hover:text-white hover:pl-8 transition-all duration-300 font-semibold text-sm">
                <i class="fas fa-chevron-right text-xs opacity-50 mr-2"></i> ম্যানেজিং কমিটি
              </a>
            </div>
          </li>
          <li class="flex-1 text-center group">
            <a href="#facilities" class="block text-gray-100 py-3 md:py-4 text-sm font-bold uppercase tracking-wider relative overflow-hidden group-hover:text-white transition-colors duration-300">
              <span class="relative z-10"><i class="fas fa-building opacity-50 mr-1"></i> সুবিধা</span>
              <span class="absolute bottom-0 left-0 w-full h-1 bg-blue-500 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left duration-300"></span>
            </a>
          </li>
          <li class="flex-1 text-center group">
            <a href="#blog" class="block text-gray-100 py-3 md:py-4 text-sm font-bold uppercase tracking-wider relative overflow-hidden group-hover:text-white transition-colors duration-300">
              <span class="relative z-10"><i class="fas fa-blog opacity-50 mr-1"></i> ব্লগ</span>
              <span class="absolute bottom-0 left-0 w-full h-1 bg-pink-500 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left duration-300"></span>
            </a>
          </li>
          <li class="flex-1 text-center group">
            <a href="#blog" class="block text-gray-100 py-3 md:py-4 text-sm font-bold uppercase tracking-wider relative overflow-hidden group-hover:text-white transition-colors duration-300">
              <span class="relative z-10"><i class="fas fa-image opacity-50 mr-1"></i> গ্যালারী</span>
              <span class="absolute bottom-0 left-0 w-full h-1 bg-yellow-500 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left duration-300"></span>
            </a>
          </li>
          <li class="flex-1 md:ml-4 flex items-center justify-center p-2 mt-2 md:mt-0">
            <a :href="'/admission/' + school.code" class="relative group inline-flex items-center justify-center px-6 py-2.5 w-full md:w-auto text-sm font-bold text-white uppercase tracking-wider rounded-full shadow-lg shadow-green-500/30 overflow-hidden transform transition hover:scale-105 active:scale-95">
              <span class="absolute inset-0 bg-gradient-to-r from-green-500 via-[#008037] to-green-600"></span>
              <span class="absolute inset-0 bg-gradient-to-r from-purple-500 via-indigo-500 to-blue-500 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></span>
              <span class="relative flex items-center gap-2"><i class="fas fa-user-plus animate-bounce-slow"></i> ভর্তি চলছে</span>
            </a>
          </li>
        </ul>
      </div>
    </nav>

    <!-- Notice Marquee with Gradient Background -->
    <div class="bg-gradient-to-r from-indigo-900 via-[#1e293b] to-purple-900 border-b border-indigo-500/30 text-white py-2 overflow-hidden shadow-inner flex items-center relative z-20">
      <div class="max-w-7xl mx-auto px-4 w-full flex">
        <div class="bg-gradient-to-r from-pink-500 to-rose-500 text-white text-xs font-black px-4 py-1.5 rounded shadow-[0_0_15px_rgba(244,63,94,0.5)] z-10 whitespace-nowrap mr-4 flex-shrink-0 flex items-center uppercase tracking-widest border border-pink-400/50">
          <i class="fas fa-bolt mr-2 animate-pulse text-yellow-300"></i> আপডেট
        </div>
        <div class="overflow-hidden relative w-full flex items-center">
          <div class="whitespace-nowrap animate-marquee flex gap-12 font-semibold">
            <template v-if="settings.marquee_text">
              <a href="#" class="text-indigo-100 hover:text-yellow-400 transition-colors inline-block hover:scale-105 transform">
                <i class="fas fa-bullseye text-pink-400 mr-2"></i> {{ settings.marquee_text }}
              </a>
            </template>
            <template v-else>
              <a href="#" class="text-indigo-100 hover:text-yellow-400 transition-colors inline-block hover:scale-105 transform"><i class="fas fa-bullseye text-pink-400 mr-2"></i> অনলাইন ভর্তি কার্যক্রম শুরু হয়েছে</a>
              <a href="#" class="text-indigo-100 hover:text-yellow-400 transition-colors inline-block hover:scale-105 transform"><i class="fas fa-bullseye text-pink-400 mr-2"></i> আগামী মাসের ৫ তারিখ অভিভাবক মিটিং অনুষ্ঠিত হবে</a>
              <a href="#" class="text-indigo-100 hover:text-yellow-400 transition-colors inline-block hover:scale-105 transform"><i class="fas fa-bullseye text-pink-400 mr-2"></i> ৬ষ্ঠ শ্রেণির ভর্তি ফরম বিতরণ চলছে</a>
              <a href="#" class="text-indigo-100 hover:text-yellow-400 transition-colors inline-block hover:scale-105 transform"><i class="fas fa-bullseye text-pink-400 mr-2"></i> বার্ষিক ক্রীড়া প্রতিযোগিতা আগামী ২০ তারিখে</a>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Enhanced Hero Section -->
    <section id="home" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-10 mb-20 relative">
      <!-- Background decorators -->
      <div class="absolute top-0 right-0 w-96 h-96 bg-purple-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
      <div class="absolute bottom-0 left-0 w-96 h-96 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>

      <div class="flex flex-col lg:flex-row gap-8 relative z-10">
        
        <!-- Animated Hero Image Slider Area -->
        <div class="lg:w-7/12 xl:w-2/3" data-aos="zoom-in-right" data-aos-duration="1000">
          <div class="bg-white rounded-[40px] overflow-hidden shadow-2xl relative h-[400px] sm:h-[480px] group ring-4 ring-white border-[6px] border-indigo-50/50">
            <img :src="settings.hero_image ? '/storage/' + settings.hero_image : 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80'" alt="Campus Banner" class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110" />
            
            <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/40 to-transparent mix-blend-multiply"></div>
            
            <!-- Animated Content Overlay -->
            <div class="absolute bottom-0 left-0 w-full p-8 md:p-12 text-white transform transition-transform duration-700">
              <span class="bg-white/20 backdrop-blur-md border border-white/50 text-white text-xs font-black px-4 py-1.5 rounded-full mb-4 inline-flex items-center shadow-lg uppercase tracking-wider">
                <i class="fas fa-star text-yellow-300 mr-2 animate-spin-slow"></i> স্বাগতম জানাই
              </span>
              <h2 class="text-3xl md:text-5xl font-extrabold mb-3 leading-tight drop-shadow-2xl">
                {{ settings.hero_title || schoolNameBn }}
              </h2>
              <p class="text-gray-100 md:text-xl max-w-2xl font-medium drop-shadow-md border-l-4 border-indigo-400 pl-4 py-1">
                 {{ settings.hero_subtitle || 'ঐতিহ্য, আধুনিকতা ও প্রযুক্তির এক অনন্য মেলবন্ধন। শ্রেষ্ঠত্বের পথে আমাদের এই পথচলা অনন্তকাল...' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Glassmorphism Vertical Notice Board -->
        <div class="lg:w-5/12 xl:w-1/3 flex flex-col" data-aos="zoom-in-left" data-aos-duration="1000" data-aos-delay="200">
          <div class="bg-white/80 backdrop-blur-xl rounded-[40px] overflow-hidden shadow-2xl flex-1 flex flex-col border border-white/50 transition-all hover:shadow-[0_25px_50px_-12px_rgba(79,70,229,0.25)] hover:-translate-y-2 duration-500">
            
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-1">
              <div class="bg-[#111827]/90 text-white px-6 py-5 rounded-t-[36px] font-bold text-xl flex items-center justify-between shadow-inner">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center">
                    <i class="fas fa-bullhorn text-indigo-400 animate-pulse"></i>
                  </div>
                  নোটিশ বোর্ড
                </div>
                <span class="text-xs font-black bg-indigo-500 px-3 py-1.5 rounded-full shadow-inner">{{ notices.length }} টি</span>
              </div>
            </div>
            
            <div class="relative flex-1 bg-gradient-to-b from-white to-indigo-50/50 h-[350px] overflow-hidden group">
              <div class="absolute w-full flex flex-col h-full overflow-y-auto custom-scrollbar p-3">
                
                <div v-for="(notice, index) in notices" :key="index" 
                     class="p-4 bg-white mb-3 rounded-2xl shadow-sm border border-gray-100 last:mb-0 hover:shadow-md hover:bg-indigo-50 transition-all duration-300 group/item hover:scale-[1.02] cursor-pointer">
                  <div class="flex gap-4">
                    <div class="flex flex-col items-center justify-center bg-indigo-100 rounded-xl px-3 py-2 border border-indigo-200">
                       <span class="text-sm font-black text-indigo-800 leading-none">{{ notice.date.split(' ')[0] }}</span>
                       <span class="text-xs font-bold text-indigo-500">{{ notice.date.split(' ')[1] }}</span>
                    </div>
                    <div>
                      <a href="#" class="text-gray-800 font-bold text-sm block mb-1 leading-snug group-hover/item:text-indigo-700 transition-colors">{{ notice.title }}</a>
                      <a href="#" class="text-xs text-indigo-500 font-semibold flex items-center group-hover/item:underline">বিস্তারিত <i class="fas fa-long-arrow-alt-right ml-1"></i></a>
                    </div>
                  </div>
                </div>

              </div>
            </div>
            
            <div class="px-6 py-5 bg-white border-t border-gray-100 flex justify-center">
               <a href="/login" class="px-6 py-2.5 rounded-full bg-indigo-50 text-indigo-700 font-bold text-sm shadow-inner hover:bg-indigo-600 hover:text-white transition-all duration-300 flex items-center gap-2 group w-full justify-center">
                 সব নোটিশ দেখতে লগইন করুন <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
               </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Colorful Stats Cards -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-20 relative z-20 -mt-10">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
          <div class="bg-gradient-to-br from-green-500 to-emerald-700 p-6 rounded-3xl text-center shadow-xl text-white transform hover:-translate-y-2 transition-all duration-300 relative overflow-hidden group">
             <div class="absolute -right-4 -top-4 text-white/20 text-7xl"><i class="fas fa-user-graduate"></i></div>
             <div class="text-4xl font-black mb-1 drop-shadow-md">৮৫০+</div>
             <div class="font-bold opacity-90 uppercase tracking-widest text-xs">শিক্ষার্থী</div>
          </div>
          <div class="bg-gradient-to-br from-indigo-500 to-blue-700 p-6 rounded-3xl text-center shadow-xl text-white transform hover:-translate-y-2 transition-all duration-300 relative overflow-hidden group">
             <div class="absolute -right-4 -top-4 text-white/20 text-7xl"><i class="fas fa-chalkboard-teacher"></i></div>
             <div class="text-4xl font-black mb-1 drop-shadow-md">৩৫</div>
             <div class="font-bold opacity-90 uppercase tracking-widest text-xs">শিক্ষক</div>
          </div>
          <div class="bg-gradient-to-br from-purple-500 to-fuchsia-700 p-6 rounded-3xl text-center shadow-xl text-white transform hover:-translate-y-2 transition-all duration-300 relative overflow-hidden group">
             <div class="absolute -right-4 -top-4 text-white/20 text-7xl"><i class="fas fa-trophy"></i></div>
             <div class="text-4xl font-black mb-1 drop-shadow-md">৫ বার</div>
             <div class="font-bold opacity-90 uppercase tracking-widest text-xs">জেলা শ্রেষ্ঠ</div>
          </div>
          <div class="bg-gradient-to-br from-rose-500 to-red-700 p-6 rounded-3xl text-center shadow-xl text-white transform hover:-translate-y-2 transition-all duration-300 relative overflow-hidden group">
             <div class="absolute -right-4 -top-4 text-white/20 text-7xl"><i class="fas fa-medal"></i></div>
             <div class="text-4xl font-black mb-1 drop-shadow-md">১০০%</div>
             <div class="font-bold opacity-90 uppercase tracking-widest text-xs">পাসের হার</div>
          </div>
      </div>
    </div>

    <!-- About Institution with Parallax Feel -->
    <section id="about" class="bg-white py-20 border-y border-gray-100 overflow-hidden relative" data-aos="fade-up">
      <div class="absolute top-0 right-0 w-64 h-64 bg-green-50 rounded-bl-full opacity-50"></div>
      <div class="absolute bottom-0 left-0 w-64 h-64 bg-indigo-50 rounded-tr-full opacity-50"></div>
      
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="flex flex-col md:flex-row items-center gap-12">
          <div class="md:w-1/2 relative">
             <div class="absolute inset-0 bg-gradient-to-tr from-green-400 to-indigo-500 rounded-[40px] transform rotate-3 scale-105 opacity-20"></div>
             <img :src="settings.about_image ? '/storage/' + settings.about_image : 'https://images.unsplash.com/photo-1546410531-bea5aad13914?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'" alt="About School" class="relative rounded-[40px] shadow-2xl object-cover h-[400px] w-full border-4 border-white" />
             <div class="absolute -bottom-6 -right-6 bg-white p-4 rounded-3xl shadow-xl flex items-center gap-4 animate-bounce-slow border border-gray-100">
                <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center text-green-600 text-2xl">
                  <i class="fas fa-certificate"></i>
                </div>
                <div>
                  <h4 class="font-black text-gray-800 leading-none">প্রতিষ্ঠিত</h4>
                  <p class="text-sm font-bold text-gray-500">{{ school.founding_year || 'Long Ago' }}</p>
                </div>
             </div>
          </div>
          <div class="md:w-1/2">
             <span class="text-indigo-600 font-bold uppercase tracking-widest text-sm mb-2 block">— প্রতিষ্ঠান পরিচিতি</span>
             <h2 class="text-4xl font-black text-gray-900 mb-6 leading-tight">জ্ঞানের আলোয় আলোকিত <br><span class="text-transparent bg-clip-text bg-gradient-to-r from-green-600 to-indigo-600">সুন্দর ভবিষ্যৎ গড়ার প্রত্যয়</span></h2>
             
             <p v-if="settings.about_text" class="text-gray-600 leading-relaxed text-lg mb-8 whitespace-pre-line">{{ settings.about_text }}</p>
             <p v-else class="text-gray-600 leading-relaxed text-lg mb-8">
               <strong>{{ schoolNameBn }}</strong> ঐতিহ্যবাহী ও মানসম্মত শিক্ষার এক অনন্য প্রতিষ্ঠান। {{ school.founding_year ? school.founding_year + ' সালে' : 'প্রতিষ্ঠার পর থেকে' }} এটি একটি আধুনিক প্রগতিশীল শিক্ষাপ্রতিষ্ঠান হিসেবে দীর্ঘকাল ধরে তার শ্রেষ্ঠত্ব প্রমাণ করে আসছে। আমাদের উদ্দেশ্য হলো একুশ শতকের চ্যালেঞ্জ মোকাবিলায় শিক্ষার্থীদের তথ্য প্রযুক্তি জ্ঞান, নৈতিকতা ও মানবিক মূল্যবোধে বলীয়ান করে গড়ে তোলা।
             </p>
             <a href="#mission" class="inline-flex items-center justify-center px-8 py-4 text-base font-bold text-white uppercase tracking-wider rounded-full shadow-lg shadow-indigo-500/30 overflow-hidden transform transition hover:scale-105 bg-gradient-to-r from-indigo-600 to-purple-600">
                মিশন ও ভিশন জানুন <i class="fas fa-arrow-right ml-2"></i>
             </a>
          </div>
        </div>
      </div>
    </section>

    <!-- Administration/Principal Section -->
    <section v-if="settings.principal_message" id="administration" class="bg-[#fefcf5] py-24 border-y border-gray-100 overflow-hidden relative" data-aos="fade-up">
       <!-- Decorator -->
      <div class="absolute -left-10 top-0 w-40 h-40 bg-blue-100 rounded-full mix-blend-multiply filter blur-3xl opacity-50"></div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="flex flex-col md:flex-row items-center gap-16 lg:gap-24">
          <div class="md:w-5/12 relative">
             <div class="absolute inset-0 bg-blue-500 rounded-3xl transform rotate-3 scale-105 opacity-10"></div>
             <div class="relative overflow-hidden rounded-3xl shadow-2xl border-[10px] border-white ring-1 ring-gray-100 aspect-[4/5] max-w-sm mx-auto">
                <img v-if="settings.principal_image" :src="'/storage/' + settings.principal_image" alt="Principal" class="w-full h-full object-cover transition-transform duration-700 hover:scale-110" />
                <div v-else class="w-full h-full bg-blue-100 text-blue-400 flex items-center justify-center text-8xl">
                   <i class="fas fa-user-tie"></i>
                </div>
                <div class="absolute bottom-0 left-0 w-full bg-blue-600 p-4 text-white text-center">
                   <h4 class="font-black text-lg">{{ settings.principal_name || 'অধ্যক্ষ মহোদয়' }}</h4>
                   <p class="text-xs uppercase font-bold tracking-widest text-blue-200">অধ্যক্ষ, {{ school.name }}</p>
                </div>
             </div>
          </div>
          
          <div class="md:w-7/12">
             <div class="inline-flex items-center gap-3 px-4 py-1.5 bg-blue-50 text-blue-700 rounded-full text-sm font-black mb-6 border border-blue-100">
               <span class="relative flex h-2 w-2">
                 <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                 <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
               </span>
               অধ্যক্ষের বাণী
             </div>
             
             <h2 class="text-4xl md:text-5xl font-black text-gray-900 mb-8 leading-tight">ভবিষ্যৎ প্রজন্ম গড়ার <br><span class="text-blue-600">এক অনন্য মিশন</span></h2>
             
             <div class="relative">
                <i class="fas fa-quote-left text-5xl text-blue-100 absolute -top-6 -left-6"></i>
                <p class="text-gray-600 leading-relaxed text-xl mb-8 relative z-10 whitespace-pre-line italic">
                  {{ settings.principal_message }}
                </p>
             </div>
             
             <div class="flex items-center gap-4 mt-10">
                <div class="w-16 h-1 bg-blue-600 rounded-full"></div>
                <span class="font-bold text-gray-800 text-lg">{{ settings.principal_name }}</span>
             </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Committee Section -->
    <section v-if="settings.committee_text" id="committee" class="py-24 bg-white relative overflow-hidden" data-aos="fade-up">
      <div class="absolute -right-20 bottom-0 w-96 h-96 bg-indigo-50 rounded-full mix-blend-multiply filter blur-3xl opacity-50"></div>
      
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <div class="inline-flex items-center px-4 py-1.5 bg-indigo-50 text-indigo-700 rounded-full text-sm font-black mb-6 border border-indigo-100 tracking-widest uppercase">
          পরিচালনা পর্ষদ
        </div>
        <h2 class="text-4xl md:text-5xl font-black text-gray-900 mb-12">স্কুল ম্যানেজিং কমিটি</h2>
        
        <div class="max-w-4xl mx-auto">
          <div class="bg-gradient-to-br from-white to-indigo-50 p-10 md:p-16 rounded-[40px] shadow-2xl border border-white text-left relative group">
            <div class="absolute top-0 right-0 p-8 text-indigo-100 text-8xl opacity-30 group-hover:opacity-50 transition-opacity">
               <i class="fas fa-users-cog"></i>
            </div>
            <div class="prose prose-indigo prose-xl max-w-none text-gray-700 whitespace-pre-line leading-relaxed font-medium relative z-10">
              {{ settings.committee_text }}
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- School Facilities (Animated Grid) -->
    <section id="facilities" class="py-24 bg-[#f3f8fb]" data-aos="fade-up">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <span class="text-indigo-600 font-bold uppercase tracking-widest text-sm mb-2 block">— আধুনিক সুবিধা</span>
          <h2 class="text-4xl md:text-5xl font-black text-gray-900 mb-4">আমাদের স্কুল সুবিধাসমূহ</h2>
          <div class="w-32 h-2 bg-gradient-to-r from-green-500 to-indigo-600 mx-auto rounded-full"></div>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
          <div v-for="(facility, index) in facilities" :key="index" 
               class="bg-white rounded-[32px] p-8 text-center shadow-[0_10px_30px_-15px_rgba(0,0,0,0.1)] hover:shadow-[0_20px_40px_-15px_rgba(79,70,229,0.2)] hover:-translate-y-3 transition-all duration-500 group border border-transparent hover:border-indigo-100">
            <div :class="facility.colorClass" class="w-20 h-20 mx-auto rounded-full flex items-center justify-center mb-6 transition-transform duration-500 group-hover:rotate-12 group-hover:scale-110 shadow-inner border-[6px] border-white ring-1 ring-gray-100">
              <i :class="facility.icon" class="text-3xl"></i>
            </div>
            <h6 class="font-extrabold text-gray-800 text-lg group-hover:text-indigo-600 transition-colors">{{ facility.name }}</h6>
          </div>
        </div>
      </div>
    </section>

    <!-- Detailed Blog / Recent Events Grid -->
    <section id="blog" class="py-24 bg-white border-t border-gray-100 relative" data-aos="fade-up">
        <!-- Decorator -->
        <div class="absolute top-1/2 left-0 w-32 h-32 bg-yellow-200 rounded-full mix-blend-multiply filter blur-3xl opacity-50"></div>
        <div class="absolute top-1/2 right-0 w-32 h-32 bg-pink-200 rounded-full mix-blend-multiply filter blur-3xl opacity-50"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-16">
              <span class="text-pink-500 font-bold uppercase tracking-widest text-sm mb-2 block">— সংবাদ ও ব্লগ</span>
              <h2 class="text-4xl md:text-5xl font-black text-gray-900 mb-4">সাম্প্রতিক আয়োজন ও ঘটনাবলী</h2>
              <div class="w-32 h-2 bg-gradient-to-r from-pink-500 to-rose-500 mx-auto rounded-full"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Blog Post 1 -->
                <div class="bg-white rounded-3xl overflow-hidden shadow-xl border border-gray-100 flex flex-col group hover:-translate-y-2 transition-all duration-500 cursor-pointer">
                    <div class="relative h-56 overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1577896851231-70ef18881754?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Blog 1" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700">
                        <div class="absolute top-4 left-4 bg-white/90 backdrop-blur-md px-3 py-1 rounded-full text-xs font-bold text-indigo-700 shadow-sm flex items-center">
                            <i class="far fa-calendar-alt mr-1"></i> ১০ ফেব্রুয়ারি, ২০২৬
                        </div>
                    </div>
                    <div class="p-6 flex flex-col flex-1">
                        <span class="text-xs font-black uppercase text-pink-500 mb-2 tracking-wider">ইভেন্ট</span>
                        <h4 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-indigo-600 transition-colors">বার্ষিক ক্রীড়া প্রতিযোগিতা ও পুরস্কার বিতরণী</h4>
                        <p class="text-gray-600 text-sm flex-1 leading-relaxed">উৎসবমুখর পরিবেশে বিদ্যালয়ের মাঠে বার্ষিক ক্রীড়া প্রতিযোগিতা অনুষ্ঠিত, যেখানে শিক্ষার্থীরা ১৫টি ইভেন্টে স্বতঃস্ফূর্তভাবে অংশ নেয়...</p>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <a href="#" class="text-indigo-600 font-bold uppercase text-sm flex items-center group-hover:text-indigo-800 transition-colors">
                                আরও পড়ুন <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-2 transition-transform"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Blog Post 2 -->
                <div class="bg-white rounded-3xl overflow-hidden shadow-xl border border-gray-100 flex flex-col group hover:-translate-y-2 transition-all duration-500 cursor-pointer">
                    <div class="relative h-56 overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1532094349884-543bc11b234d?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Blog 2" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700">
                        <div class="absolute top-4 left-4 bg-white/90 backdrop-blur-md px-3 py-1 rounded-full text-xs font-bold text-indigo-700 shadow-sm flex items-center">
                            <i class="far fa-calendar-alt mr-1"></i> ২৫ জানুয়ারি, ২০২৬
                        </div>
                    </div>
                    <div class="p-6 flex flex-col flex-1">
                        <span class="text-xs font-black uppercase text-green-500 mb-2 tracking-wider">অর্জন</span>
                        <h4 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-green-600 transition-colors">জেলা বিজ্ঞান মেলায় আমাদের বিদ্যালয়ের শ্রেষ্ঠত্ব</h4>
                        <p class="text-gray-600 text-sm flex-1 leading-relaxed">আমাদের বিজ্ঞান ক্লাবের ‘স্মার্ট এগ্রিকালচার’ প্রকল্প জেলা পর্যায়ে প্রথম স্থান অর্জন করেছে। শিক্ষার্থীদের এই অভাবনীয় সাফল্যে...</p>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <a href="#" class="text-green-600 font-bold uppercase text-sm flex items-center group-hover:text-green-800 transition-colors">
                                আরও পড়ুন <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-2 transition-transform"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Blog Post 3 -->
                <div class="bg-white rounded-3xl overflow-hidden shadow-xl border border-gray-100 flex flex-col group hover:-translate-y-2 transition-all duration-500 cursor-pointer">
                    <div class="relative h-56 overflow-hidden">
                        <img src="https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Blog 3" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700">
                        <div class="absolute top-4 left-4 bg-white/90 backdrop-blur-md px-3 py-1 rounded-full text-xs font-bold text-indigo-700 shadow-sm flex items-center">
                            <i class="far fa-calendar-alt mr-1"></i> ৫ ডিসেম্বর, ২০২৫
                        </div>
                    </div>
                    <div class="p-6 flex flex-col flex-1">
                        <span class="text-xs font-black uppercase text-blue-500 mb-2 tracking-wider">শিক্ষা</span>
                        <h4 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-blue-600 transition-colors">পাঠাগার সপ্তাহ ও আন্তঃশ্রেণী বিতর্ক প্রতিযোগিতা</h4>
                        <p class="text-gray-600 text-sm flex-1 leading-relaxed">শিক্ষার্থীদের বই পড়ায় আগ্রহী করার লক্ষ্যে গ্রন্থাগার সপ্তাহ এবং যুক্তিবাদী মনন গড়ে তোলার জন্য বিতর্ক প্রতিযোগিতা...</p>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <a href="#" class="text-blue-600 font-bold uppercase text-sm flex items-center group-hover:text-blue-800 transition-colors">
                                আরও পড়ুন <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-2 transition-transform"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-12 text-center">
                <a href="#" class="inline-flex items-center px-8 py-3 rounded-full text-indigo-600 border-2 border-indigo-600 font-bold hover:bg-indigo-600 hover:text-white transition-all duration-300 uppercase tracking-widest text-sm">
                   সব ব্লগ পোস্ট দেখুন
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="bg-gradient-to-b from-[#0a2f3b] to-gray-900 text-[#e2e8f0] relative pt-20 pb-8 border-t-[8px] border-indigo-600 overflow-hidden">
      <!-- Decorator rings -->
      <div class="absolute -right-40 -top-40 w-96 h-96 rounded-full border-[20px] border-white/5 pointer-events-none"></div>
      
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-12 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-12 lg:gap-16">
          
          <div class="md:col-span-2">
            <h4 class="text-3xl font-extrabold text-white mb-2 flex items-center gap-4">
              <img v-if="school.logo" :src="'/storage/' + school.logo" alt="Logo" class="h-14 w-auto bg-white rounded-xl p-1 shadow-md" />
              <div v-else class="h-12 w-12 rounded-lg bg-indigo-500 flex items-center justify-center text-white"><i class="fas fa-school"></i></div>
              <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-300 to-green-300">{{ schoolNameBn }}</span>
            </h4>
            <p class="text-sm uppercase tracking-widest text-indigo-300 font-bold mb-8">{{ school.name || 'Your School' }}</p>
            
            <div class="space-y-4">
               <div class="flex items-start gap-4 p-4 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors">
                  <div class="w-10 h-10 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center shrink-0">
                     <i class="fas fa-map-marker-alt"></i>
                  </div>
                  <div>
                     <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-1">ঠিকানা</p>
                     <p class="text-gray-300 font-medium">{{ settings.contact_address || school.address_bn || school.address || 'ঠিকানা সেট করা হয়নি' }}</p>
                  </div>
               </div>
               
               <div class="flex flex-col sm:flex-row gap-4">
                   <div class="flex items-start gap-4 p-4 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors flex-1">
                      <div class="w-10 h-10 rounded-full bg-green-500/20 text-green-400 flex items-center justify-center shrink-0">
                         <i class="fas fa-phone-alt"></i>
                      </div>
                      <div>
                         <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-1">ফোন</p>
                         <p class="text-gray-300 font-medium">{{ settings.contact_phone || school.phone || 'N/A' }}</p>
                      </div>
                   </div>
                   
                   <div class="flex items-start gap-4 p-4 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors flex-1">
                      <div class="w-10 h-10 rounded-full bg-pink-500/20 text-pink-400 flex items-center justify-center shrink-0">
                         <i class="fas fa-envelope"></i>
                      </div>
                      <div>
                         <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-1">ইমেইল</p>
                         <p class="text-gray-300 font-medium text-sm break-all">{{ settings.contact_email || school.email || 'N/A' }}</p>
                      </div>
                   </div>
               </div>
            </div>
          </div>
          
          <div>
            <h5 class="text-xl font-bold text-white mb-6 uppercase tracking-wider relative inline-block after:content-[''] after:absolute after:-bottom-3 after:left-0 after:w-12 after:h-1 after:bg-indigo-500 after:rounded">দ্রুত লিংক</h5>
            <ul class="space-y-4 font-medium mt-4">
              <li><a href="#about" class="text-gray-400 hover:text-white hover:pl-3 transition-all duration-300 flex items-center"><i class="fas fa-angle-right text-indigo-500 mr-2"></i> প্রতিষ্ঠান পরিচিতি</a></li>
              <li><a href="#teachers" class="text-gray-400 hover:text-white hover:pl-3 transition-all duration-300 flex items-center"><i class="fas fa-angle-right text-indigo-500 mr-2"></i> শিক্ষক ও কর্মচারী</a></li>
              <li><a href="#facilities" class="text-gray-400 hover:text-white hover:pl-3 transition-all duration-300 flex items-center"><i class="fas fa-angle-right text-indigo-500 mr-2"></i> স্কুল সুবিধা</a></li>
              <li><a :href="'/admission/' + school.code" class="text-green-400 hover:text-green-300 hover:pl-3 transition-all duration-300 font-bold flex items-center"><i class="fas fa-angle-right text-green-500 mr-2"></i> অনলাইন ভর্তি</a></li>
            </ul>
          </div>
          
          <div>
            <h5 class="text-xl font-bold text-white mb-6 uppercase tracking-wider relative inline-block after:content-[''] after:absolute after:-bottom-3 after:left-0 after:w-12 after:h-1 after:bg-purple-500 after:rounded">সোশ্যাল মিডিয়া</h5>
            <div class="flex gap-4 mb-10 mt-4">
              <a v-if="settings.facebook_url" :href="settings.facebook_url" target="_blank" class="w-12 h-12 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-white hover:bg-[#1877F2] hover:border-transparent hover:-translate-y-2 transition-all duration-300 text-xl shadow-lg">
                <i class="fab fa-facebook-f"></i>
              </a>
              <a v-if="settings.youtube_url" :href="settings.youtube_url" target="_blank" class="w-12 h-12 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-white hover:bg-[#FF0000] hover:border-transparent hover:-translate-y-2 transition-all duration-300 text-xl shadow-lg">
                <i class="fab fa-youtube"></i>
              </a>
              <a href="#" v-if="!settings.facebook_url && !settings.youtube_url" class="w-12 h-12 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-white hover:bg-gradient-to-tr hover:from-orange-500 hover:via-pink-500 hover:to-purple-500 hover:border-transparent hover:-translate-y-2 transition-all duration-300 text-xl shadow-lg">
                <i class="fab fa-instagram"></i>
              </a>
            </div>
            
            <div class="bg-gradient-to-br from-indigo-900/50 to-purple-900/50 p-5 rounded-2xl border border-indigo-500/20 text-center shadow-inner">
               <p class="text-xs text-indigo-300 uppercase font-black tracking-widest mb-2">ইনস্টিটিউট কোড (EIIN)</p>
               <p class="text-3xl font-black text-white mix-blend-screen tracking-wider">{{ school.eiin || 'N/A' }}</p>
            </div>
          </div>
          
        </div>
      </div>
      
      <div class="border-t border-white/10 pt-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center text-sm text-gray-400 font-medium relative z-10">
        <p>&copy; {{ new Date().getFullYear() }} {{ schoolNameBn }}। সর্বস্বত্ব সংরক্ষিত।</p>
        <p class="mt-2 md:mt-0 flex items-center gap-2">
            Developed with <i class="fas fa-heart text-rose-500 animate-pulse"></i> by <span class="bg-white text-gray-900 px-2 py-1 rounded font-black tracking-widest text-xs ml-1">BATIGHOR</span> EIMS
        </p>
      </div>
    </footer>
    
  </div>
</template>

<script>
export default {
  name: 'FrontendHome',
  props: {
    school: {
      type: Object,
      required: true
    },
    settings: {
      type: Object,
      required: false,
      default: () => ({})
    }
  },
  data() {
    return {
      isMobileMenuOpen: false,
      facilities: [
        { name: 'বিজ্ঞান ল্যাব', icon: 'fas fa-flask text-teal-600', colorClass: 'bg-teal-50' },
        { name: 'কম্পিউটার ল্যাব', icon: 'fas fa-laptop-code text-indigo-600', colorClass: 'bg-indigo-50' },
        { name: 'গ্রন্থাগার', icon: 'fas fa-book text-amber-600', colorClass: 'bg-amber-50' },
        { name: 'খেলার মাঠ', icon: 'fas fa-futbol text-emerald-600', colorClass: 'bg-emerald-50' },
        { name: 'ডিজিটাল ক্লাস', icon: 'fas fa-chalkboard text-blue-600', colorClass: 'bg-blue-50' },
        { name: 'স্কুল বাস', icon: 'fas fa-bus text-yellow-500', colorClass: 'bg-yellow-50' },
        { name: 'বৃত্তি সহায়তা', icon: 'fas fa-hand-holding-heart text-rose-600', colorClass: 'bg-rose-50' },
        { name: 'মাল্টিমিডিয়া', icon: 'fas fa-microphone-alt text-purple-600', colorClass: 'bg-purple-50' }
      ],
      notices: [
        { title: 'এসএসসি পরীক্ষা ২০২৬ সংক্রান্ত বিজ্ঞপ্তি ও রুটিন', date: '৩০ মার্চ' },
        { title: '৬ষ্ঠ শ্রেণিতে ভর্তি আবেদন শুরু ১০ এপ্রিল থেকে', date: '২৫ মার্চ' },
        { title: 'বার্ষিক ক্রীড়া প্রতিযোগিতা ২০২৫-এর তারিখ ঘোষণা', date: '২০ মার্চ' },
        { title: 'অভিভাবক সভা আগামী ১৫ মে তারিখে অনুষ্ঠিত হবে', date: '১৫ মার্চ' },
        { title: 'বার্ষিক পরীক্ষার ফলাফল ও মার্কশীট বিতরণ', date: '১০ মার্চ' },
      ]
    };
  },
  computed: {
    schoolNameBn() {
      return this.school.name_bn || this.school.name || "বিদ্যালয়";
    }
  },
  mounted() {
    if (window.AOS) {
      window.AOS.init({ duration: 800, once: true, offset: 50 });
    }
  }
}
</script>

<style scoped>
@keyframes marquee {
  0% { transform: translateX(10%); }
  100% { transform: translateX(-100%); }
}

@keyframes blob {
  0% { transform: translate(0px, 0px) scale(1); }
  33% { transform: translate(30px, -50px) scale(1.1); }
  66% { transform: translate(-20px, 20px) scale(0.9); }
  100% { transform: translate(0px, 0px) scale(1); }
}

.animate-marquee {
  animation: marquee 35s linear infinite;
  display: flex;
}

.animate-marquee:hover {
  animation-play-state: paused;
}

.animate-blob {
  animation: blob 7s infinite;
}

.animation-delay-2000 {
  animation-delay: 2s;
}

.animate-spin-slow {
  animation: spin 3s linear infinite;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(99, 102, 241, 0.2);
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(99, 102, 241, 0.5);
}
</style>
