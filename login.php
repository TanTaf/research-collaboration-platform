<?php

require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) { redirect('dashboard.php'); }

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email    = strtolower(trim((string)input('email')));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '')    { $errors[] = 'Please enter your email address.'; }
    if ($password === '') { $errors[] = 'Please enter your password.'; }

    if (!$errors) {
        $user = fetchOne('SELECT * FROM users WHERE email = ?', [$email]);

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'Those details do not match any account. Please try again.';
        } elseif ($user['status'] !== 'active') {
            $errors[] = 'This account has been suspended. Please contact the administrator.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            setFlash('success', 'Welcome back, ' . $user['first_name'] . '!');
            redirect($user['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php');
        }
    }
}

$pageTitle = 'Log in';
$layout    = 'blank';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card">
    <img class="logo" src="<?= url('assets/img/logo.jpg') ?>" alt="<?= e(SITE_NAME) ?>">
    <h1>Welcome back</h1>
    <p class="sub">Log in to access your projects, publications and research network.</p>

    <?php foreach (getFlashes() as $f): ?>
      <div class="alert alert-<?= e($f['type']) ?>"><span><?= e($f['message']) ?></span></div>
    <?php endforeach; ?>

    <?php if ($errors): ?>
      <div class="alert alert-error">
        <span><?= implode('<br>', array_map('e', $errors)) ?></span>
      </div>
    <?php endif; ?>

    <form method="post" novalidate>
      <?= csrfField() ?>

      <div class="form-group">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>"
               placeholder="e.g. researcher@uiu.ac.bd" autocomplete="email" required autofocus>
      </div>

      <div class="form-group">
        <div class="flex-between" style="margin-bottom:6px">
          <label for="password" style="margin:0">Password</label>
          <a href="<?= url('page.php?p=support') ?>" style="font-size:12px">Forgot password?</a>
        </div>
        <input type="password" id="password" name="password" placeholder="••••••••"
               autocomplete="current-password" required>
      </div>

      <label class="check">
        <input type="checkbox" name="remember" value="1">
        <span>Keep me logged in on this computer</span>
      </label>

      <button class="btn btn-primary btn-block btn-lg mt-2" type="submit">Log In</button>
    </form>

    <div class="divider">OR</div>

    <p class="text-center muted-sm mb-1">New to the Research Hub?</p>
    <a class="btn btn-outline btn-block" href="<?= url('register.php') ?>">Create Institutional Account</a>

    <div class="demo-creds">
      <strong style="color:var(--ink)">Demo accounts</strong> &mdash; password for all of them is
      <code>password123</code><br>
      Student: <code>mehrab@bscse.uiu.ac.bd</code><br>
      Faculty: <code>rezwan.khan@cse.uiu.ac.bd</code><br>
      Admin: <code>admin@rchuiu.edu</code>
    </div>
  </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
