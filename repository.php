<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me      = currentUser();
$keyword = (string)input('q');
$cat     = (string)input('category');
$year    = (int)input('year', 0);
$sort    = (string)input('sort', 'newest');

$perPage = 6;
$page    = max(1, (int)input('page', 1));
$offset  = ($page - 1) * $perPage;

$where  = ['p.status = "published"'];
$params = [];

if ($keyword !== '') {
    $where[] = '(p.title LIKE ? OR p.abstract LIKE ? OR p.authors LIKE ?)';
    $like = '%' . $keyword . '%';
    array_push($params, $like, $like, $like);
}
if ($cat  !== '') { $where[] = 'p.category = ?'; $params[] = $cat; }
if ($year)        { $where[] = 'p.year = ?';     $params[] = $year; }

$whereSql = 'WHERE ' . implode(' AND ', $where);

$orderSql = match ($sort) {
    'views'     => 'p.views DESC',
    'downloads' => 'p.downloads DESC',
    'citations' => 'p.citations DESC',
    'title'     => 'p.title ASC',
    default     => 'p.created_at DESC',
};

$total = (int)fetchValue("SELECT COUNT(*) FROM publications p $whereSql", $params);
$pages = max(1, (int)ceil($total / $perPage));

$papers = fetchAll(
    "SELECT p.*, u.first_name, u.last_name FROM publications p
     JOIN users u ON u.id = p.user_id
     $whereSql ORDER BY $orderSql LIMIT $perPage OFFSET $offset", $params);

$categories = fetchAll(
    'SELECT category, COUNT(*) c FROM publications
     WHERE status = "published" AND category IS NOT NULL AND category <> ""
     GROUP BY category ORDER BY c DESC');

$years = array_column(fetchAll(
    'SELECT DISTINCT year FROM publications WHERE year IS NOT NULL ORDER BY year DESC'), 'year');

$trending = fetchAll(
    'SELECT * FROM publications WHERE status = "published" ORDER BY views DESC LIMIT 3');

$repoStats = [
    'papers'      => (int)fetchValue('SELECT COUNT(*) FROM publications WHERE status = "published"'),
    'researchers' => (int)fetchValue('SELECT COUNT(DISTINCT user_id) FROM publications'),
    'categories'  => count($categories),
    'downloads'   => (int)fetchValue('SELECT COALESCE(SUM(downloads),0) FROM publications'),
];

function pageLink(int $n): string
{
    $qs = $_GET;
    $qs['page'] = $n;
    return url('repository.php?' . http_build_query($qs));
}

$pageTitle = 'Open Research Repository';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="layout-main-side">
  <div>
    <div class="page-head">
      <div>
        <h1>Open Research Repository</h1>
        <p class="sub">Explore, discover and access research papers from every department.</p>
      </div>
      <a class="btn btn-primary" href="<?= url('publication_form.php') ?>"><?= icon('upload', 16) ?> Upload Paper</a>
    </div>

    <form class="card card-tight mb-3" method="get">
      <div class="form-group" style="margin-bottom:12px">
        <input type="search" name="q" value="<?= e($keyword) ?>"
               placeholder="Search paper by title, author or abstract...">
      </div>
      <div class="flex flex-wrap" style="gap:10px">
        <select name="year" style="width:auto;min-width:130px">
          <option value="">All years</option>
          <?php foreach ($years as $y): ?>
            <option value="<?= (int)$y ?>" <?= $year === (int)$y ? 'selected' : '' ?>><?= (int)$y ?></option>
          <?php endforeach; ?>
        </select>
        <select name="sort" style="width:auto;min-width:160px">
          <option value="newest"    <?= $sort === 'newest' ? 'selected' : '' ?>>Sort by: Newest</option>
          <option value="views"     <?= $sort === 'views' ? 'selected' : '' ?>>Sort by: Most viewed</option>
          <option value="downloads" <?= $sort === 'downloads' ? 'selected' : '' ?>>Sort by: Most downloaded</option>
          <option value="citations" <?= $sort === 'citations' ? 'selected' : '' ?>>Sort by: Most cited</option>
          <option value="title"     <?= $sort === 'title' ? 'selected' : '' ?>>Sort by: Title A-Z</option>
        </select>
        <?php if ($cat !== ''): ?><input type="hidden" name="category" value="<?= e($cat) ?>"><?php endif; ?>
        <button class="btn btn-primary" type="submit">Apply</button>
        <?php if ($keyword || $cat || $year): ?>
          <a class="btn btn-outline" href="<?= url('repository.php') ?>">Clear</a>
        <?php endif; ?>
      </div>
    </form>

    <p class="muted-sm">Showing <?= $total ? $offset + 1 : 0 ?> - <?= min($offset + $perPage, $total) ?>
      of <?= $total ?> papers<?= $cat ? ' in ' . e($cat) : '' ?></p>

    <?php if (!$papers): ?>
      <div class="empty"><h3>No papers found</h3><p>Try a different search or clear the filters.</p></div>
    <?php else: foreach ($papers as $p): ?>
      <div class="card mb-2">
        <div class="flex" style="align-items:flex-start;gap:14px">
          <span class="stat-ico" style="width:42px;height:42px"><?= icon('file', 19) ?></span>
          <div class="flex-1">
            <h3 style="font-family:var(--serif);font-size:18px;margin-bottom:5px">
              <a href="<?= url('publication.php?id=' . (int)$p['id']) ?>" style="color:inherit"><?= e($p['title']) ?></a>
            </h3>
            <p class="muted-sm mb-1"><?= e($p['authors'] ?: $p['first_name'] . ' ' . $p['last_name']) ?></p>
            <p class="mb-1" style="font-size:12.5px;color:var(--orange);font-style:italic">
              <?= e($p['journal']) ?> &nbsp;||&nbsp; <?= (int)$p['year'] ?></p>
            <div class="flex muted-sm" style="gap:16px">
              <span><?= icon('eye', 12) ?> <?= fmtCount((int)$p['views']) ?></span>
              <span><?= icon('download', 12) ?> <?= fmtCount((int)$p['downloads']) ?></span>
              <span class="chip" style="font-size:11px;padding:2px 9px"><?= e($p['pub_type']) ?></span>
              <?php if ((int)$p['current_version'] > 1): ?><?= versionBadge((int)$p['current_version']) ?><?php endif; ?>
            </div>
          </div>
          <div class="flex-col" style="gap:6px">
            <a class="icon-btn" href="<?= url('publication.php?id=' . (int)$p['id']) ?>" title="Read"><?= icon('bookmark', 16) ?></a>
            <?php if ($p['file_name']): ?>
              <a class="icon-btn" href="<?= url('download.php?type=publication&id=' . (int)$p['id']) ?>"
                 title="Download"><?= icon('download', 16) ?></a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; endif; ?>

    <?php if ($pages > 1): ?>
      <div class="pagination">
        <?php if ($page > 1): ?><a href="<?= pageLink($page - 1) ?>">&lsaquo;</a><?php endif; ?>
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <?php if ($i === $page): ?>
            <span class="current"><?= $i ?></span>
          <?php else: ?>
            <a href="<?= pageLink($i) ?>"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $pages): ?><a href="<?= pageLink($page + 1) ?>">&rsaquo;</a><?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="flex-col" style="gap:18px">
    <div class="card">
      <h5 class="eyebrow eyebrow-muted">Repository Overview</h5>
      <div class="grid grid-2" style="gap:10px">
        <div class="stat-tile" style="padding:12px">
          <span class="stat-ico" style="width:32px;height:32px"><?= icon('file', 15) ?></span>
          <span><span class="n" style="font-size:17px"><?= $repoStats['papers'] ?></span>
            <span class="l">Total Papers</span></span>
        </div>
        <div class="stat-tile" style="padding:12px">
          <span class="stat-ico" style="width:32px;height:32px"><?= icon('users', 15) ?></span>
          <span><span class="n" style="font-size:17px"><?= $repoStats['researchers'] ?></span>
            <span class="l">Researchers</span></span>
        </div>
        <div class="stat-tile" style="padding:12px">
          <span class="stat-ico" style="width:32px;height:32px"><?= icon('globe', 15) ?></span>
          <span><span class="n" style="font-size:17px"><?= $repoStats['categories'] ?></span>
            <span class="l">Categories</span></span>
        </div>
        <div class="stat-tile" style="padding:12px">
          <span class="stat-ico" style="width:32px;height:32px"><?= icon('download', 15) ?></span>
          <span><span class="n" style="font-size:17px"><?= fmtCount($repoStats['downloads']) ?></span>
            <span class="l">Downloads</span></span>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-head"><h5 class="eyebrow eyebrow-muted" style="margin:0">Categories</h5>
        <a class="card-link" href="<?= url('repository.php') ?>">View all</a></div>
      <?php foreach ($categories as $c): ?>
        <a class="flex-between list-row" style="text-decoration:none;color:inherit"
           href="<?= url('repository.php?category=' . urlencode($c['category'])) ?>">
          <span style="font-size:13px;<?= $cat === $c['category'] ? 'color:var(--orange);font-weight:600' : '' ?>">
            <?= e($c['category']) ?></span>
          <span class="muted-sm"><?= (int)$c['c'] ?></span>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="card" style="background:#FEF8F2;border-color:#F6E2CC">
      <h5 class="eyebrow">Trending</h5>
      <?php foreach ($trending as $t): ?>
        <a class="list-row" style="text-decoration:none;color:inherit"
           href="<?= url('publication.php?id=' . (int)$t['id']) ?>">
          <span class="stat-ico" style="width:34px;height:34px;background:#fff"><?= icon('trending', 15) ?></span>
          <span class="flex-1">
            <strong style="font-size:12.5px;display:block"><?= e(excerpt($t['title'], 46)) ?></strong>
            <span class="muted-sm"><?= fmtCount((int)$t['views']) ?> views &middot; <?= (int)$t['year'] ?></span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
