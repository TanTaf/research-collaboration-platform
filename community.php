<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me  = currentUser();
$uid = (int)$me['id'];
$cid = (int)input('id', 0);

$c = fetchOne('SELECT * FROM communities WHERE id = ?', [$cid]);
if (!$c) { setFlash('error', 'Community not found.'); redirect('communities.php'); }

$joined = (bool)fetchValue('SELECT COUNT(*) FROM community_members WHERE community_id = ? AND user_id = ?',
                           [$cid, $uid]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string)input('action');

    if ($action === 'join') {
        q('INSERT IGNORE INTO community_members (community_id, user_id) VALUES (?,?)', [$cid, $uid]);
        setFlash('success', 'You joined ' . $c['name'] . '.');

    } elseif ($action === 'leave') {
        q('DELETE FROM community_members WHERE community_id = ? AND user_id = ?', [$cid, $uid]);
        setFlash('info', 'You left the community.');

    } elseif ($action === 'new_post') {
        if (!$joined) {
            setFlash('error', 'Join the community before posting.');
        } else {
            $title = trim((string)input('title'));
            $body  = trim((string)input('body'));
            if ($title !== '' && $body !== '') {
                q('INSERT INTO community_posts (community_id, user_id, title, body) VALUES (?,?,?,?)',
                  [$cid, $uid, $title, $body]);
                setFlash('success', 'Discussion posted.');
            } else {
                setFlash('error', 'A discussion needs both a title and a body.');
            }
        }
    }
    redirect('community.php?id=' . $cid);
}

$posts = fetchAll(
    'SELECT cp.*, u.first_name, u.last_name, u.avatar, u.id AS uid, u.department,
            (SELECT COUNT(*) FROM community_replies cr WHERE cr.post_id = cp.id) AS replies
     FROM community_posts cp JOIN users u ON u.id = cp.user_id
     WHERE cp.community_id = ? ORDER BY cp.created_at DESC', [$cid]);

$members = fetchAll('SELECT u.* FROM community_members cm JOIN users u ON u.id = cm.user_id
                     WHERE cm.community_id = ? ORDER BY cm.joined_at DESC LIMIT 8', [$cid]);
$memberCount = (int)fetchValue('SELECT COUNT(*) FROM community_members WHERE community_id = ?', [$cid]);

$pageTitle = $c['name'];
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="card mb-3">
  <div class="flex-between flex-wrap" style="gap:16px">
    <div class="flex" style="align-items:flex-start">
      <span class="stat-ico" style="width:52px;height:52px;font-weight:700;font-size:17px"><?= e($c['icon']) ?></span>
      <div>
        <h1 style="margin-bottom:5px"><?= e($c['name']) ?></h1>
        <p class="muted mb-1" style="max-width:560px"><?= e($c['description']) ?></p>
        <div class="flex muted-sm" style="gap:16px">
          <span class="chip chip-blue"><?= e($c['field']) ?></span>
          <span><?= icon('users', 13) ?> <?= $memberCount ?> members</span>
          <span><?= icon('chat', 13) ?> <?= count($posts) ?> discussions</span>
        </div>
      </div>
    </div>

    <form method="post"><?= csrfField() ?>
      <input type="hidden" name="action" value="<?= $joined ? 'leave' : 'join' ?>">
      <button class="btn <?= $joined ? 'btn-outline' : 'btn-primary' ?>" type="submit">
        <?= $joined ? 'Leave Community' : 'Join Community' ?></button>
    </form>
  </div>
</div>

<div class="layout-main-side">
  <div>
    <?php if ($joined): ?>
      <div class="card mb-3">
        <h3>Start a discussion</h3>
        <form method="post">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="new_post">
          <div class="form-group">
            <label for="title">Question or topic</label>
            <input type="text" id="title" name="title" placeholder="e.g. Best augmentation strategy for small datasets?" required>
          </div>
          <div class="form-group">
            <label for="body">Details</label>
            <textarea id="body" name="body" placeholder="Give enough context for someone to actually help..." required></textarea>
          </div>
          <button class="btn btn-primary" type="submit">Post Discussion</button>
        </form>
      </div>
    <?php else: ?>
      <div class="alert alert-info"><span>Join this community to start or reply to discussions.</span></div>
    <?php endif; ?>

    <h2 class="section-title">Discussions</h2>

    <?php if (!$posts): ?>
      <div class="empty"><h3>No discussions yet</h3><p>Be the first to ask a question here.</p></div>
    <?php else: foreach ($posts as $p): ?>
      <a class="list-card" href="<?= url('post.php?id=' . (int)$p['id']) ?>">
        <div class="flex" style="align-items:flex-start">
          <?= avatar(['id' => $p['uid'], 'first_name' => $p['first_name'],
                      'last_name' => $p['last_name'], 'avatar' => $p['avatar']], 40) ?>
          <div class="flex-1">
            <h4 style="font-size:15.5px;margin-bottom:4px"><?= e($p['title']) ?></h4>
            <p class="muted-sm mb-1"><?= e(excerpt($p['body'], 150)) ?></p>
            <div class="flex muted-sm" style="gap:16px">
              <span><?= e($p['first_name'] . ' ' . $p['last_name']) ?></span>
              <span><?= timeAgo($p['created_at']) ?></span>
              <span><?= icon('chat', 12) ?> <?= (int)$p['replies'] ?> replies</span>
              <span><?= icon('eye', 12) ?> <?= (int)$p['views'] ?></span>
            </div>
          </div>
          <?= icon('chevron-right', 18) ?>
        </div>
      </a>
    <?php endforeach; endif; ?>
  </div>

  <div class="flex-col" style="gap:18px">
    <div class="card">
      <div class="card-head"><h3 style="margin:0">Members</h3><span class="muted-sm"><?= $memberCount ?></span></div>
      <?php foreach ($members as $m): ?>
        <a class="flex mb-2" style="text-decoration:none;color:inherit"
           href="<?= url('profile.php?id=' . (int)$m['id']) ?>">
          <?= avatar($m, 34) ?>
          <span>
            <span style="font-size:13px;font-weight:600;display:block"><?= e(fullName($m)) ?></span>
            <span class="muted-sm"><?= e($m['department']) ?></span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="card">
      <h5 class="eyebrow eyebrow-muted">Community Guidelines</h5>
      <ul class="muted-sm" style="padding-left:18px;margin:0;line-height:1.9">
        <li>Give enough context for others to help.</li>
        <li>Credit the work and data you build on.</li>
        <li>Keep criticism about the work, not the person.</li>
        <li>Do not post unpublished data you do not own.</li>
      </ul>
    </div>
  </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
