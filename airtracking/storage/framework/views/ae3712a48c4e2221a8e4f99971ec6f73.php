<?php $__env->startSection('title', 'Audit admin · AirTracking'); ?>
<?php $__env->startSection('content'); ?>
<?php echo $__env->make('partials.admin-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<section class="panel">
  <div class="head">
    <div>
      <h2>Audit admin</h2>
      <p class="note" style="margin:4px 0 0">100 aksi terbaru: login, logout, dan perubahan data oleh admin. Credential, kata sandi, dan token tidak pernah dicatat.</p>
    </div>
    <a class="btn s ghost" href="<?php echo e(route('admin.devices')); ?>">Kelola alat</a>
  </div>

  <div class="tw" style="margin-top:12px"><table class="l">
    <tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Subjek</th><th>IP</th><th>Ringkasan</th></tr>
    <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
      <tr>
        <td class="mono"><?php echo e($log->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? '–'); ?></td>
        <td><?php echo e($log->user?->email ?? '–'); ?></td>
        <td class="mono"><?php echo e($log->action); ?></td>
        <td class="mono"><?php echo e($log->subject_type ? class_basename($log->subject_type).'#'.$log->subject_id : '–'); ?></td>
        <td class="mono"><?php echo e($log->ip ?: '–'); ?></td>
        <td class="mono" style="white-space:pre-wrap"><?php echo e($log->meta ? json_encode($log->meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '–'); ?></td>
      </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
      <tr><td colspan="6" style="text-align:center;color:var(--ink2)">Belum ada catatan audit.</td></tr>
    <?php endif; ?>
  </table></div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Than\Documents\Default Project\airtracking-main\airtracking\resources\views/admin/audit.blade.php ENDPATH**/ ?>