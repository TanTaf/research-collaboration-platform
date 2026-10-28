<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me  = currentUser();
$uid = (int)$me['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string)input('action');
    $cid    = (int)input('community_id');

    if ($action === 'join') {
        q('INSERT IGNORE INTO community_members (community_id, user_id) VALUES (?,?)', [$cid, $uid]);
        setFlash('success', 'You joined the community.');
    } elseif ($action === 'leave') {
        q('DELETE FROM community_members WHERE community_id = ? AND user_id = ?', [$cid, $uid]);
        setFlash('info', 'You left the community.');
    } elseif ($action === 'create') {
        $name = trim((string)input('name'));
        if ($name !== '') {
            q('INSERT INTO communities (name, description, field, icon, created_by) VALUES (?,?,?,?,?)',
              [$name, input('description') ?: null, input('field') ?: 'General',
               strtoupper(substr($name, 0, 2)), $uid]);
            $newId = (int)db()->lastInsertId();
            q('INSERT IGNORE INTO community_members (community_id, user_id) VALUES (?,?)', [$newId, $uid]);
            setFlash('success', 'Community created.');
            redirect('community.php?id=' . $newId);
        }
    }
    redirect('communities.php');
}

$field = (string)input('field');
$sort  = (string)input('sort', 'active');

$sql    = 'SELECT c.*,
             (SELECT COUNT(*) FROM community_members cm WHERE cm.community_id = c.id) AS members,
             (SELECT COUNT(*) FROM community_posts cp WHERE cp.community_id = c.id) AS posts
           FROM communities c';
$params = [];

if ($field !== '') { $sql .= ' WHERE c.field = ?'; $params[] = $field; }
$sql .= $sort === 'newest' ? ' ORDER BY c.created_at DESC' : ' ORDER BY members DESC, posts DESC';

$communities = fetchAll($sql, $params);
$fields      = array_column(fetchAll('SELECT DISTINCT field FROM communities ORDER BY field'), 'field');
$myComms     = array_map('intval', array_column(
    fetchAll('SELECT community_id FROM community_members WHERE user_id = ?', [$uid]), 'community_id'));

$pageTitle = 'Research Communities';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="card mb-3">
  <h1>Research Communities</h1>
  <p class="muted mb-0" style="max-width:620px">Join specialised academic groups to collaborate with experts in
    your field. Connect with peers, share resources, and accelerate your research journey in a collaborative
    environment.</p>
</div>

<div class="flex-between flex-wrap mb-3" style="gap:12px">
  <div class="flex flex-wrap" style="gap:8px">
    <a class="chip <?= $field === '' ? 'chip-orange' : '' ?> chip-lg" href="<?= url('communities.php') ?>">All Fields</a>
    <?php foreach ($fields as $f): ?>
      <a class="chip <?= $field === $f ? 'chip-orange' : '' ?> chip-lg"
         href="<?= url('communities.php?field=' . urlencode($f)) ?>"><?= e($f) ?></a>
    <?php endforeach; ?>
  </div>

  <form method="get" class="flex">
    <?php if ($field !== ''): ?><input type="hidden" name="field" value="<?= e($field) ?>"><?php endif; ?>
    <select name="sort" onchange="this.form.submit()" style="width:auto;min-width:180px">
      <option value="active" <?= $sort === 'active' ? 'selected' : '' ?>>Sort by: Most Active</option>
      <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Sort by: Newest</option>
    </select>
  </form>
</div>

<div class="grid grid-2 mb-3">
  <?php foreach ($communities as $c): $joined = in_array((int)$c['id'], $myComms, true); ?>
    <div class="card card-hover">
      <div class="flex-between mb-2">
        <span class="stat-ico" style="width:38px;height:38px;font-weight:700;font-size:13px"><?= e($c['icon']) ?></span>
        <?php if ((int)$c['members'] > 4): ?>
          <span class="badge badge-grey">Highly Active</span>
        <?php elseif (strtotime($c['created_at']) > strtotime('-30 days')): ?>
          <span class="badge badge-grey">New</span>
        <?php endif; ?>
      </div>

      <h3 style="font-family:var(--serif);font-size:19px">
        <a href="<?= url('community.php?id=' . (int)$c['id']) ?>" style="color:inherit"><?= e($c['name']) ?></a>
      </h3>
      <p class="muted-sm" style="min-height:56px"><?= e($c['description']) ?></p>

      <div class="flex muted-sm mb-2" style="gap:18px">
        <span><?= icon('users', 13) ?> <?= (int)$c['members'] ?> Members</span>
        <span><?= icon('chat', 13) ?> <?= (int)$c['posts'] ?> Discussions</span>
      </div>

      <div class="flex" style="gap:9px">
        <form method="post" class="flex-1"><?= csrfField() ?>
          <input type="hidden" name="community_id" value="<?= (int)$c['id'] ?>">
          <input type="hidden" name="action" value="<?= $joined ? 'leave' : 'join' ?>">
          <button class="btn <?= $joined ? 'btn-outline' : 'btn-primary' ?> btn-block" type="submit">
            <?= $joined ? 'Leave Community' : 'Join Community' ?></button>
        </form>
        <a class="btn btn-outline" href="<?= url('community.php?id=' . (int)$c['id']) ?>">Explore</a>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card">
  <h3>Start a new community</h3>
  <p class="muted-sm">Cannot find a group for your field? Create one and invite others to join.</p>
  <form method="post">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="create">
    <div class="form-row">
      <div class="form-group">
        <label for="name">Community name</label>
        <input type="text" id="name" name="name" placeholder="e.g. Bengali NLP Circle" required>
      </div>
      <div class="form-group">
        <label for="field">Field</label>
        <input type="text" id="field" name="field" placeholder="e.g. Artificial Intelligence" list="fieldList">
        <datalist id="fieldList">
          <?php foreach ($fields as $f): ?><option value="<?= e($f) ?>"><?php endforeach; ?>
        </datalist>
      </div>
    </div>
    <div class="form-group">
      <label for="description">Description</label>
      <textarea id="description" name="description" style="min-height:80px"
                placeholder="What will this community discuss?"></textarea>
    </div>
    <button class="btn btn-primary" type="submit"><?= icon('plus', 15) ?> Create Community</button>
  </form>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
