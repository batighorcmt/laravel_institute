@extends('frontend.cms-layout')

@section('cms_body')
    <h1 class="text-3xl font-black text-indigo-950 mb-8">ব্লগ ও সংবাদ</h1>

    @if($posts->isEmpty())
        <p class="text-slate-500">এখনও কোনো প্রকাশিত পোস্ট নেই।</p>
    @else
        <div class="space-y-8">
            @foreach($posts as $post)
                <article class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden flex flex-col md:flex-row">
                    @if($post->featured_image)
                        <a href="{{ route('frontend.blog.show', $post->slug) }}" class="md:w-1/3 shrink-0">
                            <img src="{{ asset('storage/'.$post->featured_image) }}" alt="" class="w-full h-48 md:h-full object-cover">
                        </a>
                    @endif
                    <div class="p-6 flex-grow">
                        <time class="text-xs text-slate-400 font-semibold uppercase">
                            {{ $post->published_at?->format('d F Y') }}
                        </time>
                        <h2 class="text-xl font-bold mt-1 mb-2">
                            <a href="{{ route('frontend.blog.show', $post->slug) }}" class="text-indigo-900 hover:text-indigo-600">
                                {{ $post->title }}
                            </a>
                        </h2>
                        @if($post->excerpt)
                            <p class="text-slate-600 line-clamp-3">{{ $post->excerpt }}</p>
                        @endif
                        <a href="{{ route('frontend.blog.show', $post->slug) }}" class="inline-block mt-3 text-sm font-bold text-indigo-600">
                            আরও পড়ুন <i class="fas fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-8">{{ $posts->links() }}</div>
    @endif
@endsection
