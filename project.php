<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me  = currentUser();
$uid = (int)$me['id'];
$pid = (int)input('id', 0);

$p = fetchOne('SELECT p.*, u.first_name, u.last_name FROM projects p
               JOIN users u ON u.id = p.owner_id WHERE p.id = ?', [$pid]);

if (!$p) { setFlash('error', 'Project not found.'); redirect('projects.php'); }

$isMember = (bool)fetchValue('SELECT COUNT(*) FROM project_members WHERE project_id = ? AND user_id = ?', [$pid, $uid]);
$isOwner  = ((int)$p['owner_id'] === $uid);

if (!$isMember && !isAdmin()) {
    setFlash('info', 'You are not on this team yet. Here is the public view.');
    redirect('opportunity.php?id=' . $pid);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string)input('action');

    if ($action === 'post_message') {
        $body = trim((string)input('body'));
        if ($body !== '') {
            q('INSERT INTO project_messages (project_id, user_id, body) VALUES (?,?,?)', [$pid, $uid, $body]);
        }

    } elseif ($action === 'add_task') {
        $title = trim((string)input('title'));
        if ($title !== '') {
            q('INSERT INTO tasks (project_id, title, assigned_to, due_date) VALUES (?,?,?,?)',
              [$pid, $title, (int)input('assigned_to') ?: null, input('due_date') ?: null]);
            setFlash('success', 'Task added.');
        }

    } elseif ($action === 'toggle_task') {
        q('UPDATE tasks SET is_done = 1 - is_done WHERE id = ? AND project_id = ?', [(int)input('task_id'), $pid]);

    } elseif ($action === 'upload_file') {
        $up = handleUpload('doc', 'projects',
              ['pdf','doc','docx','xls','xlsx','ppt','pptx','csv','txt','zip','rar','png','jpg','jpeg'],
              15 * 1024 * 1024);
        if ($up) {
            q('INSERT INTO project_files (project_id, user_id, file_name, file_path, file_size) VALUES (?,?,?,?,?)',
              [$pid, $uid, $up[1], $up[0], $up[2]]);
            setFlash('success', 'File uploaded to the project.');
        }

    } elseif ($action === 'add_milestone' && $isOwner) {
        $title = trim((string)input('ms_title'));
        if ($title !== '') {
            q('INSERT INTO milestones (project_id, title, description, status, progress, due_date)
               VALUES (?,?,?,?,?,?)',
              [$pid, $title, input('ms_desc') ?: null, input('ms_status', 'upcoming'),
               (int)input('ms_progress', 0), input('ms_due') ?: null]);
            setFlash('success', 'Milestone added.');
        }

    } elseif ($action === 'update_progress' && $isOwner) {
        $prog   = max(0, min(100, (int)input('progress')));
        $status = in_array(input('status'), ['pending','in_progress','completed'], true)
                  ? input('status') : $p['status'];
        q('UPDATE projects SET progress = ?, status = ?, is_open = ? WHERE id = ?',
          [$prog, $status, !empty($_POST['is_open']) ? 1 : 0, $pid]);
        setFlash('success', 'Project updated.');
    }

    redirect('project.php?id=' . $pid);
}

$members    = fetchAll('SELECT pm.member_role, u.* FROM project_members pm
                        JOIN users u ON u.id = pm.user_id WHERE pm.project_id = ?
                        ORDER BY pm.joined_at', [$pid]);
$milestones = fetchAll('SELECT * FROM milestones WHERE project_id = ?
                        ORDER BY FIELD(status,"completed","in_progress","upcoming"), due_date', [$pid]);
$tasks      = fetchAll('SELECT t.*, u.first_name, u.last_name FROM tasks t
                        LEFT JOIN users u ON u.id = t.assigned_to
                        WHERE t.project_id = ? ORDER BY t.is_done, t.due_date', [$pid]);
$files      = fetchAll('SELECT f.*, u.first_name FROM project_files f
                        JOIN users u ON u.id = f.user_id
                        WHERE f.project_id = ? ORDER BY f.created_at DESC', [$pid]);
$chat       = fetchAll('SELECT pm.*, u.first_name, u.last_name, u.avatar, u.id AS uid
                        FROM project_messages pm JOIN users u ON u.id = pm.user_id
                        WHERE pm.project_id = ? ORDER BY pm.created_at', [$pid]);

$pageTitle = $p['title'];
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <div class="flex mb-1" style="gap:9px">
      <span class="badge badge-<?= e($p['status']) ?>"><?= e(statusLabel($p['status'])) ?></span>
      <span class="muted-sm"><?= icon('calendar', 13) ?>
        Est. completion: <?= $p['deadline'] ? fmtDate($p['deadline'], 'M Y') : 'Not set' ?></span>
    </div>
    <h1><?= e($p['title']) ?></h1>
    <p class="sub"><?= e($p['summary']) ?></p>
  </div>

  <div class="flex" style="gap:10px">
    <div class="text-center">
      <div class="ring ring-sm" style="--pct:<?= (int)$p['progress'] ?>">
        <div class="ring-inner"><?= (int)$p['progress'] ?>%</div>
      </div>
      <div class="match-label mt-1">Total Progress</div>
    </div>
    <?php if ($isOwner): ?>
      <a class="btn btn-primary" href="<?= url('project_form.php?id=' . $pid) ?>"><?= icon('edit', 15) ?> Edit</a>
    <?php endif; ?>
    <a class="btn btn-orange-outline" href="<?= url('opportunity.php?id=' . $pid) ?>"><?= icon('share', 15) ?> Public View</a>
  </div>
</div>

<div class="layout-main-side">
  <div class="flex-col" style="gap:18px">

    <div class="card">
      <div class="card-head"><h2>Project Milestones</h2></div>

      <?php if (!$milestones): ?>
        <p class="muted-sm">No milestones yet.</p>
      <?php else: ?>
        <div class="timeline">
          <?php foreach ($milestones as $m): ?>
            <div class="tl-item <?= $m['status'] === 'completed' ? 'done' : ($m['status'] === 'in_progress' ? 'active' : '') ?>">
              <div class="tl-kicker <?= $m['status'] === 'upcoming' ? 'grey' : '' ?>">
                <?= e(strtoupper(statusLabel($m['status']))) ?>
                <?= $m['due_date'] ? ' &middot; ' . fmtDate($m['due_date'], 'M Y') : '' ?>
              </div>
              <h4><?= e($m['title']) ?></h4>
              <?php if ($m['description']): ?>
                <p class="muted-sm mb-1"><?= e($m['description']) ?></p>
              <?php endif; ?>
              <?php if ($m['status'] === 'in_progress'): ?>
                <div class="progress"><span style="width:<?= (int)$m['progress'] ?>%"></span></div>
                <div class="text-right muted-sm"><?= (int)$m['progress'] ?>% progress</div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($isOwner): ?>
        <details class="mt-3">
          <summary style="cursor:pointer;font-size:13px;font-weight:600;color:var(--orange)">+ Add milestone</summary>
          <form method="post" class="mt-2">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add_milestone">
            <div class="form-group"><label>Title</label><input type="text" name="ms_title" required></div>
            <div class="form-group"><label>Description</label><textarea name="ms_desc" style="min-height:70px"></textarea></div>
            <div class="form-row">
              <div class="form-group"><label>Status</label>
                <select name="ms_status">
                  <option value="upcoming">Upcoming</option>
                  <option value="in_progress">In Progress</option>
                  <option value="completed">Completed</option>
                </select>
              </div>
              <div class="form-group"><label>Due date</label><input type="date" name="ms_due"></div>
            </div>
            <div class="form-group"><label>Progress (%)</label>
              <input type="number" name="ms_progress" min="0" max="100" value="0"></div>
            <button class="btn btn-primary btn-sm" type="submit">Add Milestone</button>
          </form>
        </details>
      <?php endif; ?>
    </div>

    <div class="grid grid-2">
      <div class="card">
        <div class="card-head"><h2>Task Pipeline</h2></div>

        <?php if (!$tasks): ?>
          <p class="muted-sm">No tasks yet.</p>
        <?php else: foreach ($tasks as $t): ?>
          <form method="post" class="task-row <?= $t['is_done'] ? 'task-done' : 'open' ?>">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="toggle_task">
            <input type="hidden" name="task_id" value="<?= (int)$t['id'] ?>">
            <input type="checkbox" <?= $t['is_done'] ? 'checked' : '' ?> onchange="this.form.submit()">
            <span class="flex-1">
              <span class="t"><?= e($t['title']) ?></span>
              <span class="m" style="display:block">
                <?= $t['first_name'] ? 'Assigned to: ' . e($t['first_name'] . ' ' . $t['last_name']) : 'Unassigned' ?>
                <?= $t['due_date'] ? ' &middot; due ' . fmtDate($t['due_date']) : '' ?>
              </span>
            </span>
          </form>
        <?php endforeach; endif; ?>

        <details class="mt-2">
          <summary style="cursor:pointer;font-size:13px;font-weight:600;color:var(--orange)">+ Add Task</summary>
          <form method="post" class="mt-2">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add_task">
            <div class="form-group"><label>Task</label><input type="text" name="title" required></div>
            <div class="form-row">
              <div class="form-group"><label>Assign to</label>
                <select name="assigned_to">
                  <option value="">Unassigned</option>
                  <?php foreach ($members as $m): ?>
                    <option value="<?= (int)$m['id'] ?>"><?= e(fullName($m)) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group"><label>Due date</label><input type="date" name="due_date"></div>
            </div>
            <button class="btn btn-primary btn-sm" type="submit">Add Task</button>
          </form>
        </details>
      </div>

      <div class="card">
        <div class="card-head"><h2>Shared Files</h2></div>

        <?php if (!$files): ?>
          <p class="muted-sm">No files shared yet.</p>
        <?php else: foreach ($files as $f): ?>
          <div class="file-row">
            <span class="ico"><?= e(strtoupper(pathinfo($f['file_name'], PATHINFO_EXTENSION) ?: 'F')) ?></span>
            <span class="flex-1">
              <span class="nm"><?= e($f['file_name']) ?></span>
              <span class="mt"><?= fmtBytes((int)$f['file_size']) ?> &middot;
                <?= e($f['first_name']) ?> &middot; <?= timeAgo($f['created_at']) ?></span>
            </span>
            <a class="icon-btn" href="<?= url('download.php?type=project&id=' . (int)$f['id']) ?>"
               title="Download"><?= icon('download', 16) ?></a>
          </div>
        <?php endforeach; endif; ?>

        <form method="post" enctype="multipart/form-data" class="mt-2">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="upload_file">
          <div class="form-group">
            <input type="file" name="doc" required>
            <div class="field-hint">PDF, Office documents, images or archives. Max 15 MB.</div>
          </div>
          <button class="btn btn-orange-outline btn-sm btn-block" type="submit">
            <?= icon('upload', 15) ?> Upload File</button>
        </form>
      </div>
    </div>

    <div class="card" style="padding:0;overflow:hidden">
      <div class="card-head" style="padding:20px 22px 0;margin-bottom:0"><h2>Team Chat</h2></div>

      <div class="chat-thread" style="background:#fff">
        <?php if (!$chat): ?>
          <p class="muted-sm text-center">No messages yet. Start the conversation.</p>
        <?php else: foreach ($chat as $c): $mine = ((int)$c['uid'] === $uid); ?>
          <div class="bubble-row <?= $mine ? 'me' : '' ?>">
            <?= avatar(['id' => $c['uid'], 'first_name' => $c['first_name'],
                        'last_name' => $c['last_name'], 'avatar' => $c['avatar']], 32) ?>
            <div class="bubble">
              <strong style="font-size:12.5px"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></strong>
              <div><?= nl2br(e($c['body'])) ?></div>
              <span class="tm"><?= fmtDate($c['created_at'], 'd M, g:i A') ?></span>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>

      <form method="post" class="chat-compose">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="post_message">
        <input type="text" name="body" placeholder="Share an update or ask a question..." required>
        <button class="btn btn-primary" type="submit">Post Message</button>
      </form>
    </div>
  </div>

  <div class="flex-col" style="gap:18px">
    <div class="card">
      <div class="card-head"><h3 style="margin:0">Collaborators</h3>
        <span class="muted-sm"><?= count($members) ?></span></div>

      <?php foreach ($members as $m): ?>
        <a class="flex mb-2" style="text-decoration:none;color:inherit"
           href="<?= url('profile.php?id=' . (int)$m['id']) ?>">
          <?= avatar($m, 38) ?>
          <span>
            <span style="font-size:13px;font-weight:600;display:block"><?= e(fullName($m)) ?></span>
            <span class="muted-sm" style="color:var(--orange);font-weight:600;font-size:11px;text-transform:uppercase">
              <?= e($m['member_role']) ?></span>
          </span>
        </a>
      <?php endforeach; ?>

      <?php if ($isOwner): ?>
        <a class="btn btn-outline btn-block btn-sm mt-1" href="<?= url('requests.php') ?>">Manage Join Requests</a>
      <?php endif; ?>
    </div>

    <?php if ($isOwner): ?>
      <form class="card" method="post">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="update_progress">
        <h3>Update Status</h3>

        <div class="form-group">
          <label for="progress">Overall progress (%)</label>
          <input type="number" id="progress" name="progress" min="0" max="100" value="<?= (int)$p['progress'] ?>">
        </div>
        <div class="form-group">
          <label for="status">Project status</label>
          <select id="status" name="status">
            <?php foreach (['pending','in_progress','completed'] as $s): ?>
              <option value="<?= $s ?>" <?= $p['status'] === $s ? 'selected' : '' ?>><?= e(statusLabel($s)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <label class="check">
          <input type="checkbox" name="is_open" value="1" <?= $p['is_open'] ? 'checked' : '' ?>>
          <span>Still recruiting collaborators</span>
        </label>
        <button class="btn btn-primary btn-block btn-sm mt-2" type="submit">Save</button>
      </form>
    <?php endif; ?>

    <div class="card">
      <h5 class="eyebrow eyebrow-muted">Project Details</h5>
      <div class="flex-col" style="gap:11px">
        <div class="flex-between"><span class="muted-sm">Type</span><strong style="font-size:13px"><?= e($p['project_type']) ?></strong></div>
        <div class="flex-between"><span class="muted-sm">Owner</span><strong style="font-size:13px"><?= e($p['first_name'] . ' ' . $p['last_name']) ?></strong></div>
        <div class="flex-between"><span class="muted-sm">Started</span><strong style="font-size:13px"><?= fmtDate($p['created_at']) ?></strong></div>
        <div class="flex-between"><span class="muted-sm">Deadline</span><strong style="font-size:13px"><?= $p['deadline'] ? fmtDate($p['deadline']) : '--' ?></strong></div>
        <div class="flex-between"><span class="muted-sm">Recruiting</span><strong style="font-size:13px"><?= $p['is_open'] ? 'Yes' : 'Closed' ?></strong></div>
      </div>
    </div>
  </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
