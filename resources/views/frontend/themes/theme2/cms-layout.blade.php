@php
    $seoTitle = $seoTitle ?? ($school->name_bn ?? $school->name ?? 'School');
    $seoDescription = $seoDescription ?? '';
    $seoKeywords = $seoKeywords ?? '';
    $seoRobots = $seoRobots ?? 'index, follow';
    $settings = $siteSettings ?? ($settings ?? null);
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

    @if($school->logo)
        <link rel="icon" type="image/png" href="{{ storage_asset($school->logo) }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tiro+Bangla:ital@0;1&family=Hind+Siliguri:wght@300;400;500;600;700&family=Noto+Sans+Bengali:wght@500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- Tailwind only (no Vue/jQuery bundle) so shared dynamic-content partials keep their utility classes --}}
    @vite(['resources/css/app.css'])
    @include('frontend.themes.theme2._style')
</head>
<body class="frontend-body theme2-frontend">

@include('frontend.themes.theme2._header')

<section class="inner-hero">
  <div class="container">
    <span class="eyebrow">{{ $school->name_bn ?: $school->name }}</span>
    <h1>{{ $seoTitle }}</h1>
  </div>
</section>

<div class="container" style="padding-top:40px; padding-bottom:80px;">
    @yield('cms_body')
</div>

@include('frontend.themes.theme2._footer')
</body>
</html>
