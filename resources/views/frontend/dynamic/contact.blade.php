@php($contact = $dynamicData)

<div class="mb-8 text-center">
    <h1 class="text-3xl md:text-4xl font-black text-slate-800">যোগাযোগ</h1>
    <p class="text-slate-500 mt-2">আমাদের সাথে যোগাযোগ করুন অথবা নিচের ফরমের মাধ্যমে বার্তা পাঠান</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">

    <!-- Contact Info -->
    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 md:p-8 space-y-4">
            <h2 class="text-lg font-black text-slate-800 mb-2">যোগাযোগের তথ্য</h2>

            @if(!empty($contact['address']))
                <div class="flex items-start gap-3">
                    <span class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0"><i class="fas fa-map-marker-alt"></i></span>
                    <p class="text-slate-700 pt-1.5">{{ $contact['address'] }}</p>
                </div>
            @endif
            @if(!empty($contact['phone']))
                <div class="flex items-start gap-3">
                    <span class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0"><i class="fas fa-phone"></i></span>
                    <p class="text-slate-700 pt-1.5">{{ $contact['phone'] }}</p>
                </div>
            @endif
            @if(!empty($contact['mobile']))
                <div class="flex items-start gap-3">
                    <span class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0"><i class="fas fa-mobile-alt"></i></span>
                    <p class="text-slate-700 pt-1.5">{{ $contact['mobile'] }}</p>
                </div>
            @endif
            @if(!empty($contact['email']))
                <div class="flex items-start gap-3">
                    <span class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0"><i class="fas fa-envelope"></i></span>
                    <p class="text-slate-700 pt-1.5">{{ $contact['email'] }}</p>
                </div>
            @endif
            @if(!empty($contact['website']))
                <div class="flex items-start gap-3">
                    <span class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0"><i class="fas fa-globe"></i></span>
                    <a href="{{ $contact['website'] }}" target="_blank" class="text-indigo-600 font-semibold pt-1.5 hover:underline">{{ $contact['website'] }}</a>
                </div>
            @endif

            <div class="flex gap-3 pt-2">
                @if(!empty($contact['facebook_url']))
                    <a href="{{ $contact['facebook_url'] }}" target="_blank" class="text-indigo-600"><i class="fab fa-facebook fa-lg"></i></a>
                @endif
                @if(!empty($contact['youtube_url']))
                    <a href="{{ $contact['youtube_url'] }}" target="_blank" class="text-indigo-600"><i class="fab fa-youtube fa-lg"></i></a>
                @endif
            </div>
        </div>

        @if(!empty($contact['dshe_info_center']) || !empty($contact['dshe_info_mobile']) || !empty($contact['gro_name']) || !empty($contact['gro_mobile']) || !empty($contact['office_hours']))
            <div class="bg-emerald-50 rounded-2xl border border-emerald-100 p-6 md:p-8 space-y-3">
                <h2 class="text-lg font-black text-emerald-800 mb-2">
                    <i class="fas fa-shield-halved mr-1"></i> DSHE ২০২৫ — বাধ্যতামূলক তথ্য
                </h2>
                @if(!empty($contact['dshe_info_center']))
                    <p class="text-sm text-emerald-900"><span class="font-bold">তথ্যসেবা কেন্দ্র:</span> {{ $contact['dshe_info_center'] }}</p>
                @endif
                @if(!empty($contact['dshe_info_mobile']))
                    <p class="text-sm text-emerald-900"><span class="font-bold">তথ্যসেবা মোবাইল:</span> {{ $contact['dshe_info_mobile'] }}</p>
                @endif
                @if(!empty($contact['gro_name']))
                    <p class="text-sm text-emerald-900"><span class="font-bold">অভিযোগ প্রতিকার কর্মকর্তা (GRO):</span> {{ $contact['gro_name'] }}@if(!empty($contact['gro_designation'])) ({{ $contact['gro_designation'] }})@endif</p>
                @endif
                @if(!empty($contact['gro_mobile']))
                    <p class="text-sm text-emerald-900"><span class="font-bold">GRO মোবাইল:</span> {{ $contact['gro_mobile'] }}</p>
                @endif
                @if(!empty($contact['office_hours']))
                    <p class="text-sm text-emerald-900"><span class="font-bold">অফিস সময়:</span> {{ $contact['office_hours'] }}</p>
                @endif
            </div>
        @endif
    </div>

    <!-- Contact Form -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 md:p-8">
        <h2 class="text-lg font-black text-slate-800 mb-4">আমাদের বার্তা পাঠান</h2>

        <div id="contact-form-alert" class="hidden mb-4 p-4 rounded-xl text-sm font-semibold"></div>

        <form id="public-contact-form" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">নাম <span class="text-red-500">*</span></label>
                <input type="text" name="name" required class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:border-indigo-500">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">ইমেইল <span class="text-red-500">*</span></label>
                    <input type="email" name="email" required class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">ফোন</label>
                    <input type="text" name="phone" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:border-indigo-500">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">বিষয়</label>
                <select name="subject" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:border-indigo-500">
                    <option value="সাধারণ জিজ্ঞাসা">সাধারণ জিজ্ঞাসা</option>
                    <option value="ভর্তি সংক্রান্ত">ভর্তি সংক্রান্ত</option>
                    <option value="অভিযোগ">অভিযোগ</option>
                    <option value="অন্যান্য">অন্যান্য</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">বার্তা <span class="text-red-500">*</span></label>
                <textarea name="message" rows="5" required class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:border-indigo-500"></textarea>
            </div>
            <p class="text-xs text-slate-400">আপনার তথ্য শুধুমাত্র প্রতিষ্ঠান কর্তৃপক্ষের সাথে যোগাযোগের উদ্দেশ্যে ব্যবহৃত হবে।</p>
            <button type="submit" id="contact-form-submit" class="w-full py-3.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-black transition-colors">
                বার্তা পাঠান
            </button>
        </form>
    </div>
</div>

@if(!empty($contact['map_embed_url']))
    <div class="mt-8 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <iframe src="{{ $contact['map_embed_url'] }}" width="100%" height="380" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        <p class="text-center text-xs text-slate-400 py-3">প্রতিষ্ঠানের অবস্থান — গুগল ম্যাপ</p>
    </div>
@endif

<script>
(function () {
    var form = document.getElementById('public-contact-form');
    var alertBox = document.getElementById('contact-form-alert');
    var submitBtn = document.getElementById('contact-form-submit');
    if (!form) return;

    function showAlert(message, isError) {
        alertBox.textContent = message;
        alertBox.classList.remove('hidden');
        alertBox.className = 'mb-4 p-4 rounded-xl text-sm font-semibold ' + (isError ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100');
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var token = document.querySelector('meta[name="csrf-token"]');
        var formData = new FormData(form);
        var payload = {};
        formData.forEach(function (value, key) { payload[key] = value; });

        submitBtn.disabled = true;
        submitBtn.textContent = 'পাঠানো হচ্ছে...';

        fetch('/contact-message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token ? token.getAttribute('content') : ''
            },
            body: JSON.stringify(payload)
        })
            .then(function (res) { return res.json().then(function (data) { return { status: res.status, data: data }; }); })
            .then(function (result) {
                if (result.status >= 200 && result.status < 300) {
                    showAlert(result.data.message || 'বার্তা পাঠানো হয়েছে।', false);
                    form.reset();
                } else {
                    var msg = result.data.message || 'দুঃখিত, বার্তা পাঠানো যায়নি। আবার চেষ্টা করুন।';
                    if (result.data.errors) {
                        msg = Object.values(result.data.errors).map(function (a) { return a[0]; }).join(' ');
                    }
                    showAlert(msg, true);
                }
            })
            .catch(function () {
                showAlert('দুঃখিত, বার্তা পাঠানো যায়নি। আবার চেষ্টা করুন।', true);
            })
            .finally(function () {
                submitBtn.disabled = false;
                submitBtn.textContent = 'বার্তা পাঠান';
            });
    });
})();
</script>
