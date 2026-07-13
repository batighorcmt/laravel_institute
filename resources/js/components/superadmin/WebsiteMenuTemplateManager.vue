<template>
  <div v-if="loaded">
    <div class="card shadow-sm border-0 mb-3">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0 font-weight-bold text-primary">
          <i class="fas fa-bars mr-2"></i> ডিফল্ট মেনু টেমপ্লেট
        </h3>
        <button class="btn btn-primary btn-sm" @click="createTemplate">
          <i class="fas fa-plus mr-1"></i> নতুন টেমপ্লেট
        </button>
      </div>
      <div class="card-body">
        <div class="form-row align-items-end">
          <div class="col-md-5 form-group mb-md-0">
            <label class="small font-weight-bold text-muted">টেমপ্লেট নির্বাচন</label>
            <select v-model="activeTemplateId" class="form-control">
              <option v-for="t in menuTemplates" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
          </div>
          <div class="col-md-4 form-group mb-md-0">
            <label class="small font-weight-bold text-muted">নাম</label>
            <input v-model="activeTemplate.name" type="text" class="form-control">
          </div>
          <div class="col-md-3 d-flex">
            <button type="button" class="btn btn-sm mr-2" :class="activeTemplate.is_active ? 'btn-success' : 'btn-secondary'" @click="toggleActive">
              {{ activeTemplate.is_active ? 'চালু' : 'বাতিল' }}
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm" @click="deleteTemplate" :disabled="menuTemplates.length < 1">
              <i class="fas fa-trash-alt"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="activeTemplate.id" class="row">
      <div class="col-lg-4 mb-4">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-light font-weight-bold">
            <i class="fas fa-plus-circle mr-1 text-primary"></i> মেনুতে যোগ করুন
          </div>
          <div class="card-body p-0">
            <div class="border-bottom">
              <button type="button" class="btn btn-link btn-block text-left font-weight-bold px-3 py-3" @click="toggleAddPanel('pages')">
                <i class="fas fa-file-alt mr-2 text-muted"></i> Page Templates
                <i class="fas float-right mt-1" :class="openAddPanel === 'pages' ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
              </button>
              <div v-show="openAddPanel === 'pages'" class="px-3 pb-3">
                <label v-for="p in pageTemplates" :key="'p-'+p.id" class="d-flex align-items-center mb-1 small">
                  <input v-model="selectedPageIds" type="checkbox" class="mr-2" :value="p.id">
                  {{ p.title }}
                </label>
                <p v-if="!pageTemplates.length" class="text-muted small mb-0">কোনো পৃষ্ঠা টেমপ্লেট নেই।</p>
                <button type="button" class="btn btn-sm btn-primary btn-block mt-2" @click="addSelectedPages">Add to Menu</button>
              </div>
            </div>
            <div class="border-bottom">
              <button type="button" class="btn btn-link btn-block text-left font-weight-bold px-3 py-3" @click="toggleAddPanel('sections')">
                <i class="fas fa-home mr-2 text-muted"></i> হোমপেজ সেকশন
                <i class="fas float-right mt-1" :class="openAddPanel === 'sections' ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
              </button>
              <div v-show="openAddPanel === 'sections'" class="px-3 pb-3">
                <label v-for="sec in sections" :key="'s-'+sec.id" class="d-flex align-items-center mb-1 small">
                  <input v-model="selectedSectionIds" type="checkbox" class="mr-2" :value="sec.value">
                  {{ sec.label }}
                </label>
                <button type="button" class="btn btn-sm btn-success btn-block mt-2" @click="addSelectedSections">Add to Menu</button>
              </div>
            </div>
            <div>
              <button type="button" class="btn btn-link btn-block text-left font-weight-bold px-3 py-3" @click="toggleAddPanel('custom')">
                <i class="fas fa-link mr-2 text-muted"></i> Custom Links
                <i class="fas float-right mt-1" :class="openAddPanel === 'custom' ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
              </button>
              <div v-show="openAddPanel === 'custom'" class="px-3 pb-3">
                <div class="form-group mb-2">
                  <label class="small font-weight-bold">URL</label>
                  <input v-model="customUrl" type="text" class="form-control form-control-sm">
                </div>
                <div class="form-group mb-2">
                  <label class="small font-weight-bold">Link Text</label>
                  <input v-model="customLabel" type="text" class="form-control form-control-sm">
                </div>
                <button type="button" class="btn btn-sm btn-secondary btn-block" @click="addCustomLink">Add to Menu</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-3">
          <div class="card-body">
            <div class="form-row align-items-end">
              <div class="col-md-6 form-group mb-md-0">
                <label class="small font-weight-bold text-muted">সাব-মেনু নির্বাচন</label>
                <select v-model="activeMenuId" class="form-control">
                  <option v-for="menu in menus" :key="menu.id" :value="menu.id">{{ menu.name }}</option>
                </select>
              </div>
              <div class="col-md-6 form-group mb-md-0">
                <label class="small font-weight-bold text-muted">সাব-মেনুর নাম</label>
                <input v-model="activeMenu.name" type="text" class="form-control">
              </div>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0 mb-3">
          <div class="card-header bg-white font-weight-bold d-flex justify-content-between align-items-center">
            <span>Menu Structure</span>
            <span class="badge badge-secondary">{{ activeMenuItems.length }} items</span>
          </div>
          <div class="card-body menu-structure-panel">
            <p v-if="!activeMenuItems.length" class="text-muted text-center py-4 mb-0">বাম পাশ থেকে আইটেম যোগ করুন।</p>
            <menu-item-list v-if="activeMenuItems.length" v-model:items="activeMenuItems" :depth="0" @remove="removeItem" />
          </div>
        </div>

        <div class="card shadow-sm border-0 mb-3">
          <div class="card-header bg-light font-weight-bold">Menu Locations</div>
          <div class="card-body">
            <div class="form-row">
              <div class="col-md-6">
                <label class="font-weight-bold small">Header Menu</label>
                <select v-model="locations.header" class="form-control">
                  <option value="">— নির্বাচন করুন —</option>
                  <option v-for="menu in menus" :key="'h-'+menu.id" :value="menu.id">{{ menu.name }}</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="font-weight-bold small">Footer Menu</label>
                <select v-model="locations.footer" class="form-control">
                  <option value="">— নির্বাচন করুন —</option>
                  <option v-for="menu in menus" :key="'f-'+menu.id" :value="menu.id">{{ menu.name }}</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <button type="button" class="btn btn-primary btn-lg" :disabled="saving" @click="save">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-1"></span> Save Template
        </button>
      </div>
    </div>
  </div>
  <div v-else class="text-center py-5 text-muted">
    <span class="spinner-border"></span> লোড হচ্ছে...
  </div>
</template>

<script>
import MenuItemList from '../menu/MenuItemList.vue';

function newId() {
  return 'item-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8);
}

function emptyItem(overrides = {}) {
  return { id: newId(), label: '', type: 'custom', url: '', section: null, page_id: null, page_slug: null, target: '_self', children: [], ...overrides };
}

function defaultConfig() {
  return {
    menus: [
      { id: 'menu-primary', name: 'Primary Menu', items: [] },
      { id: 'menu-footer', name: 'Footer Menu', items: [] },
    ],
    locations: { header: 'menu-primary', footer: 'menu-footer' },
  };
}

export default {
  components: { MenuItemList },
  props: {
    apiUrl: { type: String, required: true },
    csrfToken: { type: String, required: true },
  },
  data() {
    return {
      loaded: false,
      saving: false,
      menuTemplates: [],
      pageTemplates: [],
      sections: [],
      activeTemplateId: null,
      menus: [],
      locations: {},
      activeMenuId: '',
      openAddPanel: 'pages',
      selectedPageIds: [],
      selectedSectionIds: [],
      customUrl: '',
      customLabel: '',
    };
  },
  computed: {
    activeTemplate() {
      return this.menuTemplates.find((t) => t.id === this.activeTemplateId) || {};
    },
    activeMenu() {
      return this.menus.find((m) => m.id === this.activeMenuId) || this.menus[0] || { id: '', name: '', items: [] };
    },
    activeMenuItems: {
      get() {
        const menu = this.menus.find((m) => m.id === this.activeMenuId);
        return menu ? menu.items : [];
      },
      set(items) {
        const menu = this.menus.find((m) => m.id === this.activeMenuId);
        if (menu) menu.items = items;
      },
    },
  },
  watch: {
    activeTemplateId() {
      this.loadActiveConfig();
    },
  },
  mounted() {
    this.fetchData();
  },
  methods: {
    toggleAddPanel(panel) {
      this.openAddPanel = this.openAddPanel === panel ? '' : panel;
    },
    async fetchData() {
      try {
        const resp = await fetch(this.apiUrl, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await resp.json();
        this.menuTemplates = data.menuTemplates || [];
        this.pageTemplates = data.pageTemplates || [];
        this.sections = data.sections || [];
        if (this.menuTemplates.length) {
          this.activeTemplateId = this.menuTemplates[0].id;
          this.loadActiveConfig();
        }
      } finally {
        this.loaded = true;
      }
    },
    loadActiveConfig() {
      const config = this.activeTemplate.config || defaultConfig();
      this.menus = (config.menus || []).map((m) => ({ ...m, items: this.normalizeItems(m.items || []) }));
      this.locations = { ...(config.locations || {}) };
      this.activeMenuId = this.menus[0]?.id || '';
    },
    normalizeItems(items) {
      return items.map((item) => ({ ...item, id: item.id || newId(), children: this.normalizeItems(item.children || []), expanded: false }));
    },
    async createTemplate() {
      const name = prompt('নতুন টেমপ্লেটের নাম?', 'নতুন মেনু টেমপ্লেট');
      if (!name) return;
      const resp = await fetch(this.apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ name }),
      });
      const data = await resp.json();
      if (resp.ok) {
        await this.fetchData();
        this.activeTemplateId = data.menuTemplate.id;
        this.loadActiveConfig();
        if (window.toastr) window.toastr.success(data.message);
      }
    },
    async toggleActive() {
      const resp = await fetch(`${this.apiUrl}/${this.activeTemplateId}/toggle`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': this.csrfToken, Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await resp.json();
      if (resp.ok) {
        if (window.toastr) window.toastr.success(data.message);
        this.fetchData();
      }
    },
    async deleteTemplate() {
      if (!confirm('এই মেনু টেমপ্লেট মুছে ফেলবেন?')) return;
      const resp = await fetch(`${this.apiUrl}/${this.activeTemplateId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': this.csrfToken, Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const data = await resp.json();
      if (resp.ok) {
        if (window.toastr) window.toastr.success(data.message);
        this.fetchData();
      } else {
        alert(data.message || 'মুছে ফেলা সম্ভব হয়নি।');
      }
    },
    addSelectedPages() {
      this.selectedPageIds.forEach((id) => {
        const p = this.pageTemplates.find((x) => x.id === id);
        if (!p) return;
        this.activeMenuItems.push(emptyItem({ label: p.title, type: 'page', page_slug: p.default_slug }));
      });
      this.selectedPageIds = [];
    },
    addSelectedSections() {
      this.selectedSectionIds.forEach((val) => {
        const sec = this.sections.find((s) => s.value === val);
        if (!sec) return;
        this.activeMenuItems.push(emptyItem({ label: sec.label, type: 'section', section: sec.value }));
      });
      this.selectedSectionIds = [];
    },
    addCustomLink() {
      const label = (this.customLabel || '').trim() || 'Custom Link';
      const url = (this.customUrl || '').trim() || '#';
      this.activeMenuItems.push(emptyItem({ label, type: 'custom', url }));
      this.customUrl = '';
      this.customLabel = '';
    },
    removeItem(itemId) {
      const removeFrom = (list) => {
        for (let i = 0; i < list.length; i++) {
          if (list[i].id === itemId) {
            list.splice(i, 1);
            return true;
          }
          if (list[i].children?.length && removeFrom(list[i].children)) return true;
        }
        return false;
      };
      removeFrom(this.activeMenuItems);
    },
    stripUiFields(items) {
      return items.map((item) => ({
        id: item.id,
        label: item.label,
        type: item.type,
        url: item.url || null,
        section: item.section || null,
        page_id: item.page_id || null,
        page_slug: item.page_slug || null,
        target: item.target || '_self',
        children: this.stripUiFields(item.children || []),
      }));
    },
    async save() {
      this.saving = true;
      try {
        const resp = await fetch(`${this.apiUrl}/${this.activeTemplateId}`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: JSON.stringify({
            name: this.activeTemplate.name,
            config: {
              menus: this.menus.map((m) => ({ id: m.id, name: m.name, items: this.stripUiFields(m.items) })),
              locations: this.locations,
            },
          }),
        });
        const data = await resp.json();
        if (resp.ok) {
          if (window.toastr) window.toastr.success(data.message);
          await this.fetchData();
        } else if (window.toastr) {
          window.toastr.error(data.message || 'সেভ করতে সমস্যা হয়েছে');
        }
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>

<style scoped>
.menu-structure-panel {
  min-height: 200px;
  background: #f8fafc;
  border-radius: 8px;
}
</style>
