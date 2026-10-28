<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me  = currentUser();
$uid = (int)$me['id'];
$id  = (int)input('id', 0);

$isEdit = false;
$pub = [
    'title' => '', 'abstract' => '', 'authors' => fullName($me), 'journal' => '',
    'year' => date('Y'), 'pub_type' => 'Paper', 'category' => '', 'status' => 'published',
    'file_name' => null, 'file_path' => null, 'file_size' => 0,
];

if ($id) {
    $row = fetchOne('SELECT * FROM publications WHERE id = ?', [$id]);
    if (!$row) { setFlash('error', 'Publication not found.'); redirect('publications.php'); }
    if ((int)$row['user_id'] !== $uid && !isAdmin()) {
        setFlash('error', 'You can only edit your own publications.');
        redirect('publications.php');
    }
    $pub    = $row;
    $isEdit = true;
}

$selTags = $isEdit ? array_map('intval', array_column(
    fetchAll('SELECT interest_id FROM publication_tags WHERE publication_id = ?', [$id]), 'interest_id')) : [];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    foreach (['title','abstract','authors','journal','pub_type','category','status'] as $k) {
        $pub[$k] = (string)input($k);
    }
    $pub['year'] = (int)input('year', date('Y'));
    $selTags     = array_map('intval', (array)($_POST['tags'] ?? []));

    if ($pub['title'] === '')    { $errors[] = 'Please enter the title.'; }
    if ($pub['abstract'] === '') { $errors[] = 'Please write an abstract.'; }
    if (!in_array($pub['pub_type'], ['Paper','Pre-print','Dataset','Thesis'], true)) { $pub['pub_type'] = 'Paper'; }
    if (!in_array($pub['status'], ['published','draft'], true)) { $pub['status'] = 'published'; }

    $up      = handleUpload('doc', 'publications', ['pdf','doc','docx','csv','xlsx','zip','txt'], 20 * 1024 * 1024);
    $newFile = false;
    if ($up) {
        [$pub['file_path'], $pub['file_name'], $pub['file_size']] = $up;
        $newFile = true;
    }

    $changeNote = trim((string)input('change_note'));
    $makeVersion = !$isEdit || $newFile || input('new_version') === '1';

    if (!$errors) {
        if ($isEdit) {
            q('UPDATE publications SET title=?, abstract=?, authors=?, journal=?, year=?, pub_type=?,
                 category=?, status=?, file_name=?, file_path=?, file_size=? WHERE id=?',
              [$pub['title'], $pub['abstract'], $pub['authors'], $pub['journal'], $pub['year'],
               $pub['pub_type'], $pub['category'], $pub['status'], $pub['file_name'],
               $pub['file_path'], $pub['file_size'], $id]);
            $savedId = $id;
        } else {
            q('INSERT INTO publications (user_id, title, abstract, authors, journal, year, pub_type,
                 category, status, file_name, file_path, file_size)
               VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
              [$uid, $pub['title'], $pub['abstract'], $pub['authors'], $pub['journal'], $pub['year'],
               $pub['pub_type'], $pub['category'], $pub['status'], $pub['file_name'],
               $pub['file_path'], $pub['file_size']]);
            $savedId = (int)db()->lastInsertId();
        }

        q('DELETE FROM publication_tags WHERE publication_id = ?', [$savedId]);
        foreach (array_slice($selTags, 0, 6) as $t) {
            q('INSERT IGNORE INTO publication_tags (publication_id, interest_id) VALUES (?,?)', [$savedId, $t]);
        }

        if ($makeVersion) {
            $vNo = addPubVersion($savedId, $pub, $changeNote, $uid);
            setFlash('success', $isEdit
                ? 'Saved as version ' . $vNo . '. The earlier versions are still available.'
                : 'Publication uploaded. This is version 1.');
        } else {
            setFlash('success', 'Publication updated. No new version was created.');
        }

        redirect('publication.php?id=' . $savedId);
    }
}

$pageTitle = $isEdit ? 'Edit publication' : 'Upload a paper';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <h1><?= $isEdit ? 'Edit Publication' : 'Upload a Paper' ?></h1>
    <p class="sub">Share your research with the UIU community through the open repository.</p>
  </div>
  <a class="btn btn-outline" href="<?= url('publications.php') ?>">Cancel</a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-error"><span><?= implode('<br>', array_map('e', $errors)) ?></span></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <?= csrfField() ?>

  <div class="layout-main-side">
    <div class="card">
      <div class="form-group">
        <label for="title">Title <span class="req">*</span></label>
        <input type="text" id="title" name="title" value="<?= e($pub['title']) ?>" required>
      </div>

      <div class="form-group">
        <label for="abstract">Abstract <span class="req">*</span></label>
        <textarea id="abstract" name="abstract" style="min-height:200px" required><?= e($pub['abstract']) ?></textarea>
      </div>

      <div class="form-group">
        <label for="authors">Authors <span class="muted-sm">(comma separated)</span></label>
        <input type="text" id="authors" name="authors" value="<?= e($pub['authors']) ?>">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="journal">Journal / venue</label>
          <input type="text" id="journal" name="journal" value="<?= e($pub['journal']) ?>"
                 placeholder="e.g. International Journal of Research">
        </div>
        <div class="form-group">
          <label for="year">Year</label>
          <input type="number" id="year" name="year" min="1990" max="2100" value="<?= (int)$pub['year'] ?>">
        </div>
      </div>

      <h3 class="mt-3">Topic tags</h3>
      <div class="tag-picker" data-max-select="6">
        <?php foreach (allInterests() as $i): ?>
          <label>
            <input type="checkbox" name="tags[]" value="<?= (int)$i['id'] ?>"
                   <?= in_array((int)$i['id'], $selTags, true) ? 'checked' : '' ?>>
            <span><?= e($i['name']) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="flex-col" style="gap:18px">
      <div class="card">
        <h3>Publication settings</h3>

        <div class="form-group">
          <label for="pub_type">Type</label>
          <select id="pub_type" name="pub_type">
            <?php foreach (['Paper','Pre-print','Dataset','Thesis'] as $t): ?>
              <option value="<?= $t ?>" <?= $pub['pub_type'] === $t ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="category">Category</label>
          <input type="text" id="category" name="category" value="<?= e($pub['category']) ?>"
                 placeholder="e.g. Machine Learning">
        </div>

        <div class="form-group">
          <label for="status">Visibility</label>
          <select id="status" name="status">
            <option value="published" <?= $pub['status'] === 'published' ? 'selected' : '' ?>>Published (public)</option>
            <option value="draft" <?= $pub['status'] === 'draft' ? 'selected' : '' ?>>Draft (only me)</option>
          </select>
        </div>
      </div>

      <div class="card">
        <h3>Attachment</h3>
        <?php if ($pub['file_name']): ?>
          <div class="file-row">
            <span class="ico"><?= e(strtoupper(pathinfo($pub['file_name'], PATHINFO_EXTENSION))) ?></span>
            <span class="flex-1"><span class="nm"><?= e($pub['file_name']) ?></span>
              <span class="mt"><?= fmtBytes((int)$pub['file_size']) ?></span></span>
          </div>
          <p class="muted-sm">The current version points at this file. Uploading a new one
            starts a new version and leaves this file attached to the old version.</p>
        <?php endif; ?>
        <div class="form-group">
          <input type="file" name="doc">
          <div class="field-hint">PDF, DOCX, CSV, XLSX or ZIP. Max 20 MB.</div>
        </div>
      </div>

      <?php if ($isEdit): ?>
        <div class="card">
          <h3>Version</h3>
          <p class="muted-sm mb-2">
            This paper is currently at <strong>version <?= (int)($pub['current_version'] ?? 1) ?></strong>.
          </p>

          <label class="check mb-2">
            <input type="checkbox" name="new_version" value="1" checked>
            <span>Save this as a new version</span>
          </label>

          <div class="form-group">
            <label for="change_note">What changed?</label>
            <textarea id="change_note" name="change_note" style="min-height:76px"
                      placeholder="e.g. Rewrote the results section after supervisor feedback."></textarea>
            <div class="field-hint">Shown in the version history so readers know why it changed.</div>
          </div>

          <p class="field-hint">Untick the box for a small correction you do not want recorded
            as a new version. Uploading a new file always creates one.</p>
        </div>
      <?php endif; ?>

      <button class="btn btn-primary btn-block btn-lg" type="submit">
        <?= $isEdit ? 'Save Changes' : 'Publish' ?>
      </button>
    </div>
  </div>
</form>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
