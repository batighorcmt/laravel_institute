@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    var textarea = document.querySelector('#cms_content_editor');
    if (!textarea) return;

    // Wait until ClassicEditor is available from Vite bundle
    function initEditor() {
        if (!window.ClassicEditor) {
            console.warn('ClassicEditor not yet available, retrying...');
            setTimeout(initEditor, 200);
            return;
        }

        window.ClassicEditor.create(textarea, {
            toolbar: [ 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'undo', 'redo' ],
            language: 'bn'
        }).then(editor => {
            // sync to textarea on change and before submit
            textarea.value = editor.getData();
            editor.model.document.on('change:data', () => textarea.value = editor.getData());
            const form = textarea.closest('form');
            if (form) form.addEventListener('submit', () => textarea.value = editor.getData());

            // style editor content area height
            try {
                var s = document.createElement('style');
                s.innerHTML = '.ck-editor__editable_inline { min-height: 520px !important; max-height: 1200px; overflow:auto; font-family: Hind Siliguri, sans-serif; font-size:16px; }';
                document.head.appendChild(s);
            } catch (e) { console.warn('Could not inject editor height style', e); }
        }).catch(err => console.error('ClassicEditor.create error:', err));
    }

    initEditor();
});
</script>
@endpush
