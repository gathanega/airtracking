<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
<script>
  (function () {
    var t = 'light';
    try { var saved = localStorage.getItem('airtheme'); if (saved) t = saved; } catch (e) { }
    var mq = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)');
    if (mq && mq.matches) { try { if (!localStorage.getItem('airtheme')) t = 'dark'; } catch (e) { } }
    document.documentElement.setAttribute('data-theme', t);
  })();
</script>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
<title><?php echo $__env->yieldContent('title', 'AirTracking'); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo e(asset('css/airtracking.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('css/header.css')); ?>">
<?php echo $__env->yieldPushContent('head'); ?>
</head>
<body>
<?php echo $__env->yieldPushContent('preloader'); ?>
<?php echo $__env->make('partials.site-header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<div class="wrap">
<?php echo $__env->yieldContent('content'); ?>
</div>
<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\Users\Than\Documents\Default Project\airtracking-main\airtracking\resources\views/layouts/app.blade.php ENDPATH**/ ?>