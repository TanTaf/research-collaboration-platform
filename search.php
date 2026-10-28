<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me      = currentUser();
$uid     = (int)$me['id'];
$keyword = trim((string)input('q'));
$like    = '%' . $keyword . '%';

$people = $projects = $papers = $communities = [];

if ($keyword !== '') {
    $people = fetchAll(
        'SELECT * FROM users
         WHERE status = "active" AND id <> ?
           AND (first_name LIKE ? OR last_name LIKE ? OR bio LIKE ? OR department LIKE ? OR designation LIKE ?)
         LIMIT 8', [$uid, $like, $like, $like, $like, $like]);

    $projects = fetchAll(
        'SELECT p.*, u.first_name, u.last_name FROM projects p JOIN users u ON u.id = p.owner_id
         WHERE p.title LIKE ? OR p.summary LIKE ? OR p.description LIKE ? LIMIT 8',
        [$like, $like, $like]);

    $papers = fetchAll(
        'SELECT * FROM publications
         WHERE status = "published" AND (title LIKE ? OR abstract LIKE ? OR authors LIKE ? OR category LIKE ?)
         LIMIT 8', [$like, $like, $like, $like]);

    $communities = fetchAll(
        'SELECT * FROM communities WHERE name LIKE ? OR description LIKE ? OR field LIKE ? LIMIT 6',
        [$like, $like, $like]);
}

$total = count($people) + count($projects) + count($papers) + count($communities);

$pageTitle = $keyword !== '' ? 'Search: ' . $keyword : 'Search';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>Search</h1>
    <?php if ($keyword !== ''): ?>
      <p class="sub"><?= $total ?> result<?= $total === 1 ? '' : 's' ?> for
        &ldquo;<strong><?= e($keyword) ?></strong>&rdquo;</p>
    <?php else: ?>
      <p class="sub">Search across researchers, projects, publications and communities.</p>
    <?php endif; ?>
  </div>
</div>

<form class="card card-tight mb-3" method="get">
  <div class="flex">
    <input type="search" name="q" value="<?= e($keyword) ?>" placeholder="Search the hub..." autofocus>
    <button class="btn btn-primary" type="submit"><?= icon('search', 15) ?> Search</button>
  </div>
</form>

<?php if ($keyword === ''): ?>
  <div class="empty"><h3>Type something to begin</h3>
    <p>Try a topic like &ldquo;machine learning&rdquo;, a person's name, or a paper title.</p></div>

<?php elseif ($total === 0): ?>
  <div class="empty"><h3>Nothing found</h3><p>No matches for that term. Try a broader keyword.</p></div>

<?php else: ?>

  <?php if ($people): ?>
    <h2 class="section-title">Researchers (<?= count($people) ?>)</h2>
    <div class="grid grid-3 mb-3">
      <?php foreach ($people as $p): ?>
        <a class="card card-hover flex" style="text-decoration:none;color:inherit"
           href="<?= url('profile.php?id=' . (int)$p['id']) ?>">
          <?= avatar($p, 44) ?>
          <span>
            <strong style="font-size:14px;display:block"><?= e(fullName($p)) ?></strong>
            <span class="muted-sm"><?= e($p['designation'] ?: $p['department']) ?></span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($projects): ?>
    <h2 class="section-title">Projects (<?= count($projects) ?>)</h2>
    <?php foreach ($projects as $p): ?>
      <a class="list-card" href="<?= url('opportunity.php?id=' . (int)$p['id']) ?>">
        <div class="flex-between mb-1">
          <strong style="font-size:15px"><?= e($p['title']) ?></strong>
          <span class="badge badge-<?= e($p['status']) ?>"><?= e(statusLabel($p['status'])) ?></span>
        </div>
        <p class="muted-sm mb-0"><?= e(excerpt($p['summary'], 140)) ?></p>
        <p class="muted-sm mb-0">Owner: <?= e($p['first_name'] . ' ' . $p['last_name']) ?></p>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($papers): ?>
    <h2 class="section-title mt-3">Publications (<?= count($papers) ?>)</h2>
    <?php foreach ($papers as $p): ?>
      <a class="list-card" href="<?= url('publication.php?id=' . (int)$p['id']) ?>">
        <div class="flex-between mb-1">
          <strong style="font-size:15px"><?= e($p['title']) ?></strong>
          <span class="chip chip-orange-soft"><?= e($p['pub_type']) ?></span>
        </div>
        <p class="muted-sm mb-0"><?= e($p['authors']) ?> &middot; <?= (int)$p['year'] ?></p>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($communities): ?>
    <h2 class="section-title mt-3">Communities (<?= count($communities) ?>)</h2>
    <div class="grid grid-3">
      <?php foreach ($communities as $c): ?>
        <a class="card card-hover" style="text-decoration:none;color:inherit"
           href="<?= url('community.php?id=' . (int)$c['id']) ?>">
          <strong style="font-size:15px;display:block"><?= e($c['name']) ?></strong>
          <span class="muted-sm"><?= e(excerpt($c['description'], 90)) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php endif; ?>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
