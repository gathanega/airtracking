{{-- Layar muat penuh halaman, mengikuti gaya preloader CV. Surya Samudera Mandiri:
     logo muncul membesar sambil berputar, tahan sebentar, lalu mengecil dan memudar.
     Dipakai di halaman publik yang datanya dimuat lewat JavaScript. --}}
@push('head')
<style id="preloader-css">
  #preloader{position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;background:var(--bg,#F4F9FF);opacity:1;visibility:visible;transition:opacity .3s ease,visibility 0s;animation:preloader-failsafe .01s linear 8s forwards}
  #preloader.is-hidden{opacity:0;visibility:hidden;pointer-events:none;transition:opacity .5s ease,visibility 0s linear .5s}
  #preloader svg{width:88px;height:88px;animation:preloader-logo 1.1s infinite;will-change:transform,opacity}
  #preloader.is-hidden svg{animation-play-state:paused}
  @keyframes preloader-logo{
    0%{opacity:0;transform:scale(.5) rotate(-90deg);animation-timing-function:cubic-bezier(.34,1.56,.64,1)}
    30%{opacity:1;transform:scale(1) rotate(0deg)}
    60%{opacity:1;transform:scale(1) rotate(0deg);animation-timing-function:cubic-bezier(.55,0,.8,.4)}
    90%,100%{opacity:0;transform:scale(.5) rotate(90deg)}
  }
  @keyframes preloader-failsafe{to{opacity:0;visibility:hidden;pointer-events:none}}
  @keyframes preloader-pulse{0%,100%{opacity:.35}50%{opacity:1}}
  @media (prefers-reduced-motion:reduce){#preloader svg{animation:preloader-pulse 1.6s ease-in-out infinite!important;animation-duration:1.6s!important}}
  @media print{#preloader{display:none!important}}
</style>
<noscript><style>#preloader{display:none!important}</style></noscript>
@endpush

@push('preloader')
<div id="preloader" role="status" aria-label="Memuat halaman">
  <svg viewBox="0 0 64 64" aria-hidden="true" focusable="false">
    <defs><linearGradient id="ath-pre-g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#4FC3F7"/><stop offset="1" stop-color="#1E6FD9"/></linearGradient></defs>
    <path d="M32 4C32 4 12 27 12 40a20 20 0 0 0 40 0C52 27 32 4 32 4Z" fill="url(#ath-pre-g)"/>
    <path d="M18 42c4-3.2 8-3.2 12 0s8 3.2 12 0c1.8-1.4 3.4-2.2 5-2.4" stroke="#fff" stroke-width="3.2" fill="none" stroke-linecap="round"/>
  </svg>
</div>
@endpush
