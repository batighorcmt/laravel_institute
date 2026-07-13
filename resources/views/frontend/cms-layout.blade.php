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
    <script>
        window.__FRONTEND_CHROME__ = {
            school: @json($schoolPayload ?? $school),
            settings: @json($settingsPayload ?? new stdClass()),
            menuItems: @json($headerMenu ?? []),
            footerMenu: @json($footerMenu ?? []),
            marqueeNotices: @json($marqueeNotices ?? []),
            storageBase: @json($storageBase ?? '/storage'),
            showMarquee: true,
            showAdmissionCta: true,
        };
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php($themeColors = $themeColors ?? [])
    <style>
        :root {
            --theme-primary: {{ $themeColors['primary'] ?? '#d97706' }};
            --theme-secondary: {{ $themeColors['secondary'] ?? '#92400e' }};
            --theme-accent: {{ $themeColors['accent'] ?? '#f59e0b' }};
            --theme-bg: {{ $themeColors['bg'] ?? '#fefcf5' }};
            --theme-text: {{ $themeColors['text'] ?? '#1f2937' }};
            --theme-font: {{ $themeColors['font'] ?? "'Hind Siliguri', sans-serif" }};
        }
        .cms-content h1,.cms-content h2,.cms-content h3 { font-weight: 700; margin-top: 1.25em; margin-bottom: .5em; }
        .cms-content p { margin-bottom: 1em; line-height: 1.75; }
        .cms-content ul,.cms-content ol { margin: 1em 0 1em 1.5em; }
        .cms-content img { max-width: 100%; height: auto; border-radius: .5rem; }
        .cms-content table { width: 100%; border-collapse: collapse; margin: 1em 0; }
        .cms-content td,.cms-content th { border: 1px solid #e2e8f0; padding: .5rem; }
    </style>
</head>
<body class="bg-[#f8fafc] text-[#1e2a32] font-sans antialiased" style="font-family: 'Hind Siliguri', sans-serif;">
    <div id="frontend-chrome-header"></div>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @yield('cms_body')
    </main>

    <div id="frontend-chrome-footer"></div>
</body>
</html>
