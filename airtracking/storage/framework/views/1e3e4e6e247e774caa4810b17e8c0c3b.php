
<?php $__env->startSection('title', 'API & integrasi · AirTracking'); ?>
<?php $__env->startSection('content'); ?>
<?php echo $__env->make('partials.admin-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<div class="doc">
<section class="panel">
  <div class="head">
    <div>
      <h2>API &amp; integrasi</h2>
      <p class="note" style="margin:4px 0 0">Semua endpoint mengembalikan JSON (kecuali CSV). API publik hanya baca dan dibatasi 60 permintaan/menit per IP.</p>
    </div>
    <a class="btn" href="<?php echo e(route('admin.api.openapi')); ?>">Unduh OpenAPI (.yaml)</a>
  </div>

  <div class="keybox" style="margin-top:12px">
    <b>Endpoint kirim data alat:</b> <code>POST <?php echo e($base); ?>/api/ingest</code>
    <div class="note">Autentikasi memakai <code>id</code> + <code>key</code> (credential alat). Jangan pernah menaruh credential di kode frontend atau repositori.</div>
  </div>
</section>

<section class="panel" style="margin-top:14px">
  <h3 style="margin-bottom:8px">Endpoint publik (tanpa login)</h3>
  <div class="tw"><table class="l">
    <tr><th>Metode</th><th>Alamat</th><th>Fungsi</th><th>Batas</th></tr>
    <?php $__currentLoopData = $public; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$method, $path, $desc, $limit]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <tr>
        <td><span class="pill"><?php echo e($method); ?></span></td>
        <td class="mono"><?php echo e($path); ?></td>
        <td><?php echo e($desc); ?></td>
        <td class="muted"><?php echo e($limit); ?></td>
      </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </table></div>
</section>

<section class="panel" style="margin-top:14px">
  <h3 style="margin-bottom:8px">Field <code>POST /api/ingest</code></h3>
  <div class="tw"><table class="l">
    <tr><th>Field</th><th>Tipe</th><th>Wajib</th><th>Keterangan</th></tr>
    <?php $__currentLoopData = $ingestFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$field, $type, $need, $desc]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <tr>
        <td class="mono"><?php echo e($field); ?></td>
        <td class="muted"><?php echo e($type); ?></td>
        <td><?php echo e($need); ?></td>
        <td><?php echo e($desc); ?></td>
      </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </table></div>
  <p class="note">Balasan sukses: <code>{"ok":true,"saved":N,"skipped":N,"ts_rejected":N,"ts_corrected":N,"heartbeat":false}</code>. <code>401</code> bila ID/credential salah; <code>422</code> bila nilai di luar batas fisik, semua record kosong, atau tidak ada pengukuran valid dan bukan heartbeat. Heartbeat (tanpa nilai sensor tetapi ada <code>fw</code>/<code>bat</code>/<code>cal_*</code>/<code>sd_pending</code>) dibalas <code>heartbeat:true</code> tanpa menambah baris pengukuran.</p>
</section>

<section class="panel" style="margin-top:14px">
  <h3 style="margin-bottom:8px">Contoh kirim data (curl)</h3>
  <pre class="mono" style="overflow:auto;padding:12px;border-radius:12px;background:#0F2A4A;color:#E7F1FF"><?php echo e($curl); ?></pre>
  <p class="note">Ganti <code>&lt;credential-alat&gt;</code> dengan credential yang tampil sekali saat alat dibuat/di-rotasi di <a href="<?php echo e(route('admin.devices')); ?>">Kelola alat</a>.</p>
</section>
<section class="panel" style="margin-top:14px">
  <h3 style="margin-bottom:8px">Contoh respons satu alat</h3>
  <p class="note" style="margin:4px 0 10px">Bentuk JSON yang dikembalikan oleh <code>GET /api/public/devices/{id}</code>.</p>
  <pre class="mono" id="cp_r" style="overflow:auto;padding:12px;border-radius:12px;background:#0F2A4A;color:#E7F1FF"><?php echo e($sample); ?></pre>
  <button class="btn s ghost" type="button" data-cp="r" style="margin-top:8px">Salin</button>
</section>

<section class="panel" style="margin-top:14px">
  <h3 style="margin-bottom:8px">Kirim data dari alat</h3>
  <p class="note" style="margin:4px 0 10px">Template body untuk <code>POST /api/ingest</code>. Satu paket bisa berisi banyak data dari SD card lewat <code>"records": [ {"ts": "...", "ph": 7.1, ...} ]</code>.</p>
  <pre class="mono" id="cp_s" style="overflow:auto;padding:12px;border-radius:12px;background:#0F2A4A;color:#E7F1FF">POST <?php echo e($base); ?>/api/ingest
Content-Type: application/json

<?php echo e($send); ?></pre>
  <button class="btn s ghost" type="button" data-cp="s" style="margin-top:8px">Salin</button>
</section>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
  (function () {
    function copy(text, btn) {
      var done = function () { var o = btn.textContent; btn.textContent = 'Tersalin'; setTimeout(function () { btn.textContent = o; }, 1300); };
      var fb = function () { var t = document.createElement('textarea'); t.value = text; document.body.appendChild(t); t.select(); try { document.execCommand('copy'); done(); } catch (e) { } t.remove(); };
      try { navigator.clipboard.writeText(text).then(done, fb); } catch (e) { fb(); }
    }
    document.querySelectorAll('[data-cp]').forEach(function (b) {
      b.addEventListener('click', function () { var el = document.getElementById('cp_' + b.dataset.cp); if (el) copy(el.textContent, b); });
    });
  })();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Than\Documents\Default Project\airtracking-main\airtracking\resources\views/admin/api.blade.php ENDPATH**/ ?>