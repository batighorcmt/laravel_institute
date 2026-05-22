@php
    $seoTitle = $seoTitle ?? ($school->name ?? 'School');
    $seoDescription = $seoDescription ?? '';
    $seoKeywords = $seoKeywords ?? '';
    $seoRobots = $seoRobots ?? 'index, follow';
    $seoOgImage = $seoOgImage ?? null;
    $siteSettings = $siteSettings ?? null;
@endphp
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    @if($seoKeywords)<meta name="keywords" content="{{ $seoKeywords }}">@endif
    <meta name="robots" content="{{ $seoRobots }}">
    <meta property="og:type" content="{{ $ogType ?? 'article' }}">
    <meta property="og:url" content="{{ request()->url() }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    @if($seoOgImage)
        <meta property="og:image" content="{{ asset('storage/'.$seoOgImage) }}">
    @elseif($siteSettings?->hero_image)
        <meta property="og:image" content="{{ asset('storage/'.$siteSettings->hero_image) }}">
    @elseif($school->logo ?? null)
        <meta property="og:image" content="{{ asset('storage/'.$school->logo) }}">
    @endif
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        .cms-content h1,.cms-content h2,.cms-content h3 { font-weight: 700; margin-top: 1.25em; margin-bottom: .5em; }
        .cms-content p { margin-bottom: 1em; line-height: 1.75; }
        .cms-content ul,.cms-content ol { margin: 1em 0 1em 1.5em; }
        .cms-content img { max-width: 100%; height: auto; border-radius: .5rem; }
        .cms-content table { width: 100%; border-collapse: collapse; margin: 1em 0; }
        .cms-content td,.cms-content th { border: 1px solid #e2e8f0; padding: .5rem; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased" style="font-family: 'Hind Siliguri', sans-serif;">
    <header class="bg-white border-b-4 border-indigo-600 shadow">
        <div class="max-w-5xl mx-auto px-4 py-4 flex flex-wrap items-center justify-between gap-4">
            <a href="{{ route('frontend.index') }}" class="flex items-center gap-3 text-inherit no-underline">
                @if($school->logo ?? null)
                    <img src="{{ asset('storage/'.$school->logo) }}" alt="" class="h-14 w-auto rounded-lg object-contain">
                @endif
                <div>
                    <div class="text-xl font-bold text-indigo-950">{{ $school->name_bn ?? $school->name }}</div>
                    <div class="text-xs text-slate-500 uppercase tracking-wider">{{ $school->name }}</div>
                </div>
            </a>
            <nav class="flex flex-wrap gap-4 text-sm font-semibold">
                <a href="{{ route('frontend.index') }}" class="text-slate-600 hover:text-indigo-600">হোম</a>
                <a href="{{ route('frontend.blog.index') }}" class="text-slate-600 hover:text-indigo-600">ব্লগ</a>
                <a href="{{ url('/admission/'.$school->code) }}" class="text-green-700 hover:text-green-600">ভর্তি</a>
            </nav>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 py-10">
        @yield('cms_body')
    </main>

    <footer class="border-t bg-slate-900 text-slate-300 text-center text-sm py-6 mt-12">
        <p>&copy; {{ date('Y') }} {{ $school->name_bn ?? $school->name }}</p>
    </footer>
</body>
</html>
