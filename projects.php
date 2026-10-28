<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me  = currentUser();
$uid = (int)$me['id'];

$projects = fetchAll(
    'SELECT p.*, pm.member_role,
            (SELECT COUNT(*) FROM project_members m WHERE m.project_id = p.id) AS members
     FROM projects p JOIN project_members pm ON pm.project_id = p.id
     WHERE pm.user_id = ? ORDER BY FIELD(p.status,"in_progress","pending","completed"), p.created_at DESC',
    [$uid]);

$active    = count(array_filter($projects, fn($p) => $p['status'] === 'in_progress'));
$completed = count(array_filter($projects, fn($p) => $p['status'] === 'completed'));
$invites   = (int)fetchValue(
    'SELECT COUNT(*) FROM collab_requests cr JOIN projects p ON p.id = cr.project_id
     WHERE p.owner_id = ? AND cr.status = "pending"', [$uid]);

$totalProgress = $projects
    ? (int)round(array_sum(array_column($projects, 'progress')) / count($projects))
    : 0;

$milestones = fetchAll(
    'SELECT m.*, p.title AS project_title FROM milestones m
     JOIN projects p ON p.id = m.project_id
     JOIN project_members pm ON pm.project_id = p.id
     WHERE pm.user_id = ? AND m.status <> "completed" AND m.due_date IS NOT NULL
     ORDER BY m.due_date LIMIT 3', [$uid]);

$pageTitle = 'My Projects';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>My Projects</h1>
    <p class="sub">Manage your ongoing and completed research collaborations.</p>
  </div>
  <div class="flex">
    <input type="search" data-filter-input=".project-card" placeholder="Filter projects by title..."
           style="width:210px;height:40px">
    <a class="btn btn-primary" href="<?= url('project_form.php') ?>"><?= icon('plus-circle', 16) ?> Create New Project</a>
  </div>
</div>

<div class="layout-main-side">
  <div>
    <?php if (!$projects): ?>
      <div class="empty">
        <h3>You are not on any project yet</h3>
        <p class="mb-2">Start your own research project, or apply to an open opportunity.</p>
        <a class="btn btn-primary btn-sm" href="<?= url('project_form.php') ?>">Create a project</a>
        <a class="btn btn-outline btn-sm" href="<?= url('opportunities.php') ?>">Browse opportunities</a>
      </div>
    <?php else: ?>
      <div class="grid grid-2">
        <?php foreach ($projects as $p): ?>
          <div class="card card-hover project-card">
            <div class="flex-between mb-2">
              <span class="badge badge-<?= e($p['status']) ?>"><?= e(statusLabel($p['status'])) ?></span>
              <span class="muted-sm"><?= e($p['project_type']) ?></span>
            </div>

            <h3 style="font-family:var(--serif);font-size:18px;margin-bottom:8px">
              <a href="<?= url('project.php?id=' . (int)$p['id']) ?>" style="color:inherit"><?= e($p['title']) ?></a>
            </h3>
            <p class="muted-sm" style="min-height:42px"><?= e(excerpt($p['summary'], 110)) ?></p>

            <div class="flex-between muted-sm mb-1">
              <span>Completion progress</span><strong><?= (int)$p['progress'] ?>%</strong>
            </div>
            <div class="progress <?= $p['status'] === 'completed' ? 'progress-green' : '' ?>">
              <span style="width:<?= (int)$p['progress'] ?>%"></span>
            </div>

            <div class="flex-between mt-3" style="border-top:1px solid var(--line);padding-top:13px">
              <span>
                <span class="muted-sm" style="display:block;font-size:10.5px;letter-spacing:.6px;text-transform:uppercase">Your role</span>
                <strong style="font-size:13px"><?= e($p['member_role']) ?></strong>
              </span>
              <span class="muted-sm"><?= icon('users', 13) ?> <?= (int)$p['members'] ?> members</span>
            </div>
          </div>
        <?php endforeach; ?>

        <a class="dashed-card" href="<?= url('project_form.php') ?>">
          <span>
            <span class="plus"><?= icon('plus', 20) ?></span>
            <strong style="display:block;color:var(--ink);font-size:15px">Start a New Journey</strong>
            <span class="muted-sm">Click here to propose a new research project and invite collaborators.</span>
          </span>
        </a>
      </div>
    <?php endif; ?>
  </div>

  <div class="flex-col" style="gap:18px">
    <div class="card text-center">
      <h3 class="mb-2">Project Stats</h3>
      <div class="ring" style="--pct:<?= $totalProgress ?>">
        <div class="ring-inner"><?= $totalProgress ?>%</div>
      </div>
      <p class="muted-sm mt-1 mb-3">Total work across your projects</p>

      <div class="flex-col" style="gap:12px">
        <div class="flex">
          <span class="stat-ico" style="width:34px;height:34px"><?= icon('activity', 16) ?></span>
          <span class="flex-1 text-left">
            <span style="font-size:13px;font-weight:600">Active Projects</span>
            <div class="progress progress-thin mt-1"><span style="width:<?= $projects ? $active / count($projects) * 100 : 0 ?>%"></span></div>
          </span>
          <strong><?= str_pad((string)$active, 2, '0', STR_PAD_LEFT) ?></strong>
        </div>
        <div class="flex">
          <span class="stat-ico" style="width:34px;height:34px"><?= icon('check-circle', 16) ?></span>
          <span class="flex-1 text-left">
            <span style="font-size:13px;font-weight:600">Completed</span>
            <div class="progress progress-thin progress-green mt-1"><span style="width:<?= $projects ? $completed / count($projects) * 100 : 0 ?>%"></span></div>
          </span>
          <strong><?= str_pad((string)$completed, 2, '0', STR_PAD_LEFT) ?></strong>
        </div>
        <div class="flex">
          <span class="stat-ico" style="width:34px;height:34px"><?= icon('mail', 16) ?></span>
          <span class="flex-1 text-left">
            <span style="font-size:13px;font-weight:600">Join Requests</span>
            <div class="progress progress-thin mt-1"><span style="width:<?= min(100, $invites * 20) ?>%"></span></div>
          </span>
          <strong><?= str_pad((string)$invites, 2, '0', STR_PAD_LEFT) ?></strong>
        </div>
      </div>
    </div>

    <div class="card" style="background:#FEF8F2;border-color:#F6E2CC">
      <h5 class="eyebrow"><?= icon('calendar', 13) ?> Upcoming Milestones</h5>
      <?php if (!$milestones): ?>
        <p class="muted-sm mb-0">No milestones due. Add some inside a project workspace.</p>
      <?php else: foreach ($milestones as $m):
              $days = (int)ceil((strtotime($m['due_date']) - time()) / 86400); ?>
        <div style="border-left:3px solid var(--orange);padding-left:11px;margin-bottom:13px">
          <strong style="font-size:13px;display:block"><?= e($m['title']) ?></strong>
          <span class="muted-sm">
            <?= $days < 0 ? 'Overdue' : ($days === 0 ? 'Due today' : 'Due in ' . $days . ' day' . ($days === 1 ? '' : 's')) ?>
            &middot; <?= e($m['project_title']) ?>
          </span>
        </div>
      <?php endforeach; endif; ?>
      <a class="btn btn-orange-outline btn-block btn-sm" href="<?= url('events.php') ?>">View Full Calendar</a>
    </div>
  </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
