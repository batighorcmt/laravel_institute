<template>
  <div class="frontend-menu-manager" v-if="loaded">
    <div class="row">
      <!-- Left: Add menu items -->
      <div class="col-lg-4 mb-4">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-light font-weight-bold">
            <i class="fas fa-plus-circle mr-1 text-primary"></i> মেনুতে যোগ করুন
          </div>
          <div class="card-body p-0">
            <div class="menu-add-panels">
              <!-- Pages -->
              <div class="border-bottom">
                <button
                  type="button"
                  class="btn btn-link btn-block text-left font-weight-bold px-3 py-3 menu-add-panel-toggle"
                  @click="toggleAddPanel('pages')"
                >
                  <i class="fas fa-file-alt mr-2 text-muted"></i> Pages
                  <i class="fas float-right mt-1" :class="openAddPanel === 'pages' ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
                <div v-show="openAddPanel === 'pages'" class="menu-add-panel-body">
                  <div class="px-3 pb-3">
                    <input v-model="pageSearch" type="text" class="form-control form-control-sm mb-2" placeholder="খুঁজুন...">
                    <div class="menu-pages-list border rounded p-2 mb-2" style="max-height: 180px; overflow-y: auto;">
                      <label v-for="page in filteredPages" :key="'p-'+page.id" class="d-flex align-items-center mb-1 small">
                        <input v-model="selectedPageIds" type="checkbox" class="mr-2" :value="page.id">
                        {{ page.title }}
                      </label>
                      <p v-if="!filteredPages.length" class="text-muted small mb-0 text-center py-2">কোনো পেজ নেই — Website → Pages থেকে পেজ তৈরি করুন।</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary btn-block" @click="addSelectedPages">Add to Menu</button>
                  </div>
                </div>
              </div>

              <!-- Homepage sections -->
              <div class="border-bottom">
                <button
                  type="button"
                  class="btn btn-link btn-block text-left font-weight-bold px-3 py-3 menu-add-panel-toggle"
                  @click="toggleAddPanel('sections')"
                >
                  <i class="fas fa-home mr-2 text-muted"></i> হোমপেজ সেকশন
                  <i class="fas float-right mt-1" :class="openAddPanel === 'sections' ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
                <div v-show="openAddPanel === 'sections'" class="menu-add-panel-body">
                  <div class="px-3 pb-3">
                    <label v-for="sec in sections" :key="'s-'+sec.id" class="d-flex align-items-center mb-1 small">
                      <input v-model="selectedSectionIds" type="checkbox" class="mr-2" :value="sec.value">
                      {{ sec.label }}
                    </label>
                    <button type="button" class="btn btn-sm btn-success btn-block mt-2" @click="addSelectedSections">Add to Menu</button>
                  </div>
                </div>
              </div>

              <!-- Custom links -->
              <div>
                <button
                  type="button"
                  class="btn btn-link btn-block text-left font-weight-bold px-3 py-3 menu-add-panel-toggle"
                  @click="toggleAddPanel('custom')"
                >
                  <i class="fas fa-link mr-2 text-muted"></i> Custom Links
                  <i class="fas float-right mt-1" :class="openAddPanel === 'custom' ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
                <div v-show="openAddPanel === 'custom'" class="menu-add-panel-body">
                  <div class="px-3 pb-3">
                    <div class="form-group mb-2">
                      <label class="small font-weight-bold">URL</label>
                      <input v-model="customUrl" type="text" class="form-control form-control-sm" placeholder="https://... বা /path">
                    </div>
                    <div class="form-group mb-2">
                      <label class="small font-weight-bold">Link Text</label>
                      <input v-model="customLabel" type="text" class="form-control form-control-sm" placeholder="লেবেল">
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary btn-block" @click="addCustomLink">Add to Menu</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Menu structure -->
      <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-3">
          <div class="card-body">
            <div class="form-row align-items-end">
              <div class="col-md-5 form-group mb-md-0">
                <label class="small font-weight-bold text-muted">মেনু নির্বাচন</label>
                <select v-model="activeMenuId" class="form-control">
                  <option v-for="menu in menus" :key="menu.id" :value="menu.id">{{ menu.name }}</option>
                </select>
              </div>
              <div class="col-md-4 form-group mb-md-0">
                <label class="small font-weight-bold text-muted">মেনুর নাম</label>
                <input v-model="activeMenu.name" type="text" class="form-control">
              </div>
              <div class="col-md-3">
                <button type="button" class="btn btn-outline-primary btn-block" @click="createMenu">
                  <i class="fas fa-plus"></i> নতুন মেনু
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="card shadow-sm border-0 mb-3">
          <div class="card-header bg-white font-weight-bold d-flex justify-content-between align-items-center">
            <span><i class="fas fa-bars mr-2 text-indigo"></i> Menu Structure</span>
            <span class="badge badge-secondary">{{ activeMenuItems.length }} items</span>
          </div>
          <div class="card-body menu-structure-panel">
            <p v-if="!activeMenuItems.length" class="text-muted text-center py-4 mb-0">বাম পাশ থেকে আইটেম যোগ করুন।</p>
            <menu-item-list
              v-if="activeMenuItems.length"
              v-model:items="activeMenuItems"
              :depth="0"
              @remove="removeItem"
            />
          </div>
        </div>

        <div class="card shadow-sm border-0 mb-3">
          <div class="card-header bg-light font-weight-bold">Menu Settings</div>
          <div class="card-body">
            <p class="small text-muted mb-3">কোন মেনু কোথায় দেখাবে তা নির্ধারণ করুন।</p>
            <div class="form-row">
              <div class="col-md-6">
                <label class="font-weight-bold small">Header Menu (হেডার)</label>
                <select v-model="locations.header" class="form-control">
                  <option value="">— নির্বাচন করুন —</option>
                  <option v-for="menu in menus" :key="'h-'+menu.id" :value="menu.id">{{ menu.name }}</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="font-weight-bold small">Footer Menu (ফুটার)</label>
                <select v-model="locations.footer" class="form-control">
                  <option value="">— নির্বাচন করুন —</option>
                  <option v-for="menu in menus" :key="'f-'+menu.id" :value="menu.id">{{ menu.name }}</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <button type="button" class="btn btn-primary btn-lg" :disabled="saving" @click="saveMenus">
          <span v-if="saving" class="spinner-border spinner-border-sm mr-1"></span>
          Save Menu
        </button>
        <button v-if="menus.length > 1" type="button" class="btn btn-outline-danger btn-lg ml-2" @click="deleteActiveMenu">Delete Menu</button>
      </div>
    </div>
  </div>
  <div v-else class="text-center py-5 text-muted">
    <span class="spinner-border"></span> লোড হচ্ছে...
  </div>
</template>

<script>
import draggable from 'vuedraggable';
import MenuItemList from './menu/MenuItemList.vue';

function newId() {
  return 'item-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8);
}

function emptyItem(overrides = {}) {
  return {
    id: newId(),
    label: '',
    type: 'custom',
    url: '',
    section: null,
    page_id: null,
    page_slug: null,
    target: '_self',
    children: [],
    ...overrides,
  };
}

export default {
  name: 'FrontendMenuManager',
  components: { draggable, MenuItemList },
  props: {
    schoolId: { type: Number, required: true },
  },
  data() {
    return {
      loaded: false,
      saving: false,
      menus: [],
      locations: { header: 'menu-primary', footer: 'menu-footer' },
      activeMenuId: 'menu-primary',
      pages: [],
      sections: [],
      pageSearch: '',
      selectedPageIds: [],
      selectedSectionIds: [],
      customUrl: '',
      customLabel: '',
      openAddPanel: 'pages',
    };
  },
  computed: {
    activeMenu() {
      return this.menus.find(m => m.id === this.activeMenuId) || this.menus[0] || { id: '', name: '', items: [] };
    },
    activeMenuItems: {
      get() {
        const menu = this.menus.find(m => m.id === this.activeMenuId);
        return menu ? menu.items : [];
      },
      set(items) {
        const menu = this.menus.find(m => m.id === this.activeMenuId);
        if (menu) {
          menu.items = items;
        }
      },
    },
    filteredPages() {
      const q = (this.pageSearch || '').trim().toLowerCase();
      if (!q) return this.pages;
      return this.pages.filter(p => (p.title || '').toLowerCase().includes(q));
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
        const res = await axios.get(`/principal/institute/${this.schoolId}/frontend/menus/data`);
        const data = res.data.frontend_menus;
        this.menus = (data.menus || []).map(m => ({
          ...m,
          items: this.normalizeItems(m.items || []),
        }));
        this.locations = { ...data.locations };
        this.pages = res.data.pages || [];
        this.sections = res.data.sections || [];
        if (this.menus.length && !this.menus.find(m => m.id === this.activeMenuId)) {
          this.activeMenuId = this.menus[0].id;
        }
      } catch (e) {
        if (window.toastr) window.toastr.error('ডেটা লোড করতে সমস্যা হয়েছে');
      } finally {
        this.loaded = true;
      }
    },
    normalizeItems(items) {
      return items.map(item => ({
        ...item,
        id: item.id || newId(),
        children: this.normalizeItems(item.children || []),
        expanded: false,
      }));
    },
    createMenu() {
      const id = 'menu-' + Date.now();
      this.menus.push({ id, name: 'New Menu', items: [] });
      this.activeMenuId = id;
    },
    deleteActiveMenu() {
      if (!confirm('এই মেনু মুছে ফেলবেন?')) return;
      this.menus = this.menus.filter(m => m.id !== this.activeMenuId);
      if (this.locations.header === this.activeMenuId) this.locations.header = '';
      if (this.locations.footer === this.activeMenuId) this.locations.footer = '';
      this.activeMenuId = this.menus[0]?.id || '';
    },
    addSelectedPages() {
      this.selectedPageIds.forEach(pageId => {
        const page = this.pages.find(p => p.id === pageId);
        if (!page) return;
        this.activeMenuItems.push(emptyItem({
          label: page.title,
          type: 'page',
          page_id: page.id,
          page_slug: page.slug,
        }));
      });
      this.selectedPageIds = [];
    },
    addSelectedSections() {
      this.selectedSectionIds.forEach(section => {
        const sec = this.sections.find(s => s.value === section);
        if (!sec) return;
        this.activeMenuItems.push(emptyItem({
          label: sec.label,
          type: 'section',
          section: sec.value,
        }));
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
          if (list[i].children?.length && removeFrom(list[i].children)) {
            return true;
          }
        }
        return false;
      };
      removeFrom(this.activeMenuItems);
    },
    async saveMenus() {
      this.saving = true;
      try {
        await axios.post(`/principal/institute/${this.schoolId}/frontend/menus/data`, {
          menus: this.menus.map(m => ({
            id: m.id,
            name: m.name,
            items: this.stripUiFields(m.items),
          })),
          locations: this.locations,
        });
        if (window.toastr) window.toastr.success('মেনু সংরক্ষণ হয়েছে');
        await this.fetchData();
      } catch (e) {
        if (window.toastr) window.toastr.error(e.response?.data?.message || 'সেভ করতে সমস্যা হয়েছে');
      } finally {
        this.saving = false;
      }
    },
    stripUiFields(items) {
      return items.map(item => ({
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
  },
};
</script>

<style scoped>
.menu-structure-panel {
  min-height: 200px;
  background: #f8fafc;
  border-radius: 8px;
}
.menu-pages-list label:hover {
  background: #f1f5f9;
}
.menu-add-panel-toggle {
  text-decoration: none !important;
  color: #1e293b !important;
}
.menu-add-panel-toggle:hover {
  background: #f8fafc;
}
.menu-add-panel-body {
  display: block;
  background: #fff;
  border-top: 1px solid #e2e8f0;
}
</style>
