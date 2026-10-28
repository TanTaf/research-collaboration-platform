<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me  = currentUser();
$uid = (int)$me['id'];
$pid = (int)input('id', 0);

$post = fetchOne(
    'SELECT cp.*, u.first_name, u.last_name, u.avatar, u.department, u.id AS uid,
            c.name AS community_name, c.id AS cid
     FROM community_posts cp
     JOIN users u ON u.id = cp.user_id
     JOIN communities c ON c.id = cp.community_id
     WHERE cp.id = ?', [$pid]);

if (!$post) { setFlash('error', 'Discussion not found.'); redirect('communities.php'); }

$joined = (bool)fetchValue('SELECT COUNT(*) FROM community_members WHERE community_id = ? AND user_id = ?',
                           [$post['cid'], $uid]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string)input('action');

    if ($action === 'reply') {
        if (!$joined) {
            setFlash('error', 'Join the community before replying.');
        } else {
            $body = trim((string)input('body'));
            if ($body !== '') {
                q('INSERT INTO community_replies (post_id, user_id, body) VALUES (?,?,?)', [$pid, $uid, $body]);
                if ((int)$post['uid'] !== $uid) {
                    notify((int)$post['uid'], fullName($me) . ' replied to your discussion.', 'post.php?id=' . $pid);
                }
            }
        }
    } elseif ($action === 'delete' && ((int)$post['uid'] === $uid || isAdmin())) {
        q('DELETE FROM community_posts WHERE id = ?', [$pid]);
        setFlash('success', 'Discussion deleted.');
        redirect('community.php?id=' . $post['cid']);
    }
    redirect('post.php?id=' . $pid);
}

if (empty($_SESSION['viewed_post'][$pid])) {
    q('UPDATE community_posts SET views = views + 1 WHERE id = ?', [$pid]);
    $_SESSION['viewed_post'][$pid] = true;
}

$replies = fetchAll(
    'SELECT cr.*, u.first_name, u.last_name, u.avatar, u.department, u.id AS uid
     FROM community_replies cr JOIN users u ON u.id = cr.user_id
     WHERE cr.post_id = ? ORDER BY cr.created_at', [$pid]);

$pageTitle = $post['title'];
require_once ROOT_PATH . '/includes/header.php';
?>

<p class="muted-sm mb-2">
  <a href="<?= url('communities.php') ?>">Communities</a> /
  <a href="<?= url('community.php?id=' . (int)$post['cid']) ?>"><?= e($post['community_name']) ?></a>
</p>

<div class="card mb-3">
  <div class="flex mb-2" style="align-items:flex-start">
    <?= avatar(['id' => $post['uid'], 'first_name' => $post['first_name'],
                'last_name' => $post['last_name'], 'avatar' => $post['avatar']], 44) ?>
    <div class="flex-1">
      <h1 style="font-size:24px;margin-bottom:6px"><?= e($post['title']) ?></h1>
      <div class="flex muted-sm" style="gap:14px">
        <a href="<?= url('profile.php?id=' . (int)$post['uid']) ?>"><?= e($post['first_name'] . ' ' . $post['last_name']) ?></a>
        <span><?= e($post['department']) ?></span>
        <span><?= timeAgo($post['created_at']) ?></span>
        <span><?= icon('eye', 12) ?> <?= (int)$post['views'] ?> views</span>
      </div>
    </div>
    <?php if ((int)$post['uid'] === $uid || isAdmin()): ?>
      <form method="post"><?= csrfField() ?>
        <input type="hidden" name="action" value="delete">
        <button class="btn btn-outline btn-sm" type="submit" data-confirm="Delete this discussion?">
          <?= icon('trash', 14) ?></button>
      </form>
    <?php endif; ?>
  </div>

  <p class="muted" style="line-height:1.85;white-space:pre-line"><?= e($post['body']) ?></p>
</div>

<div class="card">
  <h2>Replies (<?= count($replies) ?>)</h2>

  <?php if (!$replies): ?>
    <p class="muted-sm">No replies yet. Be the first to help.</p>
  <?php else: foreach ($replies as $r):
        $isOp = ((int)$r['uid'] === (int)$post['uid']); ?>
    <div class="comment">
      <?= avatar(['id' => $r['uid'], 'first_name' => $r['first_name'],
                  'last_name' => $r['last_name'], 'avatar' => $r['avatar']], 36) ?>
      <div class="comment-body <?= $isOp ? 'author' : '' ?>">
        <a class="nm" style="color:inherit" href="<?= url('profile.php?id=' . (int)$r['uid']) ?>">
          <?= e($r['first_name'] . ' ' . $r['last_name']) ?></a>
        <?php if ($isOp): ?><span class="tm" style="color:var(--orange);font-weight:700"> Original poster</span><?php endif; ?>
        <span class="tm"> &middot; <?= timeAgo($r['created_at']) ?></span>
        <p class="tx"><?= nl2br(e($r['body'])) ?></p>
      </div>
    </div>
  <?php endforeach; endif; ?>

  <?php if ($joined): ?>
    <form method="post" class="flex mt-3" style="align-items:flex-start">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="reply">
      <?= avatar($me, 36) ?>
      <div class="flex-1">
        <textarea name="body" style="min-height:92px" placeholder="Write a helpful reply..." required></textarea>
        <div class="text-right mt-1"><button class="btn btn-primary btn-sm" type="submit">Post Reply</button></div>
      </div>
    </form>
  <?php else: ?>
    <div class="alert alert-info mt-3">
      <span>Join <a href="<?= url('community.php?id=' . (int)$post['cid']) ?>"><?= e($post['community_name']) ?></a>
        to reply to this discussion.</span>
    </div>
  <?php endif; ?>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
