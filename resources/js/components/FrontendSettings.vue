<template>
  <div class="frontend-settings-wrapper min-h-[600px]">
    
    <!-- Top Header -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight">উন্নত ফ্রন্টএন্ড সেটিংস</h2>
        <p class="text-slate-500 text-sm mt-1">স্লাইডার, নোটিশ এবং প্রতিষ্ঠানের তথ্য এখান থেকে নিয়ন্ত্রণ করুন।</p>
      </div>
      <div class="flex items-center gap-3">
        <a :href="'/admission/' + schoolCode" target="_blank" class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-slate-700 hover:bg-slate-50 transition shadow-sm font-medium text-sm">
          <i class="fas fa-external-link-alt text-indigo-500"></i>
          লাইভ ওয়েবসাইট
        </a>
      </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-8 items-start">
      
      <!-- Sidebar -->
      <aside class="w-full lg:w-80 shrink-0 lg:sticky lg:top-4">
        <div class="bg-white rounded-3xl border border-slate-100 shadow-xl overflow-hidden">
          <nav class="p-4 space-y-2">
            <button v-for="section in sections" :key="section.id" @click="switchSection(section.id)"
              class="w-full flex items-center gap-4 px-4 py-4 rounded-2xl transition-all duration-300 group text-left"
              :class="activeSection === section.id ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-600 hover:bg-slate-50'">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors"
                :class="activeSection === section.id ? 'bg-white/20' : 'bg-slate-100 text-slate-500 group-hover:bg-indigo-100 group-hover:text-indigo-600'">
                <i :class="section.icon"></i>
              </div>
              <span class="block font-bold">{{ section.name }}</span>
            </button>
          </nav>
        </div>
      </aside>

      <!-- Main Content -->
      <main class="flex-grow w-full pb-20">
        
        <div v-show="activeSection === 'banner'" class="space-y-6">
          <div class="section-card">
            <div class="section-header">
              <i class="fas fa-images text-indigo-500"></i>
              <h3 class="font-bold text-slate-800 ml-3 text-lg">হোম স্লাইডার ম্যানেজমেন্ট</h3>
            </div>
            
            <div class="section-body p-8 space-y-8">
               <div class="bg-indigo-50/50 p-6 rounded-[32px] border border-indigo-100/50 text-center relative group cursor-pointer hover:bg-indigo-100 transition-colors">
                  <input type="file" multiple @change="addNewSlides" class="absolute inset-0 opacity-0 cursor-pointer">
                  <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-3 shadow-sm">
                    <i class="fas fa-cloud-upload-alt text-2xl text-indigo-600"></i>
                  </div>
                  <h4 class="font-bold text-slate-700">নতুন স্লাইডার ছবি যোগ করুন</h4>
                  <p class="text-xs text-slate-500 mt-1">একাধিক ছবি একসাথে আপলোড করা যাবে</p>
               </div>

               <!-- Slider Items List -->
               <div class="space-y-6 mt-8">
                  <h5 class="text-sm font-black text-slate-400 uppercase tracking-widest">বর্তমানে আছে ({{ sliderItems.length }})</h5>
                  
                  <div v-for="(item, idx) in sliderItems" :key="idx" class="bg-white border rounded-[40px] p-6 shadow-sm hover:shadow-md transition-all relative overflow-hidden group border-slate-100">
                     <div class="flex flex-col md:flex-row gap-8">
                        <div class="w-full md:w-64 shrink-0">
                           <div class="aspect-video rounded-[30px] overflow-hidden shadow-inner bg-slate-100 group">
                              <img :src="item.isNew ? item.preview : '/storage/' + item.image" class="w-full h-full object-cover">
                           </div>
                           <div class="mt-4 flex items-center justify-between px-2">
                              <label class="flex items-center gap-2 cursor-pointer">
                                 <div class="relative inline-block w-10 h-6">
                                    <input type="checkbox" v-model="item.active" class="sr-only peer">
                                    <div class="w-full h-full bg-slate-200 rounded-full peer-checked:bg-green-500 transition-colors"></div>
                                    <div class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4 shadow"></div>
                                 </div>
                                 <span class="text-xs font-bold" :class="item.active ? 'text-green-600' : 'text-slate-400'">{{ item.active ? 'সক্রিয়' : 'বন্ধ' }}</span>
                              </label>
                              <button @click="removeSliderItem(idx)" class="text-rose-500 hover:text-rose-700 transition-colors text-sm font-bold flex items-center gap-1">
                                 <i class="fas fa-trash-alt"></i> ডিলিট
                              </button>
                           </div>
                        </div>
                        
                        <div class="flex-grow space-y-4">
                           <div class="space-y-1">
                              <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest pl-2">স্লাইডার টাইটেল</label>
                              <input type="text" v-model="item.title" class="input-field-sm font-bold" placeholder="ছবির ওপরের লাল বড় লেখাটি">
                           </div>
                           <div class="space-y-1">
                              <label class="text-[10px] font-black uppercase text-slate-400 tracking-widest pl-2">স্লাইডার সাব-টাইটেল</label>
                              <input type="text" v-model="item.subtitle" class="input-field-sm" placeholder="টাইটেলের নিচের ছোট লেখাটি">
                           </div>
                        </div>
                     </div>
                  </div>
               </div>

               <div v-if="sliderItems.length === 0" class="p-20 text-center border-2 border-dashed border-slate-100 rounded-[40px]">
                  <i class="fas fa-folder-open text-5xl text-slate-100 mb-4 block"></i>
                  <p class="text-slate-400 font-bold">কোনো স্লাইডার পাওয়া যায়নি। ছবি যোগ করুন।</p>
               </div>
            </div>

            <div class="section-footer">
               <button @click="saveSlider" class="save-btn" :disabled="saving === 'banner'">
                  <span v-if="saving === 'banner'"><i class="fas fa-spinner fa-spin mr-2"></i> সংরক্ষন হচ্ছে...</span>
                  <span v-else><i class="fas fa-check-circle mr-2"></i> স্লাইডার আপডেট করুন</span>
               </button>
            </div>
          </div>

          <div class="section-card">
             <div class="section-header">
                <i class="fas fa-bullhorn text-indigo-500"></i>
                <h3 class="font-bold text-slate-800 ml-3">হেডলাইন সেটিংস</h3>
             </div>
             <div class="section-body p-8">
                <label class="input-label">স্ক্রলিং হেডলাইন (Marquee)</label>
                <input type="text" v-model="form.marquee_text" class="input-field" placeholder="সব নোটিশ একসাথে স্ক্রল করবে...">
             </div>
             <div class="section-footer">
               <button @click="savePartial('contact')" class="save-btn" :disabled="saving === 'banner'">সেভ করুন</button>
             </div>
          </div>
        </div>

        <!-- Other sections (About, Principal, etc.) -->
        <div v-show="activeSection !== 'banner'">
           <div class="section-card" v-for="section in sections.filter(s => s.id !== 'banner' && s.id === activeSection)" :key="section.id">
              <div class="section-header">
                <i :class="section.icon + ' text-indigo-500'"></i>
                <h3 class="font-bold text-slate-800 ml-3">{{ section.name }}</h3>
              </div>
              <div class="section-body p-8 space-y-6">
                 <div v-if="activeSection === 'about'" class="space-y-6">
                    <textarea ref="about_editor" v-model="form.about_text" class="rich-editor"></textarea>
                    <div class="p-4 border border-slate-100 rounded-[30px] flex items-center gap-6 bg-slate-50/50">
                       <img v-if="settings.about_image" :src="'/storage/' + settings.about_image" class="w-32 h-32 rounded-3xl object-cover shadow-sm">
                       <input type="file" @change="handleFileUpload('about_image', $event)" class="file-input flex-grow">
                    </div>
                 </div>

                 <div v-if="activeSection === 'principal'" class="space-y-6">
                    <input type="text" v-model="form.principal_name" class="input-field font-bold" placeholder="অধ্যক্ষের নাম">
                    <textarea ref="principal_editor" v-model="form.principal_message" class="rich-editor"></textarea>
                    <div class="p-4 border border-slate-100 rounded-[30px] flex items-center gap-6 bg-slate-50/50">
                       <img v-if="settings.principal_image" :src="'/storage/' + settings.principal_image" class="w-32 h-40 rounded-3xl object-cover shadow-sm">
                       <input type="file" @change="handleFileUpload('principal_image', $event)" class="file-input flex-grow">
                    </div>

                    <div class="border-t border-slate-100 pt-6">
                       <label class="input-label">ফিচার ফটো (প্রধান শিক্ষক ও সভাপতির বাণী — উভয় সেকশনে বড় করে দেখানো হবে)</label>
                       <div class="p-4 border border-slate-100 rounded-[30px] flex items-center gap-6 bg-slate-50/50 mt-2">
                          <img v-if="settings.feature_image" :src="'/storage/' + settings.feature_image" class="w-32 h-40 rounded-3xl object-cover shadow-sm">
                          <input type="file" @change="handleFileUpload('feature_image', $event)" class="file-input flex-grow">
                       </div>
                       <p class="text-xs text-slate-400 mt-2">ভালো ফলাফলের জন্য ৮০০x১০০০ পিক্সেল আকারের ছবি আপলোড করুন — বড় সাইজের ছবি ওয়েবসাইট ধীরগতির করে দিতে পারে।</p>
                    </div>
                 </div>

                 <div v-if="activeSection === 'chairman'" class="space-y-6">
                    <input type="text" v-model="form.chairman_name" class="input-field font-bold" placeholder="সভাপতির নাম">
                    <textarea ref="chairman_editor" v-model="form.chairman_message" class="rich-editor"></textarea>
                    <div class="p-4 border border-slate-100 rounded-[30px] flex items-center gap-6 bg-slate-50/50">
                       <img v-if="settings.chairman_image" :src="'/storage/' + settings.chairman_image" class="w-32 h-40 rounded-3xl object-cover shadow-sm">
                       <input type="file" @change="handleFileUpload('chairman_image', $event)" class="file-input flex-grow">
                    </div>
                 </div>

                 <div v-if="activeSection === 'social'" class="space-y-6">
                    <div><label class="input-label"><i class="fab fa-facebook mr-1"></i> ফেসবুক পেজ/প্রোফাইল লিংক</label><input type="url" v-model="form.facebook_url" class="input-field" placeholder="https://facebook.com/..."></div>
                    <div><label class="input-label"><i class="fab fa-youtube mr-1"></i> ইউটিউব চ্যানেল লিংক</label><input type="url" v-model="form.youtube_url" class="input-field" placeholder="https://youtube.com/..."></div>
                    <p class="text-xs text-slate-400">এই লিংকগুলো ওয়েবসাইটের টপ বার ও ফুটারে দেখানো হবে।</p>
                 </div>

                 <div v-if="activeSection === 'committee'" class="space-y-8">
                    <div>
                       <label class="input-label">কমিটি সম্পর্কে ভূমিকা (ঐচ্ছিক)</label>
                       <textarea ref="committee_editor" v-model="form.committee_text" class="rich-editor"></textarea>
                    </div>
                    <committee-members-manager :school-id="schoolId"></committee-members-manager>
                 </div>

                 <div v-if="activeSection === 'contact'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2"><label class="input-label">ঠিকানা</label><textarea v-model="form.contact_address" class="input-field"></textarea></div>
                    <div><label class="input-label">ইমেইল</label><input type="email" v-model="form.contact_email" class="input-field"></div>
                    <div><label class="input-label">ফোন</label><input type="text" v-model="form.contact_phone" class="input-field"></div>
                 </div>

                 <div v-if="activeSection === 'seo'" class="space-y-6">
                    <div><label class="input-label">মেটা টাইটেল</label><input type="text" v-model="form.meta_title" class="input-field"></div>
                    <div><label class="input-label">মেটা ডেসক্রিপশন</label><textarea v-model="form.meta_description" class="input-field"></textarea></div>
                    <div><label class="input-label">কী-ওয়ার্ডস</label><input type="text" v-model="form.meta_keywords" class="input-field"></div>
                 </div>
              </div>
              <div class="section-footer">
                 <button @click="savePartial(activeSection)" class="save-btn" :disabled="saving === activeSection">সেভ করুন</button>
              </div>
           </div>
        </div>

      </main>
    </div>
  </div>
</template>

<script>
export default {
  name: 'FrontendSettings',
  props: { schoolId: Number, schoolCode: String },
  data() {
    return {
      activeSection: 'banner',
      loading: false, saving: null,
      sections: [
        { id: 'banner', name: 'ব্যানার ও স্লাইডার', icon: 'fas fa-images' },
        { id: 'about', name: 'ইতিহাস ও পরিচিতি', icon: 'fas fa-history' },
        { id: 'principal', name: 'অধ্যক্ষের বাণী', icon: 'fas fa-user-tie' },
        { id: 'chairman', name: 'সভাপতির বাণী', icon: 'fas fa-user-shield' },
        { id: 'committee', name: 'ম্যানেজিং কমিটি', icon: 'fas fa-users-cog' },
        { id: 'contact', name: 'যোগাযোগ তথ্য', icon: 'fas fa-address-book' },
        { id: 'social', name: 'সোশ্যাল লিংক', icon: 'fas fa-share-nodes' },
        { id: 'seo', name: 'SEO সেটিংস', icon: 'fas fa-search' }
      ],
      settings: {},
      form: { marquee_text: '', about_text: '', principal_name: '', principal_message: '', chairman_name: '', chairman_message: '', committee_text: '', contact_address: '', contact_email: '', contact_phone: '', facebook_url: '', youtube_url: '', meta_title: '', meta_description: '', meta_keywords: '' },
      sliderItems: [],
      files: { about_image: null, principal_image: null, chairman_image: null, feature_image: null }
    };
  },
  async mounted() {
    await this.fetchData();
    this.$nextTick(() => this.initEditorForSection(this.activeSection));
  },
  beforeUnmount() {
    this.destroyEditor();
  },
  methods: {
    // Only the active section's fields exist in the DOM (see v-for filter above),
    // so the rich-text editor for a field must be (re)created every time its
    // section becomes active — a fixed selector on mount can't find elements
    // that don't exist yet, which is why editors previously failed to appear.
    switchSection(id) {
      if (id === this.activeSection) return;
      this.destroyEditor();
      this.activeSection = id;
      this.$nextTick(() => this.initEditorForSection(id));
    },
    editorConfigFor(sectionId) {
      return {
        about: { field: 'about_text', ref: 'about_editor' },
        principal: { field: 'principal_message', ref: 'principal_editor' },
        chairman: { field: 'chairman_message', ref: 'chairman_editor' },
        committee: { field: 'committee_text', ref: 'committee_editor' },
      }[sectionId] || null;
    },
    initEditorForSection(sectionId) {
      const cfg = this.editorConfigFor(sectionId);
      if (!cfg || !window.ClassicEditor) return;
      const { field, ref } = cfg;
      const el = this.$refs[ref];
      const target = Array.isArray(el) ? el[0] : el;
      if (!target) return;

      window.ClassicEditor.create(target, {
        toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'undo', 'redo'],
        language: 'bn',
      }).then((editor) => {
        this.editorInstance = editor;
        this.editorField = field;
        editor.setData(this.form[field] || '');
        editor.model.document.on('change:data', () => {
          this.form[field] = editor.getData();
        });
      }).catch(() => {});
    },
    destroyEditor() {
      if (this.editorInstance) {
        this.editorInstance.destroy().catch(() => {});
        this.editorInstance = null;
        this.editorField = null;
      }
    },
    async fetchData() {
      this.loading = true;
      try {
        const res = await axios.get(`/principal/institute/${this.schoolId}/frontend/settings/data`);
        this.settings = res.data.settings;
        Object.keys(this.form).forEach(k => this.form[k] = this.settings[k] || '');
        this.sliderItems = Array.isArray(this.settings.hero_images) ? this.settings.hero_images : (JSON.parse(this.settings.hero_images || "[]"));
        this.sliderItems = this.sliderItems.map(item => typeof item === 'string' ? { image: item, title: '', subtitle: '', active: true } : item);
        if (this.editorInstance && this.editorField) {
          this.editorInstance.setData(this.form[this.editorField] || '');
        }
      } catch (e) { toastr.error('Load error'); } finally { this.loading = false; }
    },
    addNewSlides(e) {
       const files = Array.from(e.target.files);
       files.forEach(file => {
          this.sliderItems.push({
             image: null, title: this.form.hero_title || '', subtitle: this.form.hero_subtitle || '', active: true,
             file: file, preview: URL.createObjectURL(file), isNew: true
          });
       });
       toastr.success(files.length + ' টি নতুন ছবি স্লাইডারে যোগ করা হয়েছে।');
    },
    removeSliderItem(idx) { this.sliderItems.splice(idx, 1); },
    handleFileUpload(k, e) { if (e.target.files.length > 0) this.files[k] = e.target.files[0]; },

    async saveSlider() {
       this.saving = 'banner';
       try {
          let fd = new FormData();
          fd.append('marquee_text', this.form.marquee_text);
          // Separate existing from new
          const existing = this.sliderItems.filter(i => !i.isNew).map(i => ({ image: i.image, title: i.title, subtitle: i.subtitle, active: i.active }));
          const news = this.sliderItems.filter(i => i.isNew);
          
          fd.append('hero_images_json', JSON.stringify(existing));
          news.forEach((item, idx) => {
             fd.append(`hero_slider_files[${idx}]`, item.file);
             fd.append(`hero_slider_meta[${idx}]`, JSON.stringify({ title: item.title, subtitle: item.subtitle, active: item.active }));
          });

          const res = await axios.post(`/principal/institute/${this.schoolId}/frontend/settings/data`, fd);
          this.settings = res.data.settings;
          await this.fetchData(); // Reload to clear new flags
          toastr.success('স্লাইডার আপডেট হয়েছে');
       } catch (e) { toastr.error('Error saving'); } finally { this.saving = null; }
    },

    async savePartial(id) {
      if (id === 'banner') return this.saveSlider();
      this.saving = id;
      try {
        let fd = new FormData();
        const map = {
          about: ['about_text', 'about_image'],
          principal: ['principal_name', 'principal_message', 'principal_image', 'feature_image'],
          chairman: ['chairman_name', 'chairman_message', 'chairman_image'],
          committee: ['committee_text'],
          contact: ['contact_address', 'contact_email', 'contact_phone'],
          social: ['facebook_url', 'youtube_url'],
          seo: ['meta_title', 'meta_description', 'meta_keywords']
        };
        map[id].forEach(f => {
           if (f.includes('_image') && this.files[f]) fd.append(f, this.files[f]);
           else if (!f.includes('_image')) fd.append(f, this.form[f] || '');
        });
        const res = await axios.post(`/principal/institute/${this.schoolId}/frontend/settings/data`, fd);
        this.settings = res.data.settings;
        toastr.success('সেভ হয়েছে');
      } catch (e) { toastr.error('Save error'); } finally { this.saving = null; }
    }
  }
};
</script>

<style scoped>
.section-card { background: white; border-radius: 2.5rem; border: 1px solid #f1f5f9; box-shadow: 0 20px 40px -15px rgba(0,0,0,0.05); overflow: hidden; }
.section-header { padding: 1.5rem 2.5rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; background: #f8fafc; }
.input-label { display: block; font-size: 0.8rem; font-weight: 800; color: #475569; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em; }
.input-field { width: 100%; padding: 1rem 1.5rem; border-radius: 1.5rem; background: #f8fafc; border: 1px solid #e2e8f0; outline: none; transition: 0.3s; }
.input-field-sm { width: 100%; padding: 0.75rem 1.25rem; border-radius: 1rem; background: #f8fafc; border: 1px solid #e2e8f0; outline: none; font-size: 0.9rem; transition: 0.3s; }
.input-field:focus, .input-field-sm:focus { background: white; border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
.section-footer { padding: 1.5rem 2.5rem; border-top: 1px solid #f1f5f9; background: #f8fafc; display: flex; justify-content: flex-end; }
.save-btn { padding: 1rem 3rem; background: #4f46e5; color: white; border-radius: 1.5rem; font-weight: 800; transition: 0.3s; box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4); }
.save-btn:hover { background: #4338ca; transform: translateY(-2px); box-shadow: 0 15px 25px -5px rgba(79, 70, 229, 0.5); }
.file-input { @apply text-xs bg-white border border-slate-100 rounded-xl p-2; }
</style>
