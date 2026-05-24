<template>
  <ul :class="rootClass">
    <li
      v-for="item in items"
      :key="item.id"
      :class="itemClass"
      class="site-nav-item"
    >
      <template v-if="hasChildren(item)">
        <div class="relative" :class="nested ? 'w-full' : ''">
          <button
            type="button"
            class="site-nav-parent flex items-center gap-1 text-left w-full"
            :class="linkClass"
            :aria-expanded="isOpen(item.id) ? 'true' : 'false'"
            @click="toggleOpen(item.id)"
          >
            <span>{{ item.label }}</span>
            <i
              class="fas fa-chevron-down text-[10px] opacity-80 transition-transform duration-200"
              :class="{ 'rotate-180': isOpen(item.id) }"
            ></i>
          </button>
          <div
            v-show="isOpen(item.id)"
            class="site-nav-dropdown"
            :class="nested ? 'site-nav-dropdown-nested' : 'site-nav-dropdown-root'"
          >
            <frontend-site-nav
              :items="item.children"
              nested
              :variant="variant"
              :open-menus="openMenus"
              @toggle="$emit('toggle', $event)"
            />
          </div>
        </div>
      </template>
      <a
        v-else
        :href="item.url"
        :target="item.target === '_blank' ? '_blank' : undefined"
        :rel="item.target === '_blank' ? 'noopener noreferrer' : undefined"
        :class="linkClass"
        class="block text-left"
      >
        {{ item.label }}
      </a>
    </li>
  </ul>
</template>

<script>
export default {
  name: 'FrontendSiteNav',
  props: {
    items: { type: Array, default: () => [] },
    nested: { type: Boolean, default: false },
    variant: { type: String, default: 'header' },
    openMenus: { type: Object, default: null },
  },
  emits: ['toggle'],
  data() {
    return {
      localOpenMenus: {},
    };
  },
  computed: {
    openState() {
      return this.openMenus ?? this.localOpenMenus;
    },
    rootClass() {
      if (this.nested) {
        return 'site-nav-nested-list';
      }
      if (this.variant === 'footer') {
        return 'flex flex-wrap justify-start gap-x-6 gap-y-2 text-sm font-bold';
      }
      return 'flex flex-col md:flex-row md:flex-wrap md:justify-start md:items-stretch md:gap-x-1 w-full';
    },
    itemClass() {
      if (this.nested || this.variant === 'footer') {
        return '';
      }
      return 'relative shrink-0';
    },
    linkClass() {
      if (this.nested) {
        return 'site-nav-nested-link';
      }
      if (this.variant === 'footer') {
        return 'text-slate-400 hover:text-indigo-400 transition-colors py-1';
      }
      return 'site-nav-top-link';
    },
  },
  mounted() {
    if (!this.nested && this.variant === 'header') {
      document.addEventListener('click', this.onDocumentClick);
    }
  },
  beforeUnmount() {
    document.removeEventListener('click', this.onDocumentClick);
  },
  methods: {
    hasChildren(item) {
      return Array.isArray(item.children) && item.children.length > 0;
    },
    isOpen(id) {
      return !!this.openState[id];
    },
    toggleOpen(id) {
      const currentlyOpen = this.isOpen(id);
      const next = {};

      if (!currentlyOpen) {
        next[id] = true;
      }

      if (this.openMenus !== null) {
        this.$emit('toggle', next);
      } else {
        this.localOpenMenus = next;
      }
    },
    onDocumentClick(event) {
      if (!this.$el.contains(event.target)) {
        this.localOpenMenus = {};
        this.$emit('toggle', {});
      }
    },
  },
};
</script>

<style scoped>
.site-nav-top-link {
  display: block;
  text-align: left;
  padding: 0.85rem 0.9rem;
  font-size: 0.875rem;
  font-weight: 700;
  text-transform: none;
  letter-spacing: normal;
  word-spacing: normal;
  color: #e2e8f0;
  transition: color 0.2s, background 0.2s;
  white-space: nowrap;
}
.site-nav-top-link:hover,
.site-nav-parent.site-nav-top-link:hover {
  color: #fff;
  background: rgba(99, 102, 241, 0.15);
}
.site-nav-parent {
  background: transparent;
  border: none;
  cursor: pointer;
}
.site-nav-dropdown-root {
  position: absolute;
  left: 0;
  top: 100%;
  min-width: 220px;
  z-index: 60;
  padding-top: 0.25rem;
}
.site-nav-dropdown-nested {
  position: static;
  padding-left: 0.75rem;
  padding-top: 0.25rem;
}
.site-nav-nested-list {
  list-style: none;
  margin: 0;
  padding: 0.35rem 0;
  background: #1e293b;
  border-radius: 0.75rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 12px 40px -8px rgba(0, 0, 0, 0.5);
}
.site-nav-nested-link {
  display: block;
  text-align: left;
  padding: 0.55rem 1rem;
  font-size: 0.9rem;
  font-weight: 600;
  letter-spacing: normal;
  color: #e2e8f0;
  transition: background 0.15s, color 0.15s;
  white-space: nowrap;
}
.site-nav-nested-link:hover {
  background: rgba(99, 102, 241, 0.35);
  color: #fff;
}
</style>
