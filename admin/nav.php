<?php  ?>
<div class="flex flex-wrap mb-3" style="gap:8px">
  <?php
    $adminLinks = [
      'index.php'        => 'Overview',
      'users.php'        => 'Users',
      'projects.php'     => 'Projects',
      'publications.php' => 'Publications',
      'communities.php'  => 'Communities',
      'events.php'       => 'Events',
    ];
    foreach ($adminLinks as $file => $label):
      $active = isActive($file);
  ?>
    <a class="chip chip-lg <?= $active ? 'chip-orange' : '' ?>" href="<?= url('admin/' . $file) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
  <a class="chip chip-lg" href="<?= url('dashboard.php') ?>" style="margin-left:auto">&larr; Back to site</a>
</div>
