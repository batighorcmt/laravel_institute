@extends('frontend.cms-layout')

@section('cms_body')
    <article>
        <a href="{{ route('frontend.blog.index') }}" class="text-sm font-semibold text-indigo-600 mb-4 inline-block">
            <i class="fas fa-arrow-left"></i> সব পোস্ট
        </a>
        <time class="text-xs text-slate-400 font-semibold uppercase block mb-2">
            {{ $post->published_at?->format('d F Y') }}
            @if($post->author)
                · {{ $post->author->name }}
            @endif
        </time>
        <h1 class="text-3xl font-black text-indigo-950 mb-6">{{ $post->title }}</h1>

        @if($post->featured_image)
            <img src="{{ asset('storage/'.$post->featured_image) }}" alt="" class="w-full max-h-96 object-cover rounded-2xl mb-8 shadow">
        @endif

        @if($post->excerpt)
            <p class="text-lg text-slate-600 mb-6 font-medium border-l-4 border-indigo-500 pl-4">{{ $post->excerpt }}</p>
        @endif

        <div class="cms-content prose max-w-none bg-white rounded-2xl shadow-sm border border-slate-100 p-6 md:p-10">
            {!! $post->content !!}
        </div>
    </article>
@endsection
