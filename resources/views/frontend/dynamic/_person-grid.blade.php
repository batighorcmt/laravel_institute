@php
    $people = $people ?? [];
    $placeholderIcon = $placeholderIcon ?? 'fa-user';
    $gridId = $gridId ?? 'person-grid-'.uniqid();
@endphp

<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 sm:gap-5" id="{{ $gridId }}">
    @forelse($people as $i => $person)
        <div
            class="person-card group bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-lg cursor-pointer transition-all duration-300 hover:-translate-y-1 p-4 sm:p-5 text-center"
            data-person-index="{{ $i }}"
        >
            <div class="relative w-16 h-16 sm:w-20 sm:h-20 mx-auto rounded-full overflow-hidden bg-gradient-to-br from-indigo-100 to-slate-200 ring-4 ring-slate-50 group-hover:ring-indigo-100 transition-all duration-300">
                @if(!empty($person['photo']))
                    <img src="{{ $person['photo'] }}" alt="{{ $person['name'] }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy">
                @else
                    <div class="w-full h-full flex items-center justify-center text-2xl sm:text-3xl text-indigo-300">
                        <i class="fas {{ $placeholderIcon }}"></i>
                    </div>
                @endif
            </div>
            <h3 class="mt-3 text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition-colors leading-tight">{{ $person['name'] }}</h3>
            <p class="text-[10px] font-bold text-indigo-500 uppercase tracking-wide mt-1 leading-tight">{{ $person['designation'] ?? '' }}</p>
        </div>
    @empty
        <p class="text-slate-500 col-span-full text-center py-10">কোনো তথ্য পাওয়া যায়নি।</p>
    @endforelse
</div>

<!-- Person Detail Modal (shared, vanilla JS) -->
<div id="{{ $gridId }}-modal" class="fixed inset-0 z-[999] hidden items-center justify-center p-4 bg-slate-900/75 backdrop-blur-sm">
    <div class="bg-white rounded-[32px] shadow-2xl w-full max-w-md overflow-hidden border border-slate-100 relative">
        <div class="relative h-32 bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500">
            <button type="button" class="person-modal-close absolute top-4 right-4 w-10 h-10 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="px-8 pb-8 -mt-16 relative">
            <div class="mx-auto w-28 h-28 rounded-[28px] overflow-hidden border-4 border-white shadow-2xl bg-slate-100" id="{{ $gridId }}-modal-photo-wrap">
                <!-- filled by JS -->
            </div>
            <div class="text-center mt-5">
                <h3 class="text-2xl font-black text-slate-800" id="{{ $gridId }}-modal-name"></h3>
                <p class="text-indigo-600 font-bold mt-1" id="{{ $gridId }}-modal-designation"></p>
            </div>
            <div class="mt-6 space-y-3" id="{{ $gridId }}-modal-contacts"></div>
            <button type="button" class="person-modal-close mt-6 w-full py-3 rounded-2xl text-sm font-black text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                বন্ধ করুন
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    var data = @json(array_values($people));
    var placeholderIcon = @json($placeholderIcon);
    var grid = document.getElementById(@json($gridId));
    var modal = document.getElementById(@json($gridId) + '-modal');
    if (!grid || !modal) return;

    var photoWrap = document.getElementById(@json($gridId) + '-modal-photo-wrap');
    var nameEl = document.getElementById(@json($gridId) + '-modal-name');
    var designationEl = document.getElementById(@json($gridId) + '-modal-designation');
    var contactsEl = document.getElementById(@json($gridId) + '-modal-contacts');

    function openModal(person) {
        photoWrap.innerHTML = person.photo
            ? '<img src="' + person.photo + '" alt="" class="w-full h-full object-cover">'
            : '<div class="w-full h-full flex items-center justify-center text-4xl text-indigo-300"><i class="fas ' + placeholderIcon + '"></i></div>';
        nameEl.textContent = person.name || '';
        designationEl.textContent = person.designation || '';

        var rows = '';
        if (person.phone) {
            rows += '<a href="tel:' + person.phone + '" class="flex items-center gap-3 p-4 rounded-2xl bg-slate-50 hover:bg-indigo-50 transition-colors text-slate-700 font-bold">' +
                '<span class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0"><i class="fas fa-phone-alt"></i></span>' +
                '<span>' + person.phone + '</span></a>';
        }
        if (person.email) {
            rows += '<a href="mailto:' + person.email + '" class="flex items-center gap-3 p-4 rounded-2xl bg-slate-50 hover:bg-purple-50 transition-colors text-slate-700 font-bold">' +
                '<span class="w-10 h-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center shrink-0"><i class="fas fa-envelope"></i></span>' +
                '<span class="break-all">' + person.email + '</span></a>';
        }
        contactsEl.innerHTML = rows;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    grid.querySelectorAll('.person-card').forEach(function (card) {
        card.addEventListener('click', function () {
            var idx = parseInt(card.getAttribute('data-person-index'), 10);
            if (data[idx]) openModal(data[idx]);
        });
    });

    modal.querySelectorAll('.person-modal-close').forEach(function (btn) {
        btn.addEventListener('click', closeModal);
    });
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
    });
})();
</script>
