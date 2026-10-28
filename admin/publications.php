<?php

require_once dirname(__DIR__) . '/config/config.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $id = (int)input('pub_id');

    if (input('action') === 'delete') {
        deletePubFiles($id);
        q('DELETE FROM publications WHERE id = ?', [$id]);
        setFlash('success', 'Publication removed.');

    } elseif (input('action') === 'toggle_status') {
        q('UPDATE publications SET status = IF(status = "published", "draft", "published") WHERE id = ?', [$id]);
        setFlash('success', 'Visibility changed.');
    }
    redirect('admin/publications.php');
}

$keyword = (string)input('q');
$type    = (string)input('type');

$sql = 'SELECT p.*, u.first_name, u.last_name,
          (SELECT COUNT(*) FROM publication_comments c WHERE c.publication_id = p.id) AS comments
        FROM publications p JOIN users u ON u.id = p.user_id WHERE 1=1';
$params = [];

if ($keyword !== '') {
    $sql .= ' AND (p.title LIKE ? OR p.authors LIKE ?)';
    array_push($params, '%' . $keyword . '%', '%' . $keyword . '%');
}
if ($type !== '') { $sql .= ' AND p.pub_type = ?'; $params[] = $type; }

$sql .= ' ORDER BY p.created_at DESC';
$pubs = fetchAll($sql, $params);

$pageTitle = 'Manage Publications';
require_once ROOT_PATH . '/includes/header.php';
require_once __DIR__ . '/nav.php';
?>

<div class="page-head">
  <div>
    <h1>Manage Publications</h1>
    <p class="sub"><?= count($pubs) ?> record<?= count($pubs) === 1 ? '' : 's' ?> in the repository.</p>
  </div>
</div>

<form class="filterbar" method="get">
  <input class="grow" type="search" name="q" value="<?= e($keyword) ?>" placeholder="Search by title or author...">
  <select name="type">
    <option value="">All types</option>
    <?php foreach (['Paper', 'Pre-print', 'Dataset', 'Thesis'] as $t): ?>
      <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= $t ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-primary" type="submit">Filter</button>
</form>

<div class="table-wrap">
  <table class="data">
    <thead>
      <tr><th>Title</th><th>Uploader</th><th>Type</th><th>Version</th><th>Year</th><th>Views</th>
          <th>Comments</th><th>Visibility</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($pubs as $p): ?>
      <tr>
        <td style="max-width:300px">
          <a href="<?= url('publication.php?id=' . (int)$p['id']) ?>" style="color:inherit;text-decoration:none">
            <strong style="font-size:13px"><?= e($p['title']) ?></strong></a>
          <div class="muted-sm"><?= e($p['journal']) ?></div>
        </td>
        <td class="muted"><?= e($p['first_name'] . ' ' . $p['last_name']) ?></td>
        <td class="muted"><?= e($p['pub_type']) ?></td>
        <td class="muted">v<?= (int)$p['current_version'] ?></td>
        <td class="muted"><?= (int)$p['year'] ?></td>
        <td class="muted"><?= fmtCount((int)$p['views']) ?></td>
        <td class="muted"><?= (int)$p['comments'] ?></td>
        <td>
          <form method="post"><?= csrfField() ?>
            <input type="hidden" name="action" value="toggle_status">
            <input type="hidden" name="pub_id" value="<?= (int)$p['id'] ?>">
            <button class="badge badge-<?= $p['status'] === 'published' ? 'completed' : 'grey' ?>" type="submit"
                    style="border:none;cursor:pointer;font-family:inherit"><?= e($p['status']) ?></button>
          </form>
        </td>
        <td>
          <div class="flex" style="gap:5px">
            <a class="btn btn-outline btn-sm" href="<?= url('publication_form.php?id=' . (int)$p['id']) ?>">Edit</a>
            <form method="post"><?= csrfField() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="pub_id" value="<?= (int)$p['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit"
                      data-confirm="Delete this publication permanently?"><?= icon('trash', 13) ?></button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
