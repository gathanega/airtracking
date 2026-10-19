{{-- Header bersama AirTracking (dipasang otomatis oleh layouts/app). Menu admin tampil di halaman /admin saat admin login. --}}
@php
    $admin = auth()->check() && auth()->user()->isAdmin();
    $inAdmin = $admin && request()->routeIs('admin.*');
@endphp
@php
    if ($inAdmin) {
        $menu = [
            ['Kelola alat', route('admin.devices'), request()->routeIs('admin.devices*')],
            ['Titik lokasi', route('admin.locations'), request()->routeIs('admin.locations')],
            ['Setup WiFi alat', route('admin.wifi'), request()->routeIs('admin.wifi')],
            ['Rentang normal', route('admin.limits'), request()->routeIs('admin.limits*')],
            ['Dashboard publik', route('home'), false],
        ];
    } else {
        $menu = [
            ['Dashboard', route('home'), request()->routeIs('home')],
            ['Peta lokasi', route('lokasi'), request()->routeIs('lokasi')],
        ];
    }
@endphp
<header class="ath">
  <div class="ath-bar">
    <a class="ath-brand" href="{{ route('home') }}" aria-label="AirTracking, ke dashboard publik">
      <svg viewBox="0 0 64 64" width="52" height="52" aria-hidden="true" focusable="false">
        <defs><linearGradient id="ath-g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#4FC3F7"/><stop offset="1" stop-color="#1E6FD9"/></linearGradient></defs>
        <path d="M32 4C32 4 12 27 12 40a20 20 0 0 0 40 0C52 27 32 4 32 4Z" fill="url(#ath-g)"/>
        <path d="M18 42c4-3.2 8-3.2 12 0s8 3.2 12 0c1.8-1.4 3.4-2.2 5-2.4" stroke="#fff" stroke-width="3.2" fill="none" stroke-linecap="round"/>
      </svg>
      <span><b>Air<i>Tracking</i></b><small>{{ $inAdmin ? 'Panel admin · '.auth()->user()->email : 'Pemantauan kualitas air' }}</small></span>
    </a>

    <button class="ath-burger" type="button" aria-controls="ath-nav" aria-expanded="false" aria-label="Buka menu">
      <svg class="m" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
      <svg class="x" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
    </button>

    <nav class="ath-nav" id="ath-nav" aria-label="Menu utama">
      @foreach ($menu as [$label, $href, $on])
        <a class="ath-link" href="{{ $href }}" @if ($on) aria-current="page" @endif>{{ $label }}</a>
      @endforeach

      @if ($inAdmin)
        <form class="ath-out" method="POST" action="{{ route('admin.logout') }}">@csrf<button class="ath-cta" type="submit">Keluar</button></form>
      @elseif ($admin)
        <a class="ath-cta" href="{{ route('admin.devices') }}">Panel admin</a>
      @else
        <a class="ath-cta" href="{{ route('admin.login') }}">Masuk admin</a>
      @endif
    </nav>
  </div>
</header>
<script>
  (function () {
    var b = document.querySelector('.ath-burger'), n = document.getElementById('ath-nav');
    if (!b || !n) return;
    b.addEventListener('click', function () {
      var open = n.classList.toggle('is-open');
      b.setAttribute('aria-expanded', open); b.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
    });
  })();
</script>
