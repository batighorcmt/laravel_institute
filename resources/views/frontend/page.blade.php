@extends('frontend.cms-layout')

@section('cms_body')
    <article>
        <h1 class="text-3xl font-black text-indigo-950 mb-6">{{ $page->title }}</h1>
        <div class="cms-content prose max-w-none bg-white rounded-2xl shadow-sm border border-slate-100 p-6 md:p-10">
            {!! $page->content !!}
        </div>
    </article>
@endsection
