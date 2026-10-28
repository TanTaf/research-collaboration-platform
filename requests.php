<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me  = currentUser();
$uid = (int)$me['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $rid    = (int)input('request_id');
    $action = (string)input('action');

    $r = fetchOne(
        'SELECT cr.*, p.owner_id, p.title FROM collab_requests cr
         JOIN projects p ON p.id = cr.project_id WHERE cr.id = ?', [$rid]);

    if ($r && (int)$r['owner_id'] === $uid) {
        if ($action === 'accept') {
            q('UPDATE collab_requests SET status = "accepted" WHERE id = ?', [$rid]);
            q('INSERT IGNORE INTO project_members (project_id, user_id, member_role) VALUES (?,?,?)',
              [$r['project_id'], $r['user_id'], 'Member']);
            notify((int)$r['user_id'], 'You were accepted into "' . $r['title'] . '".',
                   'project.php?id=' . $r['project_id']);
            setFlash('success', 'Request accepted. They have been added to the team.');

        } elseif ($action === 'reject') {
            q('UPDATE collab_requests SET status = "rejected" WHERE id = ?', [$rid]);
            notify((int)$r['user_id'], 'Your application to "' . $r['title'] . '" was not accepted this time.',
                   'opportunities.php');
            setFlash('info', 'Request declined.');
        }
    }
    redirect('requests.php');
}

$incoming = fetchAll(
    'SELECT cr.*, p.title, p.id AS pid, u.*, u.id AS applicant_id
     FROM collab_requests cr
     JOIN projects p ON p.id = cr.project_id
     JOIN users u ON u.id = cr.user_id
     WHERE p.owner_id = ? ORDER BY FIELD(cr.status,"pending","accepted","rejected"), cr.created_at DESC',
    [$uid]);

$outgoing = fetchAll(
    'SELECT cr.*, p.title, p.id AS pid, u.first_name, u.last_name
     FROM collab_requests cr
     JOIN projects p ON p.id = cr.project_id
     JOIN users u ON u.id = p.owner_id
     WHERE cr.user_id = ? ORDER BY cr.created_at DESC', [$uid]);

$pageTitle = 'Collaboration Requests';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>Collaboration Requests</h1>
    <p class="sub">People who want to join your projects, and the projects you have applied to.</p>
  </div>
</div>

<div class="tabs mb-3" data-tab-group="req" style="margin:0 0 22px;padding:0">
  <button class="active" data-tab="in">Incoming (<?= count(array_filter($incoming, fn($r) => $r['status'] === 'pending')) ?> pending)</button>
  <button data-tab="out">My Applications (<?= count($outgoing) ?>)</button>
</div>

<div data-panel-group="req" data-panel="in">
  <?php if (!$incoming): ?>
    <div class="empty"><h3>No requests yet</h3>
      <p>When someone applies to one of your projects it will show up here.</p></div>
  <?php else: foreach ($incoming as $r): ?>
    <div class="card mb-2">
      <div class="flex-between flex-wrap" style="gap:14px">
        <div class="flex flex-1" style="align-items:flex-start">
          <?= avatar($r, 46) ?>
          <div class="flex-1">
            <div class="flex" style="gap:8px">
              <strong><?= e(fullName($r)) ?></strong><?= roleBadge($r['role']) ?>
              <span class="badge badge-<?= $r['status'] === 'pending' ? 'pending' : ($r['status'] === 'accepted' ? 'completed' : 'grey') ?>">
                <?= e($r['status']) ?></span>
            </div>
            <p class="muted-sm mb-1"><?= e($r['department']) ?>
              <?= $r['trimester'] ? ' &middot; ' . e($r['trimester']) : '' ?>
              &middot; applied <?= timeAgo($r['created_at']) ?></p>
            <p class="muted-sm mb-1">Project: <a href="<?= url('opportunity.php?id=' . (int)$r['pid']) ?>"><?= e($r['title']) ?></a></p>
            <?php if ($r['message']): ?>
              <p class="mb-0" style="background:#FAFBFC;border-radius:9px;padding:11px 13px;font-size:13px">
                &ldquo;<?= e($r['message']) ?>&rdquo;</p>
            <?php endif; ?>
          </div>
        </div>

        <div class="flex-col" style="gap:7px;min-width:128px">
          <a class="btn btn-outline btn-sm" href="<?= url('profile.php?id=' . (int)$r['applicant_id']) ?>">View Profile</a>
          <?php if ($r['status'] === 'pending'): ?>
            <form method="post"><?= csrfField() ?>
              <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
              <input type="hidden" name="action" value="accept">
              <button class="btn btn-primary btn-sm btn-block" type="submit">Accept</button>
            </form>
            <form method="post"><?= csrfField() ?>
              <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
              <input type="hidden" name="action" value="reject">
              <button class="btn btn-outline btn-sm btn-block" type="submit"
                      data-confirm="Decline this application?">Decline</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<div data-panel-group="req" data-panel="out" class="hide">
  <?php if (!$outgoing): ?>
    <div class="empty"><h3>No applications yet</h3>
      <p class="mb-2">Browse open opportunities and apply to the ones that fit you.</p>
      <a class="btn btn-primary btn-sm" href="<?= url('opportunities.php') ?>">Browse opportunities</a></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Project</th><th>Owner</th><th>Applied</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($outgoing as $o): ?>
          <tr>
            <td><strong><?= e($o['title']) ?></strong></td>
            <td class="muted"><?= e($o['first_name'] . ' ' . $o['last_name']) ?></td>
            <td class="muted"><?= fmtDate($o['created_at']) ?></td>
            <td><span class="badge badge-<?= $o['status'] === 'accepted' ? 'completed' : ($o['status'] === 'pending' ? 'pending' : 'grey') ?>">
              <?= e($o['status']) ?></span></td>
            <td class="text-right">
              <a class="btn btn-outline btn-sm" href="<?= url('opportunity.php?id=' . (int)$o['pid']) ?>">Open</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
