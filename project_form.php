<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me  = currentUser();
$uid = (int)$me['id'];
$pid = (int)input('id', 0);

$isEdit = false;
$p = [
    'title' => '', 'summary' => '', 'description' => '', 'objectives' => '',
    'project_type' => 'FYDP', 'status' => 'in_progress', 'is_open' => 1,
    'required_members' => '2-3', 'deadline' => '', 'progress' => 0,
];

if ($pid) {
    $row = fetchOne('SELECT * FROM projects WHERE id = ?', [$pid]);
    if (!$row) { setFlash('error', 'Project not found.'); redirect('projects.php'); }
    if ((int)$row['owner_id'] !== $uid && !isAdmin()) {
        setFlash('error', 'Only the project owner can edit this project.');
        redirect('project.php?id=' . $pid);
    }
    $p      = $row;
    $isEdit = true;
}

$selInterests = $isEdit ? array_map('intval', array_column(
    fetchAll('SELECT interest_id FROM project_interests WHERE project_id = ?', [$pid]), 'interest_id')) : [];
$selSkills = $isEdit ? array_map('intval', array_column(
    fetchAll('SELECT skill_id FROM project_skills WHERE project_id = ?', [$pid]), 'skill_id')) : [];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    foreach (['title','summary','description','objectives','project_type','status','required_members','deadline'] as $k) {
        $p[$k] = (string)input($k);
    }
    $p['progress'] = max(0, min(100, (int)input('progress')));
    $p['is_open']  = !empty($_POST['is_open']) ? 1 : 0;

    $selInterests = array_map('intval', (array)($_POST['interests'] ?? []));
    $selSkills    = array_map('intval', (array)($_POST['skills'] ?? []));

    if ($p['title']   === '') { $errors[] = 'Please give the project a title.'; }
    if ($p['summary'] === '') { $errors[] = 'Please write a one-line summary.'; }
    if (!in_array($p['project_type'], ['FYDP','Thesis','RA','Research','Lab Work'], true)) { $p['project_type'] = 'Research'; }
    if (!in_array($p['status'], ['pending','in_progress','completed'], true)) { $p['status'] = 'in_progress'; }

    if (!$errors) {
        if ($isEdit) {
            q('UPDATE projects SET title=?, summary=?, description=?, objectives=?, project_type=?,
                 status=?, is_open=?, required_members=?, deadline=?, progress=? WHERE id=?',
              [$p['title'], $p['summary'], $p['description'], $p['objectives'], $p['project_type'],
               $p['status'], $p['is_open'], $p['required_members'], $p['deadline'] ?: null, $p['progress'], $pid]);
            $savedId = $pid;
            setFlash('success', 'Project updated.');
        } else {
            q('INSERT INTO projects (owner_id, title, summary, description, objectives, project_type,
                 status, is_open, required_members, deadline, progress)
               VALUES (?,?,?,?,?,?,?,?,?,?,?)',
              [$uid, $p['title'], $p['summary'], $p['description'], $p['objectives'], $p['project_type'],
               $p['status'], $p['is_open'], $p['required_members'], $p['deadline'] ?: null, $p['progress']]);
            $savedId = (int)db()->lastInsertId();
            q('INSERT INTO project_members (project_id, user_id, member_role) VALUES (?,?,?)',
              [$savedId, $uid, 'Project Owner']);
            setFlash('success', 'Project created. Invite collaborators from the workspace.');
        }

        q('DELETE FROM project_interests WHERE project_id = ?', [$savedId]);
        foreach (array_slice($selInterests, 0, 8) as $i) {
            q('INSERT IGNORE INTO project_interests (project_id, interest_id) VALUES (?,?)', [$savedId, $i]);
        }
        q('DELETE FROM project_skills WHERE project_id = ?', [$savedId]);
        foreach (array_slice($selSkills, 0, 10) as $s) {
            q('INSERT IGNORE INTO project_skills (project_id, skill_id) VALUES (?,?)', [$savedId, $s]);
        }

        redirect('project.php?id=' . $savedId);
    }
}

$pageTitle = $isEdit ? 'Edit project' : 'Create a project';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <h1><?= $isEdit ? 'Edit Project' : 'Create New Project' ?></h1>
    <p class="sub">Describe your research so the right collaborators can find it.</p>
  </div>
  <a class="btn btn-outline" href="<?= $isEdit ? url('project.php?id=' . $pid) : url('projects.php') ?>">Cancel</a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-error"><span><?= implode('<br>', array_map('e', $errors)) ?></span></div>
<?php endif; ?>

<form method="post">
  <?= csrfField() ?>

  <div class="layout-main-side">
    <div class="flex-col" style="gap:18px">
      <div class="card">
        <h3>Project details</h3>

        <div class="form-group">
          <label for="title">Project title <span class="req">*</span></label>
          <input type="text" id="title" name="title" value="<?= e($p['title']) ?>"
                 placeholder="e.g. AI-Based Plant Disease Detection" required>
        </div>

        <div class="form-group">
          <label for="summary">One-line summary <span class="req">*</span></label>
          <input type="text" id="summary" name="summary" value="<?= e($p['summary']) ?>" maxlength="400"
                 placeholder="Shown on the opportunity card" required>
        </div>

        <div class="form-group">
          <label for="description">Full description</label>
          <textarea id="description" name="description" style="min-height:160px"
                    placeholder="Background, approach, what makes this worth doing..."><?= e($p['description']) ?></textarea>
        </div>

        <div class="form-group">
          <label for="objectives">Objectives <span class="muted-sm">(one per line)</span></label>
          <textarea id="objectives" name="objectives" style="min-height:120px"
                    placeholder="Build a DL model for classification&#10;Optimise for mobile inference"><?= e($p['objectives']) ?></textarea>
        </div>
      </div>

      <div class="card">
        <h3>Research fields</h3>
        <p class="muted-sm">Pick up to 8. Students with these interests will see your project first.</p>
        <div class="tag-picker mb-3" data-max-select="8">
          <?php foreach (allInterests() as $i): ?>
            <label>
              <input type="checkbox" name="interests[]" value="<?= (int)$i['id'] ?>"
                     <?= in_array((int)$i['id'], $selInterests, true) ? 'checked' : '' ?>>
              <span><?= e($i['name']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>

        <h3>Stack &amp; tools</h3>
        <p class="muted-sm">What will the team actually use?</p>
        <div class="tag-picker" data-max-select="10">
          <?php foreach (allSkills() as $s): ?>
            <label>
              <input type="checkbox" name="skills[]" value="<?= (int)$s['id'] ?>"
                     <?= in_array((int)$s['id'], $selSkills, true) ? 'checked' : '' ?>>
              <span><?= e($s['name']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="flex-col" style="gap:18px">
      <div class="card">
        <h3>Settings</h3>

        <div class="form-group">
          <label for="project_type">Project type</label>
          <select id="project_type" name="project_type">
            <?php foreach (['FYDP','Thesis','RA','Research','Lab Work'] as $t): ?>
              <option value="<?= $t ?>" <?= $p['project_type'] === $t ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="status">Status</label>
          <select id="status" name="status">
            <?php foreach (['pending','in_progress','completed'] as $s): ?>
              <option value="<?= $s ?>" <?= $p['status'] === $s ? 'selected' : '' ?>><?= e(statusLabel($s)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="required_members">Members needed</label>
          <input type="text" id="required_members" name="required_members"
                 value="<?= e($p['required_members']) ?>" placeholder="2-3">
        </div>

        <div class="form-group">
          <label for="deadline">Deadline</label>
          <input type="date" id="deadline" name="deadline"
                 value="<?= e($p['deadline'] ? substr((string)$p['deadline'], 0, 10) : '') ?>">
        </div>

        <div class="form-group">
          <label for="progress">Progress (%)</label>
          <input type="number" id="progress" name="progress" min="0" max="100" value="<?= (int)$p['progress'] ?>">
        </div>

        <label class="check">
          <input type="checkbox" name="is_open" value="1" <?= $p['is_open'] ? 'checked' : '' ?>>
          <span>List this under Opportunities (recruiting)</span>
        </label>
      </div>

      <button class="btn btn-primary btn-block btn-lg" type="submit">
        <?= $isEdit ? 'Save Changes' : 'Create Project' ?>
      </button>
    </div>
  </div>
</form>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
