<?php

require_once dirname(__DIR__) . '/config/config.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (input('action') === 'delete_community') {
        q('DELETE FROM communities WHERE id = ?', [(int)input('community_id')]);
        setFlash('success', 'Community deleted.');

    } elseif (input('action') === 'delete_post') {
        q('DELETE FROM community_posts WHERE id = ?', [(int)input('post_id')]);
        setFlash('success', 'Discussion removed.');

    } elseif (input('action') === 'create') {
        $name = trim((string)input('name'));
        if ($name !== '') {
            q('INSERT INTO communities (name, description, field, icon, created_by) VALUES (?,?,?,?,?)',
              [$name, input('description') ?: null, input('field') ?: 'General',
               strtoupper(substr($name, 0, 2)), currentUser()['id']]);
            setFlash('success', 'Community created.');
        }
    }
    redirect('admin/communities.php');
}

$communities = fetchAll(
    'SELECT c.*, u.first_name, u.last_name,
       (SELECT COUNT(*) FROM community_members m WHERE m.community_id = c.id) AS members,
       (SELECT COUNT(*) FROM community_posts p WHERE p.community_id = c.id) AS posts
     FROM communities c LEFT JOIN users u ON u.id = c.created_by
     ORDER BY members DESC');

$recentPosts = fetchAll(
    'SELECT p.*, u.first_name, u.last_name, c.name AS community_name
     FROM community_posts p JOIN users u ON u.id = p.user_id
     JOIN communities c ON c.id = p.community_id
     ORDER BY p.created_at DESC LIMIT 12');

$pageTitle = 'Manage Communities';
require_once ROOT_PATH . '/includes/header.php';
require_once __DIR__ . '/nav.php';
?>

<div class="page-head">
  <div>
    <h1>Manage Communities</h1>
    <p class="sub">Groups, their membership, and the newest discussions across all of them.</p>
  </div>
</div>

<div class="layout-main-side">
  <div class="flex-col" style="gap:18px">
    <div class="card" style="padding:0">
      <div class="table-wrap" style="border:none">
        <table class="data">
          <thead><tr><th>Community</th><th>Field</th><th>Members</th><th>Discussions</th><th>Created by</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($communities as $c): ?>
            <tr>
              <td>
                <a href="<?= url('community.php?id=' . (int)$c['id']) ?>" style="color:inherit;text-decoration:none">
                  <strong style="font-size:13px"><?= e($c['name']) ?></strong></a>
                <div class="muted-sm"><?= e(excerpt($c['description'], 60)) ?></div>
              </td>
              <td class="muted"><?= e($c['field']) ?></td>
              <td class="muted"><?= (int)$c['members'] ?></td>
              <td class="muted"><?= (int)$c['posts'] ?></td>
              <td class="muted"><?= e(trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')) ?: '--') ?></td>
              <td>
                <form method="post"><?= csrfField() ?>
                  <input type="hidden" name="action" value="delete_community">
                  <input type="hidden" name="community_id" value="<?= (int)$c['id'] ?>">
                  <button class="btn btn-danger btn-sm" type="submit"
                          data-confirm="Delete this community and all its discussions?">
                    <?= icon('trash', 13) ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <h2>Recent Discussions</h2>
      <?php foreach ($recentPosts as $p): ?>
        <div class="list-row">
          <span class="flex-1">
            <a href="<?= url('post.php?id=' . (int)$p['id']) ?>" style="color:inherit;text-decoration:none">
              <strong style="font-size:13.5px"><?= e($p['title']) ?></strong></a>
            <span class="muted-sm" style="display:block">
              <?= e($p['first_name'] . ' ' . $p['last_name']) ?> in <?= e($p['community_name']) ?>
              &middot; <?= timeAgo($p['created_at']) ?></span>
          </span>
          <form method="post"><?= csrfField() ?>
            <input type="hidden" name="action" value="delete_post">
            <input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>">
            <button class="btn btn-outline btn-sm" type="submit"
                    data-confirm="Remove this discussion?"><?= icon('trash', 13) ?></button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <form class="card" method="post">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="create">
    <h3>New community</h3>
    <div class="form-group"><label for="name">Name</label>
      <input type="text" id="name" name="name" required></div>
    <div class="form-group"><label for="field">Field</label>
      <input type="text" id="field" name="field" placeholder="e.g. Data Science"></div>
    <div class="form-group"><label for="description">Description</label>
      <textarea id="description" name="description" style="min-height:90px"></textarea></div>
    <button class="btn btn-primary btn-block" type="submit">Create</button>
  </form>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
