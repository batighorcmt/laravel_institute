<template>
  <div v-if="loaded">
    <div v-if="current.applied_at" class="alert alert-info">
      সর্বশেষ থিম/মেনু প্রয়োগ করা হয়েছে: {{ current.applied_at }}
    </div>
    <div class="alert alert-secondary">
      <i class="fas fa-info-circle mr-1"></i>
      নিচের প্রতিটি অংশ (থিম, মেনু, পৃষ্ঠা) সম্পূর্ণ স্বতন্ত্র — একটি সংরক্ষণ করলে অন্যগুলো পরিবর্তন হবে না। যেটি প্রয়োজন শুধু সেটির "প্রয়োগ করুন" বাটনে ক্লিক করুন।
    </div>

    <!-- THEME SECTION -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white font-weight-bold d-flex justify-content-between align-items-center">
        <span><i class="fas fa-palette mr-2 text-primary"></i> থিম নির্বাচন করুন</span>
        <span v-if="hasThemeCustomization" class="badge badge-warning">থিম ইতিমধ্যে প্রয়োগকৃত</span>
      </div>
      <div class="card-body">
        <div v-if="!themes.length" class="text-muted">কোনো সক্রিয় থিম পাওয়া যায়নি।</div>
        <div class="row">
          <div class="col-md-4 mb-3" v-for="theme in themes" :key="theme.id">
            <div
              class="theme-card p-3 border rounded h-100"
              :class="{ 'theme-card--selected': selectedThemeId === theme.id }"
              @click="selectedThemeId = theme.id"
            >
              <div class="d-flex align-items-center mb-2">
                <span class="swatch" :style="{ background: theme.colors.primary }"></span>
                <span class="swatch" :style="{ background: theme.colors.secondary }"></span>
                <span class="swatch" :style="{ background: theme.colors.accent }"></span>
              </div>
              <strong>{{ theme.name }}</strong>
              <p class="small text-muted mb-0">{{ theme.description }}</p>
              <span v-if="selectedThemeId === theme.id" class="badge badge-primary mt-2">নির্বাচিত</span>
            </div>
          </div>
        </div>
        <button type="button" class="btn btn-primary" :disabled="applyingTheme || !selectedThemeId" @click="confirmApplyTheme">
          <span v-if="applyingTheme" class="spinner-border spinner-border-sm mr-1"></span>
          <i v-else class="fas fa-check-circle mr-1"></i> শুধু থিম প্রয়োগ করুন
        </button>
      </div>
    </div>

    <!-- MENU SECTION -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white font-weight-bold d-flex justify-content-between align-items-center">
        <span><i class="fas fa-bars mr-2 text-primary"></i> মেনু টেমপ্লেট নির্বাচন করুন</span>
        <span v-if="hasMenuCustomization" class="badge badge-warning">মেনু ইতিমধ্যে প্রয়োগকৃত</span>
      </div>
      <div class="card-body">
        <div v-if="!menuTemplates.length" class="text-muted">কোনো সক্রিয় মেনু টেমপ্লেট পাওয়া যায়নি।</div>
        <div class="custom-control custom-radio mb-2" v-for="mt in menuTemplates" :key="mt.id">
          <input :id="'menu-'+mt.id" v-model="selectedMenuTemplateId" type="radio" class="custom-control-input" :value="mt.id">
          <label class="custom-control-label" :for="'menu-'+mt.id">{{ mt.name }}</label>
        </div>
        <button type="button" class="btn btn-primary mt-2" :disabled="applyingMenu || !selectedMenuTemplateId" @click="confirmApplyMenu">
          <span v-if="applyingMenu" class="spinner-border spinner-border-sm mr-1"></span>
          <i v-else class="fas fa-check-circle mr-1"></i> শুধু মেনু প্রয়োগ করুন
        </button>
      </div>
    </div>

    <!-- PAGES SECTION -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white font-weight-bold d-flex justify-content-between align-items-center">
        <span><i class="fas fa-file-alt mr-2 text-primary"></i> ডিফল্ট পৃষ্ঠাসমূহ</span>
        <span v-if="hasPagesCustomization" class="badge badge-warning">পৃষ্ঠা ইতিমধ্যে আছে</span>
      </div>
      <div class="card-body">
        <div v-if="!pageTemplates.length" class="text-muted">কোনো সক্রিয় পৃষ্ঠা টেমপ্লেট পাওয়া যায়নি।</div>
        <div class="d-flex justify-content-end mb-2" v-if="pageTemplates.length">
          <button type="button" class="btn btn-link btn-sm p-0 mr-3" @click="selectedPageTemplateIds = pageTemplates.map(p => p.id)">সব নির্বাচন করুন</button>
          <button type="button" class="btn btn-link btn-sm p-0" @click="selectedPageTemplateIds = []">সব বাতিল করুন</button>
        </div>
        <div class="custom-control custom-checkbox mb-2" v-for="pt in pageTemplates" :key="pt.id">
          <input :id="'page-'+pt.id" v-model="selectedPageTemplateIds" type="checkbox" class="custom-control-input" :value="pt.id">
          <label class="custom-control-label" :for="'page-'+pt.id">{{ pt.title_bn || pt.title }}</label>
        </div>
        <button type="button" class="btn btn-primary mt-2" :disabled="applyingPages || !selectedPageTemplateIds.length" @click="confirmApplyPages">
          <span v-if="applyingPages" class="spinner-border spinner-border-sm mr-1"></span>
          <i v-else class="fas fa-check-circle mr-1"></i> শুধু নির্বাচিত পৃষ্ঠাসমূহ প্রয়োগ করুন
        </button>
      </div>
    </div>
  </div>
  <div v-else class="text-center py-5 text-muted">
    <span class="spinner-border"></span> লোড হচ্ছে...
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
      applyingTheme: false,
      applyingMenu: false,
      applyingPages: false,
      themes: [],
      menuTemplates: [],
      pageTemplates: [],
      current: {},
      hasThemeCustomization: false,
      hasMenuCustomization: false,
      hasPagesCustomization: false,
      selectedThemeId: null,
      selectedMenuTemplateId: null,
      selectedPageTemplateIds: [],
    };
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    async fetchData() {
      try {
        const resp = await axios.get(`/principal/institute/${this.schoolId}/frontend/website-template/data`);
        this.themes = resp.data.themes || [];
        this.menuTemplates = resp.data.menuTemplates || [];
        this.pageTemplates = resp.data.pageTemplates || [];
        this.current = resp.data.current || {};
        this.hasThemeCustomization = !!resp.data.hasThemeCustomization;
        this.hasMenuCustomization = !!resp.data.hasMenuCustomization;
        this.hasPagesCustomization = !!resp.data.hasPagesCustomization;

        this.selectedThemeId = this.current.theme_id || this.themes.find((t) => t.is_default)?.id || this.themes[0]?.id || null;
        this.selectedMenuTemplateId = this.current.applied_menu_template_id || this.menuTemplates[0]?.id || null;
        this.selectedPageTemplateIds = this.pageTemplates.map((p) => p.id);
      } catch (e) {
        if (window.toastr) window.toastr.error('ডেটা লোড করতে সমস্যা হয়েছে');
      } finally {
        this.loaded = true;
      }
    },
    confirmApplyTheme() {
      const msg = this.hasThemeCustomization
        ? 'এটি শুধু থিম (রং/প্যাটার্ন) পরিবর্তন করবে। আপনার মেনু ও পৃষ্ঠার কনটেন্ট অপরিবর্তিত থাকবে। এগিয়ে যেতে চান?'
        : 'নির্বাচিত থিম প্রয়োগ করতে চান?';
      if (!confirm(msg)) return;
      this.applyTheme();
    },
    async applyTheme() {
      this.applyingTheme = true;
      try {
        const resp = await axios.post(`/principal/institute/${this.schoolId}/frontend/website-template/apply-theme`, {
          theme_id: this.selectedThemeId,
        });
        if (window.toastr) window.toastr.success(resp.data.message);
        await this.fetchData();
      } catch (e) {
        if (window.toastr) window.toastr.error(e.response?.data?.message || 'থিম প্রয়োগ করতে সমস্যা হয়েছে');
      } finally {
        this.applyingTheme = false;
      }
    },
    confirmApplyMenu() {
      const msg = this.hasMenuCustomization
        ? 'এটি আপনার বর্তমান মেনু প্রতিস্থাপন করবে (থিম বা পৃষ্ঠায় কোনো প্রভাব পড়বে না)। আপনি কি নিশ্চিত?'
        : 'নির্বাচিত মেনু টেমপ্লেট প্রয়োগ করতে চান?';
      if (!confirm(msg)) return;
      this.applyMenu();
    },
    async applyMenu() {
      this.applyingMenu = true;
      try {
        const resp = await axios.post(`/principal/institute/${this.schoolId}/frontend/website-template/apply-menu`, {
          menu_template_id: this.selectedMenuTemplateId,
        });
        if (window.toastr) window.toastr.success(resp.data.message);
        await this.fetchData();
      } catch (e) {
        if (window.toastr) window.toastr.error(e.response?.data?.message || 'মেনু প্রয়োগ করতে সমস্যা হয়েছে');
      } finally {
        this.applyingMenu = false;
      }
    },
    confirmApplyPages() {
      const msg = this.hasPagesCustomization
        ? 'নির্বাচিত পৃষ্ঠাগুলোর সাথে মিলে যাওয়া বিদ্যমান পৃষ্ঠাগুলোর কনটেন্ট প্রতিস্থাপিত হবে (থিম বা মেনুতে কোনো প্রভাব পড়বে না)। এগিয়ে যেতে চান?'
        : 'নির্বাচিত পৃষ্ঠাসমূহ প্রয়োগ করতে চান?';
      if (!confirm(msg)) return;
      this.applyPages();
    },
    async applyPages() {
      this.applyingPages = true;
      try {
        const resp = await axios.post(`/principal/institute/${this.schoolId}/frontend/website-template/apply-pages`, {
          page_template_ids: this.selectedPageTemplateIds,
        });
        if (window.toastr) window.toastr.success(resp.data.message);
        await this.fetchData();
      } catch (e) {
        if (window.toastr) window.toastr.error(e.response?.data?.message || 'পৃষ্ঠা প্রয়োগ করতে সমস্যা হয়েছে');
      } finally {
        this.applyingPages = false;
      }
    },
  },
};
</script>

<style scoped>
.theme-card {
  cursor: pointer;
  transition: all 0.15s;
}
.theme-card:hover {
  border-color: #6366f1 !important;
}
.theme-card--selected {
  border-color: #4f46e5 !important;
  box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.25);
}
.swatch {
  display: inline-block;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  margin-right: 4px;
  border: 1px solid rgba(0, 0, 0, 0.15);
}
</style>
