<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me  = currentUser();
$uid = (int)$me['id'];

$departments = ['CSE Department', 'EEE Department', 'Civil Engineering', 'Data Science',
                'Pharmacy', 'Economics', 'Business Administration', 'Physics Department',
                'Environmental Science', 'Administration', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $avatar = $me['avatar'];
    $up = handleUpload('avatar', 'avatars', ['jpg', 'jpeg', 'png', 'gif', 'webp'], 3 * 1024 * 1024);
    if ($up) {
        if ($avatar && is_file(UPLOAD_PATH . '/avatars/' . $avatar)) {
            @unlink(UPLOAD_PATH . '/avatars/' . $avatar);
        }
        $avatar = $up[0];
    }

    $cgpaRaw = input('cgpa');
    $cgpa    = ($cgpaRaw === '' ? null : max(0, min(4, (float)$cgpaRaw)));

    q('UPDATE users SET
         first_name = ?, last_name = ?, department = ?, designation = ?, trimester = ?,
         student_id = ?, cgpa = ?, bio = ?, phone = ?, city = ?, linkedin = ?, orcid = ?,
         website = ?, availability = ?, preferred_team = ?, open_to_collab = ?, avatar = ?
       WHERE id = ?',
      [
        input('first_name') ?: $me['first_name'],
        input('last_name')  ?: $me['last_name'],
        input('department') ?: null,
        input('designation') ?: null,
        input('trimester') ?: null,
        input('student_id') ?: null,
        $cgpa,
        input('bio') ?: null,
        input('phone') ?: null,
        input('city') ?: 'Dhaka',
        input('linkedin') ?: null,
        input('orcid') ?: null,
        input('website') ?: null,
        input('availability') ?: null,
        input('preferred_team') ?: null,
        !empty($_POST['open_to_collab']) ? 1 : 0,
        $avatar,
        $uid,
      ]);

    q('DELETE FROM user_interests WHERE user_id = ?', [$uid]);
    foreach (array_slice(array_map('intval', (array)($_POST['interests'] ?? [])), 0, 8) as $iid) {
        q('INSERT IGNORE INTO user_interests (user_id, interest_id) VALUES (?,?)', [$uid, $iid]);
    }

    q('DELETE FROM user_skills WHERE user_id = ?', [$uid]);
    $levels = (array)($_POST['skill_level'] ?? []);
    foreach (array_map('intval', (array)($_POST['skills'] ?? [])) as $sid) {
        $lvl = $levels[$sid] ?? 'Intermediate';
        if (!in_array($lvl, ['Beginner', 'Intermediate', 'Advanced', 'Expert'], true)) {
            $lvl = 'Intermediate';
        }
        q('INSERT IGNORE INTO user_skills (user_id, skill_id, level) VALUES (?,?,?)', [$uid, $sid, $lvl]);
    }

    $newPw = (string)($_POST['new_password'] ?? '');
    if ($newPw !== '') {
        if (strlen($newPw) < 8) {
            setFlash('error', 'The new password must be at least 8 characters long. Everything else was saved.');
        } elseif ($newPw !== (string)($_POST['new_password_confirm'] ?? '')) {
            setFlash('error', 'The new passwords did not match. Everything else was saved.');
        } elseif (!password_verify((string)($_POST['current_password'] ?? ''), $me['password'])) {
            setFlash('error', 'Your current password was incorrect, so the password was not changed.');
        } else {
            q('UPDATE users SET password = ? WHERE id = ?', [password_hash($newPw, PASSWORD_DEFAULT), $uid]);
            setFlash('success', 'Password updated.');
        }
    }

    setFlash('success', 'Profile saved.');
    redirect('profile.php');
}

$myInterests = userInterestIds($uid);
$mySkills    = [];
foreach (fetchAll('SELECT skill_id, level FROM user_skills WHERE user_id = ?', [$uid]) as $r) {
    $mySkills[(int)$r['skill_id']] = $r['level'];
}

$pageTitle = 'Edit profile';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>Edit Profile</h1>
    <p class="sub">A complete profile gets you better collaborator matches and more project invitations.</p>
  </div>
  <a class="btn btn-outline" href="<?= url('profile.php') ?>">Cancel</a>
</div>

<form method="post" enctype="multipart/form-data">
  <?= csrfField() ?>

  <div class="layout-main-side">
    <div class="flex-col" style="gap:18px">

      <div class="card">
        <h3>Basic details</h3>
        <div class="form-row">
          <div class="form-group">
            <label for="first_name">First name</label>
            <input type="text" id="first_name" name="first_name" value="<?= e($me['first_name']) ?>" required>
          </div>
          <div class="form-group">
            <label for="last_name">Last name</label>
            <input type="text" id="last_name" name="last_name" value="<?= e($me['last_name']) ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="department">Department</label>
            <select id="department" name="department">
              <option value="">Not set</option>
              <?php foreach ($departments as $d): ?>
                <option value="<?= e($d) ?>" <?= $me['department'] === $d ? 'selected' : '' ?>><?= e($d) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="designation">Designation <span class="muted-sm">(faculty)</span></label>
            <input type="text" id="designation" name="designation" value="<?= e($me['designation']) ?>"
                   placeholder="e.g. Assistant Professor">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="trimester">Trimester</label>
            <input type="text" id="trimester" name="trimester" value="<?= e($me['trimester']) ?>"
                   placeholder="e.g. 12th Trimester">
          </div>
          <div class="form-group">
            <label for="student_id">Student / Employee ID</label>
            <input type="text" id="student_id" name="student_id" value="<?= e($me['student_id']) ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="cgpa">CGPA</label>
            <input type="number" id="cgpa" name="cgpa" step="0.01" min="0" max="4"
                   value="<?= $me['cgpa'] !== null ? e($me['cgpa']) : '' ?>" placeholder="3.50">
          </div>
          <div class="form-group">
            <label for="city">City</label>
            <input type="text" id="city" name="city" value="<?= e($me['city']) ?>">
          </div>
        </div>

        <div class="form-group">
          <label for="bio">About me</label>
          <textarea id="bio" name="bio" placeholder="What do you research? What kind of collaboration are you looking for?"><?= e($me['bio']) ?></textarea>
        </div>
      </div>

      <div class="card">
        <h3>Research interests</h3>
        <p class="muted-sm">Pick up to 8. These drive your match scores.</p>
        <div class="tag-picker" data-max-select="8">
          <?php foreach (allInterests() as $i): ?>
            <label>
              <input type="checkbox" name="interests[]" value="<?= (int)$i['id'] ?>"
                     <?= in_array((int)$i['id'], $myInterests, true) ? 'checked' : '' ?>>
              <span><?= e($i['name']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card">
        <h3>Technical skills</h3>
        <p class="muted-sm">Tick a skill, then set how strong you are at it.</p>
        <div class="grid grid-2" style="gap:10px">
          <?php foreach (allSkills() as $s):
                $sid = (int)$s['id'];
                $has = isset($mySkills[$sid]); ?>
            <div class="flex" style="gap:10px">
              <label class="check" style="flex:1;margin:0">
                <input type="checkbox" name="skills[]" value="<?= $sid ?>" <?= $has ? 'checked' : '' ?>>
                <span><?= e($s['name']) ?></span>
              </label>
              <select name="skill_level[<?= $sid ?>]" style="width:132px">
                <?php foreach (['Beginner', 'Intermediate', 'Advanced', 'Expert'] as $lvl): ?>
                  <option value="<?= $lvl ?>" <?= ($has && $mySkills[$sid] === $lvl) ? 'selected' : '' ?>><?= $lvl ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card">
        <h3>Change password <span class="muted-sm" style="font-weight:400">(optional)</span></h3>
        <div class="form-group">
          <label for="current_password">Current password</label>
          <input type="password" id="current_password" name="current_password" autocomplete="current-password">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="new_password">New password</label>
            <input type="password" id="new_password" name="new_password" autocomplete="new-password">
          </div>
          <div class="form-group">
            <label for="new_password_confirm">Confirm new password</label>
            <input type="password" id="new_password_confirm" name="new_password_confirm" autocomplete="new-password">
          </div>
        </div>
      </div>
    </div>

    <div class="flex-col" style="gap:18px">
      <div class="card text-center">
        <h3>Profile photo</h3>
        <div style="display:flex;justify-content:center;margin:6px 0 14px"><?= avatar($me, 96) ?></div>
        <div class="form-group text-left">
          <input type="file" name="avatar" accept="image/*">
          <div class="field-hint">JPG, PNG or WEBP. Max 3 MB.</div>
        </div>
      </div>

      <div class="card">
        <h3>Collaboration</h3>
        <label class="check">
          <input type="checkbox" name="open_to_collab" value="1" <?= $me['open_to_collab'] ? 'checked' : '' ?>>
          <span>I am available for collaboration</span>
        </label>
        <div class="form-group mt-2">
          <label for="availability">Weekly availability</label>
          <input type="text" id="availability" name="availability" value="<?= e($me['availability']) ?>"
                 placeholder="e.g. 15 - 20 hrs/week">
        </div>
        <div class="form-group">
          <label for="preferred_team">Preferred team size</label>
          <input type="text" id="preferred_team" name="preferred_team" value="<?= e($me['preferred_team']) ?>"
                 placeholder="e.g. 2-3 Members">
        </div>
      </div>

      <div class="card">
        <h3>Links</h3>
        <div class="form-group">
          <label for="phone">Phone</label>
          <input type="tel" id="phone" name="phone" value="<?= e($me['phone']) ?>" placeholder="+880 ...">
        </div>
        <div class="form-group">
          <label for="linkedin">LinkedIn</label>
          <input type="text" id="linkedin" name="linkedin" value="<?= e($me['linkedin']) ?>"
                 placeholder="linkedin.com/in/yourname">
        </div>
        <div class="form-group">
          <label for="orcid">ORCID iD</label>
          <input type="text" id="orcid" name="orcid" value="<?= e($me['orcid']) ?>" placeholder="0000-0000-0000">
        </div>
        <div class="form-group">
          <label for="website">Personal website</label>
          <input type="url" id="website" name="website" value="<?= e($me['website']) ?>" placeholder="https://">
        </div>
      </div>

      <button class="btn btn-primary btn-block btn-lg" type="submit">Save Profile</button>
    </div>
  </div>
</form>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
