<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me  = currentUser();
$uid = (int)$me['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && input('action') === 'delete') {
    verifyCsrf();
    $delId = (int)input('pub_id');
    $row   = fetchOne('SELECT * FROM publications WHERE id = ? AND user_id = ?', [$delId, $uid]);
    if ($row) {
        deletePubFiles($delId);
        q('DELETE FROM publications WHERE id = ?', [$delId]);
        setFlash('success', 'Publication deleted.');
    }
    redirect('publications.php');
}

$pubs = fetchAll('SELECT * FROM publications WHERE user_id = ? ORDER BY created_at DESC', [$uid]);

$totalViews = array_sum(array_column($pubs, 'views'));
$totalDl    = array_sum(array_column($pubs, 'downloads'));
$totalCit   = array_sum(array_column($pubs, 'citations'));

$pageTitle = 'My Publications';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>My Publications</h1>
    <p class="sub">Papers, pre-prints, datasets and theses you have shared with the research community.</p>
  </div>
  <a class="btn btn-primary" href="<?= url('publication_form.php') ?>"><?= icon('upload', 16) ?> Upload Paper</a>
</div>

<div class="grid grid-4 mb-3">
  <div class="stat-tile"><span class="stat-ico"><?= icon('file', 19) ?></span>
    <span><span class="n"><?= count($pubs) ?></span><span class="l">Publications</span></span></div>
  <div class="stat-tile"><span class="stat-ico"><?= icon('eye', 19) ?></span>
    <span><span class="n"><?= fmtCount((int)$totalViews) ?></span><span class="l">Total Views</span></span></div>
  <div class="stat-tile"><span class="stat-ico"><?= icon('download', 19) ?></span>
    <span><span class="n"><?= fmtCount((int)$totalDl) ?></span><span class="l">Downloads</span></span></div>
  <div class="stat-tile"><span class="stat-ico"><?= icon('award', 19) ?></span>
    <span><span class="n"><?= (int)$totalCit ?></span><span class="l">Citations</span></span></div>
</div>

<?php if (!$pubs): ?>
  <div class="empty">
    <h3>You have not published anything yet</h3>
    <p class="mb-2">Share a paper, pre-print or dataset so others can build on your work.</p>
    <a class="btn btn-primary btn-sm" href="<?= url('publication_form.php') ?>">Upload your first paper</a>
  </div>
<?php else: foreach ($pubs as $pub): ?>
  <div class="card mb-2">
    <div class="flex-between flex-wrap" style="gap:14px">
      <div class="flex-1">
        <div class="flex mb-1" style="gap:8px">
          <span class="chip chip-orange-soft"><?= e($pub['pub_type']) ?></span>
          <?= versionBadge((int)$pub['current_version']) ?>
          <?php if ($pub['status'] === 'draft'): ?><span class="badge badge-grey">Draft</span><?php endif; ?>
          <span class="muted-sm"><?= fmtDate($pub['created_at']) ?></span>
        </div>
        <h3 style="font-family:var(--serif);font-size:18px;margin-bottom:5px">
          <a href="<?= url('publication.php?id=' . (int)$pub['id']) ?>" style="color:inherit"><?= e($pub['title']) ?></a>
        </h3>
        <p class="muted-sm mb-1"><?= e($pub['journal']) ?> &middot; <?= (int)$pub['year'] ?></p>
        <div class="flex muted-sm" style="gap:18px">
          <span><?= icon('eye', 13) ?> <?= fmtCount((int)$pub['views']) ?></span>
          <span><?= icon('download', 13) ?> <?= fmtCount((int)$pub['downloads']) ?></span>
          <span><?= icon('award', 13) ?> <?= (int)$pub['citations'] ?> citations</span>
        </div>
      </div>

      <div class="flex-col" style="gap:7px;min-width:120px">
        <a class="btn btn-outline btn-sm" href="<?= url('publication.php?id=' . (int)$pub['id']) ?>">View</a>
        <a class="btn btn-outline btn-sm" href="<?= url('publication_form.php?id=' . (int)$pub['id']) ?>">Edit</a>
        <form method="post"><?= csrfField() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="pub_id" value="<?= (int)$pub['id'] ?>">
          <button class="btn btn-danger btn-sm btn-block" type="submit"
                  data-confirm="Delete this publication permanently?">Delete</button>
        </form>
      </div>
    </div>
  </div>
<?php endforeach; endif; ?>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
