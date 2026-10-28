<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me  = currentUser();
$uid = (int)$me['id'];
$to  = (int)input('to', 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $receiver = (int)input('receiver_id');
    $body     = trim((string)input('body'));

    if ($receiver > 0 && $receiver !== $uid && $body !== '') {
        $exists = fetchValue('SELECT COUNT(*) FROM users WHERE id = ? AND status = "active"', [$receiver]);
        if ($exists) {
            q('INSERT INTO messages (sender_id, receiver_id, body) VALUES (?,?,?)', [$uid, $receiver, $body]);
            notify($receiver, fullName($me) . ' sent you a message.', 'messages.php?to=' . $uid);
        }
    }
    redirect('messages.php?to=' . $receiver);
}

$conversations = fetchAll(
    'SELECT u.id, u.first_name, u.last_name, u.avatar, u.department,
            (SELECT body FROM messages m2
              WHERE (m2.sender_id = u.id AND m2.receiver_id = ?)
                 OR (m2.sender_id = ? AND m2.receiver_id = u.id)
              ORDER BY m2.created_at DESC LIMIT 1) AS last_body,
            (SELECT created_at FROM messages m3
              WHERE (m3.sender_id = u.id AND m3.receiver_id = ?)
                 OR (m3.sender_id = ? AND m3.receiver_id = u.id)
              ORDER BY m3.created_at DESC LIMIT 1) AS last_time,
            (SELECT COUNT(*) FROM messages m4
              WHERE m4.sender_id = u.id AND m4.receiver_id = ? AND m4.is_read = 0) AS unread
     FROM users u
     WHERE u.id <> ? AND u.id IN (
        SELECT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END
        FROM messages WHERE sender_id = ? OR receiver_id = ?)
     ORDER BY last_time DESC', array_fill(0, 9, $uid));

if (!$to && $conversations) { $to = (int)$conversations[0]['id']; }

$partner = $to ? fetchOne('SELECT * FROM users WHERE id = ? AND status = "active"', [$to]) : null;
$thread  = [];

if ($partner) {
    q('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?', [$to, $uid]);
    $thread = fetchAll(
        'SELECT * FROM messages
         WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
         ORDER BY created_at', [$uid, $to, $to, $uid]);
}

$pageTitle = 'Messages';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>Messages</h1>
    <p class="sub">Talk directly with collaborators, supervisors and community members.</p>
  </div>
  <a class="btn btn-outline" href="<?= url('directory.php') ?>"><?= icon('user-plus', 15) ?> Find someone</a>
</div>

<div class="layout-side-main">

  <div class="card" style="padding:0;overflow:hidden">
    <div style="padding:14px 16px;border-bottom:1px solid var(--line)">
      <input type="search" data-filter-input=".chat-item" placeholder="Search researchers...">
    </div>
    <div class="chat-list">
      <?php if (!$conversations): ?>
        <p class="muted-sm" style="padding:20px;text-align:center">No conversations yet.<br>
          Open someone's profile and press Message.</p>
      <?php else: foreach ($conversations as $cv): ?>
        <a class="chat-item <?= (int)$cv['id'] === $to ? 'active' : '' ?>"
           href="<?= url('messages.php?to=' . (int)$cv['id']) ?>">
          <?= avatar($cv, 40) ?>
          <span class="flex-1" style="min-width:0">
            <span class="flex-between">
              <span class="nm"><?= e($cv['first_name'] . ' ' . $cv['last_name']) ?></span>
              <span class="muted-sm nowrap"><?= timeAgo($cv['last_time']) ?></span>
            </span>
            <span class="muted-sm" style="display:block;font-size:11px"><?= e($cv['department']) ?></span>
            <span class="pv"><?= e(excerpt($cv['last_body'], 34)) ?></span>
          </span>
          <?php if ((int)$cv['unread'] > 0): ?>
            <span class="pill" style="align-self:center"><?= (int)$cv['unread'] ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <div class="card" style="padding:0;overflow:hidden">
    <?php if (!$partner): ?>
      <div class="empty" style="border:none;min-height:360px;display:grid;place-items:center">
        <div>
          <h3>No conversation selected</h3>
          <p class="mb-2">Pick someone on the left, or find a new collaborator.</p>
          <a class="btn btn-primary btn-sm" href="<?= url('directory.php') ?>">Browse researchers</a>
        </div>
      </div>
    <?php else: ?>
      <div class="flex" style="padding:14px 18px;border-bottom:1px solid var(--line)">
        <?= avatar($partner, 40) ?>
        <div class="flex-1">
          <a href="<?= url('profile.php?id=' . (int)$partner['id']) ?>"
             style="font-size:14px;font-weight:600;color:var(--ink)"><?= e(fullName($partner)) ?></a>
          <div class="muted-sm"><?= e($partner['department']) ?>
            <?= $partner['open_to_collab'] ? ' &middot; Open to collaboration' : '' ?></div>
        </div>
        <a class="icon-btn" href="<?= url('profile.php?id=' . (int)$partner['id']) ?>"
           title="View profile"><?= icon('user', 18) ?></a>
      </div>

      <div class="chat-thread">
        <?php
          $lastDay = null;
          foreach ($thread as $m):
            $day = fmtDate($m['created_at'], 'd M Y');
            if ($day !== $lastDay) { echo '<div class="chat-day">' . e($day) . '</div>'; $lastDay = $day; }
            $mine = ((int)$m['sender_id'] === $uid);
        ?>
          <div class="bubble-row <?= $mine ? 'me' : '' ?>">
            <?= avatar($mine ? $me : $partner, 30) ?>
            <div class="bubble">
              <?= nl2br(e($m['body'])) ?>
              <span class="tm"><?= fmtDate($m['created_at'], 'g:i A') ?></span>
            </div>
          </div>
        <?php endforeach; ?>

        <?php if (!$thread): ?>
          <p class="muted-sm text-center">No messages yet. Say hello.</p>
        <?php endif; ?>
      </div>

      <form method="post" class="chat-compose">
        <?= csrfField() ?>
        <input type="hidden" name="receiver_id" value="<?= (int)$partner['id'] ?>">
        <input type="text" name="body" placeholder="Write your message here..." required autocomplete="off">
        <button class="btn btn-primary" type="submit"><?= icon('send', 16) ?></button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
