// AdminLTE 3.2 setup (jQuery + Bootstrap 4 + Popper + OverlayScrollbars)
import $ from 'jquery';
// Load plugins after exposing jQuery globally to ensure they attach to the same instance
const pluginsReady = Promise.all([
    import('popper.js'),
    import('bootstrap'),
    import('admin-lte'),
    import('select2/dist/js/select2.full')
]);
import toastr from 'toastr';
import { Chart } from 'chart.js/auto';

// Expose helpful libs globally if needed
window.$ = window.jQuery = $;
window.toastr = toastr;
window.Chart = Chart;

// Expose jQuery globally (AdminLTE plugins expect window.$)
pluginsReady.then(() => {
$(function () {
    // Bootstrap 4 tooltip init (guard if plugin present)
    if ($.fn && $.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    }

    // Initialize Select2 on any .select2 elements
    if ($.fn.select2) {
        $('.select2').select2({ width: '100%' });
    }

    // Theme toggle
    const applyTheme = (mode) => {
        const body = document.body;
        if (mode === 'dark') {
            body.classList.add('dark-mode');
        } else {
            body.classList.remove('dark-mode');
        }
    };
    const saved = localStorage.getItem('theme');
    if (saved) applyTheme(saved);
    $(document).on('click', '#themeToggle', function () {
        const current = localStorage.getItem('theme') === 'dark' ? 'light' : 'dark';
        localStorage.setItem('theme', current);
        applyTheme(current);
    });
});
});
