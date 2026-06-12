<div class="print-overlay-settings no-print" id="printOverlaySettings">
    <button type="button" class="overlay-toggle-btn" id="overlayToggleBtn" title="সেটিংস">
        <i class="fas fa-cog"></i>
    </button>
    <div class="overlay-panel" id="overlayPanel" hidden>
        <div class="overlay-panel-title">প্রিন্ট সেটিংস</div>
        <label class="overlay-checkbox-label">
            <span>স্বাক্ষরের উপরে নোট (ঐচ্ছিক)</span>
        </label>
        <textarea
            id="signatureNoteInput"
            class="overlay-note-input"
            rows="3"
            placeholder="নোট লিখুন... (লিখলে পৃষ্ঠায় অটো দেখাবে)"
        ></textarea>
        <div class="overlay-actions">
            <button type="button" class="overlay-print-btn" onclick="window.print()">
                <i class="fas fa-print"></i> প্রিন্ট
            </button>
            <a href="{{ url()->previous() }}" class="overlay-back-btn">ফিরে যান</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggleBtn = document.getElementById('overlayToggleBtn');
    var panel = document.getElementById('overlayPanel');
    var noteInput = document.getElementById('signatureNoteInput');

    if (!toggleBtn || !panel || !noteInput) {
        return;
    }

    function updateNoteDisplays() {
        var text = noteInput.value.trim();
        var noteDisplays = document.querySelectorAll('.report-card-signature-note');

        noteDisplays.forEach(function (el) {
            if (text !== '') {
                el.textContent = text;
                el.classList.add('visible');
            } else {
                el.textContent = '';
                el.classList.remove('visible');
            }
        });
    }

    toggleBtn.addEventListener('click', function () {
        var isHidden = panel.hasAttribute('hidden');
        if (isHidden) {
            panel.removeAttribute('hidden');
            toggleBtn.classList.add('active');
        } else {
            panel.setAttribute('hidden', 'hidden');
            toggleBtn.classList.remove('active');
        }
    });

    noteInput.addEventListener('input', updateNoteDisplays);
    
    // Initialize state
    updateNoteDisplays();
});
</script>
