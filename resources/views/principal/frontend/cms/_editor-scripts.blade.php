@push('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.tinymce) return;
    tinymce.init({
        selector: '#cms_content_editor',
        height: 420,
        menubar: false,
        plugins: 'lists link image table code fullscreen',
        toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image table | code fullscreen',
        branding: false,
        language: 'bn',
        content_style: 'body { font-family: Hind Siliguri, sans-serif; font-size: 16px; }',
        setup: function (editor) {
            editor.on('change keyup blur', function () {
                editor.save();
            });
        }
    });
});
</script>
@endpush
