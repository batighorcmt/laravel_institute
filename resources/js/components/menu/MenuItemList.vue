<template>
  <draggable
    :list="items"
    item-key="id"
    handle=".drag-handle"
    :group="{ name: 'frontend-menu', pull: true, put: true }"
    :animation="200"
    class="menu-item-list"
    :class="['depth-' + depth, { 'menu-item-list--drop': !items.length && depth > 0 }]"
  >
    <template #item="{ element }">
      <div class="menu-item-block mb-2">
        <div
          class="menu-item-row d-flex align-items-center bg-white border rounded shadow-sm px-2 py-2"
          :class="{ 'border-left-submenu': depth > 0 }"
          :style="rowStyle"
        >
          <span class="drag-handle text-muted px-2 cursor-move" title="ড্র্যাগ করে সরান বা সাবমেনুর ভিতরে ছাড়ুন">
            <i class="fas fa-grip-vertical"></i>
          </span>
          <div class="flex-grow-1 min-width-0">
            <strong class="d-block text-truncate">
              <span v-if="depth > 0" class="text-indigo-500 mr-1">↳</span>
              {{ element.label || '(শিরোনাম নেই)' }}
            </strong>
            <small class="text-muted">{{ typeLabel(element) }}</small>
          </div>
          <button
            type="button"
            class="btn btn-sm btn-outline-indigo"
            title="সাবমেনু যোগ করুন"
            @click="addSubmenu(element)"
          >
            <i class="fas fa-level-down-alt"></i>
          </button>
          <button type="button" class="btn btn-sm btn-link text-secondary" title="সেটিংস" @click="element.expanded = !element.expanded">
            <i class="fas" :class="element.expanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
          </button>
          <button type="button" class="btn btn-sm btn-link text-danger" title="মুছুন" @click="$emit('remove', element.id)">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div v-show="element.expanded" class="menu-item-edit border rounded bg-light p-3 mt-1" :style="rowStyle">
          <div class="form-group mb-2">
            <label class="small font-weight-bold mb-0">Navigation Label</label>
            <input v-model="element.label" type="text" class="form-control form-control-sm">
          </div>
          <div v-if="element.type === 'custom'" class="form-group mb-2">
            <label class="small font-weight-bold mb-0">URL</label>
            <input v-model="element.url" type="text" class="form-control form-control-sm">
          </div>
          <div class="custom-control custom-checkbox mb-0">
            <input :id="'target-'+element.id" v-model="element.target" type="checkbox" class="custom-control-input" true-value="_blank" false-value="_self">
            <label class="custom-control-label small" :for="'target-'+element.id">নতুন ট্যাবে খুলুন</label>
          </div>
        </div>

        <div class="submenu-zone mt-1" :style="submenuZoneStyle">
          <menu-item-list
            v-model:items="element.children"
            :depth="depth + 1"
            @remove="$emit('remove', $event)"
          />
        </div>
      </div>
    </template>
  </draggable>
</template>

<script>
import draggable from 'vuedraggable';

function newSubmenuId() {
  return 'item-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8);
}

export default {
  name: 'MenuItemList',
  components: { draggable },
  props: {
    items: { type: Array, required: true },
    depth: { type: Number, default: 0 },
  },
  emits: ['update:items', 'remove'],
  computed: {
    rowStyle() {
      return { marginLeft: this.depth * 20 + 'px' };
    },
    submenuZoneStyle() {
      return { marginLeft: this.depth * 20 + 28 + 'px' };
    },
  },
  methods: {
    typeLabel(item) {
      const map = {
        home: 'হোম',
        section: 'হোমপেজ সেকশন',
        page: 'পেজ',
        blog: 'ব্লগ',
        custom: 'কাস্টম লিংক',
        admission: 'ভর্তি',
      };
      return map[item.type] || item.type;
    },
    ensureChildren(element) {
      if (!Array.isArray(element.children)) {
        element.children = [];
      }
    },
    addSubmenu(element) {
      this.ensureChildren(element);
      element.children.push({
        id: newSubmenuId(),
        label: 'নতুন সাবমেনু',
        type: 'custom',
        url: '#',
        section: null,
        page_id: null,
        page_slug: null,
        target: '_self',
        children: [],
        expanded: true,
      });
      element.expanded = true;
    },
  },
};
</script>

<style scoped>
.cursor-move { cursor: move; }
.border-left-submenu {
  border-left: 3px solid #818cf8 !important;
}
.submenu-zone {
  border-left: 2px dashed #cbd5e1;
  padding-left: 12px;
}
.menu-item-list--drop {
  min-height: 44px;
  border: 2px dashed #cbd5e1;
  border-radius: 6px;
  background: #f8fafc;
}
.menu-item-list.depth-0 > .menu-item-block:first-child > .menu-item-row {
  border-left: 3px solid #4f46e5 !important;
}
.btn-outline-indigo {
  color: #4f46e5;
  border-color: #c7d2fe;
}
.btn-outline-indigo:hover {
  background: #eef2ff;
  color: #4338ca;
}
</style>
