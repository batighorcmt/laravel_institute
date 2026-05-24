/**
 * Laravel 12 + Vite + AdminLTE
 * FINAL stable app.js
 */

// -------------------------
// jQuery (MUST be first)
// -------------------------
import $ from 'jquery';
window.$ = window.jQuery = $;

import './bootstrap';

// -------------------------
// Bootstrap
// -------------------------
import 'bootstrap';

// -------------------------
// AdminLTE
// -------------------------
import 'admin-lte/dist/js/adminlte';

// -------------------------
// ✅ Select2 (FORCE bind)
// -------------------------
import select2 from 'select2/dist/js/select2.full.js';
select2(window.$);

// -------------------------
// Toastr
// -------------------------
import toastr from 'toastr';
window.toastr = toastr;

// -------------------------
// Chart.js
// -------------------------
import { Chart } from 'chart.js/auto';
window.Chart = Chart;

// -------------------------
// Vue (NEW)
// -------------------------
import { createApp } from 'vue';
import FrontendSiteHeader from './components/frontend/FrontendSiteHeader.vue';
import FrontendSiteFooter from './components/frontend/FrontendSiteFooter.vue';
import FrontendSiteNav from './components/frontend/FrontendSiteNav.vue';

const registerFrontendChromeComponents = (vueApp) => {
    vueApp.component('FrontendSiteNav', FrontendSiteNav);
    vueApp.component('FrontendSiteHeader', FrontendSiteHeader);
    vueApp.component('FrontendSiteFooter', FrontendSiteFooter);
};

const createFrontendChromeApp = (rootComponent, props) => {
    const chromeApp = createApp(rootComponent, props);
    registerFrontendChromeComponents(chromeApp);

    return chromeApp;
};

const app = createApp({});
registerFrontendChromeComponents(app);

// Expose CKEditor5 Classic build via the app bundle so Blade can initialize without CDN
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
window.ClassicEditor = ClassicEditor;

// Auto-register components
const modules = import.meta.glob('./components/**/*.vue', { eager: true });
Object.entries(modules).forEach(([path, definition]) => {
    const componentName = path.split('/').pop().replace(/\.\w+$/, '');
    app.component(componentName, definition.default);
});

// Mount Vue
const mountApp = () => {
    const el = document.getElementById('app');
    if (el && !el.__vue_app__) {
        app.mount('#app');
    }
};

const mountFrontendChrome = () => {
    const data = window.__FRONTEND_CHROME__;
    if (!data) {
        return;
    }

    const headerEl = document.getElementById('frontend-chrome-header');
    const footerEl = document.getElementById('frontend-chrome-footer');

    if (headerEl && !headerEl.__vue_app__) {
        createFrontendChromeApp(FrontendSiteHeader, {
            school: data.school,
            settings: data.settings || {},
            menuItems: data.menuItems || [],
            marqueeNotices: data.marqueeNotices || [],
            storageBase: data.storageBase || '/storage',
            showMarquee: data.showMarquee !== false,
            showAdmissionCta: data.showAdmissionCta !== false,
        }).mount(headerEl);
    }

    if (footerEl && !footerEl.__vue_app__) {
        createFrontendChromeApp(FrontendSiteFooter, {
            school: data.school,
            settings: data.settings || {},
            menuItems: data.footerMenu || [],
        }).mount(footerEl);
    }
};

const mountAll = () => {
    mountApp();
    mountFrontendChrome();
};

if (document.readyState === 'loading') {
    window.addEventListener('DOMContentLoaded', mountAll);
} else {
    mountAll();
}

// -------------------------
// App Ready
// -------------------------
$(document).ready(function () {

    /* Tooltip */
    $('[data-toggle="tooltip"]').tooltip();

    /* ✅ Select2 */
    if ($.fn.select2) {
        $('.select2').select2({
            width: '100%'
        });
    }

    /* Toastr config */
    toastr.options = {
        closeButton: true,
        progressBar: true,
        timeOut: 3000,
        positionClass: 'toast-top-right',
    };

});
