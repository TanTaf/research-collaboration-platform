<?php

require_once __DIR__ . '/config/config.php';

$pageTitle = 'Advancing the world\'s research, together';
$layout    = 'public';

$stats = [
    'researchers'  => (int)fetchValue('SELECT COUNT(*) FROM users WHERE status = "active"'),
    'publications' => (int)fetchValue('SELECT COUNT(*) FROM publications WHERE status = "published"'),
    'projects'     => (int)fetchValue('SELECT COUNT(*) FROM projects'),
    'communities'  => (int)fetchValue('SELECT COUNT(*) FROM communities'),
];

$trending = fetchAll('SELECT i.name, COUNT(*) c FROM interests i
                      JOIN project_interests pi ON pi.interest_id = i.id
                      GROUP BY i.id ORDER BY c DESC LIMIT 3');

$latestProjects = fetchAll(
    'SELECT p.*, u.first_name, u.last_name, u.department
     FROM projects p JOIN users u ON u.id = p.owner_id
     WHERE p.is_open = 1 ORDER BY p.created_at DESC LIMIT 3');

$latestEvents = fetchAll('SELECT * FROM events WHERE event_date >= NOW() ORDER BY event_date LIMIT 3');

require_once ROOT_PATH . '/includes/header.php';
?>

<section class="hero">
  <div class="wrap">
    <div class="hero-grid">
      <div>
        <div class="eyebrow">Academic Network</div>
        <h1>Advancing the world's<br>research, <span class="text-orange">together.</span></h1>
        <p class="lead">Join UIU researchers sharing their findings, discovering new insights and building
          meaningful collaborations across departments and institutions.</p>
        <div class="flex">
          <a class="btn btn-primary btn-lg" href="<?= url('register.php') ?>">Join now for free</a>
          <a class="btn btn-outline btn-lg" href="<?= url('login.php') ?>">Log in <?= icon('arrow-right', 16) ?></a>
        </div>
      </div>

      <div class="hero-visual">
        <p class="quote">&ldquo;Collaborate. Innovate. Impact.&rdquo;<br>
          <span style="font-family:var(--sans);font-size:13px;opacity:.8">
            The research network of United International University
          </span></p>
        <div class="hero-float">
          <span class="stat-ico"><?= icon('trending', 18) ?></span>
          <span>
            <span class="k">Active Members</span><br>
            <span class="v"><?= $stats['researchers'] ?> Researchers</span>
          </span>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="searchband">
  <div class="wrap">
    <h2>Find what you need to advance your work</h2>
    <form class="bigsearch" action="<?= url('search.php') ?>" method="get">
      <input type="search" name="q" placeholder="Search researchers, publications, or ongoing projects...">
      <button class="btn btn-primary" type="submit">Search</button>
    </form>
    <p class="trending">Trending:
      <?php foreach ($trending as $t): ?>
        <a href="<?= url('search.php?q=' . urlencode($t['name'])) ?>"><?= e($t['name']) ?></a>
      <?php endforeach; ?>
    </p>
  </div>
</section>

<section class="feature-band" id="features">
  <div class="wrap">
    <div class="grid grid-3">
      <div class="feature-card">
        <div class="ico"><?= icon('search', 20) ?></div>
        <h3>Discover</h3>
        <p>Find the research you need to advance your work. Browse an open repository of papers, pre-prints
          and datasets uploaded by students and faculty across every department.</p>
        <div class="foot"><?= $stats['publications'] ?> Publications</div>
      </div>
      <div class="feature-card">
        <div class="ico"><?= icon('users', 20) ?></div>
        <h3>Connect</h3>
        <p>Build your network and collaborate with peers. Message researchers directly, join thematic
          communities, and find co-authors for your next paper or final year design project.</p>
        <div class="foot"><?= $stats['researchers'] ?> Researchers</div>
      </div>
      <div class="feature-card">
        <div class="ico"><?= icon('activity', 20) ?></div>
        <h3>Measure</h3>
        <p>Track your impact with real-time statistics. Monitor how your work is being viewed, downloaded
          and discussed across the academic community.</p>
        <div class="foot">Real-time Metrics</div>
      </div>
    </div>
  </div>
</section>

<section class="statband">
  <div class="wrap">
    <div class="grid grid-3">
      <div class="item"><div class="n"><?= $stats['researchers'] ?>+</div><div class="l">Active Researchers</div></div>
      <div class="item"><div class="n"><?= $stats['publications'] ?>+</div><div class="l">Total Publications</div></div>
      <div class="item"><div class="n"><?= $stats['projects'] ?>+</div><div class="l">Ongoing Projects</div></div>
    </div>
  </div>
</section>

<section class="feature-band" id="network">
  <div class="wrap">
    <div class="flex-between mb-3">
      <h2 style="margin:0">Open research opportunities</h2>
      <a class="card-link" href="<?= url('opportunities.php') ?>">Browse all <?= icon('arrow-right', 13) ?></a>
    </div>
    <div class="grid grid-3">
      <?php foreach ($latestProjects as $p): ?>
        <a class="card card-hover" href="<?= url('login.php') ?>" style="text-decoration:none;color:inherit">
          <div class="flex-between mb-1">
            <span class="chip chip-orange-soft"><?= e($p['project_type']) ?></span>
            <span class="muted-sm"><?= $p['deadline'] ? 'Due ' . fmtDate($p['deadline']) : '' ?></span>
          </div>
          <h3 style="font-family:var(--serif);font-size:17px"><?= e($p['title']) ?></h3>
          <p class="muted" style="font-size:13px"><?= e(excerpt($p['summary'], 110)) ?></p>
          <div class="muted-sm"><?= icon('user', 13) ?> <?= e($p['first_name'] . ' ' . $p['last_name']) ?>
            &middot; <?= e($p['department']) ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($latestEvents): ?>
<section class="ctaband" id="events" style="padding-bottom:20px">
  <div class="wrap">
    <div class="flex-between mb-3">
      <h2 style="margin:0">Upcoming events &amp; seminars</h2>
      <a class="card-link" href="<?= url('events.php') ?>">See all <?= icon('arrow-right', 13) ?></a>
    </div>
    <div class="grid grid-3">
      <?php foreach ($latestEvents as $ev): ?>
        <div class="card">
          <span class="chip chip-blue"><?= e($ev['event_type']) ?></span>
          <h3 class="mt-2" style="font-size:16px"><?= e($ev['title']) ?></h3>
          <p class="muted-sm mb-2"><?= icon('calendar', 13) ?> <?= fmtDate($ev['event_date'], 'd M Y, g:i A') ?></p>
          <p class="muted-sm"><?= icon('pin', 13) ?> <?= e($ev['venue']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="ctaband" id="repository">
  <div class="wrap">
    <div class="ctabox">
      <div class="eyebrow">Join the hub</div>
      <h2>Ready to amplify your academic impact?</h2>
      <p>Create your research profile today and start collaborating with the best minds in your field.
        It is free, and always will be, for UIU researchers.</p>
      <a class="btn btn-primary btn-lg" href="<?= url('register.php') ?>">Join now for free</a>
      <p class="muted-sm mt-3 mb-0">Already have an account? <a href="<?= url('login.php') ?>">Log in here</a></p>
    </div>
  </div>
</section>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
