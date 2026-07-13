@extends($cmsLayout ?? 'frontend.cms-layout')

@section('cms_body')
    <article class="blog-post-article max-w-6xl mx-auto">
        {{-- Hero box --}}
        <div class="blog-box blog-box-hero mb-6 md:mb-8">
            <nav class="mb-6">
                <a
                    href="{{ route('frontend.blog.index') }}"
                    class="inline-flex items-center gap-2 text-sm font-bold text-indigo-600 hover:text-indigo-800 transition-colors group"
                >
                    <span class="w-9 h-9 rounded-xl bg-white/80 shadow-sm flex items-center justify-center group-hover:bg-indigo-100 transition-colors">
                        <i class="fas fa-arrow-left text-xs"></i>
                    </span>
                    সব পোস্ট
                </a>
            </nav>

            <div class="flex flex-wrap items-center gap-2 mb-5">
                <time class="blog-chip blog-chip-date">
                    <i class="far fa-calendar-alt"></i>
                    {{ $post->published_at?->format('d F Y') }}
                </time>
                @if($post->author)
                    <span class="blog-chip blog-chip-author">
                        <i class="far fa-user"></i>
                        {{ $post->author->name }}
                    </span>
                @endif
                <span class="blog-chip blog-chip-tag">
                    <i class="fas fa-newspaper"></i>
                    ব্লগ
                </span>
            </div>

            <h1 class="text-3xl sm:text-4xl lg:text-[2.75rem] font-black text-[#1e1b4b] leading-[1.15] tracking-tight">
                {{ $post->title }}
            </h1>
        </div>

        @if($post->featured_image)
            <div class="blog-box blog-box-media mb-6 md:mb-8 p-3 md:p-4">
                <img
                    src="{{ storage_asset($post->featured_image) }}"
                    alt="{{ $post->title }}"
                    class="blog-featured-image w-full h-auto max-h-[min(85vh,920px)] object-contain object-center mx-auto block rounded-2xl"
                    loading="eager"
                >
            </div>
        @endif

        {{-- Grid: sidebar meta + main content --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
            <aside class="lg:col-span-4 xl:col-span-3 space-y-4 lg:sticky lg:top-28">
                <div class="blog-box blog-box-side">
                    <h2 class="blog-side-title">পোস্ট তথ্য</h2>
                    <dl class="blog-meta-list">
                        <div class="blog-meta-row">
                            <dt><i class="far fa-calendar-alt text-indigo-500"></i> প্রকাশ</dt>
                            <dd>{{ $post->published_at?->format('d M, Y') ?? '—' }}</dd>
                        </div>
                        @if($post->author)
                            <div class="blog-meta-row">
                                <dt><i class="far fa-user text-indigo-500"></i> লেখক</dt>
                                <dd>{{ $post->author->name }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <div class="blog-box blog-box-side blog-box-accent">
                    <a href="{{ route('frontend.blog.index') }}" class="blog-side-cta">
                        <i class="fas fa-th-large"></i>
                        <span>সব ব্লগ পোস্ট</span>
                        <i class="fas fa-arrow-right text-xs opacity-70"></i>
                    </a>
                </div>
            </aside>

            <div class="lg:col-span-8 xl:col-span-9">
                <div class="blog-box blog-box-content">
                    <div class="blog-content-header">
                        <span class="blog-content-label">পোস্ট বিস্তারিত</span>
                    </div>
                    <div class="cms-content blog-post-content">
                        {!! $post->content !!}
                    </div>
                </div>
            </div>
        </div>
    </article>

    <style>
        .blog-box {
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 1.5rem;
            box-shadow: 0 4px 24px -8px rgba(15, 23, 42, 0.12);
        }
        .blog-box-hero {
            padding: 1.5rem 1.75rem;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 45%, #faf5ff 100%);
            border-color: #e0e7ff;
        }
        @media (min-width: 768px) {
            .blog-box-hero { padding: 2rem 2.25rem; }
        }
        .blog-box-media {
            background: linear-gradient(180deg, #f1f5f9 0%, #fff 100%);
        }
        .blog-box-side {
            padding: 1.25rem 1.5rem;
        }
        .blog-box-accent {
            padding: 0;
            overflow: hidden;
            background: #0f172a;
            border-color: #1e293b;
        }
        .blog-box-content {
            padding: 0;
            overflow: hidden;
        }
        .blog-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .blog-chip-date { background: #fff; color: #4338ca; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .blog-chip-author { background: rgba(255,255,255,.7); color: #475569; }
        .blog-chip-tag { background: #4f46e5; color: #fff; }
        .blog-side-title {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #94a3b8;
            margin-bottom: 1rem;
        }
        .blog-meta-list { margin: 0; }
        .blog-meta-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            padding: 0.65rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .blog-meta-row:last-child { border-bottom: none; }
        .blog-meta-row dt {
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
            margin: 0;
        }
        .blog-meta-row dd {
            font-size: 0.875rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            text-align: right;
        }
        .blog-side-cta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            color: #fff;
            font-weight: 800;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            text-decoration: none;
            transition: background 0.2s;
        }
        .blog-side-cta:hover {
            background: #312e81;
            color: #fff;
            text-decoration: none;
        }
        .blog-content-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            background: linear-gradient(90deg, #f8fafc, #fff);
        }
        .blog-content-label {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: #6366f1;
        }
        .blog-post-content {
            padding: 1.5rem 1.5rem 2rem;
            font-size: 1.0625rem;
            line-height: 1.85;
            color: #334155;
        }
        @media (min-width: 768px) {
            .blog-post-content { padding: 2rem 2.25rem 2.5rem; }
        }
        .blog-featured-image {
            width: 100%;
            height: auto;
            object-fit: contain;
        }
        .blog-post-content :where(p) {
            margin-bottom: 1.15em;
        }
        .blog-post-content :where(h2) {
            font-size: 1.5rem;
            font-weight: 800;
            color: #1e1b4b;
            margin: 1.75em 0 0.65em;
            padding-bottom: 0.35rem;
            border-bottom: 2px solid #e0e7ff;
        }
        .blog-post-content :where(h3) {
            font-size: 1.2rem;
            font-weight: 700;
            color: #312e81;
            margin: 1.5em 0 0.5em;
        }
        .blog-post-content :where(img) {
            display: block;
            max-width: 100%;
            height: auto;
            margin: 1.5rem auto;
            border-radius: 1rem;
            box-shadow: 0 8px 30px -12px rgba(15, 23, 42, 0.25);
        }
        .blog-post-content :where(ul, ol) {
            margin: 1em 0 1.25em 1.35em;
            padding-left: 0.5em;
        }
        .blog-post-content :where(li) {
            margin-bottom: 0.4em;
        }
        .blog-post-content :where(blockquote) {
            margin: 1.5rem 0;
            padding: 1.25rem 1.5rem;
            border-left: 4px solid #6366f1;
            background: #f8fafc;
            border-radius: 0 1rem 1rem 0;
            font-style: normal;
            color: #475569;
        }
        .blog-post-content :where(a) {
            color: #4f46e5;
            font-weight: 600;
            text-decoration: underline;
            text-underline-offset: 2px;
        }
        .blog-post-content :where(table) {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
            font-size: 0.95rem;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,.08);
        }
        .blog-post-content :where(th, td) {
            border: 1px solid #e2e8f0;
            padding: 0.65rem 0.85rem;
        }
        .blog-post-content :where(th) {
            background: #f1f5f9;
            font-weight: 700;
        }
    </style>
@endsection
