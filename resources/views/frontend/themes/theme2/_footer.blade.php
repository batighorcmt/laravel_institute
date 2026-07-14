@php
    $settings = $settings ?? null;
    $footerMenu = $footerMenu ?? [];
@endphp

<!-- ============ FOOTER ============ -->
<footer id="contact">
  <div class="container">
    <div class="foot-grid">
      <div class="foot-col">
        <div class="foot-brand">
          <div class="brand-emblem">
              @if($school->logo)
                  <img src="{{ storage_asset($school->logo) }}" alt="{{ $school->name }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
              @else
                  <i class="fa-solid fa-book-open-reader"></i>
              @endif
          </div>
          <strong>{{ $school->name_bn ?: $school->name }}</strong>
        </div>
        @if($settings?->about_text)
            <p>{{ \Illuminate\Support\Str::limit(strip_tags($settings->about_text), 140) }}</p>
        @endif
        <div class="foot-social">
          @if($settings?->facebook_url)<a href="{{ $settings->facebook_url }}" target="_blank" rel="noopener"><i class="fa-brands fa-facebook-f"></i></a>@endif
          @if($settings?->youtube_url)<a href="{{ $settings->youtube_url }}" target="_blank" rel="noopener"><i class="fa-brands fa-youtube"></i></a>@endif
          @if($school->email)<a href="mailto:{{ $school->email }}"><i class="fa-solid fa-envelope"></i></a>@endif
        </div>
      </div>

      @if(!empty($footerMenu))
      <div class="foot-col">
        <h5>দ্রুত লিংক</h5>
        <ul>
          @foreach($footerMenu as $item)
            <li><a href="{{ $item['url'] ?? '#' }}"><i class="fa-solid fa-chevron-right"></i> {{ $item['label'] ?? '' }}</a></li>
          @endforeach
        </ul>
      </div>
      @endif

      <div class="foot-col">
        <h5>প্রতিষ্ঠানের তথ্য</h5>
        <ul>
          @if($school->eiin)<li><a href="#"><i class="fa-solid fa-hashtag"></i> EIIN: {{ $school->eiin }}</a></li>@endif
          @if($school->code)<li><a href="#"><i class="fa-solid fa-code"></i> স্কুল কোড: {{ $school->code }}</a></li>@endif
          @if($school->founding_year)<li><a href="#"><i class="fa-solid fa-landmark"></i> স্থাপিত: {{ $school->founding_year }}</a></li>@endif
        </ul>
      </div>

      <div class="foot-col">
        <h5>যোগাযোগ</h5>
        <ul>
          @if($settings?->contact_address ?: $school->address_bn ?: $school->address)
            <li><a href="#"><i class="fa-solid fa-location-dot"></i> {{ $settings?->contact_address ?: ($school->address_bn ?: $school->address) }}</a></li>
          @endif
          @if($settings?->contact_phone ?: $school->phone)
            <li><a href="tel:{{ $settings?->contact_phone ?: $school->phone }}"><i class="fa-solid fa-phone"></i> {{ $settings?->contact_phone ?: $school->displayPhone() }}</a></li>
          @endif
          @if($settings?->contact_email ?: $school->email)
            <li><a href="mailto:{{ $settings?->contact_email ?: $school->email }}"><i class="fa-solid fa-envelope"></i> {{ $settings?->contact_email ?: $school->email }}</a></li>
          @endif
        </ul>
      </div>
    </div>
    <div class="copyline">
      <span>&copy; {{ now()->year }} {{ $school->name_bn ?: $school->name }}। সর্বস্বত্ব সংরক্ষিত।</span>
      <span>Powered by <a href="{{ url('/') }}">BATIGHOR EIMS</a></span>
    </div>
  </div>
</footer>

<button id="backTop" aria-label="উপরে যান"><i class="fa-solid fa-arrow-up"></i></button>

<script>
/* ===== Sticky nav shadow on scroll ===== */
(function(){
  const navwrap = document.getElementById('navwrap');
  const backTop = document.getElementById('backTop');
  if (navwrap && backTop) {
    window.addEventListener('scroll', () => {
      navwrap.classList.toggle('scrolled', window.scrollY > 40);
      backTop.classList.toggle('show', window.scrollY > 500);
    });
    backTop.addEventListener('click', () => window.scrollTo({top:0, behavior:'smooth'}));
  }

  /* ===== Mobile nav toggle ===== */
  const navToggle = document.getElementById('navToggle');
  const navClose = document.getElementById('navClose');
  const navList = document.getElementById('navList');
  const navOverlay = document.getElementById('navOverlay');

  function openNav(){
    navList.classList.add('mobile-open');
    navOverlay.classList.add('show');
    navToggle.setAttribute('aria-expanded','true');
  }
  function closeNav(){
    navList.classList.remove('mobile-open');
    navOverlay.classList.remove('show');
    navToggle.setAttribute('aria-expanded','false');
    document.querySelectorAll('.nav-item.open').forEach(i => i.classList.remove('open'));
  }
  if (navToggle && navClose && navList && navOverlay) {
    navToggle.addEventListener('click', openNav);
    navClose.addEventListener('click', closeNav);
    navOverlay.addEventListener('click', closeNav);
  }

  /* Submenu toggle for touch devices */
  document.querySelectorAll('.nav-item').forEach(item => {
    const link = item.querySelector(':scope > a');
    const sub = item.querySelector('.submenu');
    if(!sub || !link) return;
    link.addEventListener('click', (e) => {
      if(window.innerWidth <= 960){
        e.preventDefault();
        const isOpen = item.classList.contains('open');
        document.querySelectorAll('.nav-item.open').forEach(i => i !== item && i.classList.remove('open'));
        item.classList.toggle('open', !isOpen);
      }
    });
  });
  /* Close mobile nav after clicking a real link */
  document.querySelectorAll('.nav-list a').forEach(a => {
    a.addEventListener('click', () => { if(window.innerWidth <= 960 && !a.nextElementSibling){ closeNav(); }});
  });

  /* ===== Ticker marquee: keep a consistent scroll speed no matter how much notice text there is ===== */
  const tickerTrack = document.getElementById('tickerTrack');
  if (tickerTrack) {
    const pxPerSecond = 90;
    const setTickerSpeed = () => {
      const setWidth = tickerTrack.scrollWidth / 2; // track content is duplicated once for a seamless loop
      const duration = Math.max(setWidth / pxPerSecond, 6);
      tickerTrack.style.animationDuration = duration + 's';
    };
    setTickerSpeed();
    window.addEventListener('resize', setTickerSpeed);
  }

  /* ===== Reveal on scroll ===== */
  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if(entry.isIntersecting){ entry.target.classList.add('in'); revealObserver.unobserve(entry.target); }
    });
  }, {threshold:.12});
  document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));
})();
</script>
