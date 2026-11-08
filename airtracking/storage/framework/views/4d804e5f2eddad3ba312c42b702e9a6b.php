<?php $__env->startSection('title', 'Masuk admin · AirTracking'); ?>
<?php $__env->startSection('content'); ?>
<div class="login panel">
  <h1>Air<b>Tracking</b></h1>
  <p class="sub" style="margin:4px 0 16px">Masuk khusus admin untuk mengelola alat. Dashboard publik tidak memerlukan login.</p>
  <form method="POST" action="<?php echo e(route('admin.login.post')); ?>">
    <?php echo csrf_field(); ?>
    <label>Email<input type="email" name="email" value="<?php echo e(old('email')); ?>" autocomplete="username" required autofocus></label>
    <label>Kata sandi<input type="password" name="password" autocomplete="current-password" required></label>
    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="err" role="alert"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    <button class="btn" type="submit" style="width:100%">Masuk</button>
  </form>
  <p class="note"><a href="<?php echo e(route('home')); ?>">‹ Kembali ke dashboard publik</a></p>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Than\Documents\Default Project\airtracking-main\airtracking\resources\views/admin/login.blade.php ENDPATH**/ ?>