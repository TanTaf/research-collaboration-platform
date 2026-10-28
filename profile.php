<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me     = currentUser();
$viewId = (int)(input('id', 0) ?: $me['id']);
$user   = fetchOne('SELECT * FROM users WHERE id = ? AND status = "active"', [$viewId]);

if (!$user) {
    setFlash('error', 'That researcher profile could not be found.');
    redirect('directory.php');
}

$isSelf = ((int)$user['id'] === (int)$me['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && input('action') === 'connect') {
    verifyCsrf();
    q('INSERT IGNORE INTO connections (requester_id, addressee_id, status) VALUES (?,?,"pending")',
      [$me['id'], $user['id']]);
    notify((int)$user['id'], fullName($me) . ' wants to connect with you.', 'profile.php?id=' . $me['id']);
    setFlash('success', 'Connection request sent to ' . fullName($user) . '.');
    redirect('profile.php?id=' . $user['id']);
}

$interests = userInterestNames($viewId);

$skills = fetchAll(
    'SELECT s.name, us.level FROM user_skills us
     JOIN skills s ON s.id = us.skill_id
     WHERE us.user_id = ? ORDER BY FIELD(us.level,"Expert","Advanced","Intermediate","Beginner"), s.name',
    [$viewId]);

$projects = fetchAll(
    'SELECT p.*, pm.member_role FROM projects p
     JOIN project_members pm ON pm.project_id = p.id
     WHERE pm.user_id = ? ORDER BY p.created_at DESC', [$viewId]);

$publications = fetchAll(
    'SELECT * FROM publications WHERE user_id = ? AND status = "published" ORDER BY created_at DESC',
    [$viewId]);

$connCount = (int)fetchValue(
    'SELECT COUNT(*) FROM connections WHERE status = "accepted" AND (requester_id = ? OR addressee_id = ?)',
    [$viewId, $viewId]);
$citations = (int)fetchValue('SELECT COALESCE(SUM(citations),0) FROM publications WHERE user_id = ?', [$viewId]);

$connState = fetchOne(
    'SELECT status FROM connections WHERE (requester_id = ? AND addressee_id = ?) OR (requester_id = ? AND addressee_id = ?)',
    [$me['id'], $user['id'], $user['id'], $me['id']]);

$levelPct = ['Beginner' => 30, 'Intermediate' => 55, 'Advanced' => 78, 'Expert' => 96];

$pageTitle = fullName($user);
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="profile-card mb-3">
  <div class="profile-cover"></div>
  <div class="profile-body">
    <div class="flex-between flex-wrap" style="align-items:flex-end">
      <div class="profile-avatar"><?= avatar($user, 112) ?></div>

      <div class="flex" style="gap:9px;padding-bottom:14px">
        <?php if ($isSelf): ?>
          <a class="btn btn-primary" href="<?= url('profile_edit.php') ?>"><?= icon('edit', 15) ?> Edit Profile</a>
        <?php else: ?>
          <?php if (!$connState): ?>
            <form method="post" style="display:inline">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="connect">
              <button class="btn btn-primary" type="submit"><?= icon('user-plus', 15) ?> Connect</button>
            </form>
          <?php else: ?>
            <span class="btn btn-outline disabled"><?= icon('check', 15) ?>
              <?= $connState['status'] === 'accepted' ? 'Connected' : 'Request Pending' ?></span>
          <?php endif; ?>
          <a class="btn btn-orange-outline" href="<?= url('messages.php?to=' . (int)$user['id']) ?>">
            <?= icon('send', 15) ?> Message</a>
        <?php endif; ?>
      </div>
    </div>

    <h1 class="mt-2" style="margin-bottom:6px"><?= e(fullName($user)) ?> <?= roleBadge($user['role']) ?></h1>

    <div class="profile-meta">
      <?php if ($user['designation']): ?><span><?= e($user['designation']) ?></span><span>&middot;</span><?php endif; ?>
      <span><?= e($user['department'] ?: 'Department not set') ?></span>
      <?php if ($user['trimester']): ?><span>&middot;</span><span><?= e($user['trimester']) ?></span><?php endif; ?>
      <span>&middot;</span><span><?= e($user['university']) ?>, <?= e($user['city']) ?></span>
    </div>

    <div class="profile-stats">
      <?php if ($user['cgpa']): ?>
        <div><div class="v"><?= number_format((float)$user['cgpa'], 2) ?></div><div class="k">CGPA</div></div>
      <?php endif; ?>
      <?php if ($user['student_id']): ?>
        <div><div class="v"><?= e($user['student_id']) ?></div><div class="k">Student ID</div></div>
      <?php endif; ?>
      <div><div class="v"><?= $connCount ?></div><div class="k">Connections</div></div>
      <div><div class="v"><?= $citations ?></div><div class="k">Citations</div></div>
      <?php if ($user['open_to_collab']): ?>
        <span class="match-tag" style="padding:6px 14px"><?= icon('check-circle', 13) ?> Available for Collaboration</span>
      <?php endif; ?>
    </div>

    <div class="tabs mt-3" data-tab-group="profile">
      <button class="active" data-tab="overview">Overview</button>
      <button data-tab="projects">Projects (<?= count($projects) ?>)</button>
      <button data-tab="publications">Publications (<?= count($publications) ?>)</button>
    </div>
  </div>
</div>

<div data-panel-group="profile" data-panel="overview">
  <div class="layout-main-side">
    <div class="flex-col" style="gap:18px">

      <div class="card">
        <div class="card-head"><h3>About</h3>
          <?php if ($isSelf): ?><a class="card-link" href="<?= url('profile_edit.php') ?>">Edit <?= icon('edit', 12) ?></a><?php endif; ?>
        </div>
        <p class="muted" style="margin-bottom:18px">
          <?= $user['bio'] ? nl2br(e($user['bio'])) : 'This researcher has not written a bio yet.' ?>
        </p>

        <div class="grid grid-2">
          <div class="info-tile">
            <span class="ico"><?= icon('folder', 16) ?></span>
            <span><span class="k">Active Projects</span><br>
              <span class="v"><?= count(array_filter($projects, fn($p) => $p['status'] === 'in_progress')) ?> running</span></span>
          </div>
          <div class="info-tile">
            <span class="ico"><?= icon('users', 16) ?></span>
            <span><span class="k">Looking for Team</span><br>
              <span class="v"><?= $user['open_to_collab'] ? 'Yes' : 'Not right now' ?></span></span>
          </div>
          <div class="info-tile">
            <span class="ico"><?= icon('target', 16) ?></span>
            <span><span class="k">Preferred Team Size</span><br>
              <span class="v"><?= e($user['preferred_team'] ?: 'Not set') ?></span></span>
          </div>
          <div class="info-tile">
            <span class="ico"><?= icon('clock', 16) ?></span>
            <span><span class="k">Availability</span><br>
              <span class="v"><?= e($user['availability'] ?: 'Not set') ?></span></span>
          </div>
        </div>
      </div>

      <div class="card">
        <h3>Contact Information</h3>
        <div class="flex-col" style="gap:12px">
          <div class="flex"><?= icon('mail', 16, 'muted') ?><span class="muted"><?= e($user['email']) ?></span></div>
          <?php if ($user['phone']): ?>
            <div class="flex"><?= icon('phone', 16) ?><span class="muted"><?= e($user['phone']) ?></span></div>
          <?php endif; ?>
          <?php if ($user['linkedin']): ?>
            <div class="flex"><?= icon('link', 16) ?><a href="https://<?= e(ltrim($user['linkedin'], 'https://')) ?>"
               target="_blank" rel="noopener"><?= e($user['linkedin']) ?></a></div>
          <?php endif; ?>
          <?php if ($user['orcid']): ?>
            <div class="flex"><?= icon('award', 16) ?><span class="muted">ORCID: <?= e($user['orcid']) ?></span></div>
          <?php endif; ?>
          <?php if ($user['website']): ?>
            <div class="flex"><?= icon('globe', 16) ?><a href="<?= e($user['website']) ?>" target="_blank" rel="noopener"><?= e($user['website']) ?></a></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="flex-col" style="gap:18px">
      <div class="card">
        <div class="card-head"><h3>Research Interests</h3></div>
        <?php if (!$interests): ?>
          <p class="muted-sm">No interests added yet.</p>
        <?php else: ?>
          <div class="flex flex-wrap" style="gap:8px">
            <?php foreach ($interests as $i): ?>
              <span class="chip chip-blue"><?= e($i) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="card">
        <div class="card-head"><h3>Skills</h3></div>
        <?php if (!$skills): ?>
          <p class="muted-sm">No skills added yet.</p>
        <?php else: foreach ($skills as $s): ?>
          <div class="skill-row">
            <div class="flex-between">
              <span class="nm"><?= e($s['name']) ?></span>
              <span class="lv"><?= e($s['level']) ?></span>
            </div>
            <div class="progress"><span style="width:<?= $levelPct[$s['level']] ?? 50 ?>%"></span></div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<div data-panel-group="profile" data-panel="projects" class="hide">
  <?php if (!$projects): ?>
    <div class="empty"><h3>No projects yet</h3><p>Projects this researcher joins will appear here.</p></div>
  <?php else: ?>
    <div class="grid grid-2">
      <?php foreach ($projects as $p): ?>
        <a class="card card-hover" href="<?= url('project.php?id=' . (int)$p['id']) ?>"
           style="text-decoration:none;color:inherit">
          <div class="flex-between mb-1">
            <span class="badge badge-<?= e($p['status']) ?>"><?= e(statusLabel($p['status'])) ?></span>
            <span class="muted-sm"><?= e($p['project_type']) ?></span>
          </div>
          <h3 style="font-family:var(--serif);font-size:17px"><?= e($p['title']) ?></h3>
          <p class="muted-sm"><?= e(excerpt($p['summary'], 105)) ?></p>
          <div class="flex-between muted-sm mb-1"><span>Progress</span><span><?= (int)$p['progress'] ?>%</span></div>
          <div class="progress"><span style="width:<?= (int)$p['progress'] ?>%"></span></div>
          <p class="muted-sm mt-2 mb-0">Role: <strong><?= e($p['member_role']) ?></strong></p>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div data-panel-group="profile" data-panel="publications" class="hide">
  <?php if (!$publications): ?>
    <div class="empty"><h3>No publications yet</h3><p>Papers, pre-prints and datasets will be listed here.</p></div>
  <?php else: foreach ($publications as $pub): ?>
    <a class="list-card" href="<?= url('publication.php?id=' . (int)$pub['id']) ?>">
      <div class="flex-between mb-1">
        <span class="chip chip-orange-soft"><?= e($pub['pub_type']) ?></span>
        <?php if ((int)$pub['current_version'] > 1): ?><?= versionBadge((int)$pub['current_version']) ?><?php endif; ?>
        <span class="muted-sm"><?= (int)$pub['year'] ?></span>
      </div>
      <h4 style="font-family:var(--serif);font-size:16px;margin-bottom:5px"><?= e($pub['title']) ?></h4>
      <p class="muted-sm mb-1"><?= e($pub['journal']) ?></p>
      <div class="flex muted-sm" style="gap:18px">
        <span><?= icon('eye', 13) ?> <?= fmtCount((int)$pub['views']) ?> views</span>
        <span><?= icon('download', 13) ?> <?= fmtCount((int)$pub['downloads']) ?> downloads</span>
        <span><?= icon('award', 13) ?> <?= (int)$pub['citations'] ?> citations</span>
      </div>
    </a>
  <?php endforeach; endif; ?>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
