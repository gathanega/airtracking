<?php $__env->startSection('title', 'Rentang normal · AirTracking'); ?>
<?php $__env->startSection('content'); ?>
<?php echo $__env->make('partials.admin-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<section class="panel">
  <h2>Rentang normal (baku mutu)</h2>
  <p class="note" style="margin-bottom:12px">Dipakai untuk status Normal / Waspada / Di luar rentang di dashboard dan prediksi. Angka awal hanya acuan, sesuaikan dengan baku mutu penelitian.</p>
  <form method="POST" action="<?php echo e(route('admin.limits.update')); ?>">
    <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
    <div class="tw"><table>
      <tr><th>Parameter</th><th>Satuan</th><th>Batas bawah</th><th>Batas atas</th><th>Waspada dekat</th></tr>
      <?php $__currentLoopData = $limits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
          <td><?php echo e($l->name); ?></td><td><?php echo e($l->unit ?: '–'); ?></td>
          <td><input type="number" step="any" name="limits[<?php echo e($l->param); ?>][lo]" value="<?php echo e(old('limits.'.$l->param.'.lo', $l->lo)); ?>" required></td>
          <td><input type="number" step="any" name="limits[<?php echo e($l->param); ?>][hi]" value="<?php echo e(old('limits.'.$l->param.'.hi', $l->hi)); ?>" required></td>
          <td><?php echo e(['l' => 'batas bawah', 'h' => 'batas atas', 'lh' => 'kedua batas'][$l->sides] ?? $l->sides); ?></td>
        </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </table></div>
    <?php if($errors->any()): ?><div class="err" role="alert" style="margin-top:8px"><?php echo e($errors->first()); ?></div><?php endif; ?>
    <button class="btn" type="submit" style="margin-top:12px">Simpan</button>
  </form>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Than\Documents\Default Project\airtracking-main\airtracking\resources\views/admin/limits.blade.php ENDPATH**/ ?>