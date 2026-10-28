<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me  = currentUser();
$uid = (int)$me['id'];

$countInvites  = (int)fetchValue(
    'SELECT COUNT(*) FROM collab_requests cr JOIN projects p ON p.id = cr.project_id
     WHERE p.owner_id = ? AND cr.status = "pending"', [$uid]);
$countPapers   = (int)fetchValue('SELECT COUNT(*) FROM publications WHERE status = "published"');
$countOpps     = (int)fetchValue('SELECT COUNT(*) FROM projects WHERE is_open = 1 AND owner_id <> ?', [$uid]);
$countComms    = (int)fetchValue('SELECT COUNT(*) FROM community_members WHERE user_id = ?', [$uid]);

$candidates = fetchAll(
    'SELECT u.* FROM users u
     WHERE u.id <> ? AND u.status = "active" AND u.role <> "admin" AND u.open_to_collab = 1
     LIMIT 40', [$uid]);

$matches = [];
foreach ($candidates as $c) {
    $score = matchScore($uid, (int)$c['id']);
    if ($score > 0) { $c['score'] = $score; $matches[] = $c; }
}
usort($matches, fn($a, $b) => $b['score'] <=> $a['score']);
$matches = array_slice($matches, 0, 2);

$opportunities = fetchAll(
    'SELECT p.*, u.first_name, u.last_name FROM projects p
     JOIN users u ON u.id = p.owner_id
     WHERE p.is_open = 1 AND p.owner_id <> ?
       AND p.id NOT IN (SELECT project_id FROM project_members WHERE user_id = ?)
     ORDER BY p.created_at DESC LIMIT 3', [$uid, $uid]);

$events = fetchAll('SELECT * FROM events WHERE event_date >= NOW() ORDER BY event_date LIMIT 2');

$recentMsgs = fetchAll(
    'SELECT m.*, u.first_name, u.last_name, u.avatar, u.id AS uid
     FROM messages m JOIN users u ON u.id = m.sender_id
     WHERE m.receiver_id = ? ORDER BY m.created_at DESC LIMIT 3', [$uid]);

$myProjects = fetchAll(
    'SELECT p.* FROM projects p
     JOIN project_members pm ON pm.project_id = p.id
     WHERE pm.user_id = ? AND p.status = "in_progress"
     ORDER BY p.created_at DESC LIMIT 3', [$uid]);

$completion = profileCompletion($me);

$pageTitle = 'Dashboard';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>Welcome, <?= e($me['first_name']) ?>!</h1>
    <p class="sub">Here is what is happening in your research journey today.</p>
  </div>
  <a class="btn btn-primary" href="<?= url('project_form.php') ?>"><?= icon('plus', 16) ?> New Project</a>
</div>

<div class="grid grid-4 mb-3">
  <a class="stat-tile" href="<?= url('requests.php') ?>" style="text-decoration:none;color:inherit">
    <span class="stat-ico"><?= icon('users', 19) ?></span>
    <span><span class="n"><?= $countInvites ?></span><span class="l">Join Requests</span></span>
  </a>
  <a class="stat-tile" href="<?= url('repository.php') ?>" style="text-decoration:none;color:inherit">
    <span class="stat-ico"><?= icon('file', 19) ?></span>
    <span><span class="n"><?= $countPapers ?></span><span class="l">Papers in Repository</span></span>
  </a>
  <a class="stat-tile" href="<?= url('opportunities.php') ?>" style="text-decoration:none;color:inherit">
    <span class="stat-ico"><?= icon('briefcase', 19) ?></span>
    <span><span class="n"><?= $countOpps ?></span><span class="l">Open Opportunities</span></span>
  </a>
  <a class="stat-tile" href="<?= url('communities.php') ?>" style="text-decoration:none;color:inherit">
    <span class="stat-ico"><?= icon('globe', 19) ?></span>
    <span><span class="n"><?= $countComms ?></span><span class="l">My Communities</span></span>
  </a>
</div>

<div class="layout-main-side">

  <div>

    <div class="card mb-3">
      <div class="card-head">
        <h2>Recommended Collaborators</h2>
        <a class="card-link" href="<?= url('matchmaking.php') ?>">View all matches</a>
      </div>

      <?php if (!$matches): ?>
        <div class="empty">
          <h3>No matches yet</h3>
          <p class="mb-2">Add a few research interests and we will find people working on the same things.</p>
          <a class="btn btn-primary btn-sm" href="<?= url('profile_edit.php') ?>">Add interests</a>
        </div>
      <?php else: ?>
        <div class="grid grid-2">
          <?php foreach ($matches as $m): ?>
            <div class="card card-tight text-center">
              <div style="display:flex;justify-content:center;margin-bottom:10px"><?= avatar($m, 62) ?></div>
              <h4 style="margin-bottom:2px"><?= e(fullName($m)) ?></h4>
              <p class="muted-sm mb-1"><?= e($m['department']) ?></p>
              <span class="match-tag"><?= (int)$m['score'] ?>% match</span>
              <div class="flex mt-2" style="gap:7px">
                <a class="btn btn-primary btn-sm flex-1" href="<?= url('profile.php?id=' . (int)$m['id']) ?>">View Profile</a>
                <a class="btn btn-outline btn-sm" href="<?= url('messages.php?to=' . (int)$m['id']) ?>"
                   title="Message"><?= icon('mail', 14) ?></a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="card mb-3">
      <div class="card-head">
        <h2>Latest Opportunities</h2>
        <a class="card-link" href="<?= url('opportunities.php') ?>">See all</a>
      </div>

      <?php if (!$opportunities): ?>
        <p class="muted">No open opportunities right now. Check back soon.</p>
      <?php else: foreach ($opportunities as $o): ?>
        <a class="list-card" href="<?= url('opportunity.php?id=' . (int)$o['id']) ?>">
          <div class="flex-between mb-1">
            <span class="chip chip-orange-soft"><?= e($o['project_type']) ?></span>
            <span class="muted-sm"><?= $o['deadline'] ? 'Deadline: ' . fmtDate($o['deadline']) : 'Rolling' ?></span>
          </div>
          <h4 style="font-family:var(--serif);font-size:16px;margin-bottom:6px"><?= e($o['title']) ?></h4>
          <div class="flex-between">
            <p class="muted-sm mb-0" style="max-width:74%"><?= e(excerpt($o['summary'], 95)) ?></p>
            <span class="card-link nowrap">View Details <?= icon('arrow-right', 12) ?></span>
          </div>
        </a>
      <?php endforeach; endif; ?>
    </div>

    <?php if ($myProjects): ?>
    <div class="card">
      <div class="card-head">
        <h2>Your Active Projects</h2>
        <a class="card-link" href="<?= url('projects.php') ?>">Manage</a>
      </div>
      <?php foreach ($myProjects as $p): ?>
        <a class="list-card" href="<?= url('project.php?id=' . (int)$p['id']) ?>">
          <div class="flex-between mb-1">
            <h4 style="margin:0;font-size:15px"><?= e($p['title']) ?></h4>
            <span class="badge badge-<?= e($p['status']) ?>"><?= e(statusLabel($p['status'])) ?></span>
          </div>
          <div class="flex-between muted-sm mb-1">
            <span>Completion progress</span><span><?= (int)$p['progress'] ?>%</span>
          </div>
          <div class="progress"><span style="width:<?= (int)$p['progress'] ?>%"></span></div>
        </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="flex-col" style="gap:18px">

    <div class="card text-center">
      <h3 class="mb-2">Profile Completion</h3>
      <div class="ring" style="--pct:<?= $completion ?>">
        <div class="ring-inner"><?= $completion ?>%</div>
      </div>
      <p class="muted-sm mt-2 mb-2">
        <?= $completion >= 90 ? 'Excellent! Your profile stands out.'
            : ($completion >= 60 ? 'Almost there! Complete your profile to get better recommendations.'
            : 'Add more details so we can match you with the right collaborators.') ?>
      </p>
      <a class="btn btn-primary btn-block btn-sm" href="<?= url('profile_edit.php') ?>">Update Profile</a>
    </div>

    <div class="card">
      <h5 class="eyebrow eyebrow-muted">Upcoming Events</h5>
      <?php if (!$events): ?>
        <p class="muted-sm">Nothing scheduled yet.</p>
      <?php else: foreach ($events as $ev): ?>
        <div class="flex mb-2" style="align-items:flex-start">
          <span class="stat-ico" style="width:34px;height:34px"><?= icon('calendar', 16) ?></span>
          <span>
            <span style="font-size:13px;font-weight:600;display:block"><?= e($ev['title']) ?></span>
            <span class="muted-sm"><?= fmtDate($ev['event_date'], 'd M') ?> &middot;
              <?= fmtDate($ev['event_date'], 'g:i A') ?></span>
          </span>
        </div>
      <?php endforeach; endif; ?>
      <a class="btn btn-outline btn-block btn-sm mt-1" href="<?= url('events.php') ?>">View All Events</a>
    </div>

    <div class="card">
      <h5 class="eyebrow eyebrow-muted">Recent Messages</h5>
      <?php if (!$recentMsgs): ?>
        <p class="muted-sm">No messages yet.</p>
      <?php else: foreach ($recentMsgs as $msg): ?>
        <a class="flex mb-2" style="text-decoration:none;color:inherit;align-items:flex-start"
           href="<?= url('messages.php?to=' . (int)$msg['uid']) ?>">
          <?= avatar(['id' => $msg['uid'], 'first_name' => $msg['first_name'],
                      'last_name' => $msg['last_name'], 'avatar' => $msg['avatar']], 34) ?>
          <span class="flex-1">
            <span class="flex-between">
              <span style="font-size:13px;font-weight:600"><?= e($msg['first_name'] . ' ' . $msg['last_name']) ?></span>
              <span class="muted-sm"><?= timeAgo($msg['created_at']) ?></span>
            </span>
            <span class="muted-sm" style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
              <?= e(excerpt($msg['body'], 42)) ?></span>
          </span>
        </a>
      <?php endforeach; endif; ?>
      <a class="btn btn-outline btn-block btn-sm mt-1" href="<?= url('messages.php') ?>">Inbox</a>
    </div>
  </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
