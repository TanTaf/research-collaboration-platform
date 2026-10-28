<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me      = currentUser();
$keyword = (string)input('q');
$dept    = (string)input('department');
$role    = (string)input('role');

$sql    = 'SELECT * FROM users WHERE status = "active" AND id <> ?';
$params = [$me['id']];

if ($keyword !== '') {
    $sql .= ' AND (first_name LIKE ? OR last_name LIKE ? OR bio LIKE ? OR designation LIKE ?)';
    $like = '%' . $keyword . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($dept !== '') { $sql .= ' AND department = ?'; $params[] = $dept; }
if ($role !== '') { $sql .= ' AND role = ?';       $params[] = $role; }

$sql .= ' ORDER BY role = "faculty" DESC, first_name';
$people = fetchAll($sql, $params);

$departments = array_column(fetchAll('SELECT DISTINCT department FROM users WHERE department IS NOT NULL ORDER BY department'), 'department');

$pageTitle = 'Researcher Directory';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>Researcher Directory</h1>
    <p class="sub">Every student and faculty member on the hub. Open a profile to see their interests,
      skills and published work.</p>
  </div>
  <a class="btn btn-primary" href="<?= url('matchmaking.php') ?>"><?= icon('sparkle', 15) ?> Smart Matchmaking</a>
</div>

<form class="filterbar" method="get">
  <input class="grow" type="search" name="q" value="<?= e($keyword) ?>" placeholder="Search by name or expertise...">
  <select name="department">
    <option value="">All departments</option>
    <?php foreach ($departments as $d): ?>
      <option value="<?= e($d) ?>" <?= $dept === $d ? 'selected' : '' ?>><?= e($d) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="role">
    <option value="">All roles</option>
    <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Students</option>
    <option value="faculty" <?= $role === 'faculty' ? 'selected' : '' ?>>Faculty</option>
  </select>
  <button class="btn btn-primary" type="submit">Search</button>
</form>

<?php if (!$people): ?>
  <div class="empty"><h3>No researchers found</h3><p>Try a different search term.</p></div>
<?php else: ?>
  <div class="grid grid-3">
    <?php foreach ($people as $p): ?>
      <div class="card card-hover text-center">
        <div style="display:flex;justify-content:center;margin-bottom:10px"><?= avatar($p, 64) ?></div>
        <h4 style="margin-bottom:2px"><?= e(fullName($p)) ?></h4>
        <p class="muted-sm mb-1"><?= e($p['designation'] ?: $p['department']) ?></p>
        <?= roleBadge($p['role']) ?>
        <div class="flex flex-wrap mt-2 mb-2" style="gap:6px;justify-content:center">
          <?php foreach (array_slice(userInterestNames((int)$p['id']), 0, 3) as $i): ?>
            <span class="chip" style="font-size:11px;padding:3px 10px"><?= e($i) ?></span>
          <?php endforeach; ?>
        </div>
        <div class="flex" style="gap:7px">
          <a class="btn btn-outline btn-sm flex-1" href="<?= url('profile.php?id=' . (int)$p['id']) ?>">Profile</a>
          <a class="btn btn-primary btn-sm flex-1" href="<?= url('messages.php?to=' . (int)$p['id']) ?>">Message</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
