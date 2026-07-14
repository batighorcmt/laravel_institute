@extends($cmsLayout ?? 'frontend.cms-layout')

@section('cms_body')
    <article>
        @unless(str_contains($cmsLayout ?? '', 'theme2'))
            <h1 class="text-3xl font-black text-indigo-950 mb-6">{{ $page->title }}</h1>
        @endunless

        @if($page->content_mode === 'dynamic' && $page->data_source)
            @includeFirst(['frontend.dynamic.'.$page->data_source, 'frontend.dynamic.fallback'], ['dynamicData' => $dynamicData ?? []])
        @else
            <div class="cms-content prose max-w-none bg-white rounded-2xl shadow-sm border border-slate-100 p-6 md:p-10">
                {!! $page->content !!}
            </div>
        @endif
    </article>
@endsection
