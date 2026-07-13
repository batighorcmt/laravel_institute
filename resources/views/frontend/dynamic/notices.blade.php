@php($notices = $dynamicData['notices'] ?? [])

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 divide-y divide-slate-100">
    @forelse($notices as $notice)
        <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="font-semibold text-indigo-950">{{ $notice['title'] }}</h3>
                <p class="text-xs text-slate-400">{{ $notice['publish_at_label'] }}</p>
            </div>
            @if(!empty($notice['download_url']))
                <a href="{{ $notice['download_url'] }}" class="text-sm font-semibold text-indigo-600 hover:underline shrink-0">
                    ডাউনলোড <i class="fas fa-download ml-1"></i>
                </a>
            @endif
        </div>
    @empty
        <p class="text-slate-500 text-center py-10">কোনো নোটিশ পাওয়া যায়নি।</p>
    @endforelse
</div>
