<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me  = currentUser();
$uid = (int)$me['id'];

$keyword    = (string)input('q');
$filterInts = array_map('intval', (array)($_GET['interests'] ?? []));
$filterDept = (string)input('department');
$filterRole = (string)input('role');

$sql    = 'SELECT u.* FROM users u WHERE u.id <> ? AND u.status = "active" AND u.role <> "admin"';
$params = [$uid];

if ($keyword !== '') {
    $sql .= ' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.bio LIKE ? OR u.department LIKE ?)';
    $like = '%' . $keyword . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($filterDept !== '') { $sql .= ' AND u.department = ?'; $params[] = $filterDept; }
if ($filterRole !== '') { $sql .= ' AND u.role = ?';       $params[] = $filterRole; }

if ($filterInts) {
    $in   = implode(',', array_fill(0, count($filterInts), '?'));
    $sql .= " AND u.id IN (SELECT user_id FROM user_interests WHERE interest_id IN ($in))";
    $params = array_merge($params, $filterInts);
}

$sql .= ' ORDER BY u.first_name LIMIT 60';
$people = fetchAll($sql, $params);

foreach ($people as &$p) { $p['score'] = matchScore($uid, (int)$p['id']); }
unset($p);
usort($people, fn($a, $b) => $b['score'] <=> $a['score']);

$myInts = userInterestIds($uid);
$relevantProjects = [];
if ($myInts) {
    $in = implode(',', array_fill(0, count($myInts), '?'));
    $relevantProjects = fetchAll(
        "SELECT p.*, u.first_name, u.last_name,
                COUNT(DISTINCT pi.interest_id) AS overlap,
                (SELECT COUNT(*) FROM project_members m WHERE m.project_id = p.id) AS members
         FROM projects p
         JOIN users u ON u.id = p.owner_id
         JOIN project_interests pi ON pi.project_id = p.id
         WHERE pi.interest_id IN ($in) AND p.is_open = 1 AND p.owner_id <> ?
         GROUP BY p.id ORDER BY overlap DESC, p.created_at DESC LIMIT 4",
        array_merge($myInts, [$uid]));
}

$myScore     = profileCompletion($me);
$departments = array_column(fetchAll('SELECT DISTINCT department FROM users WHERE department IS NOT NULL ORDER BY department'), 'department');

$pageTitle = 'Smart Matchmaking';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <div class="eyebrow eyebrow-muted">Smart Matchmaking</div>
    <h1>Smart Matchmaking</h1>
    <p class="sub">Find research partners based on shared interests, skills and project goals. The engine scores
      every researcher against your profile and ranks the strongest fits first.</p>
  </div>
  <div class="card card-tight" style="min-width:220px">
    <p class="muted-sm mb-1">Your profile strength</p>
    <div class="flex">
      <span class="match-pct"><?= $myScore ?>%</span>
      <span class="muted-sm">Keep your profile updated<br>to get better matches.</span>
    </div>
  </div>
</div>

<div class="layout-main-side">
  <div>

    <form class="filterbar" method="get">
      <input class="grow" type="search" name="q" value="<?= e($keyword) ?>"
             placeholder="Filter by name, expertise or department...">
      <?php foreach ($filterInts as $fi): ?>
        <input type="hidden" name="interests[]" value="<?= (int)$fi ?>">
      <?php endforeach; ?>
      <button class="btn btn-primary" type="submit"><?= icon('sparkle', 15) ?> Run Matchmaking</button>
    </form>

    <h2 class="section-title">Top Recommended Collaborators</h2>

    <?php if (!$people): ?>
      <div class="empty"><h3>No researchers matched</h3><p>Try widening your filters.</p></div>
    <?php else: foreach ($people as $p): ?>
      <div class="card card-tight mb-2">
        <div class="flex flex-wrap" style="gap:14px">
          <div class="text-center" style="min-width:56px">
            <div class="match-pct"><?= (int)$p['score'] ?>%</div>
            <div class="match-label">Match</div>
          </div>
          <?= avatar($p, 48) ?>
          <div class="flex-1">
            <div class="flex" style="gap:8px">
              <strong style="font-size:14.5px"><?= e(fullName($p)) ?></strong>
              <?= roleBadge($p['role']) ?>
            </div>
            <p class="muted-sm mb-1"><?= e($p['department']) ?><?= $p['trimester'] ? ' | ' . e($p['trimester']) : '' ?></p>
            <div class="flex flex-wrap" style="gap:6px">
              <?php $ints = array_slice(userInterestNames((int)$p['id']), 0, 3);
                    $more = count(userInterestNames((int)$p['id'])) - count($ints);
                    foreach ($ints as $i): ?>
                <span class="chip chip-outline" style="font-size:11px;padding:3px 10px"><?= e($i) ?></span>
              <?php endforeach; if ($more > 0): ?>
                <span class="chip" style="font-size:11px;padding:3px 10px">+<?= $more ?></span>
              <?php endif; ?>
            </div>
          </div>
          <div class="flex-col" style="gap:7px;min-width:118px">
            <a class="btn btn-primary btn-sm" href="<?= url('profile.php?id=' . (int)$p['id']) ?>">View Profile</a>
            <a class="btn btn-orange-outline btn-sm" href="<?= url('messages.php?to=' . (int)$p['id']) ?>">Invite +</a>
          </div>
        </div>
      </div>
    <?php endforeach; endif; ?>

    <?php if ($relevantProjects): ?>
      <h2 class="section-title mt-3">Relevant Projects for You</h2>
      <?php foreach ($relevantProjects as $rp): ?>
        <a class="list-card flex" href="<?= url('opportunity.php?id=' . (int)$rp['id']) ?>">
          <span class="stat-ico"><?= icon('globe', 18) ?></span>
          <span class="flex-1">
            <strong style="font-size:14px;display:block"><?= e($rp['title']) ?></strong>
            <span class="muted-sm">Lead: <?= e($rp['first_name'] . ' ' . $rp['last_name']) ?> &middot;
              <?= (int)$rp['members'] ?> members &middot; <?= (int)$rp['overlap'] ?> shared interests</span>
          </span>
          <?= icon('chevron-right', 18) ?>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <form class="card" method="get">
    <div class="card-head">
      <h3 style="margin:0">Filter Matches</h3>
      <a class="card-link" href="<?= url('matchmaking.php') ?>">Clear all</a>
    </div>

    <input type="hidden" name="q" value="<?= e($keyword) ?>">

    <h5 class="eyebrow eyebrow-muted">Research Interests</h5>
    <div style="max-height:220px;overflow-y:auto;margin-bottom:14px">
      <?php foreach (allInterests() as $i): ?>
        <label class="check">
          <input type="checkbox" name="interests[]" value="<?= (int)$i['id'] ?>"
                 <?= in_array((int)$i['id'], $filterInts, true) ? 'checked' : '' ?>>
          <span><?= e($i['name']) ?></span>
        </label>
      <?php endforeach; ?>
    </div>

    <div class="form-group">
      <label for="department">Department</label>
      <select id="department" name="department">
        <option value="">Any department</option>
        <?php foreach ($departments as $d): ?>
          <option value="<?= e($d) ?>" <?= $filterDept === $d ? 'selected' : '' ?>><?= e($d) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="role">Academic level</label>
      <select id="role" name="role">
        <option value="">Any level</option>
        <option value="student" <?= $filterRole === 'student' ? 'selected' : '' ?>>Students</option>
        <option value="faculty" <?= $filterRole === 'faculty' ? 'selected' : '' ?>>Faculty</option>
      </select>
    </div>

    <button class="btn btn-primary btn-block" type="submit">Apply Filter</button>
  </form>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
