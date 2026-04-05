<template>
  <div class="card card-outline card-primary shadow-sm border-0 rounded-lg">
    <div class="card-header bg-white border-bottom-0 pb-0 pt-4 flex justify-between items-center">
      <h3 class="card-title text-xl font-semibold text-gray-800 flex items-center">
        <i v-if="currentView !== 'list'" class="fas fa-arrow-left text-indigo-500 mr-3 cursor-pointer hover:text-indigo-700 transition" @click="currentView = 'list'"></i>
        <i v-else class="fas fa-desktop text-indigo-500 mr-2"></i> 
        {{ viewTitle }}
      </h3>
      <div v-if="currentView !== 'list'">
         <button @click="currentView = 'list'" class="btn btn-sm btn-outline-secondary rounded-full px-3">
           <i class="fas fa-times mr-1"></i> বাতিল
         </button>
      </div>
    </div>
    
    <div class="card-body pt-3">
      
      <!-- List View -->
      <div v-if="currentView === 'list'">
        <div class="alert alert-info bg-indigo-50 text-indigo-800 border-indigo-200 shadow-sm mb-5">
          <i class="fas fa-info-circle mr-2"></i> আপনার ওয়েবসাইটের বিভিন্ন পৃষ্ঠা নির্বাচন করুন এবং নিচের কার্ডগুলো থেকে কন্টেন্ট আপডেট করুন।
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          
          <!-- Homepage Settings -->
          <div @click="openView('homepage')" class="p-6 border border-gray-100 rounded-2xl bg-white shadow-sm hover:shadow-xl hover:border-indigo-200 transition-all duration-300 cursor-pointer group relative overflow-hidden">
             <div class="absolute -right-6 -top-6 w-24 h-24 bg-indigo-50 rounded-full group-hover:scale-150 transition-transform duration-500 z-0"></div>
             <div class="relative z-10 flex flex-col h-full">
               <div class="bg-indigo-100 text-indigo-600 w-14 h-14 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                 <i class="fas fa-home text-2xl"></i>
               </div>
               <h4 class="font-bold text-gray-800 mb-2 text-lg">হোমপেজ</h4>
               <p class="text-sm text-gray-500 mb-4 flex-grow">ব্যানার, স্বাগত টেক্সট, নোটিশ বোর্ড সেটিংস এবং অন্যান্য প্রয়োজনীয় তথ্য।</p>
               <span class="text-indigo-600 text-sm font-bold flex items-center group-hover:translate-x-1 transition-transform">পরিবর্তন করুন <i class="fas fa-arrow-right ml-2"></i></span>
             </div>
          </div>

          <!-- History / About Settings -->
          <div @click="openView('about')" class="p-6 border border-gray-100 rounded-2xl bg-white shadow-sm hover:shadow-xl hover:border-green-200 transition-all duration-300 cursor-pointer group relative overflow-hidden">
             <div class="absolute -right-6 -top-6 w-24 h-24 bg-green-50 rounded-full group-hover:scale-150 transition-transform duration-500 z-0"></div>
             <div class="relative z-10 flex flex-col h-full">
               <div class="bg-green-100 text-green-600 w-14 h-14 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-green-600 group-hover:text-white transition-colors">
                 <i class="fas fa-history text-2xl"></i>
               </div>
               <h4 class="font-bold text-gray-800 mb-2 text-lg">সংক্ষিপ্ত ইতিহাস</h4>
               <p class="text-sm text-gray-500 mb-4 flex-grow">প্রতিষ্ঠানের ইতিহাস, মিশন ও ভিশন সম্পর্কে বিস্তারিত তথ্য।</p>
               <span class="text-green-600 text-sm font-bold flex items-center group-hover:translate-x-1 transition-transform">পরিবর্তন করুন <i class="fas fa-arrow-right ml-2"></i></span>
             </div>
          </div>

          <!-- Administration Settings -->
          <div @click="openView('administration')" class="p-6 border border-gray-100 rounded-2xl bg-white shadow-sm hover:shadow-xl hover:border-purple-200 transition-all duration-300 cursor-pointer group relative overflow-hidden">
             <div class="absolute -right-6 -top-6 w-24 h-24 bg-purple-50 rounded-full group-hover:scale-150 transition-transform duration-500 z-0"></div>
             <div class="relative z-10 flex flex-col h-full">
               <div class="bg-purple-100 text-purple-600 w-14 h-14 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-purple-600 group-hover:text-white transition-colors">
                 <i class="fas fa-user-tie text-2xl"></i>
               </div>
               <h4 class="font-bold text-gray-800 mb-2 text-lg">অধ্যক্ষের বাণী</h4>
               <p class="text-sm text-gray-500 mb-4 flex-grow">প্রতিষ্ঠানের অধ্যক্ষ বা প্রধান শিক্ষকের তথ্য ও অনুপ্রেরণামূলক বাণী।</p>
               <span class="text-purple-600 text-sm font-bold flex items-center group-hover:translate-x-1 transition-transform">পরিবর্তন করুন <i class="fas fa-arrow-right ml-2"></i></span>
             </div>
          </div>

          <!-- Committee Settings -->
          <div @click="openView('committee')" class="p-6 border border-gray-100 rounded-2xl bg-white shadow-sm hover:shadow-xl hover:border-blue-200 transition-all duration-300 cursor-pointer group relative overflow-hidden">
             <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-50 rounded-full group-hover:scale-150 transition-transform duration-500 z-0"></div>
             <div class="relative z-10 flex flex-col h-full">
               <div class="bg-blue-100 text-blue-600 w-14 h-14 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                 <i class="fas fa-users-cog text-2xl"></i>
               </div>
               <h4 class="font-bold text-gray-800 mb-2 text-lg">ম্যানেজিং কমিটি</h4>
               <p class="text-sm text-gray-500 mb-4 flex-grow">ম্যানেজিং কমিটি বা পরিচালনা পর্ষদের সদস্যবৃন্দের তালিকা ও তথ্য।</p>
               <span class="text-blue-600 text-sm font-bold flex items-center group-hover:translate-x-1 transition-transform">পরিবর্তন করুন <i class="fas fa-arrow-right ml-2"></i></span>
             </div>
          </div>

          <!-- Contact Settings -->
          <div @click="openView('contact')" class="p-6 border border-gray-100 rounded-2xl bg-white shadow-sm hover:shadow-xl hover:border-orange-200 transition-all duration-300 cursor-pointer group relative overflow-hidden">
             <div class="absolute -right-6 -top-6 w-24 h-24 bg-orange-50 rounded-full group-hover:scale-150 transition-transform duration-500 z-0"></div>
             <div class="relative z-10 flex flex-col h-full">
               <div class="bg-orange-100 text-orange-600 w-14 h-14 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-orange-600 group-hover:text-white transition-colors">
                 <i class="fas fa-address-book text-2xl"></i>
               </div>
               <h4 class="font-bold text-gray-800 mb-2 text-lg">যোগাযোগ সেকশন</h4>
               <p class="text-sm text-gray-500 mb-4 flex-grow">প্রতিষ্ঠানের ঠিকানা, ইমেইল, ফোন নম্বর ও সোশ্যাল মিডিয়া প্রোফাইল।</p>
               <span class="text-orange-600 text-sm font-bold flex items-center group-hover:translate-x-1 transition-transform">পরিবর্তন করুন <i class="fas fa-arrow-right ml-2"></i></span>
             </div>
          </div>

          <!-- SEO Settings -->
          <div @click="openView('seo')" class="p-6 border border-gray-100 rounded-2xl bg-white shadow-sm hover:shadow-xl hover:border-gray-200 transition-all duration-300 cursor-pointer group relative overflow-hidden">
             <div class="absolute -right-6 -top-6 w-24 h-24 bg-gray-50 rounded-full group-hover:scale-150 transition-transform duration-500 z-0"></div>
             <div class="relative z-10 flex flex-col h-full">
               <div class="bg-gray-100 text-gray-600 w-14 h-14 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-gray-800 group-hover:text-white transition-colors">
                 <i class="fas fa-search text-2xl"></i>
               </div>
               <h4 class="font-bold text-gray-800 mb-2 text-lg">সার্চ ইঞ্জিন (SEO)</h4>
               <p class="text-sm text-gray-500 mb-4 flex-grow">গুগল সার্চ ও অন্যান্য সার্চ ইঞ্জিনে প্রতিষ্ঠানের র‍্যাঙ্কিং বৃদ্ধির সেটিংস।</p>
               <span class="text-gray-600 text-sm font-bold flex items-center group-hover:translate-x-1 transition-transform">পরিবর্তন করুন <i class="fas fa-arrow-right ml-2"></i></span>
             </div>
          </div>
          
        </div>
      </div>

      <!-- Loading State -->
      <div v-else-if="loading" class="text-center py-12">
        <div class="inline-block w-12 h-12 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
        <p class="text-gray-500 mt-4 font-medium">তথ্য লোড হচ্ছে...</p>
      </div>

      <!-- Forms Wrapper -->
      <div v-else class="bg-white rounded-xl">
        <form @submit.prevent="saveSettings">
          
          <!-- Homepage View -->
          <div v-if="currentView === 'homepage'" class="space-y-6 animate-fade-in-up">
            <div class="bg-indigo-50/50 p-5 rounded-xl border border-indigo-100">
               <h5 class="font-bold text-indigo-800 mb-4 flex items-center"><i class="fas fa-image mr-2 text-indigo-500"></i> হিরো সেকশন (প্রধান ব্যানার)</h5>
               <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                  <div class="form-group">
                    <label class="font-medium text-gray-700">প্রধান শিরোনাম (Title)</label>
                    <input type="text" class="form-control rounded-lg" v-model="form.hero_title" placeholder="উদা: আমাদের বিদ্যালয়ে স্বাগতম">
                  </div>
                  <div class="form-group">
                    <label class="font-medium text-gray-700">উপ-শিরোনাম (Subtitle)</label>
                    <input type="text" class="form-control rounded-lg" v-model="form.hero_subtitle" placeholder="উদা: শ্রেষ্ঠত্বের পথে আমাদের এই পথচলা...">
                  </div>
                  <div class="form-group md:col-span-2">
                    <label class="font-medium text-gray-700">ব্যানার ইমেজ (High Quality Image)</label>
                    <div class="flex items-start gap-4">
                        <div v-if="settings.hero_image" class="w-32 h-20 rounded-lg overflow-hidden border shadow-sm shrink-0">
                           <img :src="'/storage/' + settings.hero_image" class="w-full h-full object-cover">
                        </div>
                        <input type="file" ref="hero_image" class="form-control hover:cursor-pointer p-2 rounded-lg" @change="handleFileUpload('hero_image', $event)">
                    </div>
                  </div>
               </div>
            </div>

            <div class="bg-purple-50/50 p-5 rounded-xl border border-purple-100">
               <h5 class="font-bold text-purple-800 mb-4 flex items-center"><i class="fas fa-bullhorn mr-2 text-purple-500"></i> হেডলাইন স্ক্রলার (Marquee)</h5>
               <div class="form-group mb-0">
                 <label class="font-medium text-gray-700">ব্যানারের নিচে চলমান লেখা (জরুরী নোটিশ/হাইলাইট)</label>
                 <input type="text" class="form-control rounded-lg" v-model="form.marquee_text" placeholder="উদা: অনলাইন ভর্তি কার্যক্রম শুরু হয়েছে...">
               </div>
            </div>
          </div>

          <!-- History / About View -->
          <div v-if="currentView === 'about'" class="space-y-6 animate-fade-in-up">
            <div class="bg-green-50/50 p-5 rounded-xl border border-green-100">
               <h5 class="font-bold text-green-800 mb-4 flex items-center"><i class="fas fa-history mr-2 text-green-500"></i> প্রতিষ্ঠানের সংক্ষিপ্ত ইতিহাস</h5>
               <div class="grid grid-cols-1 gap-5">
                  <div class="form-group">
                    <label class="font-medium text-gray-700">ইতিহাস ও পরিচিতি (About content)</label>
                    <textarea class="form-control rounded-lg" rows="10" v-model="form.about_text" placeholder="প্রতিষ্ঠানের ইতিহাস ও পরিচিতি লিখুন..."></textarea>
                  </div>
                  <div class="form-group">
                    <label class="font-medium text-gray-700">পরিচিতি অংশের ছবি</label>
                    <div class="flex items-start gap-4">
                        <div v-if="settings.about_image" class="w-32 h-20 rounded-lg overflow-hidden border shadow-sm shrink-0">
                           <img :src="'/storage/' + settings.about_image" class="w-full h-full object-cover">
                        </div>
                        <input type="file" ref="about_image" class="form-control hover:cursor-pointer p-2 rounded-lg" @change="handleFileUpload('about_image', $event)">
                    </div>
                  </div>
               </div>
            </div>
          </div>

          <!-- Administration View -->
          <div v-if="currentView === 'administration'" class="space-y-6 animate-fade-in-up">
            <div class="bg-purple-50/50 p-5 rounded-xl border border-purple-100">
               <h5 class="font-bold text-purple-800 mb-4 flex items-center"><i class="fas fa-user-tie mr-2 text-purple-500"></i> অধ্যক্ষের তথ্য ও বাণী</h5>
               <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                  <div class="form-group md:col-span-2">
                    <label class="font-medium text-gray-700">অধ্যক্ষের নাম</label>
                    <input type="text" class="form-control rounded-lg" v-model="form.principal_name" placeholder="অধ্যক্ষের নাম">
                  </div>
                  <div class="form-group md:col-span-2">
                    <label class="font-medium text-gray-700">অধ্যক্ষের বাণী</label>
                    <textarea class="form-control rounded-lg" rows="8" v-model="form.principal_message" placeholder="কিছু অনুপ্রেরণামূলক কথা..."></textarea>
                  </div>
                  <div class="form-group md:col-span-2">
                    <label class="font-medium text-gray-700">অধ্যক্ষের ছবি</label>
                    <div class="flex items-start gap-4">
                        <div v-if="settings.principal_image" class="w-24 h-24 rounded-full overflow-hidden border-4 border-white shadow-sm shrink-0">
                           <img :src="'/storage/' + settings.principal_image" class="w-full h-full object-cover">
                        </div>
                        <input type="file" ref="principal_image" class="form-control hover:cursor-pointer p-2 rounded-lg mt-4" @change="handleFileUpload('principal_image', $event)">
                    </div>
                  </div>
               </div>
            </div>
          </div>

          <!-- Committee View -->
          <div v-if="currentView === 'committee'" class="space-y-6 animate-fade-in-up">
            <div class="bg-blue-50/50 p-5 rounded-xl border border-blue-100">
               <h5 class="font-bold text-blue-800 mb-4 flex items-center"><i class="fas fa-users-cog mr-2 text-blue-500"></i> ম্যানেজিং কমিটি বা পরিচালনা পর্ষদ</h5>
               <div class="grid grid-cols-1 gap-5">
                  <div class="form-group">
                    <label class="font-medium text-gray-700">কমিটি সদস্যদের তালিকা বা ভূমিকা</label>
                    <textarea class="form-control rounded-lg" rows="10" v-model="form.committee_text" placeholder="কমিটির সদস্যদের নাম ও পদবি লিখুন..."></textarea>
                  </div>
               </div>
            </div>
          </div>

          <!-- Contact View -->
          <div v-if="currentView === 'contact'" class="space-y-6 animate-fade-in-up">
            <div class="bg-orange-50/50 p-5 rounded-xl border border-orange-100">
               <h5 class="font-bold text-orange-800 mb-4 flex items-center"><i class="fas fa-address-book mr-2 text-orange-500"></i> যোগাযোগের তথ্যাদি</h5>
               <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                  <div class="form-group md:col-span-2">
                    <label class="font-medium text-gray-700">পূর্ণাঙ্গ ঠিকানা</label>
                    <input type="text" class="form-control rounded-lg" v-model="form.contact_address" placeholder="উদা: ৭৯৯/১, পশ্চিম নাখালপাড়া, তেজগাঁও, ঢাকা-১২১৫">
                  </div>
                  <div class="form-group">
                    <label class="font-medium text-gray-700">অফিসিয়াল ইমেইল</label>
                    <input type="email" class="form-control rounded-lg" v-model="form.contact_email" placeholder="example@school.com">
                  </div>
                  <div class="form-group">
                    <label class="font-medium text-gray-700">অফিসিয়াল ফোন নম্বর</label>
                    <input type="text" class="form-control rounded-lg" v-model="form.contact_phone" placeholder="উদা: +880 123 456 7890">
                  </div>
               </div>
            </div>

            <div class="bg-indigo-50/50 p-5 rounded-xl border border-indigo-100">
               <h5 class="font-bold text-indigo-800 mb-4 flex items-center"><i class="fab fa-facebook mr-2 text-indigo-500"></i> সোশাল মিডিয়া লিংকস</h5>
               <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                  <div class="form-group">
                    <label class="font-medium text-gray-700">ফেইসবুক পেজ লিংক</label>
                    <input type="text" class="form-control rounded-lg" v-model="form.facebook_url" placeholder="https://facebook.com/yourschool">
                  </div>
                  <div class="form-group">
                    <label class="font-medium text-gray-700">ইউটিউব চ্যানেল লিংক</label>
                    <input type="text" class="form-control rounded-lg" v-model="form.youtube_url" placeholder="https://youtube.com/...">
                  </div>
               </div>
            </div>
          </div>

          <!-- SEO View -->
          <div v-if="currentView === 'seo'" class="space-y-6 animate-fade-in-up">
            <div class="bg-gray-50/50 p-5 rounded-xl border border-gray-200">
               <h5 class="font-bold text-gray-800 mb-4 flex items-center"><i class="fas fa-search mr-2 text-indigo-500"></i> সার্চ ইঞ্জিন অপ্টিমাইজেশন (SEO)</h5>
               <div class="grid grid-cols-1 gap-5">
                  <div class="form-group">
                    <label class="font-medium text-gray-700">এসইও টাইটেল (Meta Title)</label>
                    <input type="text" class="form-control rounded-lg" v-model="form.meta_title" placeholder="গুগল সার্চে যা দেখাবে (৬০ অক্ষরের মধ্যে)">
                  </div>
                  <div class="form-group">
                    <label class="font-medium text-gray-700">এসইও বর্ণনা (Meta Description)</label>
                    <textarea class="form-control rounded-lg" rows="4" v-model="form.meta_description" placeholder="প্রতিষ্ঠানের সংক্ষিপ্ত বর্ণনা (১৬০ অক্ষরের মধ্যে)"></textarea>
                  </div>
                  <div class="form-group">
                    <label class="font-medium text-gray-700">কীওয়ার্ডস (Keywords)</label>
                    <input type="text" class="form-control rounded-lg" v-model="form.meta_keywords" placeholder="উদা: school, dhaka school, education (কমা দিয়ে লিখুন)">
                  </div>
               </div>
               <div class="mt-4 p-3 bg-white border rounded-lg">
                  <p class="text-xs font-bold text-gray-700 mb-2">গুগল সার্চে যেমন দেখাবে (Preview):</p>
                  <h6 class="text-[#1a0dab] text-lg mb-0 truncate cursor-default">{{ form.meta_title || 'প্রতিষ্ঠানের নাম' }}</h6>
                  <p class="text-[#006621] text-sm mb-1 truncate cursor-default">https://{{ schoolDomain || 'yourschool.com' }}</p>
                  <p class="text-[#545454] text-sm break-words">{{ form.meta_description || 'প্রতিষ্ঠানের একটি সুন্দর বর্ণনা এখানে দেখাবে যা ভিজিটরদের আকৃষ্ট করবে।' }}</p>
               </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="mt-8 pt-5 border-t border-gray-100 flex items-center justify-between">
            <button type="button" @click="currentView = 'list'" class="btn btn-outline-secondary rounded-full px-6 font-bold transition hover:bg-gray-100">
               বাতিল করুন
            </button>
            <button type="submit" class="btn btn-primary bg-indigo-600 hover:bg-indigo-700 border-0 rounded-full px-8 py-2.5 shadow-lg shadow-indigo-200 font-bold tracking-wide transition transform active:scale-95" :disabled="saving">
              <span v-if="saving"><i class="fas fa-spinner fa-spin mr-2"></i> সংরক্ষণ করা হচ্ছে...</span>
              <span v-else><i class="fas fa-save mr-2"></i> পরিবর্তন সংরক্ষণ করুন</span>
            </button>
          </div>
          
        </form>
      </div>

    </div>
  </div>
</template>

<script>
export default {
  name: 'FrontendSettings',
  props: {
    schoolId: {
      type: Number,
      required: true
    },
    schoolCode: {
      type: String,
      required: true
    },
    schoolDomain: {
      type: String,
      required: false,
      default: ''
    }
  },
  data() {
    return {
      loading: false,
      saving: false,
      currentView: 'list', // 'list', 'homepage', 'about', 'administration', 'committee', 'contact', 'seo'
      settings: {},
      form: {
        hero_title: '',
        hero_subtitle: '',
        about_text: '',
        principal_name: '',
        principal_message: '',
        facebook_url: '',
        youtube_url: '',
        marquee_text: '',
        contact_address: '',
        contact_email: '',
        contact_phone: '',
        committee_text: '',
        meta_title: '',
        meta_description: '',
        meta_keywords: '',
      },
      files: {
        hero_image: null,
        about_image: null,
        principal_image: null,
      }
    };
  },
  computed: {
    viewTitle() {
      if (this.currentView === 'list') return 'ওয়েবসাইট কনফিগারেশন';
      if (this.currentView === 'homepage') return 'হোমপেজ এডিটর';
      if (this.currentView === 'about') return 'ইতিহাস ও পরিচিতি';
      if (this.currentView === 'administration') return 'অধ্যক্ষের বাণী';
      if (this.currentView === 'committee') return 'ম্যানেজিং কমিটি';
      if (this.currentView === 'contact') return 'যোগাযোগ ও সোশ্যাল';
      if (this.currentView === 'seo') return 'সার্চ ইঞ্জিন অপ্টিমাইজেশন';
      return '';
    }
  },
  mounted() {
    // Optionally pre-fetch data or fetch when opening view
  },
  methods: {
    openView(viewName) {
      this.currentView = viewName;
      // Fetch fresh data when entering a view
      this.fetchData();
    },
    handleFileUpload(key, event) {
      if (event.target.files.length > 0) {
        this.files[key] = event.target.files[0];
      }
    },
    async fetchData() {
      this.loading = true;
      try {
        const response = await axios.get(`/principal/institute/${this.schoolId}/frontend/settings/data`);
        this.settings = response.data.settings;
        this.form = {
          hero_title: this.settings.hero_title || '',
          hero_subtitle: this.settings.hero_subtitle || '',
          about_text: this.settings.about_text || '',
          principal_name: this.settings.principal_name || '',
          principal_message: this.settings.principal_message || '',
          facebook_url: this.settings.facebook_url || '',
          youtube_url: this.settings.youtube_url || '',
          marquee_text: this.settings.marquee_text || '',
          contact_address: this.settings.contact_address || '',
          contact_email: this.settings.contact_email || '',
          contact_phone: this.settings.contact_phone || '',
          committee_text: this.settings.committee_text || '',
          meta_title: this.settings.meta_title || '',
          meta_description: this.settings.meta_description || '',
          meta_keywords: this.settings.meta_keywords || '',
        };
      } catch (error) {
        toastr.error('Failed to load settings');
      } finally {
        this.loading = false;
      }
    },
    async saveSettings() {
      this.saving = true;
      try {
        let formData = new FormData();
        Object.keys(this.form).forEach(key => {
          if (this.form[key]) {
            formData.append(key, this.form[key]);
          } else {
            formData.append(key, '');
          }
        });
        
        Object.keys(this.files).forEach(key => {
          if (this.files[key]) {
            formData.append(key, this.files[key]);
          }
        });

        const response = await axios.post(`/principal/institute/${this.schoolId}/frontend/settings/data`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });

        this.settings = response.data.settings;
        toastr.success('সফলভাবে আপডেট করা হয়েছে!');
        
        // Return to list after saving
        setTimeout(() => {
            this.currentView = 'list';
        }, 1000);

      } catch (error) {
        toastr.error('আপডেট করতে সমস্যা হচ্ছে');
        if (error.response && error.response.data.errors) {
          console.error(error.response.data.errors);
        }
      } finally {
        this.saving = false;
      }
    }
  }
}
</script>

<style scoped>
.animate-fade-in-up {
  animation: fadeInUp 0.4s ease-out;
}
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>

