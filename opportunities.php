<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me      = currentUser();
$uid     = (int)$me['id'];
$keyword = (string)input('q');
$type    = (string)input('type');
$intId   = (int)input('interest', 0);

$sql = 'SELECT p.*, u.first_name, u.last_name, u.department, u.avatar AS owner_avatar, u.id AS owner_uid,
               (SELECT COUNT(*) FROM project_members m WHERE m.project_id = p.id) AS members
        FROM projects p JOIN users u ON u.id = p.owner_id
        WHERE p.is_open = 1';
$params = [];

if ($keyword !== '') {
    $sql .= ' AND (p.title LIKE ? OR p.summary LIKE ? OR p.description LIKE ?)';
    $like = '%' . $keyword . '%';
    array_push($params, $like, $like, $like);
}
if ($type !== '')  { $sql .= ' AND p.project_type = ?'; $params[] = $type; }
if ($intId)        { $sql .= ' AND p.id IN (SELECT project_id FROM project_interests WHERE interest_id = ?)';
                     $params[] = $intId; }

$sql .= ' ORDER BY p.created_at DESC';
$projects = fetchAll($sql, $params);

$myInts = userInterestIds($uid);

$pageTitle = 'Opportunities';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>Research Opportunities</h1>
    <p class="sub">Open projects looking for collaborators. Apply to the ones that match your skills,
      or start your own and let people find you.</p>
  </div>
  <a class="btn btn-primary" href="<?= url('project_form.php') ?>"><?= icon('plus', 16) ?> Post an Opportunity</a>
</div>

<form class="filterbar" method="get">
  <input class="grow" type="search" name="q" value="<?= e($keyword) ?>" placeholder="Search opportunities...">
  <select name="type">
    <option value="">All types</option>
    <?php foreach (['FYDP', 'Thesis', 'RA', 'Research', 'Lab Work'] as $t): ?>
      <option value="<?= e($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= e($t) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="interest">
    <option value="">All fields</option>
    <?php foreach (allInterests() as $i): ?>
      <option value="<?= (int)$i['id'] ?>" <?= $intId === (int)$i['id'] ? 'selected' : '' ?>><?= e($i['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-primary" type="submit"><?= icon('filter', 15) ?> Filter</button>
</form>

<?php if (!$projects): ?>
  <div class="empty">
    <h3>No open opportunities</h3>
    <p class="mb-2">Nothing matches your filters right now.</p>
    <a class="btn btn-primary btn-sm" href="<?= url('project_form.php') ?>">Post your own</a>
  </div>
<?php else: ?>
  <div class="grid grid-2">
    <?php foreach ($projects as $p):
      $pInts   = projectInterestNames((int)$p['id']);
      $overlap = count(array_intersect($myInts, array_map('intval', array_column(
                   fetchAll('SELECT interest_id FROM project_interests WHERE project_id = ?', [$p['id']]),
                   'interest_id'))));
      $applied = fetchOne('SELECT status FROM collab_requests WHERE project_id = ? AND user_id = ?', [$p['id'], $uid]);
      $isMine  = ((int)$p['owner_id'] === $uid);
    ?>
      <div class="card card-hover">
        <div class="flex-between mb-2">
          <span class="chip chip-orange"><?= e($p['project_type']) ?></span>
          <span class="muted-sm"><?= $p['deadline'] ? 'Deadline ' . fmtDate($p['deadline']) : 'Rolling' ?></span>
        </div>

        <h3 style="font-family:var(--serif);font-size:19px;margin-bottom:8px">
          <a href="<?= url('opportunity.php?id=' . (int)$p['id']) ?>" style="color:inherit"><?= e($p['title']) ?></a>
        </h3>
        <p class="muted-sm" style="min-height:44px"><?= e(excerpt($p['summary'], 130)) ?></p>

        <div class="flex flex-wrap mb-2" style="gap:6px">
          <?php foreach (array_slice($pInts, 0, 3) as $i): ?>
            <span class="chip" style="font-size:11px;padding:3px 10px"><?= e($i) ?></span>
          <?php endforeach; ?>
          <?php if ($overlap > 0): ?>
            <span class="match-tag"><?= $overlap ?> shared <?= $overlap === 1 ? 'interest' : 'interests' ?></span>
          <?php endif; ?>
        </div>

        <div class="flex-between" style="border-top:1px solid var(--line);padding-top:13px">
          <a class="flex" style="text-decoration:none;color:inherit;gap:8px"
             href="<?= url('profile.php?id=' . (int)$p['owner_uid']) ?>">
            <?= avatar(['id' => $p['owner_uid'], 'first_name' => $p['first_name'],
                        'last_name' => $p['last_name'], 'avatar' => $p['owner_avatar']], 30) ?>
            <span>
              <span style="font-size:12.5px;font-weight:600;display:block"><?= e($p['first_name'] . ' ' . $p['last_name']) ?></span>
              <span class="muted-sm"><?= (int)$p['members'] ?> in team &middot; needs <?= e($p['required_members']) ?></span>
            </span>
          </a>

          <?php if ($isMine): ?>
            <a class="btn btn-outline btn-sm" href="<?= url('project.php?id=' . (int)$p['id']) ?>">Manage</a>
          <?php elseif ($applied): ?>
            <span class="chip chip-orange-soft"><?= e(ucfirst($applied['status'])) ?></span>
          <?php else: ?>
            <a class="btn btn-primary btn-sm" href="<?= url('opportunity.php?id=' . (int)$p['id']) ?>">View Details</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
