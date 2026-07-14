<div id="galleryLightboxOverlay" class="gallery-lightbox-overlay">
  <button type="button" class="gallery-lightbox-close" aria-label="বন্ধ করুন"><i class="fas fa-times"></i></button>
  <button type="button" class="gallery-lightbox-nav gallery-lightbox-prev" aria-label="পূর্ববর্তী"><i class="fas fa-chevron-left"></i></button>
  <button type="button" class="gallery-lightbox-nav gallery-lightbox-next" aria-label="পরবর্তী"><i class="fas fa-chevron-right"></i></button>
  <img id="galleryLightboxImg" src="" alt="">
</div>

<style>
.gallery-lightbox-overlay {
  position: fixed; inset: 0; z-index: 9999; background: rgba(10,10,20,.92);
  display: flex; align-items: center; justify-content: center; padding: 24px;
  opacity: 0; visibility: hidden; transition: opacity .25s;
}
.gallery-lightbox-overlay.show { opacity: 1; visibility: visible; }
.gallery-lightbox-overlay img { max-width: 92vw; max-height: 86vh; border-radius: 8px; box-shadow: 0 20px 60px rgba(0,0,0,.5); }
.gallery-lightbox-close, .gallery-lightbox-nav {
  position: absolute; color: #fff; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.3);
  width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; cursor: pointer; transition: background .2s;
}
.gallery-lightbox-close:hover, .gallery-lightbox-nav:hover { background: rgba(255,255,255,.3); }
.gallery-lightbox-close { top: 22px; right: 22px; }
.gallery-lightbox-prev { left: 22px; top: 50%; transform: translateY(-50%); }
.gallery-lightbox-next { right: 22px; top: 50%; transform: translateY(-50%); }
@media (max-width: 640px) { .gallery-lightbox-nav { width: 38px; height: 38px; font-size: .95rem; } }
</style>

<script>
(function () {
  const overlay = document.getElementById('galleryLightboxOverlay');
  const img = document.getElementById('galleryLightboxImg');
  if (!overlay || !img) return;

  const items = Array.from(document.querySelectorAll('.gallery-lightbox-trigger'));
  let index = 0;

  function open(i) {
    index = i;
    img.src = items[index].dataset.src;
    overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
  function close() {
    overlay.classList.remove('show');
    document.body.style.overflow = '';
  }
  function nav(delta) {
    if (!items.length) return;
    index = (index + delta + items.length) % items.length;
    img.src = items[index].dataset.src;
  }

  items.forEach((el, i) => el.addEventListener('click', () => open(i)));
  overlay.querySelector('.gallery-lightbox-close').addEventListener('click', close);
  overlay.querySelector('.gallery-lightbox-prev').addEventListener('click', () => nav(-1));
  overlay.querySelector('.gallery-lightbox-next').addEventListener('click', () => nav(1));
  overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
  document.addEventListener('keydown', (e) => {
    if (!overlay.classList.contains('show')) return;
    if (e.key === 'Escape') close();
    if (e.key === 'ArrowLeft') nav(-1);
    if (e.key === 'ArrowRight') nav(1);
  });
})();
</script>
