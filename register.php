<?php

require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) { redirect('dashboard.php'); }

$errors = [];
$form   = [
    'first_name' => '', 'last_name' => '', 'email' => '',
    'role' => 'student', 'department' => '', 'student_id' => '',
];

$departments = ['CSE Department', 'EEE Department', 'Civil Engineering', 'Data Science',
                'Pharmacy', 'Economics', 'Business Administration', 'Physics Department',
                'Environmental Science', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    foreach (array_keys($form) as $k) { $form[$k] = (string)input($k); }
    $form['email'] = strtolower($form['email']);

    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['password_confirm'] ?? '');

    if ($form['first_name'] === '') { $errors[] = 'First name is required.'; }
    if ($form['last_name']  === '') { $errors[] = 'Last name is required.'; }

    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (fetchValue('SELECT COUNT(*) FROM users WHERE email = ?', [$form['email']])) {
        $errors[] = 'An account with that email already exists.';
    }

    if (strlen($password) < 8)             { $errors[] = 'Password must be at least 8 characters long.'; }
    if (!preg_match('/[0-9]/', $password)) { $errors[] = 'Password must contain at least one number.'; }
    if ($password !== $confirm)            { $errors[] = 'The two passwords do not match.'; }

    if (!in_array($form['role'], ['student', 'faculty'], true)) { $form['role'] = 'student'; }
    if (empty($_POST['agree'])) { $errors[] = 'Please accept the ethical guidelines to continue.'; }

    if (!$errors) {
        q('INSERT INTO users (first_name, last_name, email, password, role, department, student_id)
           VALUES (?,?,?,?,?,?,?)',
          [
            $form['first_name'], $form['last_name'], $form['email'],
            password_hash($password, PASSWORD_DEFAULT),
            $form['role'],
            $form['department'] ?: null,
            $form['student_id'] ?: null,
          ]);

        $newId = (int)db()->lastInsertId();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $newId;

        notify($newId, 'Welcome to ' . SITE_NAME . '! Complete your profile to get better matches.', 'profile_edit.php');
        setFlash('success', 'Account created. Let us set up your researcher profile.');
        redirect('onboarding.php');
    }
}

$pageTitle = 'Create your account';
$layout    = 'blank';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card" style="max-width:520px">
    <div class="eyebrow text-center">Registration</div>
    <h1>Create your account</h1>
    <p class="sub">Join the network of UIU researchers, students and scholars.</p>

    <?php if ($errors): ?>
      <div class="alert alert-error"><span><?= implode('<br>', array_map('e', $errors)) ?></span></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <?= csrfField() ?>

      <div class="form-row">
        <div class="form-group">
          <label for="first_name">First name <span class="req">*</span></label>
          <input type="text" id="first_name" name="first_name" value="<?= e($form['first_name']) ?>"
                 placeholder="Shifat" required>
        </div>
        <div class="form-group">
          <label for="last_name">Last name <span class="req">*</span></label>
          <input type="text" id="last_name" name="last_name" value="<?= e($form['last_name']) ?>"
                 placeholder="Mahmud" required>
        </div>
      </div>

      <div class="form-group">
        <label for="email">Institutional email <span class="req">*</span></label>
        <input type="email" id="email" name="email" value="<?= e($form['email']) ?>"
               placeholder="yourname@bscse.uiu.ac.bd" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="role">I am a <span class="req">*</span></label>
          <select id="role" name="role">
            <option value="student" <?= $form['role'] === 'student' ? 'selected' : '' ?>>Student</option>
            <option value="faculty" <?= $form['role'] === 'faculty' ? 'selected' : '' ?>>Faculty member</option>
          </select>
        </div>
        <div class="form-group">
          <label for="department">Department</label>
          <select id="department" name="department">
            <option value="">Select department</option>
            <?php foreach ($departments as $d): ?>
              <option value="<?= e($d) ?>" <?= $form['department'] === $d ? 'selected' : '' ?>><?= e($d) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label for="student_id">Student / Employee ID</label>
        <input type="text" id="student_id" name="student_id" value="<?= e($form['student_id']) ?>"
               placeholder="011201377">
      </div>

      <div class="form-group">
        <label for="password">Password <span class="req">*</span></label>
        <input type="password" id="password" name="password" placeholder="••••••••" required>
        <div class="field-hint">Minimum 8 characters, with at least one number.</div>
      </div>

      <div class="form-group">
        <label for="password_confirm">Confirm password <span class="req">*</span></label>
        <input type="password" id="password_confirm" name="password_confirm" placeholder="••••••••" required>
        <div class="field-hint" id="pwHint"></div>
      </div>

      <label class="check">
        <input type="checkbox" name="agree" value="1" <?= !empty($_POST['agree']) ? 'checked' : '' ?>>
        <span>I agree to the <a href="<?= url('page.php?p=ethics') ?>">Ethical Guidelines</a> and
          <a href="<?= url('page.php?p=terms') ?>">Terms of Service</a>.</span>
      </label>

      <button class="btn btn-primary btn-block btn-lg mt-2" type="submit">
        Create Account <?= icon('arrow-right', 16) ?>
      </button>
    </form>

    <div class="divider">OR</div>
    <p class="text-center muted-sm mb-0">Already have an account? <a href="<?= url('login.php') ?>">Log in</a></p>
  </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
