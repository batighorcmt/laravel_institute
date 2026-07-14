@php($themeColors = $themeColors ?? [])
<style>
/* ============================================================
   TOKENS (derived from the school's selected theme colors)
   ============================================================ */
:root{
  --pine-900:{{ $themeColors['primary'] ?? '#0d3d24' }};
  --pine-800:color-mix(in srgb, var(--pine-900) 88%, white);
  --pine-700:color-mix(in srgb, var(--pine-900) 78%, white);
  --pine-600:color-mix(in srgb, var(--pine-900) 66%, white);
  --maroon-800:color-mix(in srgb, {{ $themeColors['secondary'] ?? '#601722' }} 85%, black);
  --maroon-700:{{ $themeColors['secondary'] ?? '#7a1f2b' }};
  --maroon-600:color-mix(in srgb, var(--maroon-700) 88%, white);
  --gold-500:{{ $themeColors['accent'] ?? '#c89b3c' }};
  --gold-400:color-mix(in srgb, var(--gold-500) 82%, white);
  --gold-100:color-mix(in srgb, var(--gold-500) 25%, white);
  --paper-100:{{ $themeColors['bg'] ?? '#f8f4ea' }};
  --paper-200:color-mix(in srgb, var(--paper-100) 92%, black);
  --paper-300:color-mix(in srgb, var(--paper-100) 84%, black);
  --ink-900:{{ $themeColors['text'] ?? '#211c14' }};
  --ink-700:color-mix(in srgb, var(--ink-900) 72%, white);
  --ink-500:color-mix(in srgb, var(--ink-900) 46%, white);
  --white:#fffdf8;

  --display: "Tiro Bangla", serif;
  --body: {!! $themeColors['font'] ?? "'Hind Siliguri', sans-serif" !!};
  --mono: "Inter", sans-serif;
  --numeral: "Noto Sans Bengali", "Hind Siliguri", sans-serif;

  --radius: 6px;
  --shadow-card: 0 2px 4px rgba(33,28,20,.06), 0 8px 24px rgba(33,28,20,.08);
  --shadow-lift: 0 12px 32px rgba(13,61,36,.18);
}

*{box-sizing:border-box; margin:0; padding:0;}
html{scroll-behavior:smooth;}
body{
  font-family:var(--body);
  color:var(--ink-900);
  background:var(--paper-100);
  line-height:1.7;
  overflow-x:hidden;
}
img{max-width:100%; display:block;}
a{color:inherit; text-decoration:none;}
ul{list-style:none;}
button{font-family:inherit; cursor:pointer; border:none; background:none;}
.container{max-width:1240px; margin:0 auto; padding:0 20px;}

:focus-visible{outline:3px solid var(--gold-500); outline-offset:2px;}

@media (prefers-reduced-motion: reduce){
  *{animation-duration:.001ms !important; animation-iteration-count:1 !important; transition-duration:.001ms !important; scroll-behavior:auto !important;}
}

.eyebrow{
  display:inline-flex; align-items:center; gap:8px;
  font-family:var(--mono); font-weight:700; font-size:.72rem;
  letter-spacing:.16em; text-transform:uppercase;
  color:var(--maroon-700);
}
.eyebrow::before{content:""; width:22px; height:2px; background:var(--gold-500);}

.section-head{max-width:640px; margin-bottom:44px;}
.section-head h2{
  font-family:var(--display); font-size:clamp(1.7rem,3.4vw,2.5rem);
  color:var(--pine-900); margin-top:10px; line-height:1.3;
}
.section-head p{color:var(--ink-700); margin-top:12px; font-size:1.02rem;}

section{padding:80px 0;}
.bg-alt{background:var(--paper-200);}
.bg-pine{background:var(--pine-900); color:var(--paper-100);}
.bg-pine .section-head h2{color:var(--white);}
.bg-pine .section-head p{color:#cfe3d5;}

/* ============================================================
   TOP UTILITY BAR
   ============================================================ */
.topbar{
  background:var(--pine-900);
  color:#d9ead9;
  font-family:var(--numeral);
  font-size:.8rem;
}
.topbar .container{
  display:flex; align-items:center; justify-content:space-between;
  padding-top:8px; padding-bottom:8px; gap:12px; flex-wrap:wrap;
}
.topbar-left{display:flex; gap:18px; flex-wrap:wrap;}
.topbar-left span{display:inline-flex; align-items:center; gap:6px;}
.topbar-left i{color:var(--gold-400);}
.topbar-right{display:flex; align-items:center; gap:14px;}
.topbar-right a{
  width:26px; height:26px; border-radius:50%; border:1px solid rgba(255,255,255,.25);
  display:inline-flex; align-items:center; justify-content:center; font-size:.75rem;
  transition:background .2s, border-color .2s;
}
.topbar-right a:hover{background:var(--gold-500); border-color:var(--gold-500); color:var(--pine-900);}
.topbar-right .divider{width:1px; height:16px; background:rgba(255,255,255,.2);}

/* ============================================================
   HEADER / BRAND
   ============================================================ */
.brandbar{background:var(--white); border-bottom:3px solid var(--gold-500);}
.brandbar .container{
  display:flex; align-items:center; gap:20px; padding:18px 20px; flex-wrap:wrap;
}
.brand-emblem{
  width:72px; height:72px; border-radius:50%;
  background:radial-gradient(circle at 35% 30%, var(--pine-600), var(--pine-900));
  display:flex; align-items:center; justify-content:center;
  color:var(--gold-400); font-size:1.9rem; flex-shrink:0;
  box-shadow:0 0 0 4px var(--gold-100), var(--shadow-card);
}
.brand-text{flex:1; min-width:220px;}
.brand-text .school-name{
  font-family:var(--display); font-size:clamp(1.4rem,3vw,2rem);
  color:var(--pine-900); line-height:1.25;
}
.brand-text .school-sub{color:var(--maroon-700); font-family:var(--numeral); font-weight:600; font-size:.95rem; margin-top:2px; letter-spacing:.01em;}
.brand-text .school-addr{color:var(--ink-500); font-size:.85rem; margin-top:4px; font-family:var(--mono);}
.brand-cta{display:flex; gap:10px; flex-wrap:wrap;}
.btn{
  display:inline-flex; align-items:center; gap:8px;
  padding:11px 20px; border-radius:3px; font-weight:600; font-size:.9rem;
  transition:transform .18s ease, box-shadow .18s ease, background .18s ease;
  white-space:nowrap;
}
.btn-solid{background:var(--maroon-700); color:var(--white);}
.btn-solid:hover{background:var(--maroon-800); transform:translateY(-2px); box-shadow:var(--shadow-card);}
.btn-outline{border:1.5px solid var(--pine-800); color:var(--pine-800);}
.btn-outline:hover{background:var(--pine-800); color:var(--white); transform:translateY(-2px);}
.btn-gold{background:var(--gold-500); color:var(--pine-900);}
.btn-gold:hover{background:var(--gold-400); transform:translateY(-2px); box-shadow:var(--shadow-card);}

/* ============================================================
   NAVIGATION
   ============================================================ */
.navwrap{background:var(--pine-800); position:sticky; top:0; z-index:200; box-shadow:0 2px 10px rgba(0,0,0,.12);}
.navwrap.scrolled{background:var(--pine-900);}
nav.mainnav{max-width:1240px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; padding:0 20px;}
.nav-list{display:flex;}
.nav-item{position:relative;}
.nav-item > a{
  display:flex; align-items:center; gap:6px;
  padding:16px 18px; color:var(--paper-100); font-weight:600; font-size:.92rem;
  border-bottom:3px solid transparent; transition:border-color .2s, background .2s;
}
.nav-item > a i.chev{font-size:.65rem; margin-left:2px; transition:transform .2s;}
.nav-item:hover > a, .nav-item.open > a{border-bottom-color:var(--gold-500); background:rgba(255,255,255,.06);}
.nav-item:hover > a i.chev, .nav-item.open > a i.chev{transform:rotate(180deg);}
.submenu{
  position:absolute; top:100%; left:0; min-width:230px;
  background:var(--white); border-top:3px solid var(--gold-500);
  box-shadow:var(--shadow-lift); border-radius:0 0 6px 6px;
  opacity:0; visibility:hidden; transform:translateY(8px);
  transition:opacity .2s ease, transform .2s ease, visibility .2s;
  z-index:50;
}
.nav-item:hover .submenu, .nav-item.open .submenu{opacity:1; visibility:visible; transform:translateY(0);}
.submenu li a{
  display:block; padding:12px 18px; color:var(--ink-900); font-size:.88rem; font-weight:500;
  border-bottom:1px solid var(--paper-200);
}
.submenu li:last-child a{border-bottom:none;}
.submenu li a:hover{background:var(--paper-200); color:var(--maroon-700); padding-left:22px;}

.nav-toggle{display:none; color:var(--paper-100); font-size:1.4rem; padding:14px 4px;}
.nav-search{display:flex; align-items:center; gap:8px;}
.nav-search button{color:var(--gold-400); font-size:1.05rem; padding:10px;}

/* Mobile nav */
@media (max-width:960px){
  .nav-toggle{display:block;}
  nav.mainnav{padding:6px 20px;}
  .nav-list{
    position:fixed; top:0; right:-100%; height:100vh; width:min(320px,85vw);
    background:var(--pine-900); flex-direction:column; align-items:stretch;
    padding:70px 0 30px; transition:right .35s ease; overflow-y:auto;
    box-shadow:-8px 0 24px rgba(0,0,0,.25);
  }
  .nav-list.mobile-open{right:0;}
  .nav-item > a{padding:15px 24px; border-bottom:1px solid rgba(255,255,255,.08); justify-content:space-between;}
  .submenu{
    position:static; opacity:1; visibility:visible; transform:none; box-shadow:none;
    border-top:none; border-radius:0; background:rgba(0,0,0,.18);
    max-height:0; overflow:hidden; transition:max-height .3s ease;
  }
  .nav-item.open .submenu{max-height:600px;}
  .submenu li a{color:#dcefe0; padding:12px 40px; border-bottom:1px solid rgba(255,255,255,.06);}
  .submenu li a:hover{background:rgba(255,255,255,.06); padding-left:44px;}
  .nav-overlay{position:fixed; inset:0; background:rgba(13,20,15,.55); opacity:0; visibility:hidden; transition:opacity .3s; z-index:150;}
  .nav-overlay.show{opacity:1; visibility:visible;}
  .nav-close{display:block !important; position:absolute; top:16px; right:20px; color:#fff; font-size:1.6rem; z-index:5;}
}
.nav-close{display:none;}

/* ============================================================
   MARQUEE NOTICE STRIP
   ============================================================ */
.ticker{
  background:var(--gold-100); border-bottom:1px solid var(--paper-300);
  display:flex; align-items:stretch; overflow:hidden;
}
.ticker-label{
  background:var(--maroon-700); color:var(--white); font-weight:700; font-size:.85rem;
  padding:10px 18px; display:flex; align-items:center; gap:8px; flex-shrink:0;
  clip-path:polygon(0 0,100% 0,92% 100%,0% 100%);
  padding-right:30px;
}
.ticker-track-wrap{flex:1; overflow:hidden; position:relative;}
.ticker-track{
  display:flex; gap:60px; white-space:nowrap; padding:10px 0;
  animation:ticker 12s linear infinite;
  width:max-content;
}
.ticker-track:hover{animation-play-state:paused;}
.ticker-track span{font-size:.88rem; color:var(--ink-900); font-weight:500;}
.ticker-track span b{color:var(--maroon-700);}
@keyframes ticker{from{transform:translateX(0);} to{transform:translateX(-50%);}}

/* ============================================================
   HERO SLIDER
   ============================================================ */
.hero{position:relative; height:min(620px,86vh); overflow:hidden; background:var(--pine-900);}
.slide{
  position:absolute; inset:0; opacity:0; transition:opacity 1s ease;
  background-size:cover; background-position:center;
}
.slide.active{opacity:1;}
.slide::after{
  content:""; position:absolute; inset:0;
  background:linear-gradient(90deg, rgba(13,25,17,.88) 0%, rgba(13,25,17,.55) 45%, rgba(13,25,17,.25) 100%);
}
.slide-content{
  position:relative; z-index:2; height:100%; display:flex; flex-direction:column; justify-content:center;
  max-width:1240px; margin:0 auto; padding:0 20px 60px;
  color:var(--white);
}
.slide-content .eyebrow{color:var(--gold-400);}
.slide-content .eyebrow::before{background:var(--gold-400);}
.slide-content h1{
  font-family:var(--display); font-size:clamp(2rem,4.6vw,3.6rem); line-height:1.25; margin:14px 0 18px;
  max-width:720px;
}
.slide-content p{max-width:560px; color:#e4ecdf; font-size:1.05rem; margin-bottom:26px;}
.slide-actions{display:flex; gap:14px; flex-wrap:wrap;}

.hero-dots{
  position:absolute; z-index:3; bottom:26px; left:50%; transform:translateX(-50%);
  display:flex; gap:10px;
}
.hero-dots button{
  width:34px; height:4px; background:rgba(255,255,255,.4); border-radius:2px; transition:background .25s;
}
.hero-dots button.active{background:var(--gold-500);}
.hero-arrow{
  position:absolute; z-index:3; top:50%; transform:translateY(-50%);
  width:44px; height:44px; border-radius:50%; background:rgba(255,253,248,.12);
  color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.1rem;
  border:1px solid rgba(255,255,255,.3); transition:background .2s;
}
.hero-arrow:hover{background:var(--gold-500); color:var(--pine-900);}
.hero-arrow.prev{left:20px;} .hero-arrow.next{right:20px;}
@media (max-width:640px){.hero-arrow{display:none;}}

/* ============================================================
   HOME GRID: NOTICE BOARD + QUICK INFO
   ============================================================ */
.homegrid{
  display:grid; grid-template-columns:1.15fr .85fr; gap:34px; margin-top:-64px; position:relative; z-index:5;
}
@media (max-width:900px){.homegrid{grid-template-columns:1fr; margin-top:24px;}}

.panel{
  background:var(--white); border-radius:var(--radius); box-shadow:var(--shadow-card);
  overflow:hidden;
}
.panel-head{
  background:var(--pine-900); color:var(--white); padding:16px 22px;
  display:flex; align-items:center; justify-content:space-between;
}
.panel-head h3{font-family:var(--display); font-size:1.25rem; display:flex; align-items:center; gap:10px;}
.panel-head h3 i{color:var(--gold-400);}
.panel-head a{font-size:.78rem; color:var(--gold-400); font-weight:600;}

/* Notice board = pinned paper cards */
.notice-list{padding:10px 18px 18px;}
.notice-item{
  display:flex; gap:16px; align-items:flex-start;
  padding:16px 10px; border-bottom:1px dashed var(--paper-300);
  position:relative;
}
.notice-item:last-child{border-bottom:none;}
.notice-item::before{
  content:"📌"; position:absolute; left:-4px; top:14px; font-size:.8rem; opacity:.55;
}
.notice-date{
  flex-shrink:0; width:56px; text-align:center; background:var(--maroon-700); color:var(--white);
  border-radius:4px; padding:7px 4px; font-family:var(--numeral);
}
.notice-date .d{font-size:1.15rem; font-weight:800; line-height:1;}
.notice-date .m{font-size:.65rem; text-transform:uppercase; letter-spacing:.05em; display:block; margin-top:2px;}
.notice-body a{font-weight:600; color:var(--ink-900); font-size:.96rem;}
.notice-body a:hover{color:var(--maroon-700);}
.notice-tag{
  display:inline-block; margin-top:6px; font-size:.7rem; font-weight:700; padding:2px 9px;
  border-radius:20px; background:var(--gold-100); color:var(--maroon-700);
}
.notice-tag.new{background:var(--pine-800); color:var(--white);}
.notice-empty{padding:30px 18px; text-align:center; color:var(--ink-500);}

/* Quick info panel stack */
.quickstack{display:flex; flex-direction:column; gap:22px;}
.quick-card{background:var(--white); border-radius:var(--radius); box-shadow:var(--shadow-card); padding:22px;}
.quick-card h4{font-family:var(--display); color:var(--pine-900); font-size:1.05rem; margin-bottom:14px; display:flex; gap:8px; align-items:center;}
.quick-card h4 i{color:var(--maroon-700);}
.qc-row{display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--paper-200); font-size:.88rem;}
.qc-row:last-child{border-bottom:none;}
.qc-row span:last-child{font-weight:600; color:var(--pine-800); font-family:var(--numeral); letter-spacing:.01em;}

/* ============================================================
   HISTORY + HEAD MASTER MESSAGE (split)
   ============================================================ */
.split{display:grid; grid-template-columns:.9fr 1.1fr; gap:56px; align-items:center;}
@media (max-width:880px){.split{grid-template-columns:1fr; gap:32px;}}
.split.reverse .img-col{order:2;}
@media (max-width:880px){.split.reverse .img-col{order:0;}}
.img-col{position:relative;}
.img-frame{
  position:relative; border-radius:8px; overflow:hidden; box-shadow:var(--shadow-lift);
}
.img-frame img{width:100%; aspect-ratio:4/5; object-fit:cover;}
.img-badge{
  position:absolute; bottom:-18px; right:-18px; background:var(--gold-500); color:var(--pine-900);
  padding:16px 20px; border-radius:6px; box-shadow:var(--shadow-card); text-align:center;
}
@media (max-width:520px){.img-badge{right:8px; bottom:-14px; padding:10px 14px;}}
.img-badge .num{font-family:var(--numeral); font-weight:800; font-size:1.5rem; line-height:1;}
.img-badge .lbl{font-size:.68rem; font-weight:600;}

.text-col p{color:var(--ink-700); margin-bottom:14px;}
.signature-line{
  margin-top:20px; padding-top:16px; border-top:2px solid var(--paper-300);
  display:flex; align-items:center; gap:14px;
}
.signature-line img{width:56px; height:56px; border-radius:50%; object-fit:cover;}
.signature-line strong{display:block; font-family:var(--display); color:var(--pine-900); font-size:1.05rem;}
.signature-line span{font-size:.82rem; color:var(--ink-500);}
blockquote.hm-quote{
  font-family:var(--display); font-style:italic; font-size:1.2rem; color:var(--pine-900);
  border-left:3px solid var(--gold-500); padding-left:18px; margin:16px 0;
}

/* ============================================================
   MISSION / VISION
   ============================================================ */
.mv-grid{display:grid; grid-template-columns:repeat(2,1fr); gap:26px;}
@media (max-width:700px){.mv-grid{grid-template-columns:1fr;}}
.mv-card{
  background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.15); border-radius:8px;
  padding:32px 28px; position:relative; overflow:hidden;
}
.mv-card i.big-icon{position:absolute; right:14px; top:10px; font-size:3.4rem; color:rgba(255,255,255,.06);}
.mv-card h3{font-family:var(--display); font-size:1.4rem; color:var(--gold-400); margin-bottom:12px; position:relative;}
.mv-card p{color:#d9e7dc; position:relative; font-size:.98rem;}

/* ============================================================
   STATS
   ============================================================ */
.stats-strip{
  display:grid; grid-template-columns:repeat(4,1fr); gap:0;
  background:var(--white); border-radius:var(--radius); box-shadow:var(--shadow-card); overflow:hidden;
}
@media (max-width:700px){.stats-strip{grid-template-columns:repeat(2,1fr);}}
.stat-cell{
  padding:34px 20px; text-align:center; border-right:1px solid var(--paper-200);
}
.stat-cell:last-child{border-right:none;}
.stat-cell i{color:var(--gold-500); font-size:1.6rem; margin-bottom:10px;}
.stat-num{font-family:var(--numeral); font-weight:800; font-size:2.3rem; color:var(--pine-900); line-height:1; letter-spacing:.01em;}
.stat-lbl{font-size:.85rem; color:var(--ink-500); margin-top:6px; font-weight:600;}

/* ============================================================
   ACHIEVEMENTS (medal ribbon cards)
   ============================================================ */
.ach-grid{display:grid; grid-template-columns:repeat(4,1fr); gap:24px;}
@media (max-width:960px){.ach-grid{grid-template-columns:repeat(2,1fr);}}
@media (max-width:520px){.ach-grid{grid-template-columns:1fr;}}
.ach-card{
  background:var(--white); border-radius:8px; box-shadow:var(--shadow-card); text-align:center;
  padding:38px 20px 26px; position:relative; transition:transform .25s ease, box-shadow .25s ease;
}
.ach-card:hover{transform:translateY(-6px); box-shadow:var(--shadow-lift);}
.medal{
  width:74px; height:74px; margin:0 auto 16px; border-radius:50%;
  background:conic-gradient(from 210deg, var(--gold-400), var(--gold-500) 40%, var(--gold-400) 70%, var(--gold-500));
  display:flex; align-items:center; justify-content:center; color:var(--pine-900); font-size:1.6rem;
  box-shadow:0 0 0 5px var(--paper-200), 0 6px 14px rgba(200,155,60,.4);
}
.medal::after{
  content:""; position:absolute; top:-10px; left:50%; transform:translateX(-50%);
  width:0; height:0; border-left:16px solid transparent; border-right:16px solid transparent;
  border-top:22px solid var(--maroon-700); z-index:-1;
}
.ach-card h4{font-family:var(--display); color:var(--pine-900); font-size:1.05rem; margin-bottom:6px;}
.ach-card p{font-size:.85rem; color:var(--ink-500);}

/* ============================================================
   FACILITIES
   ============================================================ */
.fac-grid{display:grid; grid-template-columns:repeat(4,1fr); gap:22px;}
@media (max-width:960px){.fac-grid{grid-template-columns:repeat(2,1fr);}}
@media (max-width:520px){.fac-grid{grid-template-columns:1fr;}}
.fac-card{
  background:var(--paper-100); border:1px solid var(--paper-300); border-radius:8px; padding:26px 22px;
  transition:border-color .2s, background .2s;
}
.fac-card:hover{border-color:var(--gold-500); background:var(--white);}
.fac-card .ic{
  width:52px; height:52px; border-radius:10px; background:var(--pine-900); color:var(--gold-400);
  display:flex; align-items:center; justify-content:center; font-size:1.3rem; margin-bottom:16px;
}
.fac-card h4{font-family:var(--display); font-size:1.05rem; color:var(--pine-900); margin-bottom:6px;}
.fac-card p{font-size:.85rem; color:var(--ink-500);}

/* ============================================================
   PHOTO GALLERY
   ============================================================ */
.gal-grid{
  display:grid; grid-template-columns:repeat(4,1fr); grid-auto-rows:160px; gap:16px;
}
@media (max-width:860px){.gal-grid{grid-template-columns:repeat(2,1fr); grid-auto-rows:150px;}}
@media (max-width:480px){.gal-grid{grid-template-columns:1fr 1fr; grid-auto-rows:130px;}}
.gal-item{
  position:relative; border-radius:6px; overflow:hidden; cursor:zoom-in;
  box-shadow:var(--shadow-card);
}
.gal-item.tall{grid-row:span 2;}
.gal-item.wide{grid-column:span 2;}
.gal-item img{width:100%; height:100%; object-fit:cover; transition:transform .5s ease;}
.gal-item:hover img{transform:scale(1.08);}
.gal-caption{
  position:absolute; inset:auto 0 0 0; padding:22px 14px 10px;
  background:linear-gradient(0deg, rgba(13,61,36,.9), transparent);
  color:var(--white); font-size:.82rem; font-weight:600;
  opacity:0; transform:translateY(8px); transition:opacity .3s, transform .3s;
}
.gal-item:hover .gal-caption{opacity:1; transform:translateY(0);}
.gal-caption i{color:var(--gold-400); margin-right:6px;}

.lightbox{
  position:fixed; inset:0; z-index:500; background:rgba(13,20,15,.92);
  display:flex; align-items:center; justify-content:center; padding:24px;
  opacity:0; visibility:hidden; transition:opacity .3s;
}
.lightbox.show{opacity:1; visibility:visible;}
.lightbox figure{max-width:900px; width:100%; text-align:center;}
.lightbox img{max-height:74vh; width:auto; margin:0 auto; border-radius:6px; box-shadow:0 20px 60px rgba(0,0,0,.5);}
.lightbox figcaption{color:var(--paper-100); margin-top:16px; font-family:var(--display); font-size:1.1rem;}
.lb-close, .lb-nav{
  position:absolute; color:#fff; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.3);
  width:46px; height:46px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.15rem;
  transition:background .2s;
}
.lb-close:hover, .lb-nav:hover{background:var(--gold-500); color:var(--pine-900);}
.lb-close{top:22px; right:22px;}
.lb-nav.prev{left:22px; top:50%; transform:translateY(-50%);}
.lb-nav.next{right:22px; top:50%; transform:translateY(-50%);}
@media (max-width:640px){.lb-nav{width:38px; height:38px; font-size:1rem;}}

/* ============================================================
   STAFF
   ============================================================ */
.staff-tabs{display:flex; gap:10px; margin-bottom:32px; flex-wrap:wrap;}
.staff-tab{
  padding:9px 20px; border-radius:20px; font-size:.85rem; font-weight:600;
  border:1.5px solid var(--paper-300); color:var(--ink-700); background:var(--white);
}
.staff-tab.active{background:var(--pine-800); border-color:var(--pine-800); color:var(--white);}
.staff-grid{display:grid; grid-template-columns:repeat(4,1fr); gap:24px;}
@media (max-width:960px){.staff-grid{grid-template-columns:repeat(2,1fr);}}
@media (max-width:560px){.staff-grid{grid-template-columns:1fr;}}
.staff-card{
  background:var(--white); border-radius:8px; overflow:hidden; box-shadow:var(--shadow-card);
  text-align:center; transition:transform .25s;
}
.staff-card:hover{transform:translateY(-6px);}
.staff-photo{aspect-ratio:1/1; overflow:hidden; background:var(--paper-300); position:relative;}
.staff-photo img{width:100%; height:100%; object-fit:cover;}
.staff-info{padding:16px 12px 20px;}
.staff-info h4{font-family:var(--display); color:var(--pine-900); font-size:1.02rem;}
.staff-info span{font-size:.8rem; color:var(--maroon-700); font-weight:600; display:block; margin-top:2px;}
.staff-info .subj{font-size:.76rem; color:var(--ink-500); margin-top:4px;}
.hidden-card{display:none;}

/* ============================================================
   BLOG / NEWS
   ============================================================ */
.news-grid{display:grid; grid-template-columns:1.3fr 1fr 1fr; gap:26px;}
@media (max-width:900px){.news-grid{grid-template-columns:1fr; }}
.news-card{background:var(--white); border-radius:8px; overflow:hidden; box-shadow:var(--shadow-card); display:flex; flex-direction:column;}
.news-img{aspect-ratio:16/10; overflow:hidden; position:relative; background:var(--paper-300);}
.news-img img{width:100%; height:100%; object-fit:cover; transition:transform .4s;}
.news-card:hover .news-img img{transform:scale(1.06);}
.news-body{padding:20px; flex:1; display:flex; flex-direction:column;}
.news-meta{font-size:.75rem; color:var(--ink-500); font-family:var(--numeral); margin-bottom:8px; display:flex; gap:12px;}
.news-body h3{font-family:var(--display); font-size:1.1rem; color:var(--pine-900); line-height:1.4; margin-bottom:10px;}
.news-card.featured .news-body h3{font-size:1.35rem;}
.news-body p{font-size:.88rem; color:var(--ink-700); flex:1;}
.news-more{margin-top:14px; font-size:.82rem; font-weight:700; color:var(--maroon-700); display:inline-flex; align-items:center; gap:6px;}

/* ============================================================
   GENERIC INNER PAGE (about/faculty/notice-board/gallery/contact/cms)
   ============================================================ */
.inner-hero{background:var(--pine-900); color:var(--paper-100); padding:56px 0;}
.inner-hero h1{font-family:var(--display); font-size:clamp(1.7rem,4vw,2.6rem);}
.inner-hero .eyebrow{color:var(--gold-400);}
.inner-hero .eyebrow::before{background:var(--gold-400);}
.content-card{background:var(--white); border-radius:8px; box-shadow:var(--shadow-card); padding:28px 24px;}
@media (min-width:768px){.content-card{padding:40px;}}
.content-card :where(h1,h2,h3){font-family:var(--display); color:var(--pine-900); margin:1.2em 0 .5em;}
.content-card p{color:var(--ink-700); margin-bottom:1em; line-height:1.8;}
.content-card img{border-radius:6px; margin:1em 0;}
.content-card a{color:var(--maroon-700); font-weight:600; text-decoration:underline;}

/* ============================================================
   FOOTER
   ============================================================ */
footer{background:var(--pine-900); color:#cfe3d5; padding-top:64px;}
.foot-grid{display:grid; grid-template-columns:1.4fr 1fr 1fr 1.2fr; gap:40px; padding-bottom:40px; border-bottom:1px solid rgba(255,255,255,.12);}
@media (max-width:880px){.foot-grid{grid-template-columns:1fr 1fr; row-gap:36px;}}
@media (max-width:520px){.foot-grid{grid-template-columns:1fr;}}
.foot-col h5{font-family:var(--display); color:var(--white); font-size:1.1rem; margin-bottom:18px;}
.foot-brand{display:flex; gap:12px; align-items:center; margin-bottom:14px;}
.foot-brand .brand-emblem{width:50px; height:50px; font-size:1.3rem;}
.foot-brand strong{font-family:var(--display); color:var(--white); font-size:1.1rem;}
.foot-col p{font-size:.86rem; color:#adc7b3;}
.foot-col ul li{margin-bottom:10px;}
.foot-col ul li a{font-size:.87rem; color:#cfe3d5; transition:color .2s;}
.foot-col ul li a:hover{color:var(--gold-400); padding-left:4px;}
.foot-col ul li a i{margin-right:8px; color:var(--gold-400); width:14px;}
.foot-social{display:flex; gap:10px; margin-top:16px;}
.foot-social a{
  width:36px; height:36px; border-radius:50%; border:1px solid rgba(255,255,255,.25);
  display:flex; align-items:center; justify-content:center; transition:background .2s;
}
.foot-social a:hover{background:var(--gold-500); color:var(--pine-900); border-color:var(--gold-500);}
.copyline{
  display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;
  padding:22px 0; font-size:.8rem; color:#9fbba5;
}
.copyline a{color:var(--gold-400); font-weight:600;}

/* back to top */
#backTop{
  position:fixed; right:22px; bottom:22px; z-index:300;
  width:46px; height:46px; border-radius:50%; background:var(--maroon-700); color:#fff;
  display:flex; align-items:center; justify-content:center; box-shadow:var(--shadow-lift);
  opacity:0; visibility:hidden; transform:translateY(10px); transition:all .3s;
}
#backTop.show{opacity:1; visibility:visible; transform:none;}
#backTop:hover{background:var(--maroon-800);}

/* fade-in on scroll */
.reveal{opacity:0; transform:translateY(22px); transition:opacity .7s ease, transform .7s ease;}
.reveal.in{opacity:1; transform:none;}
</style>
