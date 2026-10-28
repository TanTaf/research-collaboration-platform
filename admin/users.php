<?php

require_once dirname(__DIR__) . '/config/config.php';
requireRole('admin');

$me = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action = (string)input('action');
    $target = (int)input('user_id');

    if ($target === (int)$me['id']) {
        setFlash('error', 'You cannot change your own admin account from here.');
        redirect('admin/users.php');
    }

    switch ($action) {
        case 'suspend':
            q('UPDATE users SET status = "suspended" WHERE id = ?', [$target]);
            setFlash('success', 'Account suspended.');
            break;

        case 'activate':
            q('UPDATE users SET status = "active" WHERE id = ?', [$target]);
            setFlash('success', 'Account reactivated.');
            break;

        case 'role':
            $role = (string)input('role');
            if (in_array($role, ['student', 'faculty', 'admin'], true)) {
                q('UPDATE users SET role = ? WHERE id = ?', [$role, $target]);
                setFlash('success', 'Role updated.');
            }
            break;

        case 'reset_password':
            $temp = 'uiu' . random_int(100000, 999999);
            q('UPDATE users SET password = ? WHERE id = ?',
              [password_hash($temp, PASSWORD_DEFAULT), $target]);
            setFlash('success', 'Temporary password set: ' . $temp . ' -- give this to the user and ask them to change it.');
            break;

        case 'delete':
            q('DELETE FROM users WHERE id = ?', [$target]);
            setFlash('success', 'Account deleted along with all of its content.');
            break;
    }
    redirect('admin/users.php');
}

$keyword = (string)input('q');
$role    = (string)input('role');
$status  = (string)input('status');

$sql    = 'SELECT * FROM users WHERE 1=1';
$params = [];

if ($keyword !== '') {
    $sql .= ' AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR student_id LIKE ?)';
    $like = '%' . $keyword . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($role   !== '') { $sql .= ' AND role = ?';   $params[] = $role; }
if ($status !== '') { $sql .= ' AND status = ?'; $params[] = $status; }

$sql .= ' ORDER BY created_at DESC';
$users = fetchAll($sql, $params);

$pageTitle = 'Manage Users';
require_once ROOT_PATH . '/includes/header.php';
require_once __DIR__ . '/nav.php';
?>

<div class="page-head">
  <div>
    <h1>Manage Users</h1>
    <p class="sub"><?= count($users) ?> account<?= count($users) === 1 ? '' : 's' ?> shown.</p>
  </div>
</div>

<form class="filterbar" method="get">
  <input class="grow" type="search" name="q" value="<?= e($keyword) ?>" placeholder="Search by name, email or ID...">
  <select name="role">
    <option value="">All roles</option>
    <?php foreach (['student' => 'Students', 'faculty' => 'Faculty', 'admin' => 'Admins'] as $k => $v): ?>
      <option value="<?= $k ?>" <?= $role === $k ? 'selected' : '' ?>><?= $v ?></option>
    <?php endforeach; ?>
  </select>
  <select name="status">
    <option value="">All statuses</option>
    <option value="active"    <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
    <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
  </select>
  <button class="btn btn-primary" type="submit">Filter</button>
</form>

<div class="table-wrap">
  <table class="data">
    <thead>
      <tr><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): $self = ((int)$u['id'] === (int)$me['id']); ?>
      <tr>
        <td>
          <a class="flex" style="text-decoration:none;color:inherit"
             href="<?= url('profile.php?id=' . (int)$u['id']) ?>">
            <?= avatar($u, 32) ?>
            <span>
              <strong style="font-size:13px;display:block"><?= e(fullName($u)) ?></strong>
              <span class="muted-sm"><?= e($u['department'] ?: '--') ?></span>
            </span>
          </a>
        </td>
        <td class="muted" style="font-size:12.5px"><?= e($u['email']) ?></td>
        <td>
          <?php if ($self): ?>
            <?= roleBadge($u['role']) ?>
          <?php else: ?>
            <form method="post" class="flex" style="gap:5px">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="role">
              <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
              <select name="role" onchange="this.form.submit()" style="width:110px;padding:5px 8px;font-size:12px">
                <?php foreach (['student', 'faculty', 'admin'] as $r): ?>
                  <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          <?php endif; ?>
        </td>
        <td>
          <span class="badge badge-<?= $u['status'] === 'active' ? 'completed' : 'grey' ?>">
            <?= e($u['status']) ?></span>
        </td>
        <td class="muted"><?= fmtDate($u['created_at']) ?></td>
        <td>
          <?php if ($self): ?>
            <span class="muted-sm">That's you</span>
          <?php else: ?>
            <div class="flex" style="gap:5px">
              <form method="post"><?= csrfField() ?>
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <input type="hidden" name="action" value="<?= $u['status'] === 'active' ? 'suspend' : 'activate' ?>">
                <button class="btn btn-outline btn-sm" type="submit">
                  <?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?></button>
              </form>
              <form method="post"><?= csrfField() ?>
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <input type="hidden" name="action" value="reset_password">
                <button class="btn btn-outline btn-sm" type="submit"
                        data-confirm="Set a temporary password for this user?">Reset PW</button>
              </form>
              <form method="post"><?= csrfField() ?>
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <input type="hidden" name="action" value="delete">
                <button class="btn btn-danger btn-sm" type="submit"
                        data-confirm="Delete this account and everything it posted? This cannot be undone.">
                  <?= icon('trash', 13) ?></button>
              </form>
            </div>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
