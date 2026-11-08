<?php $__env->startSection('title', 'AirTracking · Dashboard kualitas air'); ?>

<?php echo $__env->make('partials.preloader', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php $__env->startSection('content'); ?>
<div>
  <h1>Dashboard kualitas air</h1>
  <div class="sub">Terbuka untuk umum, tanpa login</div>
</div>
<span class="badge-ex" id="demo" hidden style="align-self:flex-start">Data contoh, server belum terhubung</span>

<section class="tiles" id="tiles" aria-label="Ringkasan"></section>
<h2>Daftar alat</h2>
<section class="grid" id="devs"></section>
<div id="detail" style="display:flex;flex-direction:column;gap:16px"></div>
<section class="panel" id="limpanel"></section>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>window.AIR_CONFIG = { API_BASE: "" };</script>
<script src="<?php echo e(asset('js/airtracking.js')); ?>"></script>
<script src="<?php echo e(asset('js/dashboard.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Than\Documents\Default Project\airtracking-main\airtracking\resources\views/dashboard.blade.php ENDPATH**/ ?>