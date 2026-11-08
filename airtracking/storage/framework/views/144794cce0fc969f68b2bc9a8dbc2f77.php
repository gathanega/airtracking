<?php $__env->startSection('title', 'Peta lokasi · AirTracking'); ?>

<?php echo $__env->make('partials.preloader', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php $__env->startPush('head'); ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<link rel="stylesheet" href="<?php echo e(asset('css/lokasi.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div>
  <h1>Peta lokasi alat</h1>
  <div class="sub">Kota Malang dan sekitarnya</div>
</div>

<section class="tiles" id="tiles" aria-label="Ringkasan"></section>
<div class="err" id="err" role="alert"></div>

<div class="lokgrid">
  <section class="card mapbox" aria-label="Peta lokasi alat">
    <div id="peta"></div>
    <div class="maplegend" aria-label="Keterangan warna titik">
      <span><i class="ok"></i>Normal</span><span><i class="warn"></i>Waspada</span>
      <span><i class="crit"></i>Perlu perhatian</span><span><i class="off"></i>Belum ada data</span>
      <button class="btn s ghost" type="button" id="reset" style="margin-left:auto">Pusatkan ke Malang</button>
    </div>
  </section>
  <div>
    <h2 style="margin-bottom:10px">Titik alat</h2>
    <div class="loclist" id="list"></div>
    <div id="nocoord"></div>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>window.AIR_CONFIG = { API_BASE: "", HOME: <?php echo json_encode(route('home'), 15, 512) ?> };</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="<?php echo e(asset('js/airtracking.js')); ?>"></script>
<script src="<?php echo e(asset('js/peta.js')); ?>"></script>
<script src="<?php echo e(asset('js/lokasi.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Than\Documents\Default Project\airtracking-main\airtracking\resources\views/lokasi.blade.php ENDPATH**/ ?>