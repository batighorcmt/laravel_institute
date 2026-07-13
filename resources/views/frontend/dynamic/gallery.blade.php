@php($gallery = $dynamicData['gallery'] ?? [])

<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
    @forelse($gallery as $image)
        <div class="aspect-square rounded-xl overflow-hidden bg-slate-100">
            <img src="{{ $image }}" alt="Gallery" class="w-full h-full object-cover">
        </div>
    @empty
        <p class="text-slate-500 col-span-full text-center py-10">কোনো ছবি পাওয়া যায়নি।</p>
    @endforelse
</div>
