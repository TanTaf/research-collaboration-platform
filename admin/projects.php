<?php

require_once dirname(__DIR__) . '/config/config.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $pid = (int)input('project_id');

    if (input('action') === 'delete') {
        q('DELETE FROM projects WHERE id = ?', [$pid]);
        setFlash('success', 'Project deleted.');
    } elseif (input('action') === 'status') {
        $s = (string)input('status');
        if (in_array($s, ['pending', 'in_progress', 'completed'], true)) {
            q('UPDATE projects SET status = ? WHERE id = ?', [$s, $pid]);
            setFlash('success', 'Project status updated.');
        }
    } elseif (input('action') === 'toggle_open') {
        q('UPDATE projects SET is_open = 1 - is_open WHERE id = ?', [$pid]);
        setFlash('success', 'Recruiting status toggled.');
    }
    redirect('admin/projects.php');
}

$keyword = (string)input('q');
$status  = (string)input('status');

$sql = 'SELECT p.*, u.first_name, u.last_name,
          (SELECT COUNT(*) FROM project_members m WHERE m.project_id = p.id) AS members,
          (SELECT COUNT(*) FROM collab_requests r WHERE r.project_id = p.id AND r.status = "pending") AS pending
        FROM projects p JOIN users u ON u.id = p.owner_id WHERE 1=1';
$params = [];

if ($keyword !== '') { $sql .= ' AND p.title LIKE ?'; $params[] = '%' . $keyword . '%'; }
if ($status  !== '') { $sql .= ' AND p.status = ?';   $params[] = $status; }

$sql .= ' ORDER BY p.created_at DESC';
$projects = fetchAll($sql, $params);

$pageTitle = 'Manage Projects';
require_once ROOT_PATH . '/includes/header.php';
require_once __DIR__ . '/nav.php';
?>

<div class="page-head">
  <div>
    <h1>Manage Projects</h1>
    <p class="sub"><?= count($projects) ?> project<?= count($projects) === 1 ? '' : 's' ?> shown.</p>
  </div>
</div>

<form class="filterbar" method="get">
  <input class="grow" type="search" name="q" value="<?= e($keyword) ?>" placeholder="Search project titles...">
  <select name="status">
    <option value="">All statuses</option>
    <?php foreach (['pending', 'in_progress', 'completed'] as $s): ?>
      <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(statusLabel($s)) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-primary" type="submit">Filter</button>
</form>

<div class="table-wrap">
  <table class="data">
    <thead>
      <tr><th>Project</th><th>Owner</th><th>Type</th><th>Team</th><th>Status</th><th>Recruiting</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($projects as $p): ?>
      <tr>
        <td>
          <a href="<?= url('opportunity.php?id=' . (int)$p['id']) ?>" style="color:inherit;text-decoration:none">
            <strong style="font-size:13px"><?= e($p['title']) ?></strong></a>
          <div class="muted-sm"><?= (int)$p['progress'] ?>% complete
            <?= (int)$p['pending'] ? ' &middot; ' . (int)$p['pending'] . ' pending request(s)' : '' ?></div>
        </td>
        <td class="muted"><?= e($p['first_name'] . ' ' . $p['last_name']) ?></td>
        <td class="muted"><?= e($p['project_type']) ?></td>
        <td class="muted"><?= (int)$p['members'] ?></td>
        <td>
          <form method="post"><?= csrfField() ?>
            <input type="hidden" name="action" value="status">
            <input type="hidden" name="project_id" value="<?= (int)$p['id'] ?>">
            <select name="status" onchange="this.form.submit()" style="width:130px;padding:5px 8px;font-size:12px">
              <?php foreach (['pending', 'in_progress', 'completed'] as $s): ?>
                <option value="<?= $s ?>" <?= $p['status'] === $s ? 'selected' : '' ?>><?= e(statusLabel($s)) ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </td>
        <td>
          <form method="post"><?= csrfField() ?>
            <input type="hidden" name="action" value="toggle_open">
            <input type="hidden" name="project_id" value="<?= (int)$p['id'] ?>">
            <button class="badge badge-<?= $p['is_open'] ? 'open' : 'grey' ?>" type="submit"
                    style="border:none;cursor:pointer;font-family:inherit">
              <?= $p['is_open'] ? 'Open' : 'Closed' ?></button>
          </form>
        </td>
        <td>
          <div class="flex" style="gap:5px">
            <a class="btn btn-outline btn-sm" href="<?= url('project.php?id=' . (int)$p['id']) ?>">Open</a>
            <form method="post"><?= csrfField() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="project_id" value="<?= (int)$p['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit"
                      data-confirm="Delete this project and all of its milestones, tasks, files and chat?">
                <?= icon('trash', 13) ?></button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
