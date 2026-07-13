@php
    $settings = $settings ?? null;
    $headerMenu = $headerMenu ?? [];
    $marqueeNotices = $marqueeNotices ?? [];
    $marqueeItems = collect($marqueeNotices)->pluck('title')->filter()->values();
    if ($marqueeItems->isEmpty() && !empty($settings?->marquee_text)) {
        $marqueeItems = collect([$settings->marquee_text]);
    }
@endphp

<!-- ============ TOP BAR ============ -->
<div class="topbar">
  <div class="container">
    <div class="topbar-left">
      @if($school->eiin)<span><i class="fa-solid fa-hashtag"></i> EIIN: {{ $school->eiin }}</span>@endif
      @if($school->phone)<span><i class="fa-solid fa-phone"></i> {{ $school->displayPhone() }}</span>@endif
      @if($school->email)<span><i class="fa-solid fa-envelope"></i> {{ $school->email }}</span>@endif
    </div>
    <div class="topbar-right">
      @if($settings?->facebook_url)<a href="{{ $settings->facebook_url }}" target="_blank" rel="noopener" aria-label="ফেসবুক"><i class="fa-brands fa-facebook-f"></i></a>@endif
      @if($settings?->youtube_url)<a href="{{ $settings->youtube_url }}" target="_blank" rel="noopener" aria-label="ইউটিউব"><i class="fa-brands fa-youtube"></i></a>@endif
      @if($school->email)<a href="mailto:{{ $school->email }}" aria-label="ইমেইল"><i class="fa-solid fa-envelope-open-text"></i></a>@endif
    </div>
  </div>
</div>

<!-- ============ BRAND HEADER ============ -->
<div class="brandbar">
  <div class="container">
    <div class="brand-emblem">
        @if($school->logo)
            <img src="{{ storage_asset($school->logo) }}" alt="{{ $school->name }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
        @else
            <i class="fa-solid fa-book-open-reader"></i>
        @endif
    </div>
    <div class="brand-text">
      <div class="school-name">{{ $school->name_bn ?: $school->name }}</div>
      <div class="school-sub">
        @if($school->founding_year)স্থাপিত {{ $school->founding_year }}@endif
        @if($school->eiin) | EIIN: {{ $school->eiin }}@endif
      </div>
      @if($school->address_bn || $school->address)
        <div class="school-addr"><i class="fa-solid fa-location-dot"></i> {{ $school->address_bn ?: $school->address }}</div>
      @endif
    </div>
    <div class="brand-cta">
      <a href="{{ route('login') }}" class="btn btn-outline"><i class="fa-solid fa-right-to-bracket"></i> লগইন</a>
      @if($school->code)
        <a href="{{ url('/admission/'.$school->code) }}" class="btn btn-solid"><i class="fa-solid fa-pen-to-square"></i> ভর্তি ফরম</a>
      @endif
    </div>
  </div>
</div>

<!-- ============ NAVIGATION ============ -->
<div class="navwrap" id="navwrap">
  <nav class="mainnav">
    <button class="nav-toggle" id="navToggle" aria-label="মেনু খুলুন" aria-expanded="false"><i class="fa-solid fa-bars"></i></button>
    <ul class="nav-list" id="navList">
      <button class="nav-close" id="navClose" aria-label="মেনু বন্ধ করুন"><i class="fa-solid fa-xmark"></i></button>
      @include('frontend.themes.theme2._nav-items', ['items' => $headerMenu])
    </ul>
  </nav>
</div>
<div class="nav-overlay" id="navOverlay"></div>

@if($marqueeItems->isNotEmpty())
<!-- ============ MARQUEE NOTICE TICKER ============ -->
<div class="ticker">
  <div class="ticker-label"><i class="fa-solid fa-bullhorn"></i> সর্বশেষ আপডেট</div>
  <div class="ticker-track-wrap">
    <div class="ticker-track" id="tickerTrack">
      @foreach($marqueeItems as $text)<span>{{ $text }}</span>@endforeach
      {{-- duplicate for seamless loop --}}
      @foreach($marqueeItems as $text)<span>{{ $text }}</span>@endforeach
    </div>
  </div>
</div>
@endif
