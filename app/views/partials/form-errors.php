<?php if (!empty($errors)): ?>
<div class="form-errors" role="alert">
  <ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>
