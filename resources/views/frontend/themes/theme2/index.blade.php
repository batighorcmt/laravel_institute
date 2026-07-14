<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $settings?->meta_title ?? (($school->name_bn ?: $school->name) . ' | ' . ($school->address_bn ?: $school->address ?: '')) }}</title>
<meta name="description" content="{{ $settings?->meta_description ?? ($settings?->about_text ? \Illuminate\Support\Str::limit(strip_tags($settings->about_text), 150) : (($school->name_bn ?: $school->name).' — ডাইনামিক ওয়েবসাইট')) }}">
@if($settings?->meta_keywords)<meta name="keywords" content="{{ $settings->meta_keywords }}">@endif
<meta name="robots" content="index, follow">

@if($school->logo)
    <link rel="icon" type="image/png" href="{{ storage_asset($school->logo) }}">
@endif

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Tiro+Bangla:ital@0;1&family=Hind+Siliguri:wght@300;400;500;600;700&family=Noto+Sans+Bengali:wght@500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

@include('frontend.themes.theme2._style')
</head>
<body class="theme2-frontend">

@include('frontend.themes.theme2._header')

@php
    $heroSlides = collect($settings?->hero_images ?? [])
        ->filter(fn ($s) => is_array($s) && ($s['active'] ?? true) && !empty($s['image']))
        ->map(fn ($s) => [
            'image' => storage_asset($s['image']),
            'title' => $s['title'] ?? null,
            'subtitle' => $s['subtitle'] ?? null,
            'button1_text' => $s['button1_text'] ?? null,
            'button1_url' => $s['button1_url'] ?? null,
            'button2_text' => $s['button2_text'] ?? null,
            'button2_url' => $s['button2_url'] ?? null,
        ])
        ->values();

    if ($heroSlides->isEmpty()) {
        $heroSlides = collect([[
            'image' => $settings?->hero_image ? storage_asset($settings->hero_image) : null,
            'title' => $settings?->hero_title ?: (($school->name_bn ?: $school->name).'-এ স্বাগতম'),
            'subtitle' => $settings?->hero_subtitle ?: 'মানসম্মত শিক্ষা ও চরিত্র গঠনে আমরা প্রতিশ্রুতিবদ্ধ।',
            'button1_text' => $school->code ? 'ভর্তি চলছে' : null,
            'button1_url' => $school->code ? url('/admission/'.$school->code) : null,
            'button2_text' => 'প্রতিষ্ঠান সম্পর্কে জানুন',
            'button2_url' => '#about',
        ]]);
    }

    $stats = $stats ?? [];
    $studentsCount = $stats['students'] ?? 0;
    $classesCount = $stats['classes'] ?? 0;
    $experienceYears = $stats['experience_years'] ?? null;
    $staffCount = $stats['staff'] ?? null;
@endphp

<!-- ============ HERO SLIDER ============ -->
<section id="home" class="hero" style="padding:0;">
  @foreach($heroSlides as $i => $slide)
    <div class="slide {{ $i === 0 ? 'active' : '' }}" @if($slide['image']) style="background-image:url('{{ $slide['image'] }}');" @endif>
      <div class="slide-content">
        @if($school->name_bn || $school->name)<span class="eyebrow">{{ $school->name_bn ?: $school->name }}</span>@endif
        @if($slide['title'])<h1>{{ $slide['title'] }}</h1>@endif
        @if($slide['subtitle'])<p>{{ $slide['subtitle'] }}</p>@endif
        @if(!empty($slide['button1_text']) || !empty($slide['button2_text']))
        <div class="slide-actions">
          @if(!empty($slide['button1_text']))
            <a href="{{ $slide['button1_url'] ?: '#' }}" class="btn btn-gold"><i class="fa-solid fa-pen-to-square"></i> {{ $slide['button1_text'] }}</a>
          @endif
          @if(!empty($slide['button2_text']))
            <a href="{{ $slide['button2_url'] ?: '#' }}" class="btn btn-outline" style="color:#fff; border-color:rgba(255,255,255,.6);">{{ $slide['button2_text'] }}</a>
          @endif
        </div>
        @endif
      </div>
    </div>
  @endforeach

  @if($heroSlides->count() > 1)
    <button class="hero-arrow prev" id="heroPrev" aria-label="পূর্ববর্তী"><i class="fa-solid fa-chevron-left"></i></button>
    <button class="hero-arrow next" id="heroNext" aria-label="পরবর্তী"><i class="fa-solid fa-chevron-right"></i></button>
    <div class="hero-dots" id="heroDots"></div>
  @endif
</section>

<!-- ============ NOTICE BOARD + QUICK INFO ============ -->
<div class="container">
  <div class="homegrid">
    <div class="panel reveal" id="notice">
      <div class="panel-head">
        <h3><i class="fa-solid fa-thumbtack"></i> নোটিশ বোর্ড</h3>
        <a href="{{ url('/notice-board') }}">সব নোটিশ <i class="fa-solid fa-arrow-right"></i></a>
      </div>
      <div class="notice-list">
        @forelse(($boardNotices ?? []) as $notice)
          @php($publishAt = $notice['publish_at'] ? \Illuminate\Support\Carbon::parse($notice['publish_at']) : null)
          <div class="notice-item">
            <div class="notice-date">
                <span class="d">{{ $publishAt?->format('d') ?? '--' }}</span>
                <span class="m">{{ $publishAt?->translatedFormat('M') ?? '' }}</span>
            </div>
            <div class="notice-body">
              <a href="{{ $notice['download_url'] ?? '#' }}">{{ $notice['title'] }}</a>
              @if($publishAt && $publishAt->gt(now()->subDays(7)))
                <span class="notice-tag new">নতুন</span>
              @endif
            </div>
          </div>
        @empty
          <div class="notice-empty">কোনো নোটিশ প্রকাশিত হয়নি।</div>
        @endforelse
      </div>
    </div>

    <div class="quickstack reveal">
      <div class="quick-card">
        <h4><i class="fa-solid fa-circle-info"></i> সংক্ষিপ্ত তথ্য</h4>
        @if($school->founding_year)<div class="qc-row"><span>প্রতিষ্ঠাকাল</span><span>{{ $school->founding_year }}</span></div>@endif
        @if($classesCount)<div class="qc-row"><span>শ্রেণি সংখ্যা</span><span>{{ $classesCount }}</span></div>@endif
        @if($school->eiin)<div class="qc-row"><span>EIIN</span><span>{{ $school->eiin }}</span></div>@endif
        @if($school->code)<div class="qc-row"><span>স্কুল কোড</span><span>{{ $school->code }}</span></div>@endif
      </div>
      @if($school->phone || $school->email)
      <div class="quick-card">
        <h4><i class="fa-solid fa-address-card"></i> যোগাযোগ</h4>
        @if($school->phone)<div class="qc-row"><span>ফোন</span><span>{{ $school->displayPhone() }}</span></div>@endif
        @if($school->email)<div class="qc-row"><span>ইমেইল</span><span>{{ $school->email }}</span></div>@endif
      </div>
      @endif
    </div>
  </div>
</div>

@if($settings?->about_text)
<!-- ============ HISTORY / ABOUT ============ -->
<section id="history">
  <div class="container">
    <div class="split reveal">
      <div class="img-col">
        <div class="img-frame">
          <img src="{{ $aboutImage }}" alt="{{ $school->name_bn ?: $school->name }}">
        </div>
        @if($experienceYears)<div class="img-badge"><div class="num">{{ $experienceYears }}</div><div class="lbl">বছরের ঐতিহ্য</div></div>@endif
      </div>
      <div class="text-col" id="about">
        <span class="eyebrow">আমাদের যাত্রা</span>
        <h2 style="font-family:var(--display); font-size:clamp(1.6rem,3vw,2.3rem); color:var(--pine-900); margin:12px 0 18px;">প্রতিষ্ঠানের ইতিহাস</h2>
        <div class="cms-content">{!! $settings->about_text !!}</div>
      </div>
    </div>
  </div>
</section>
@endif

@if($settings?->principal_message || $settings?->chairman_message)
<!-- ============ HEAD TEACHER'S & CHAIRMAN'S MESSAGE ============ -->
@php($principalFeatureImage = $settings->principal_feature_image ? storage_asset($settings->principal_feature_image) : null)
@php($chairmanFeatureImage = $settings->chairman_feature_image ? storage_asset($settings->chairman_feature_image) : null)
@php($principalDesignation = $settings->principal_designation ?: 'প্রধান শিক্ষক')
@php($chairmanDesignation = $settings->chairman_designation ?: 'সভাপতি')
<section id="hm-message" class="bg-alt">
  <div class="container">
    @if($settings?->principal_message)
    <div class="split reverse reveal {{ !$principalFeatureImage ? 'single' : '' }}" style="margin-bottom:48px;">
      @if($principalFeatureImage)
      <div class="img-col">
        <div class="img-frame feature-photo">
          <img src="{{ $principalFeatureImage }}" alt="{{ $school->name_bn ?: $school->name }}">
        </div>
      </div>
      @endif
      <div class="text-col">
        <span class="eyebrow">নেতৃত্বের বার্তা</span>
        <h2 style="font-family:var(--display); font-size:clamp(1.6rem,3vw,2.3rem); color:var(--pine-900); margin:12px 0 18px;">{{ $settings->principal_title ?: 'প্রধান শিক্ষকের বাণী' }}</h2>
        <blockquote class="hm-quote">{{ \Illuminate\Support\Str::limit(strip_tags($settings->principal_message), 200) }}</blockquote>
        <div class="signature-line">
          @if($settings->principal_image)
            <img src="{{ storage_asset($settings->principal_image) }}" alt="{{ $settings->principal_name }}">
          @endif
          <div>
            <strong>{{ $settings->principal_name }}</strong>
            <span>{{ $principalDesignation }}, {{ $school->name_bn ?: $school->name }}</span>
          </div>
        </div>
      </div>
    </div>
    @endif

    @if($settings?->chairman_message)
    <div class="split reveal {{ !$chairmanFeatureImage ? 'single' : '' }}">
      @if($chairmanFeatureImage)
      <div class="img-col">
        <div class="img-frame feature-photo">
          <img src="{{ $chairmanFeatureImage }}" alt="{{ $school->name_bn ?: $school->name }}">
        </div>
      </div>
      @endif
      <div class="text-col">
        <span class="eyebrow">সভাপতির বার্তা</span>
        <h2 style="font-family:var(--display); font-size:clamp(1.6rem,3vw,2.3rem); color:var(--pine-900); margin:12px 0 18px;">{{ $settings->chairman_title ?: 'সভাপতির বাণী' }}</h2>
        <blockquote class="hm-quote">{{ \Illuminate\Support\Str::limit(strip_tags($settings->chairman_message), 200) }}</blockquote>
        <div class="signature-line">
          @if($settings->chairman_image)
            <img src="{{ storage_asset($settings->chairman_image) }}" alt="{{ $settings->chairman_name }}">
          @endif
          <div>
            <strong>{{ $settings->chairman_name }}</strong>
            <span>{{ $chairmanDesignation }}, {{ $school->name_bn ?: $school->name }}</span>
          </div>
        </div>
      </div>
    </div>
    @endif
  </div>
</section>
@endif

@if(!empty($homepageContent['mission']) || !empty($homepageContent['vision']))
<!-- ============ MISSION & VISION ============ -->
<section id="mission" class="bg-pine">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow" style="color:var(--gold-400);">আমাদের অঙ্গীকার</span>
      <h2>মিশন ও ভিশন</h2>
    </div>
    <div class="mv-grid reveal">
      @if(!empty($homepageContent['vision']))
        <div class="mv-card">
          <i class="fa-solid fa-eye big-icon"></i>
          <h3>{{ $homepageContent['vision']['title'] ?? 'ভিশন' }}</h3>
          <p>{{ $homepageContent['vision']['body'] ?? '' }}</p>
        </div>
      @endif
      @if(!empty($homepageContent['mission']))
        <div class="mv-card">
          <i class="fa-solid fa-bullseye big-icon"></i>
          <h3>{{ $homepageContent['mission']['title'] ?? 'মিশন' }}</h3>
          <p>{{ $homepageContent['mission']['body'] ?? '' }}</p>
        </div>
      @endif
    </div>
  </div>
</section>
@endif

<!-- ============ STATS ============ -->
<section id="stats">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">এক নজরে</span>
      <h2>প্রতিষ্ঠানের পরিসংখ্যান</h2>
    </div>
    <div class="stats-strip reveal">
      <div class="stat-cell"><i class="fa-solid fa-user-graduate"></i><div class="stat-num" data-target="{{ $studentsCount }}">0</div><div class="stat-lbl">নিবন্ধিত শিক্ষার্থী</div></div>
      <div class="stat-cell"><i class="fa-solid fa-chalkboard-user"></i><div class="stat-num" data-target="{{ $stats['teachers'] ?? count($teachers ?? []) }}">0</div><div class="stat-lbl">শিক্ষকমণ্ডলী</div></div>
      @if($staffCount)
        <div class="stat-cell"><i class="fa-solid fa-user-tie"></i><div class="stat-num" data-target="{{ $staffCount }}">0</div><div class="stat-lbl">কর্মচারী</div></div>
      @endif
      <div class="stat-cell"><i class="fa-solid fa-layer-group"></i><div class="stat-num" data-target="{{ $classesCount }}">0</div><div class="stat-lbl">শ্রেণি</div></div>
      @if($experienceYears)
        <div class="stat-cell"><i class="fa-solid fa-award"></i><div class="stat-num" data-target="{{ $experienceYears }}">0</div><div class="stat-lbl">বছরের অভিজ্ঞতা</div></div>
      @endif
    </div>
  </div>
</section>

@if(!empty($homepageContent['achievements']))
<!-- ============ ACHIEVEMENTS ============ -->
<section id="achievements" class="bg-alt">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">গর্বের মুহূর্ত</span>
      <h2>বিশেষ গৌরবের অর্জন সমূহ</h2>
    </div>
    <div class="ach-grid reveal">
      @foreach($homepageContent['achievements'] as $item)
        <div class="ach-card">
          <div class="medal"><i class="fa-solid {{ $item['icon'] ?? 'fa-medal' }}"></i></div>
          <h4>{{ $item['title'] ?? '' }}</h4>
          <p>{{ $item['description'] ?? '' }} @if(!empty($item['year']))— {{ $item['year'] }}@endif</p>
        </div>
      @endforeach
    </div>
  </div>
</section>
@endif

@if(!empty($homepageContent['facilities']))
<!-- ============ FACILITIES ============ -->
<section id="facilities">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">অবকাঠামো</span>
      <h2>সুবিধাসমূহ</h2>
    </div>
    <div class="fac-grid reveal">
      @foreach($homepageContent['facilities'] as $item)
        <div class="fac-card">
          <div class="ic"><i class="fa-solid {{ $item['icon'] ?? 'fa-circle-check' }}"></i></div>
          <h4>{{ $item['title'] ?? '' }}</h4>
          <p>{{ $item['description'] ?? '' }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>
@endif

@if(!empty($homepageContent['gallery']))
<!-- ============ PHOTO GALLERY ============ -->
<section id="gallery" class="bg-alt">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">ভিজ্যুয়াল ঝলক</span>
      <h2>ছবি গ্যালারি</h2>
    </div>
    <div class="gal-grid reveal" id="galGrid">
      @foreach($homepageContent['gallery'] as $i => $image)
        <div class="gal-item {{ $i % 5 === 0 ? 'tall' : '' }}">
          <img src="{{ $image }}" alt="গ্যালারি ছবি {{ $i + 1 }}" data-caption="{{ $school->name_bn ?: $school->name }}">
        </div>
      @endforeach
    </div>
  </div>
</section>

<div class="lightbox" id="lightbox">
  <button class="lb-close" id="lbClose" aria-label="বন্ধ করুন"><i class="fa-solid fa-xmark"></i></button>
  <button class="lb-nav prev" id="lbPrev" aria-label="পূর্ববর্তী ছবি"><i class="fa-solid fa-chevron-left"></i></button>
  <button class="lb-nav next" id="lbNext" aria-label="পরবর্তী ছবি"><i class="fa-solid fa-chevron-right"></i></button>
  <figure>
    <img id="lbImg" src="" alt="">
    <figcaption id="lbCaption"></figcaption>
  </figure>
</div>
@endif

@if(!empty($teachers))
<!-- ============ STAFF ============ -->
<section id="staff" class="bg-alt">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">আমাদের কারিগর</span>
      <h2>শিক্ষকমণ্ডলী</h2>
    </div>
    <div class="staff-grid reveal" id="staffGrid">
      @foreach($teachers as $teacher)
        <div class="staff-card"
          data-photo="{{ $teacher['photo'] ?? asset('images/avatar-placeholder.png') }}"
          data-name-bn="{{ $teacher['name_bn'] ?? $teacher['name'] }}"
          data-name-en="{{ $teacher['name_en'] ?? '' }}"
          data-designation="{{ $teacher['designation'] }}"
          data-phone="{{ $teacher['phone'] ?? '' }}"
          data-email="{{ $teacher['email'] ?? '' }}"
          data-address="{{ $teacher['address'] ?? '' }}"
        >
          <div class="staff-photo">
            <img src="{{ $teacher['photo'] ?? asset('images/avatar-placeholder.png') }}" alt="{{ $teacher['name'] }}">
          </div>
          <div class="staff-info">
            <h4>{{ $teacher['name'] }}</h4>
            <span>{{ $teacher['designation'] }}</span>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>

<div class="lightbox" id="teacherModal">
  <button class="lb-close" id="teacherModalClose" aria-label="বন্ধ করুন"><i class="fa-solid fa-xmark"></i></button>
  <div class="teacher-modal-card" role="dialog" aria-modal="true">
    <img id="tmPhoto" src="" alt="">
    <h3 id="tmNameBn"></h3>
    <p id="tmNameEn" class="tm-name-en"></p>
    <span id="tmDesignation" class="tm-designation"></span>
    <div class="tm-rows">
      <div id="tmPhoneRow" class="tm-row"><i class="fa-solid fa-phone"></i><span id="tmPhone"></span></div>
      <div id="tmEmailRow" class="tm-row"><i class="fa-solid fa-envelope"></i><span id="tmEmail"></span></div>
      <div id="tmAddressRow" class="tm-row"><i class="fa-solid fa-location-dot"></i><span id="tmAddress"></span></div>
    </div>
  </div>
</div>
@endif

@if(!empty($blogPosts))
<!-- ============ BLOG & NEWS ============ -->
<section id="news">
  <div class="container">
    <div class="section-head reveal">
      <span class="eyebrow">সাম্প্রতিক</span>
      <h2>ব্লগ ও সংবাদ</h2>
    </div>
    <div class="news-grid reveal">
      @foreach($blogPosts as $i => $post)
        <div class="news-card {{ $i === 0 ? 'featured' : '' }}">
          <div class="news-img">
            @if($post['image'])<img src="{{ $post['image'] }}" alt="{{ $post['title'] }}">@endif
          </div>
          <div class="news-body">
            <div class="news-meta">@if($post['date'])<span><i class="fa-regular fa-calendar"></i> {{ $post['date'] }}</span>@endif</div>
            <h3>{{ $post['title'] }}</h3>
            <p>{{ $post['excerpt'] }}</p>
            <a href="{{ $post['url'] }}" class="news-more">বিস্তারিত পড়ুন <i class="fa-solid fa-arrow-right"></i></a>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>
@endif

@include('frontend.themes.theme2._footer')

<script>
/* ===== Hero slider ===== */
(function(){
  const slides = document.querySelectorAll('.hero .slide');
  const dotsWrap = document.getElementById('heroDots');
  if (!slides.length || !dotsWrap) return;
  let current = 0, heroTimer;

  slides.forEach((_, i) => {
    const dot = document.createElement('button');
    if(i===0) dot.classList.add('active');
    dot.addEventListener('click', () => goToSlide(i));
    dotsWrap.appendChild(dot);
  });
  const dots = dotsWrap.querySelectorAll('button');

  function goToSlide(i){
    slides[current].classList.remove('active');
    if (dots[current]) dots[current].classList.remove('active');
    current = (i + slides.length) % slides.length;
    slides[current].classList.add('active');
    if (dots[current]) dots[current].classList.add('active');
    resetHeroTimer();
  }
  function resetHeroTimer(){
    clearInterval(heroTimer);
    heroTimer = setInterval(() => goToSlide(current+1), 6000);
  }
  const nextBtn = document.getElementById('heroNext');
  const prevBtn = document.getElementById('heroPrev');
  if (nextBtn) nextBtn.addEventListener('click', () => goToSlide(current+1));
  if (prevBtn) prevBtn.addEventListener('click', () => goToSlide(current-1));
  if (slides.length > 1) resetHeroTimer();
})();

/* ===== Photo gallery lightbox ===== */
(function(){
  const galImgs = Array.from(document.querySelectorAll('#galGrid img'));
  const lightbox = document.getElementById('lightbox');
  if (!galImgs.length || !lightbox) return;
  const lbImg = document.getElementById('lbImg');
  const lbCaption = document.getElementById('lbCaption');
  let lbIndex = 0;

  function openLightbox(i){
    lbIndex = i;
    lbImg.src = galImgs[lbIndex].src;
    lbImg.alt = galImgs[lbIndex].alt;
    lbCaption.textContent = galImgs[lbIndex].dataset.caption || galImgs[lbIndex].alt;
    lightbox.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
  function closeLightbox(){
    lightbox.classList.remove('show');
    document.body.style.overflow = '';
  }
  function showLbOffset(delta){
    openLightbox((lbIndex + delta + galImgs.length) % galImgs.length);
  }
  galImgs.forEach((img, i) => img.addEventListener('click', () => openLightbox(i)));
  document.getElementById('lbClose').addEventListener('click', closeLightbox);
  document.getElementById('lbPrev').addEventListener('click', () => showLbOffset(-1));
  document.getElementById('lbNext').addEventListener('click', () => showLbOffset(1));
  lightbox.addEventListener('click', (e) => { if(e.target === lightbox) closeLightbox(); });
  document.addEventListener('keydown', (e) => {
    if(!lightbox.classList.contains('show')) return;
    if(e.key === 'Escape') closeLightbox();
    if(e.key === 'ArrowLeft') showLbOffset(-1);
    if(e.key === 'ArrowRight') showLbOffset(1);
  });
})();

/* ===== Animated counters (stats) ===== */
(function(){
  const counters = document.querySelectorAll('.stat-num');
  if (!counters.length) return;
  const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if(entry.isIntersecting){
        const el = entry.target;
        const target = +el.dataset.target;
        let cur = 0;
        const step = Math.max(1, Math.ceil(target / 60));
        const tick = () => {
          cur += step;
          if(cur >= target){ el.textContent = target.toLocaleString('bn-BD'); }
          else { el.textContent = cur.toLocaleString('bn-BD'); requestAnimationFrame(tick); }
        };
        tick();
        counterObserver.unobserve(el);
      }
    });
  }, {threshold:.4});
  counters.forEach(c => counterObserver.observe(c));
})();
</script>
</body>
</html>
