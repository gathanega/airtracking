<?php $__env->startSection('title', 'Kelola alat · AirTracking'); ?>
<?php $__env->startSection('content'); ?>
<?php echo $__env->make('partials.admin-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<section class="panel" style="margin-bottom:14px">
  <div class="head" style="margin-bottom:0">
    <div>
      <b>Integrasi alat &amp; API</b>
      <div class="note" style="margin:2px 0 0">Daftar endpoint, field <code>/api/ingest</code>, contoh curl, contoh respons, dan unduh OpenAPI.</div>
    </div>
    <a class="btn s ghost" href="<?php echo e(route('admin.api')); ?>">Buka API &amp; integrasi</a>
  </div>
</section>

<section class="panel" style="margin-bottom:14px">
  <div class="head" style="margin-bottom:0">
    <div>
      <b>Setup WiFi alat</b>
      <div class="note" style="margin:2px 0 0">Panduan mode AP/Internet dan pembuat konfigurasi JSON untuk alat (dikelola lewat ESP32).</div>
    </div>
    <a class="btn s ghost" href="<?php echo e(route('admin.wifi')); ?>">Buka Setup WiFi alat</a>
  </div>
</section>

<section class="panel" style="margin-bottom:14px">
  <div class="head" style="margin-bottom:0">
    <div>
      <b>Rentang normal</b>
      <div class="note" style="margin:2px 0 0">Ubah baku mutu per parameter untuk status Normal / Waspada / Di luar rentang.</div>
    </div>
    <a class="btn s ghost" href="<?php echo e(route('admin.limits')); ?>">Buka Rentang normal</a>
  </div>
</section>

<?php if(session('credential')): ?>
  <div class="keybox" role="alert">
    <b>Credential alat <?php echo e(session('credential.code')); ?>:</b> <code id="newkey"><?php echo e(session('credential.key')); ?></code>
    <button class="btn s ghost" type="button" onclick="navigator.clipboard.writeText(document.getElementById('newkey').textContent)">Salin</button>
    <div class="note">Catat sekarang dan masukkan ke alat lewat halaman AP. Credential tidak bisa dilihat lagi; bila hilang, gunakan “Ganti credential”.</div>
  </div>
<?php endif; ?>

<section class="panel">
  <h2 style="margin-bottom:10px">Registrasi alat baru</h2>
  <form method="POST" action="<?php echo e(route('admin.devices.store')); ?>">
    <?php echo csrf_field(); ?>
    <div class="f">
      <label>ID alat<input name="code" value="<?php echo e(old('code')); ?>" placeholder="AIR-01" required></label>
      <label>Nama<input name="name" value="<?php echo e(old('name')); ?>" placeholder="Kolam Utama" required></label>
      <label>Kode lokasi<input name="location_code" value="<?php echo e(old('location_code')); ?>" placeholder="LOK-01" required></label>
      <label>Latitude (opsional)<input name="latitude" value="<?php echo e(old('latitude')); ?>" inputmode="decimal" placeholder="-7.9800"></label>
      <label>Longitude (opsional)<input name="longitude" value="<?php echo e(old('longitude')); ?>" inputmode="decimal" placeholder="112.6300"></label>
      <label>Credential (min. 20 karakter; kosongkan untuk dibuat acak)<input name="credential" minlength="20" autocomplete="off"></label>
    </div>
    <?php if($errors->any()): ?><div class="err" role="alert"><?php echo e($errors->first()); ?></div><?php endif; ?>
    <button class="btn" type="submit">Tambah alat</button>
  </form>
</section>

<section class="panel">
  <h2 style="margin-bottom:10px">Alat terdaftar (<?php echo e($devices->count()); ?>)</h2>
  <div class="tw"><table>
    <tr><th>ID</th><th>Nama</th><th>Lokasi</th><th>Koordinat</th><th>Aktif</th><th>Credential</th><th>Terakhir kirim</th><th></th></tr>
    <?php $__empty_1 = true; $__currentLoopData = $devices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <tr>
        <td class="mono"><?php echo e($d->code); ?></td>
        <td><input form="u<?php echo e($d->id); ?>" name="name" value="<?php echo e($d->name); ?>" aria-label="Nama <?php echo e($d->code); ?>"></td>
        <td><input form="u<?php echo e($d->id); ?>" name="location_code" value="<?php echo e($d->location_code); ?>" aria-label="Lokasi <?php echo e($d->code); ?>"></td>
        <td class="mono"><?php if($d->latitude !== null): ?><a href="<?php echo e(route('admin.locations', ['alat' => $d->code])); ?>"><?php echo e(number_format($d->latitude, 5)); ?>, <?php echo e(number_format($d->longitude, 5)); ?></a><?php else: ?><a href="<?php echo e(route('admin.locations', ['alat' => $d->code])); ?>">Tandai</a><?php endif; ?></td>
        <td><label><input form="u<?php echo e($d->id); ?>" type="checkbox" name="is_active" value="1" <?php if($d->is_active): echo 'checked'; endif; ?>>Ya</label></td>
        <td class="mono"><?php echo e($d->credential ? '••••'.$d->credential->key_hint : '–'); ?></td>
        <td><?php echo e($d->last_seen_at ? $d->last_seen_at->diffForHumans() : 'belum pernah'); ?></td>
        <td style="white-space:nowrap">
          <form id="u<?php echo e($d->id); ?>" class="inline" method="POST" action="<?php echo e(route('admin.devices.update', $d)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?><button class="btn s" type="submit">Simpan</button></form>
          <form class="inline" method="POST" action="<?php echo e(route('admin.devices.rotate', $d)); ?>" onsubmit="return confirm('Ganti credential <?php echo e($d->code); ?>? Alat harus disetel ulang dengan credential baru.')"><?php echo csrf_field(); ?><button class="btn s ghost" type="submit">Ganti credential</button></form>
          <form class="inline" method="POST" action="<?php echo e(route('admin.devices.destroy', $d)); ?>" onsubmit="return confirm('Hapus <?php echo e($d->code); ?> beserta seluruh riwayat datanya?')"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="btn s d" type="submit">Hapus</button></form>
        </td>
      </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <tr><td colspan="8" style="text-align:center;color:var(--ink2)">Belum ada alat.</td></tr>
    <?php endif; ?>
  </table></div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Than\Documents\Default Project\airtracking-main\airtracking\resources\views/admin/devices.blade.php ENDPATH**/ ?>