// AdminLTE 3.2 setup (jQuery + Bootstrap 4 + Popper + OverlayScrollbars)
import $ from 'jquery';
import 'popper.js';
import 'bootstrap';
import 'overlayscrollbars/js/jquery.overlayScrollbars.min.js';
import 'admin-lte';
import 'select2';
import toastr from 'toastr';
import { Chart } from 'chart.js/auto';

// Expose helpful libs globally if needed
window.$ = window.jQuery = $;
window.toastr = toastr;
window.Chart = Chart;

// Expose jQuery globally (AdminLTE plugins expect window.$)
$(function () {
    // Bootstrap 4 tooltip init
    $('[data-toggle="tooltip"]').tooltip();

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
