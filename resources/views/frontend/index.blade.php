<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $settings?->meta_title ?? (($school->name ?? 'School Frontend') . ' - ' . ($school->name_bn ?? '')) }}</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="{{ $settings?->meta_description ?? ($settings?->about_text ? Str::limit(strip_tags($settings->about_text), 150) : ($school->name . ' - একটি আধুনিক ও মানসম্মত শিক্ষাপ্রতিষ্ঠান।')) }}">
    <meta name="keywords" content="{{ $settings?->meta_keywords ?? ($school->name . ', ' . $school->name_bn . ', School, Education, Bangladesh, ' . $school->address) }}">
    <meta name="author" content="{{ $school->name }}">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ request()->url() }}">
    <meta property="og:title" content="{{ $settings?->meta_title ?? (($school->name ?? 'School Frontend') . ' - ' . ($school->name_bn ?? '')) }}">
    <meta property="og:description" content="{{ $settings?->meta_description ?? ($settings?->about_text ? Str::limit(strip_tags($settings->about_text), 150) : ($school->name . ' - একটি আধুনিক ও মানসম্মত শিক্ষাপ্রতিষ্ঠান।')) }}">
    @if($settings?->hero_image)
        <meta property="og:image" content="{{ storage_asset_url($settings->hero_image) }}">
    @elseif($school->logo)
        <meta property="og:image" content="{{ storage_asset_url($school->logo) }}">
    @endif

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ request()->url() }}">
    <meta property="twitter:title" content="{{ $settings?->meta_title ?? (($school->name ?? 'School Frontend') . ' - ' . ($school->name_bn ?? '')) }}">
    <meta property="twitter:description" content="{{ $settings?->meta_description ?? ($settings?->about_text ? Str::limit(strip_tags($settings->about_text), 150) : ($school->name . ' - একটি আধুনিক ও মানসম্মত শিক্ষাপ্রতিষ্ঠান।')) }}">
    @if($settings?->hero_image)
        <meta property="twitter:image" content="{{ storage_asset_url($settings->hero_image) }}">
    @elseif($school->logo)
        <meta property="twitter:image" content="{{ storage_asset_url($school->logo) }}">
    @endif

    @if(isset($school) && $school->logo)
        <link rel="icon" type="image/png" sizes="32x32" href="{{ storage_asset_url($school->logo) }}">
    @else
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
        <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}">
    @endif

    <!-- Font Awesome & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    {{-- Standard Vite asset loading (Tailwind via app.css, Vue via app.js) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --theme-primary: {{ $themeColors['primary'] ?? '#d97706' }};
            --theme-secondary: {{ $themeColors['secondary'] ?? '#92400e' }};
            --theme-accent: {{ $themeColors['accent'] ?? '#f59e0b' }};
            --theme-bg: {{ $themeColors['bg'] ?? '#fefcf5' }};
            --theme-text: {{ $themeColors['text'] ?? '#1f2937' }};
            --theme-font: {!! $themeColors['font'] ?? "'Hind Siliguri', sans-serif" !!};
        }
    </style>
</head>
<body class="bg-[#fefcf5] text-gray-900 font-sans antialiased">
    <div id="app">
        {{-- Pass school data to the Vue component as a prop --}}
        <frontend-home 
            storage-base="{{ $storageBase ?? '/storage' }}"
            :school="{{ json_encode($schoolPayload ?? $school) }}"
            :settings="{{ json_encode($settingsPayload ?? new stdClass()) }}"
            :header-menu="{{ json_encode($headerMenu ?? []) }}"
            :footer-menu="{{ json_encode($footerMenu ?? []) }}"
            :board-notices="{{ json_encode($boardNotices ?? []) }}"
            :all-board-notices="{{ json_encode($allBoardNotices ?? []) }}"
            :marquee-notices="{{ json_encode($marqueeNotices ?? []) }}"
            :homepage="{{ json_encode($homepageContent ?? []) }}"
            :teachers="{{ json_encode($teachers ?? []) }}"
            :blog-posts="{{ json_encode($blogPosts ?? []) }}"
            :stats="{{ json_encode($stats ?? []) }}">
        </frontend-home>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
</body>
</html>
