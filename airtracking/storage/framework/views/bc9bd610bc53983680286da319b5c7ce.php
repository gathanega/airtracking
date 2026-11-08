
<?php
    $admin = auth()->check() && auth()->user()->isAdmin();
    $inAdmin = $admin && request()->routeIs('admin.*');
?>
<?php
    if ($inAdmin) {
        $menu = [
            ['Kelola alat', route('admin.devices'), request()->routeIs('admin.devices*')],
            ['Titik lokasi', route('admin.locations'), request()->routeIs('admin.locations')],
            ['Audit admin', route('admin.audit'), request()->routeIs('admin.audit')],
            ['Dashboard publik', route('home'), false],
        ];
    } else {
        $menu = [
            ['Dashboard', route('home'), request()->routeIs('home')],
            ['Peta lokasi', route('lokasi'), request()->routeIs('lokasi')],
        ];
    }
?>
<header class="ath">
  <div class="ath-bar">
    <a class="ath-brand" href="<?php echo e(route('home')); ?>" aria-label="AirTracking, ke dashboard publik">
      <svg viewBox="0 0 64 64" width="52" height="52" aria-hidden="true" focusable="false">
        <defs><linearGradient id="ath-g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#4FC3F7"/><stop offset="1" stop-color="#1E6FD9"/></linearGradient></defs>
        <path d="M32 4C32 4 12 27 12 40a20 20 0 0 0 40 0C52 27 32 4 32 4Z" fill="url(#ath-g)"/>
        <path d="M18 42c4-3.2 8-3.2 12 0s8 3.2 12 0c1.8-1.4 3.4-2.2 5-2.4" stroke="#fff" stroke-width="3.2" fill="none" stroke-linecap="round"/>
      </svg>
      <span><b>Air<i>Tracking</i></b><small><?php echo e($inAdmin ? 'Panel admin · '.auth()->user()->email : 'Pemantauan kualitas air'); ?></small></span>
    </a>

    <button class="ath-burger" type="button" aria-controls="ath-nav" aria-expanded="false" aria-label="Buka menu">
      <svg class="m" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
      <svg class="x" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
    </button>

    <nav class="ath-nav" id="ath-nav" aria-label="Menu utama">
      <?php $__currentLoopData = $menu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $href, $on]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a class="ath-link" href="<?php echo e($href); ?>" <?php if($on): ?> aria-current="page" <?php endif; ?>><?php echo e($label); ?></a>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

      <?php if(!$inAdmin): ?>
        <button class="ath-theme" type="button" id="ath-theme-btn" aria-label="Buka pengatur tema">
          <svg class="ath-theme-ic" data-theme="sun" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M22 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M19.1 4.9l-1.4 1.4M6.3 17.7l-1.4 1.4"/></svg>
          <svg class="ath-theme-ic" data-theme="moon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8z"/></svg>
        </button>
      <?php endif; ?>

      <?php if($inAdmin): ?>
        <form class="ath-out" method="POST" action="<?php echo e(route('admin.logout')); ?>"><?php echo csrf_field(); ?><button class="ath-cta" type="submit">Keluar</button></form>
      <?php elseif($admin): ?>
        <a class="ath-cta" href="<?php echo e(route('admin.devices')); ?>">Panel admin</a>
      <?php else: ?>
        <a class="ath-cta" href="<?php echo e(route('admin.login')); ?>">Masuk admin</a>
      <?php endif; ?>
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
    var tb = document.getElementById('ath-theme-btn');
    if (tb) {
      tb.addEventListener('click', function () {
        var dark = document.documentElement.getAttribute('data-theme') === 'dark';
        var t = dark ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', t);
        try { localStorage.setItem('airtheme', t); } catch (e) { }
      });
    }
  })();
</script>
<?php /**PATH C:\Users\Than\Documents\Default Project\airtracking-main\airtracking\resources\views/partials/site-header.blade.php ENDPATH**/ ?>