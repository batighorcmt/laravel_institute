@php($about = $dynamicData)

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 md:p-10 space-y-8">
    @if(!empty($about['about_text']))
        <div class="prose max-w-none">{!! $about['about_text'] !!}</div>
    @endif

    @if(!empty($about['mission']) || !empty($about['vision']))
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            @if(!empty($about['mission']))
                <div>
                    <h3 class="font-bold text-lg text-indigo-950 mb-2">{{ $about['mission']['title'] ?? 'মিশন' }}</h3>
                    <p class="text-slate-600">{{ $about['mission']['body'] ?? '' }}</p>
                </div>
            @endif
            @if(!empty($about['vision']))
                <div>
                    <h3 class="font-bold text-lg text-indigo-950 mb-2">{{ $about['vision']['title'] ?? 'ভিশন' }}</h3>
                    <p class="text-slate-600">{{ $about['vision']['body'] ?? '' }}</p>
                </div>
            @endif
        </div>
    @endif

    @if(!empty($about['principal_message']) || !empty($about['chairman_message']))
        <div class="border-t border-slate-100 pt-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
            @if(!empty($about['principal_message']))
                <div class="flex flex-col sm:flex-row gap-4 items-start">
                    @if(!empty($about['principal_image']))
                        <img src="{{ $about['principal_image'] }}" alt="{{ $about['principal_name'] }}" class="w-20 h-20 rounded-full object-cover">
                    @endif
                    <div>
                        <h3 class="font-bold text-indigo-950">{{ $about['principal_name'] ?? '' }}</h3>
                        <p class="text-xs text-slate-400 mb-1">অধ্যক্ষের বাণী</p>
                        <p class="text-slate-600">{{ $about['principal_message'] }}</p>
                    </div>
                </div>
            @endif
            @if(!empty($about['chairman_message']))
                <div class="flex flex-col sm:flex-row gap-4 items-start">
                    @if(!empty($about['chairman_image']))
                        <img src="{{ $about['chairman_image'] }}" alt="{{ $about['chairman_name'] }}" class="w-20 h-20 rounded-full object-cover">
                    @endif
                    <div>
                        <h3 class="font-bold text-indigo-950">{{ $about['chairman_name'] ?? '' }}</h3>
                        <p class="text-xs text-slate-400 mb-1">সভাপতির বাণী</p>
                        <p class="text-slate-600">{{ $about['chairman_message'] }}</p>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
