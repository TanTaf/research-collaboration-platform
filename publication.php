<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me  = currentUser();
$uid = (int)$me['id'];
$id  = (int)input('id', 0);

$pub = fetchOne(
    'SELECT p.*, u.first_name, u.last_name, u.avatar, u.department, u.designation, u.id AS author_id
     FROM publications p JOIN users u ON u.id = p.user_id WHERE p.id = ?', [$id]);

if (!$pub) { setFlash('error', 'Publication not found.'); redirect('repository.php'); }
if ($pub['status'] === 'draft' && (int)$pub['user_id'] !== $uid && !isAdmin()) {
    setFlash('error', 'That publication is still a private draft.');
    redirect('repository.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string)input('action');

    if ($action === 'comment') {
        $body = trim((string)input('body'));
        if ($body !== '') {
            q('INSERT INTO publication_comments (publication_id, user_id, parent_id, body) VALUES (?,?,?,?)',
              [$id, $uid, (int)input('parent_id') ?: null, $body]);
            if ((int)$pub['user_id'] !== $uid) {
                notify((int)$pub['user_id'], fullName($me) . ' commented on your publication.',
                       'publication.php?id=' . $id);
            }
        }
    } elseif ($action === 'like') {
        if (fetchValue('SELECT COUNT(*) FROM publication_likes WHERE publication_id = ? AND user_id = ?', [$id, $uid])) {
            q('DELETE FROM publication_likes WHERE publication_id = ? AND user_id = ?', [$id, $uid]);
        } else {
            q('INSERT IGNORE INTO publication_likes (publication_id, user_id) VALUES (?,?)', [$id, $uid]);
        }
    } elseif ($action === 'save') {
        if (fetchValue('SELECT COUNT(*) FROM publication_saves WHERE publication_id = ? AND user_id = ?', [$id, $uid])) {
            q('DELETE FROM publication_saves WHERE publication_id = ? AND user_id = ?', [$id, $uid]);
        } else {
            q('INSERT IGNORE INTO publication_saves (publication_id, user_id) VALUES (?,?)', [$id, $uid]);
        }
    }
    redirect('publication.php?id=' . $id);
}

if (empty($_SESSION['viewed_pub'][$id])) {
    q('UPDATE publications SET views = views + 1 WHERE id = ?', [$id]);
    $_SESSION['viewed_pub'][$id] = true;
    $pub['views']++;
}

$tags     = array_column(fetchAll(
    'SELECT i.name FROM interests i JOIN publication_tags pt ON pt.interest_id = i.id
     WHERE pt.publication_id = ?', [$id]), 'name');

$likes    = (int)fetchValue('SELECT COUNT(*) FROM publication_likes WHERE publication_id = ?', [$id]);
$saves    = (int)fetchValue('SELECT COUNT(*) FROM publication_saves WHERE publication_id = ?', [$id]);
$iLiked   = (bool)fetchValue('SELECT COUNT(*) FROM publication_likes WHERE publication_id = ? AND user_id = ?', [$id, $uid]);
$iSaved   = (bool)fetchValue('SELECT COUNT(*) FROM publication_saves WHERE publication_id = ? AND user_id = ?', [$id, $uid]);

$comments = fetchAll(
    'SELECT c.*, u.first_name, u.last_name, u.avatar, u.id AS uid FROM publication_comments c
     JOIN users u ON u.id = c.user_id WHERE c.publication_id = ? ORDER BY c.created_at', [$id]);

$related = fetchAll(
    'SELECT DISTINCT p.id, p.title, p.authors, p.year FROM publications p
     JOIN publication_tags pt ON pt.publication_id = p.id
     WHERE p.id <> ? AND p.status = "published"
       AND pt.interest_id IN (SELECT interest_id FROM publication_tags WHERE publication_id = ?)
     LIMIT 3', [$id, $id]);

$versions = pubVersions($id);
$curVer   = (int)($pub['current_version'] ?? 1);

$citation = sprintf('%s (%d). %s (version %d). %s.',
    $pub['authors'] ?: ($pub['first_name'] . ' ' . $pub['last_name']),
    (int)$pub['year'], $pub['title'], $curVer,
    $pub['journal'] ?: 'Research Collaboration Hub UIU');

$pageTitle = $pub['title'];
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="layout-main-side">
  <div>
    <div class="card mb-3">
      <div class="flex mb-2" style="gap:9px">
        <span class="badge badge-completed"><?= e($pub['status'] === 'published' ? 'Published' : 'Draft') ?></span>
        <span class="muted-sm"><?= fmtDate($pub['created_at'], 'j F Y') ?></span>
      </div>

      <h1 style="font-size:30px;line-height:1.22">
        <?= e($pub['title']) ?> <?= versionBadge($curVer) ?>
      </h1>

      <div class="flex flex-wrap mb-3" style="gap:7px">
        <span class="chip chip-orange"><?= e($pub['category'] ?: $pub['pub_type']) ?></span>
        <?php foreach ($tags as $t): ?><span class="chip"><?= e($t) ?></span><?php endforeach; ?>
      </div>

      <h2 class="text-orange" style="font-size:20px">Abstract</h2>
      <p class="muted" style="line-height:1.85"><?= nl2br(e($pub['abstract'])) ?></p>
    </div>

    <div class="grid grid-3 mb-3">
      <form method="post"><?= csrfField() ?>
        <input type="hidden" name="action" value="like">
        <button class="card card-tight text-center btn-block" type="submit"
                style="border:1px solid var(--line);background:#fff;cursor:pointer;font-family:inherit">
          <span style="color:<?= $iLiked ? 'var(--orange)' : 'var(--muted)' ?>"><?= icon('heart', 18) ?></span>
          <div style="font-weight:700;font-size:15px"><?= fmtCount($likes) ?></div>
          <div class="muted-sm">Likes</div>
        </button>
      </form>

      <form method="post"><?= csrfField() ?>
        <input type="hidden" name="action" value="save">
        <button class="card card-tight text-center btn-block" type="submit"
                style="border:1px solid var(--line);background:#fff;cursor:pointer;font-family:inherit">
          <span style="color:<?= $iSaved ? 'var(--orange)' : 'var(--muted)' ?>"><?= icon('bookmark', 18) ?></span>
          <div style="font-weight:700;font-size:15px"><?= fmtCount($saves) ?></div>
          <div class="muted-sm">Saves</div>
        </button>
      </form>

      <div class="card card-tight text-center">
        <span class="muted"><?= icon('award', 18) ?></span>
        <div style="font-weight:700;font-size:15px"><?= (int)$pub['citations'] ?></div>
        <div class="muted-sm">Citations</div>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-head">
        <h2>Author List</h2>
        <a class="btn btn-primary btn-sm" href="<?= url('messages.php?to=' . (int)$pub['author_id']) ?>">
          <?= icon('users', 14) ?> Request Collaboration</a>
      </div>
      <div class="flex flex-wrap" style="gap:12px">
        <a class="flex card card-tight" style="text-decoration:none;color:inherit;min-width:230px"
           href="<?= url('profile.php?id=' . (int)$pub['author_id']) ?>">
          <?= avatar(['id' => $pub['author_id'], 'first_name' => $pub['first_name'],
                      'last_name' => $pub['last_name'], 'avatar' => $pub['avatar']], 42) ?>
          <span>
            <strong style="font-size:13.5px;display:block"><?= e($pub['first_name'] . ' ' . $pub['last_name']) ?></strong>
            <span class="muted-sm"><?= e($pub['designation'] ?: 'Lead Researcher') ?> &middot; UIU</span>
          </span>
        </a>
        <?php
          $others = array_filter(array_map('trim', explode(',', (string)$pub['authors'])),
                     fn($a) => $a !== '' && $a !== trim($pub['first_name'] . ' ' . $pub['last_name']));
          foreach ($others as $a): ?>
          <div class="flex card card-tight" style="min-width:230px">
            <span class="avatar avatar-initials" style="width:42px;height:42px;background:#3E5C76;font-size:15px">
              <?= e(strtoupper(substr($a, 0, 2))) ?></span>
            <span><strong style="font-size:13.5px;display:block"><?= e($a) ?></strong>
              <span class="muted-sm">Co-author</span></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <h2>Discussions (<?= count($comments) ?>)</h2>

      <?php foreach ($comments as $c):
        $isAuthor = ((int)$c['uid'] === (int)$pub['user_id']); ?>
        <div class="comment <?= $c['parent_id'] ? 'comment-reply' : '' ?>">
          <?= avatar(['id' => $c['uid'], 'first_name' => $c['first_name'],
                      'last_name' => $c['last_name'], 'avatar' => $c['avatar']], 34) ?>
          <div class="comment-body <?= $isAuthor ? 'author' : '' ?>">
            <span class="nm"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></span>
            <?php if ($isAuthor): ?><span class="tm" style="color:var(--orange);font-weight:700"> Author</span><?php endif; ?>
            <span class="tm"> &middot; <?= timeAgo($c['created_at']) ?></span>
            <p class="tx"><?= nl2br(e($c['body'])) ?></p>
          </div>
        </div>
      <?php endforeach; ?>

      <form method="post" class="flex mt-3" style="align-items:flex-start">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="comment">
        <?= avatar($me, 34) ?>
        <div class="flex-1">
          <textarea name="body" style="min-height:84px"
                    placeholder="Add to the scholarly discussion..." required></textarea>
          <div class="text-right mt-1">
            <button class="btn btn-primary btn-sm" type="submit">Post Comment</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="flex-col" style="gap:18px">
    <div class="card">
      <h3><?= icon('file', 16) ?> Attachments</h3>
      <?php if ($pub['file_name']): ?>
        <div class="file-row">
          <span class="ico"><?= e(strtoupper(pathinfo($pub['file_name'], PATHINFO_EXTENSION))) ?></span>
          <span class="flex-1">
            <span class="nm"><?= e($pub['file_name']) ?></span>
            <span class="mt"><?= fmtBytes((int)$pub['file_size']) ?></span>
          </span>
          <a class="icon-btn" href="<?= url('download.php?type=publication&id=' . $id) ?>"><?= icon('download', 16) ?></a>
        </div>
      <?php else: ?>
        <p class="muted-sm">No file attached to this record.</p>
      <?php endif; ?>

      <div class="flex-between muted-sm mt-2">
        <span><?= icon('eye', 13) ?> <?= fmtCount((int)$pub['views']) ?> views</span>
        <span><?= icon('download', 13) ?> <?= fmtCount((int)$pub['downloads']) ?> downloads</span>
      </div>
    </div>

    <div class="card">
      <div class="card-head">
        <h3 style="margin:0"><?= icon('layers', 16) ?> Version History</h3>
        <span class="muted-sm"><?= count($versions) ?> version<?= count($versions) === 1 ? '' : 's' ?></span>
      </div>

      <?php if (!$versions): ?>
        <p class="muted-sm mb-0">No versions recorded for this paper yet.</p>
      <?php else: ?>
        <ul class="version-list">
          <?php foreach ($versions as $v): $isCur = ((int)$v['version_no'] === $curVer); ?>
            <li class="<?= $isCur ? 'current' : '' ?>">
              <div class="flex-between" style="gap:10px">
                <span>
                  <strong>Version <?= (int)$v['version_no'] ?></strong>
                  <?php if ($isCur): ?><span class="chip chip-orange-soft">Current</span><?php endif; ?>
                </span>
                <?php if ($v['file_path']): ?>
                  <a class="icon-btn" title="Download version <?= (int)$v['version_no'] ?>"
                     href="<?= url('download.php?type=version&id=' . (int)$v['id']) ?>"><?= icon('download', 15) ?></a>
                <?php endif; ?>
              </div>
              <div class="muted-sm"><?= fmtDate($v['created_at'], 'j M Y') ?>
                &middot; <?= e($v['first_name'] . ' ' . $v['last_name']) ?></div>
              <?php if ($v['change_note']): ?>
                <p class="note"><?= e($v['change_note']) ?></p>
              <?php endif; ?>
              <?php if (!$v['file_path']): ?>
                <p class="muted-sm mb-0"><em>No file kept for this version.</em></p>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <div class="citation-box">
      <h3 style="font-family:var(--serif)">Quick Citation</h3>
      <p class="q">&ldquo;<?= e($citation) ?>&rdquo;</p>
      <button class="btn btn-outline btn-block btn-sm mt-2" type="button" data-copy="<?= e($citation) ?>">
        Copy Citation</button>
    </div>

    <?php if ((int)$pub['user_id'] === $uid || isAdmin()): ?>
      <div class="card">
        <h3>Manage</h3>
        <a class="btn btn-outline btn-block btn-sm" href="<?= url('publication_form.php?id=' . $id) ?>">Edit Publication</a>
      </div>
    <?php endif; ?>

    <?php if ($related): ?>
      <div class="card">
        <h3>Related Work</h3>
        <?php foreach ($related as $r): ?>
          <a class="list-row" style="text-decoration:none;color:inherit;border-left:3px solid var(--orange);padding-left:11px"
             href="<?= url('publication.php?id=' . (int)$r['id']) ?>">
            <span>
              <strong style="font-size:13px;display:block"><?= e($r['title']) ?></strong>
              <span class="muted-sm"><?= e(excerpt($r['authors'], 34)) ?> &middot; <?= (int)$r['year'] ?></span>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
