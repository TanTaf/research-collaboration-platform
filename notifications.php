<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me  = currentUser();
$uid = (int)$me['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (input('action') === 'read_all') {
        q('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$uid]);
        setFlash('success', 'All notifications marked as read.');
    } elseif (input('action') === 'clear') {
        q('DELETE FROM notifications WHERE user_id = ?', [$uid]);
        setFlash('success', 'Notifications cleared.');
    }
    redirect('notifications.php');
}

$items = fetchAll('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 60', [$uid]);

$pageTitle = 'Notifications';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>Notifications</h1>
    <p class="sub">Join requests, replies, messages and reminders.</p>
  </div>
  <?php if ($items): ?>
    <div class="flex">
      <form method="post"><?= csrfField() ?>
        <input type="hidden" name="action" value="read_all">
        <button class="btn btn-outline" type="submit">Mark all read</button>
      </form>
      <form method="post"><?= csrfField() ?>
        <input type="hidden" name="action" value="clear">
        <button class="btn btn-outline" type="submit" data-confirm="Clear all notifications?">Clear all</button>
      </form>
    </div>
  <?php endif; ?>
</div>

<?php if (!$items): ?>
  <div class="empty"><h3>You are all caught up</h3><p>New activity will appear here.</p></div>
<?php else: ?>
  <div class="card" style="padding:0">
    <?php foreach ($items as $n): ?>
      <a class="flex" style="text-decoration:none;color:inherit;padding:15px 20px;border-bottom:1px solid #F1F3F6;
                <?= $n['is_read'] ? '' : 'background:#FEF8F2' ?>"
         href="<?= $n['link'] ? url($n['link']) : '#' ?>">
        <span class="stat-ico" style="width:34px;height:34px"><?= icon('bell', 15) ?></span>
        <span class="flex-1">
          <span style="font-size:13.5px;display:block"><?= e($n['text']) ?></span>
          <span class="muted-sm"><?= timeAgo($n['created_at']) ?></span>
        </span>
        <?php if (!$n['is_read']): ?>
          <span style="width:8px;height:8px;border-radius:50%;background:var(--orange)"></span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
