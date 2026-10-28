<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me  = currentUser();
$uid = (int)$me['id'];
$pid = (int)input('id', 0);

$p = fetchOne(
    'SELECT p.*, u.first_name, u.last_name, u.department, u.designation, u.avatar AS owner_avatar,
            u.id AS owner_uid, u.university
     FROM projects p JOIN users u ON u.id = p.owner_id WHERE p.id = ?', [$pid]);

if (!$p) {
    setFlash('error', 'That opportunity no longer exists.');
    redirect('opportunities.php');
}

$isMember = (bool)fetchValue('SELECT COUNT(*) FROM project_members WHERE project_id = ? AND user_id = ?', [$pid, $uid]);
$request  = fetchOne('SELECT * FROM collab_requests WHERE project_id = ? AND user_id = ?', [$pid, $uid]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && input('action') === 'apply') {
    verifyCsrf();

    if ($isMember) {
        setFlash('info', 'You are already part of this project.');
    } elseif ($request) {
        setFlash('info', 'You have already applied to this project.');
    } else {
        q('INSERT INTO collab_requests (project_id, user_id, message) VALUES (?,?,?)',
          [$pid, $uid, input('message') ?: null]);
        notify((int)$p['owner_id'], fullName($me) . ' applied to join "' . $p['title'] . '".', 'requests.php');
        setFlash('success', 'Application sent. The project owner will review it shortly.');
    }
    redirect('opportunity.php?id=' . $pid);
}

$members    = fetchAll('SELECT pm.member_role, u.* FROM project_members pm
                        JOIN users u ON u.id = pm.user_id
                        WHERE pm.project_id = ? ORDER BY pm.joined_at', [$pid]);
$interests  = projectInterestNames($pid);
$skills     = projectSkillNames($pid);
$objectives = array_filter(array_map('trim', explode("\n", (string)$p['objectives'])));

$related = fetchAll(
    'SELECT DISTINCT p2.id, p2.title, p2.deadline FROM projects p2
     JOIN project_interests pi2 ON pi2.project_id = p2.id
     WHERE p2.id <> ? AND p2.is_open = 1
       AND pi2.interest_id IN (SELECT interest_id FROM project_interests WHERE project_id = ?)
     LIMIT 3', [$pid, $pid]);

$pageTitle = $p['title'];
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="layout-main-side">

  <div>
    <div class="card mb-3">
      <div class="flex-between mb-2">
        <span class="chip chip-orange"><?= e($p['project_type']) ?></span>
        <span class="muted-sm">Posted <?= timeAgo($p['created_at']) ?></span>
      </div>

      <h1 style="font-size:28px"><?= e($p['title']) ?></h1>
      <p class="muted"><?= e($p['summary']) ?></p>

      <div class="flex flex-wrap mb-3" style="gap:7px">
        <?php foreach ($interests as $i): ?>
          <span class="chip chip-blue"><?= e($i) ?></span>
        <?php endforeach; ?>
      </div>

      <div class="grid grid-3" style="gap:12px;border-top:1px solid var(--line);padding-top:18px">
        <div class="info-tile">
          <span class="ico"><?= icon('file', 16) ?></span>
          <span><span class="k">Project Type</span><br><span class="v"><?= e($p['project_type']) ?></span></span>
        </div>
        <div class="info-tile">
          <span class="ico"><?= icon('users', 16) ?></span>
          <span><span class="k">Required Members</span><br><span class="v"><?= e($p['required_members']) ?></span></span>
        </div>
        <div class="info-tile">
          <span class="ico"><?= icon('calendar', 16) ?></span>
          <span><span class="k">Deadline</span><br>
            <span class="v"><?= $p['deadline'] ? fmtDate($p['deadline']) : 'Rolling' ?></span></span>
        </div>
      </div>
    </div>

    <div class="card mb-3">
      <h2><?= icon('grid-list', 18) ?> Project Overview</h2>
      <p class="muted" style="line-height:1.8"><?= nl2br(e($p['description'])) ?></p>
    </div>

    <div class="grid grid-2 mb-3">
      <?php if ($objectives): ?>
        <div class="card">
          <h2>Objectives</h2>
          <?php foreach ($objectives as $o): ?>
            <div class="flex mb-2" style="align-items:flex-start">
              <span style="color:var(--orange);flex-shrink:0"><?= icon('check-circle', 16) ?></span>
              <span class="muted-sm"><?= e($o) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($skills): ?>
        <div class="card">
          <h2>Stack &amp; Tools</h2>
          <div class="flex flex-wrap" style="gap:8px">
            <?php foreach ($skills as $s): ?>
              <span class="chip chip-orange-soft"><?= e($s) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($interests): ?>
      <div class="card mb-3">
        <h2><?= icon('users', 18) ?> Looking for Members With</h2>
        <div class="flex flex-wrap" style="gap:9px">
          <?php foreach ($interests as $i): ?>
            <span class="chip chip-outline chip-lg"><?= e($i) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="card" style="background:#FAFBFC">
      <div class="eyebrow">Project Owner</div>
      <div class="flex-between flex-wrap" style="gap:14px">
        <div class="flex">
          <?= avatar(['id' => $p['owner_uid'], 'first_name' => $p['first_name'],
                      'last_name' => $p['last_name'], 'avatar' => $p['owner_avatar']], 58) ?>
          <div>
            <h3 style="font-family:var(--serif);margin-bottom:2px"><?= e($p['first_name'] . ' ' . $p['last_name']) ?></h3>
            <p class="muted-sm mb-0"><?= e($p['department']) ?><?= $p['designation'] ? ' | ' . e($p['designation']) : '' ?></p>
            <p class="muted-sm mb-0"><?= e($p['university']) ?></p>
          </div>
        </div>
        <a class="btn btn-primary" href="<?= url('profile.php?id=' . (int)$p['owner_uid']) ?>">View Profile</a>
      </div>
    </div>
  </div>

  <div class="flex-col" style="gap:18px">

    <div class="card">
      <h3>Interested in this opportunity?</h3>
      <p class="muted-sm">Apply now and start your research journey with this team.</p>

      <?php if ((int)$p['owner_id'] === $uid): ?>
        <a class="btn btn-primary btn-block" href="<?= url('project.php?id=' . $pid) ?>">Open Workspace</a>
        <a class="btn btn-outline btn-block mt-1" href="<?= url('project_form.php?id=' . $pid) ?>">Edit Project</a>
      <?php elseif ($isMember): ?>
        <div class="alert alert-success mb-2"><span>You are already on this team.</span></div>
        <a class="btn btn-primary btn-block" href="<?= url('project.php?id=' . $pid) ?>">Open Workspace</a>
      <?php elseif ($request): ?>
        <div class="alert alert-<?= $request['status'] === 'rejected' ? 'error' : 'info' ?> mb-2">
          <span>Your application is <strong><?= e($request['status']) ?></strong>.</span>
        </div>
        <a class="btn btn-outline btn-block" href="<?= url('messages.php?to=' . (int)$p['owner_uid']) ?>">Message Owner</a>
      <?php else: ?>
        <form method="post">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="apply">
          <div class="form-group">
            <label for="message">Why are you a good fit?</label>
            <textarea id="message" name="message" style="min-height:96px"
                      placeholder="Tell the owner about relevant skills or coursework..."></textarea>
          </div>
          <button class="btn btn-primary btn-block" type="submit">Apply Now</button>
        </form>
        <a class="btn btn-orange-outline btn-block mt-1"
           href="<?= url('messages.php?to=' . (int)$p['owner_uid']) ?>">Connect with Owner</a>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card-head">
        <h3 style="margin:0">Team Members (<?= count($members) ?>)</h3>
        <?php if ($p['is_open']): ?><span class="badge badge-open">Open</span><?php endif; ?>
      </div>

      <?php foreach ($members as $m): ?>
        <a class="flex mb-2" style="text-decoration:none;color:inherit"
           href="<?= url('profile.php?id=' . (int)$m['id']) ?>">
          <?= avatar($m, 38) ?>
          <span>
            <span style="font-size:13px;font-weight:600;display:block"><?= e(fullName($m)) ?></span>
            <span class="muted-sm"><?= e($m['member_role']) ?><?= $m['trimester'] ? ' | ' . e($m['trimester']) : '' ?></span>
          </span>
        </a>
      <?php endforeach; ?>

      <?php if ($p['is_open']): ?>
        <div class="dashed-card" style="min-height:auto;padding:18px">
          <div class="plus"><?= icon('plus', 18) ?></div>
          <strong style="font-size:13px;color:var(--ink)"><?= e($p['required_members']) ?> Members Needed</strong>
          <?php if ($skills): ?>
            <span class="muted-sm">Specific skills: <?= e(implode(', ', array_slice($skills, 0, 3))) ?></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($related): ?>
      <div class="card">
        <h5 class="eyebrow eyebrow-muted">Related Opportunities</h5>
        <?php foreach ($related as $r): ?>
          <a class="list-row" style="text-decoration:none;color:inherit"
             href="<?= url('opportunity.php?id=' . (int)$r['id']) ?>">
            <span class="flex-1">
              <span style="font-size:13px;font-weight:600;display:block"><?= e($r['title']) ?></span>
              <span class="muted-sm"><?= $r['deadline'] ? 'Due ' . fmtDate($r['deadline']) : 'Rolling' ?></span>
            </span>
            <?= icon('chevron-right', 16) ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
