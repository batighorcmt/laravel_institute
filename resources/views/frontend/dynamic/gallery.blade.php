@php($gallery = $dynamicData['gallery'] ?? ['latest' => [], 'albums' => [], 'last_updated' => null])
@php($latest = $gallery['latest'] ?? [])
@php($albums = $gallery['albums'] ?? [])

@if($gallery['last_updated'] ?? null)
    <p class="text-slate-400 text-sm mb-6"><i class="far fa-clock mr-1"></i> সর্বশেষ আপডেট: {{ $gallery['last_updated'] }}</p>
@endif

<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
    @forelse($latest as $image)
        <div class="aspect-square rounded-xl overflow-hidden bg-slate-100 cursor-pointer gallery-lightbox-trigger" data-src="{{ $image['url'] }}">
            <img src="{{ $image['url'] }}" alt="Gallery" class="w-full h-full object-cover" loading="lazy">
        </div>
    @empty
        <p class="text-slate-500 col-span-full text-center py-10">কোনো ছবি পাওয়া যায়নি।</p>
    @endforelse
</div>

@if(!empty($albums))
    <div class="mt-12">
        <h2 class="text-xl font-black text-indigo-950 mb-4">এলবাম সমূহ</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            @foreach($albums as $album)
                <a href="{{ route('frontend.gallery.album', $album['id']) }}" class="block rounded-2xl overflow-hidden bg-white border border-slate-100 shadow-sm hover:shadow-md transition-shadow">
                    <div class="grid grid-cols-2 grid-rows-2 gap-0.5 bg-slate-200" style="height: 160px;">
                        @for($i = 0; $i < 4; $i++)
                            <div class="overflow-hidden bg-slate-200">
                                @if(!empty($album['thumbnails'][$i]))
                                    <img src="{{ $album['thumbnails'][$i] }}" alt="" class="w-full h-full object-cover">
                                @endif
                            </div>
                        @endfor
                    </div>
                    <div class="p-4">
                        <div class="font-bold text-slate-800">{{ $album['name'] }}</div>
                        <div class="text-slate-400 text-xs mt-1">{{ $album['images_count'] }} টি ছবি</div>
                        @if($album['description'])
                            <p class="text-slate-500 text-sm mt-2 line-clamp-2">{{ $album['description'] }}</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endif

@include('frontend.dynamic._gallery-lightbox')
