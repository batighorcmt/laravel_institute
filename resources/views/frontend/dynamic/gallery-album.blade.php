@extends($cmsLayout ?? 'frontend.cms-layout')

@php($album = $albumData['album'])
@php($images = $albumData['images'])

@section('cms_body')
    <article>
        @unless(str_contains($cmsLayout ?? '', 'theme2'))
            <h1 class="text-3xl font-black text-indigo-950 mb-2">{{ $album['name'] }}</h1>
        @endunless

        <div class="mb-6">
            <a href="{{ url('/gallery') }}" class="inline-flex items-center gap-2 text-sm font-bold text-indigo-600 hover:text-indigo-800">
                <i class="fas fa-arrow-left"></i> সব গ্যালারিতে ফিরে যান
            </a>
        </div>

        @if($album['description'])
            <p class="text-slate-500 mb-6">{{ $album['description'] }}</p>
        @endif

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3" id="albumGalleryGrid">
            @forelse($images as $image)
                <div class="aspect-square rounded-xl overflow-hidden bg-slate-100 cursor-pointer gallery-lightbox-trigger" data-src="{{ $image['url'] }}">
                    <img src="{{ $image['url'] }}" alt="{{ $album['name'] }}" class="w-full h-full object-cover" loading="lazy">
                </div>
            @empty
                <p class="text-slate-500 col-span-full text-center py-10">এই এলবামে কোনো ছবি নেই।</p>
            @endforelse
        </div>
    </article>

    @include('frontend.dynamic._gallery-lightbox')
@endsection
