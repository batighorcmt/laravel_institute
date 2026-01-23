/**
 * Laravel 12 + Vite + AdminLTE
 * FINAL stable app.js
 */

// -------------------------
// jQuery (MUST be first)
// -------------------------
import $ from 'jquery';
window.$ = window.jQuery = $;

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
        console.log('✅ Select2 loaded successfully');
    } else {
        console.error('❌ Select2 still not loaded');
    }

    /* Toastr config */
    toastr.options = {
        closeButton: true,
        progressBar: true,
        timeOut: 3000,
        positionClass: 'toast-top-right',
    };

});
