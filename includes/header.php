<?php

require_once ROOT_PATH . '/includes/icons.php';

$pageTitle = $pageTitle ?? SITE_NAME;
$layout    = $layout    ?? (isLoggedIn() ? 'app' : 'public');
$me        = currentUser();

if ($layout === 'app' && !$me) { redirect('login.php'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> &middot; <?= e(SITE_NAME) ?></title>
<meta name="description" content="A research collaboration platform for United International University students and faculty.">
<link rel="icon" href="<?= url('assets/img/logo.jpg') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="layout-<?= e($layout) ?>">

<?php if ($layout === 'app'): ?>

<header class="topbar">
  <button class="icon-btn menu-toggle" id="menuToggle" aria-label="Toggle menu"><?= icon('menu', 20) ?></button>

  <a class="brand" href="<?= url('dashboard.php') ?>">
    <img src="<?= url('assets/img/logo.jpg') ?>" alt="Logo">
    <span class="brand-name"><?= e(SITE_NAME) ?></span>
  </a>

  <form class="topsearch" action="<?= url('search.php') ?>" method="get" role="search">
    <span class="ico"><?= icon('search', 16) ?></span>
    <input type="search" name="q" placeholder="Search researchers, papers, projects..."
           value="<?= e($_GET['q'] ?? '') ?>">
  </form>

  <div class="topactions">
    <a class="icon-btn" href="<?= url('notifications.php') ?>" title="Notifications">
      <?= icon('bell', 19) ?>
      <?php $nCount = unreadNotifications((int)$me['id']); if ($nCount): ?>
        <span class="dot"><?= $nCount ?></span>
      <?php endif; ?>
    </a>
    <a class="icon-btn" href="<?= url('messages.php') ?>" title="Messages">
      <?= icon('mail', 19) ?>
      <?php $mCount = unreadMessages((int)$me['id']); if ($mCount): ?>
        <span class="dot"><?= $mCount ?></span>
      <?php endif; ?>
    </a>
    <a class="topuser" href="<?= url('profile.php') ?>">
      <?= avatar($me, 34) ?>
      <span>
        <span class="nm"><?= e($me['first_name']) ?></span><br>
        <span class="rl"><?= e($me['department'] ?: ucfirst($me['role'])) ?></span>
      </span>
    </a>
  </div>
</header>

<div class="app">

  <nav class="sidebar" id="sidebar">
    <a href="<?= url('dashboard.php') ?>"     class="<?= isActive('dashboard.php') ? 'active' : '' ?>"><?= icon('dashboard') ?> Dashboard</a>
    <a href="<?= url('profile.php') ?>"       class="<?= isActive('profile.php') || isActive('profile_edit.php') ? 'active' : '' ?>"><?= icon('user') ?> My Profile</a>
    <a href="<?= url('matchmaking.php') ?>"   class="<?= isActive('matchmaking.php') ? 'active' : '' ?>"><?= icon('sparkle') ?> Smart Matchmaking</a>
    <a href="<?= url('opportunities.php') ?>" class="<?= isActive('opportunities.php') || isActive('opportunity.php') ? 'active' : '' ?>"><?= icon('bulb') ?> Opportunities</a>
    <a href="<?= url('projects.php') ?>"      class="<?= isActive('projects.php') || isActive('project.php') || isActive('project_form.php') ? 'active' : '' ?>"><?= icon('folder') ?> My Projects</a>
    <a href="<?= url('publications.php') ?>"  class="<?= isActive('publications.php') || isActive('publication.php') ? 'active' : '' ?>"><?= icon('file') ?> Publications</a>
    <a href="<?= url('repository.php') ?>"    class="<?= isActive('repository.php') ? 'active' : '' ?>"><?= icon('layers') ?> Repositories</a>
    <a href="<?= url('communities.php') ?>"   class="<?= isActive('communities.php') || isActive('community.php') || isActive('post.php') ? 'active' : '' ?>"><?= icon('users') ?> Communities</a>
    <a href="<?= url('events.php') ?>"        class="<?= isActive('events.php') ? 'active' : '' ?>"><?= icon('calendar') ?> Events</a>
    <a href="<?= url('messages.php') ?>"      class="<?= isActive('messages.php') ? 'active' : '' ?>">
      <?= icon('mail') ?> Messages
      <?php if (!empty($mCount)): ?><span class="pill"><?= $mCount ?></span><?php endif; ?>
    </a>
    <a href="<?= url('requests.php') ?>"      class="<?= isActive('requests.php') ? 'active' : '' ?>"><?= icon('user-plus') ?> Requests</a>

    <?php if ($me['role'] === 'admin'): ?>
      <div class="sidebar-sep"></div>
      <div class="sidebar-label">Administration</div>
      <a href="<?= url('admin/index.php') ?>"><?= icon('shield') ?> Admin Panel</a>
    <?php endif; ?>

    <div class="sidebar-sep"></div>
    <a href="<?= url('logout.php') ?>"><?= icon('logout') ?> Log Out</a>
  </nav>
  <div class="nav-scrim" id="navScrim"></div>

  <main class="app-main">
    <?php foreach (getFlashes() as $f): ?>
      <div class="alert alert-<?= e($f['type']) ?>"><?= icon($f['type'] === 'success' ? 'check-circle' : 'help', 17) ?><span><?= e($f['message']) ?></span></div>
    <?php endforeach; ?>

<?php elseif ($layout === 'public'): ?>

<header class="public-header">
  <div class="wrap">
    <a class="brand" href="<?= url('index.php') ?>">
      <img src="<?= url('assets/img/logo.jpg') ?>" alt="Logo">
      <span class="brand-name"><?= e(SITE_NAME) ?></span>
    </a>
    <nav class="topnav">
      <a href="<?= url('index.php') ?>#features">Projects</a>
      <a href="<?= url('index.php') ?>#repository">Publications</a>
      <a href="<?= url('index.php') ?>#network">Network</a>
      <a href="<?= url('index.php') ?>#events">Events</a>
    </nav>
    <div class="topactions">
      <?php if (isLoggedIn()): ?>
        <a class="btn btn-primary btn-sm" href="<?= url('dashboard.php') ?>">Go to Dashboard</a>
      <?php else: ?>
        <a href="<?= url('login.php') ?>" style="color:var(--ink);font-weight:600;font-size:13.5px">Log in</a>
        <a class="btn btn-primary btn-sm" href="<?= url('register.php') ?>">Join now for free</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<main>
  <?php foreach (getFlashes() as $f): ?>
    <div class="wrap" style="padding-top:18px">
      <div class="alert alert-<?= e($f['type']) ?>"><span><?= e($f['message']) ?></span></div>
    </div>
  <?php endforeach; ?>

<?php else:  ?>
<header class="public-header">
  <div class="wrap">
    <a class="brand" href="<?= url('index.php') ?>">
      <img src="<?= url('assets/img/logo.jpg') ?>" alt="Logo">
      <span class="brand-name"><?= e(SITE_NAME) ?></span>
    </a>
    <div class="topactions">
      <a href="<?= url('index.php') ?>" style="color:var(--ink);font-weight:600;font-size:13.5px">Back to home</a>
    </div>
  </div>
</header>
<main>
<?php endif; ?>
