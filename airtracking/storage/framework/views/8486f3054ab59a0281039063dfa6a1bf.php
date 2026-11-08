<?php $__env->startSection('title', 'Titik lokasi · AirTracking'); ?>

<?php $__env->startPush('head'); ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<link rel="stylesheet" href="<?php echo e(asset('css/lokasi.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('partials.admin-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<div class="lokgrid">
  <section class="card mapbox" aria-label="Peta penandaan titik">
    <div id="peta"></div>
    <div class="maplegend">
      <span><i class="sel" style="background:var(--blue)"></i>Alat yang sedang diatur</span>
      <span><i class="off"></i>Alat lain</span>
      <button class="btn s ghost" type="button" id="reset" style="margin-left:auto">Pusatkan ke Malang</button>
    </div>
  </section>

  <section class="panel">
    <h2 style="margin-bottom:6px">Tandai titik alat</h2>
    <p class="note" style="margin:0 0 12px">Pilih alat, lalu klik peta atau geser penanda. Koordinat juga bisa diketik. Titik tampil di halaman <a href="<?php echo e(route('lokasi')); ?>">Peta lokasi</a>.</p>

    <?php if($devices->isEmpty()): ?>
      <div class="empty">Belum ada alat. Daftarkan alat dulu di <a href="<?php echo e(route('admin.devices')); ?>">Kelola alat</a>.</div>
    <?php else: ?>
      <form id="lform" method="POST" action="#">
        <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
        <label>Alat<select id="dsel" aria-label="Pilih alat"></select></label>
        <div class="pickrow">
          <label>Latitude<input id="lat" name="latitude" inputmode="decimal" placeholder="-7.98000" autocomplete="off"></label>
          <label>Longitude<input id="lng" name="longitude" inputmode="decimal" placeholder="112.63000" autocomplete="off"></label>
        </div>
        <?php if($errors->any()): ?><div class="err" role="alert"><?php echo e($errors->first()); ?></div><?php endif; ?>
        <div class="mapnote" id="hint" role="status"></div>
        <div class="row" style="margin-top:10px">
          <button class="btn" type="submit" id="save" disabled>Simpan titik</button>
        </div>
      </form>
    <?php endif; ?>
  </section>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
  window.LOKASI = {
    devices: <?php echo json_encode($devices, 15, 512) ?>,
    action: <?php echo json_encode(route('admin.devices.location', ['device' => '__ID__']), 512) ?>,
  };
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="<?php echo e(asset('js/peta.js')); ?>"></script>
<script src="<?php echo e(asset('js/admin-lokasi.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Than\Documents\Default Project\airtracking-main\airtracking\resources\views/admin/lokasi.blade.php ENDPATH**/ ?>