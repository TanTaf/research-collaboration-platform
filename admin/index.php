<?php

require_once dirname(__DIR__) . '/config/config.php';
requireRole('admin');

$me = currentUser();

$stats = [
    ['users',        'Total Users',      (int)fetchValue('SELECT COUNT(*) FROM users')],
    ['cap',          'Students',         (int)fetchValue('SELECT COUNT(*) FROM users WHERE role = "student"')],
    ['briefcase',    'Faculty',          (int)fetchValue('SELECT COUNT(*) FROM users WHERE role = "faculty"')],
    ['folder',       'Projects',         (int)fetchValue('SELECT COUNT(*) FROM projects')],
    ['file',         'Publications',     (int)fetchValue('SELECT COUNT(*) FROM publications')],
    ['globe',        'Communities',      (int)fetchValue('SELECT COUNT(*) FROM communities')],
    ['calendar',     'Events',           (int)fetchValue('SELECT COUNT(*) FROM events')],
    ['chat',         'Messages Sent',    (int)fetchValue('SELECT COUNT(*) FROM messages')],
];

$recentUsers = fetchAll('SELECT * FROM users ORDER BY created_at DESC LIMIT 6');
$recentPubs  = fetchAll('SELECT p.*, u.first_name, u.last_name FROM publications p
                         JOIN users u ON u.id = p.user_id ORDER BY p.created_at DESC LIMIT 5');
$openReqs    = (int)fetchValue('SELECT COUNT(*) FROM collab_requests WHERE status = "pending"');
$suspended   = (int)fetchValue('SELECT COUNT(*) FROM users WHERE status = "suspended"');

$byDept = fetchAll('SELECT department, COUNT(*) c FROM users
                    WHERE department IS NOT NULL GROUP BY department ORDER BY c DESC LIMIT 6');
$maxDept = $byDept ? max(array_column($byDept, 'c')) : 1;

$pageTitle = 'Admin Overview';
require_once ROOT_PATH . '/includes/header.php';
require_once __DIR__ . '/nav.php';
?>

<div class="page-head">
  <div>
    <h1>Admin Overview</h1>
    <p class="sub">Everything happening across <?= e(SITE_NAME) ?>.</p>
  </div>
</div>

<div class="grid grid-4 mb-3">
  <?php foreach ($stats as [$ico, $label, $n]): ?>
    <div class="stat-tile">
      <span class="stat-ico"><?= icon($ico, 19) ?></span>
      <span><span class="n"><?= $n ?></span><span class="l"><?= e($label) ?></span></span>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($openReqs || $suspended): ?>
  <div class="alert alert-warning">
    <span>
      <?= $openReqs ? $openReqs . ' collaboration request' . ($openReqs === 1 ? '' : 's') . ' awaiting a project owner. ' : '' ?>
      <?= $suspended ? $suspended . ' suspended account' . ($suspended === 1 ? '' : 's') . '.' : '' ?>
    </span>
  </div>
<?php endif; ?>

<div class="layout-main-side">
  <div class="flex-col" style="gap:18px">
    <div class="card">
      <div class="card-head"><h2>Newest Members</h2>
        <a class="card-link" href="<?= url('admin/users.php') ?>">Manage users</a></div>
      <div class="table-wrap" style="border:none">
        <table class="data">
          <thead><tr><th>Name</th><th>Role</th><th>Department</th><th>Joined</th></tr></thead>
          <tbody>
          <?php foreach ($recentUsers as $u): ?>
            <tr>
              <td>
                <a class="flex" style="text-decoration:none;color:inherit"
                   href="<?= url('profile.php?id=' . (int)$u['id']) ?>">
                  <?= avatar($u, 30) ?><strong style="font-size:13px"><?= e(fullName($u)) ?></strong>
                </a>
              </td>
              <td><?= roleBadge($u['role']) ?></td>
              <td class="muted"><?= e($u['department'] ?: '--') ?></td>
              <td class="muted"><?= fmtDate($u['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-head"><h2>Latest Publications</h2>
        <a class="card-link" href="<?= url('admin/publications.php') ?>">Manage</a></div>
      <?php foreach ($recentPubs as $p): ?>
        <a class="list-row" style="text-decoration:none;color:inherit"
           href="<?= url('publication.php?id=' . (int)$p['id']) ?>">
          <span class="stat-ico" style="width:34px;height:34px"><?= icon('file', 15) ?></span>
          <span class="flex-1">
            <strong style="font-size:13.5px;display:block"><?= e($p['title']) ?></strong>
            <span class="muted-sm"><?= e($p['first_name'] . ' ' . $p['last_name']) ?> &middot;
              <?= timeAgo($p['created_at']) ?></span>
          </span>
          <span class="chip"><?= e($p['pub_type']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="flex-col" style="gap:18px">
    <div class="card">
      <h3>Members by Department</h3>
      <?php foreach ($byDept as $d): ?>
        <div class="skill-row">
          <div class="flex-between">
            <span class="nm"><?= e($d['department']) ?></span>
            <span class="lv"><?= (int)$d['c'] ?></span>
          </div>
          <div class="progress"><span style="width:<?= (int)$d['c'] / $maxDept * 100 ?>%"></span></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="card">
      <h3>Quick actions</h3>
      <div class="flex-col" style="gap:8px">
        <a class="btn btn-outline btn-block btn-sm" href="<?= url('admin/users.php') ?>">Manage users</a>
        <a class="btn btn-outline btn-block btn-sm" href="<?= url('admin/projects.php') ?>">Review projects</a>
        <a class="btn btn-outline btn-block btn-sm" href="<?= url('admin/publications.php') ?>">Moderate publications</a>
        <a class="btn btn-outline btn-block btn-sm" href="<?= url('admin/events.php') ?>">Manage events</a>
      </div>
    </div>
  </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
