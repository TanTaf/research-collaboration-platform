<?php

require_once __DIR__ . '/config/config.php';

$slug = (string)input('p', 'support');

$pages = [
    'ethics' => ['Ethical Guidelines', [
        'Research integrity' => 'Every piece of work shared on this hub must be your own, or must clearly credit the people whose work it builds on. Fabricating, falsifying or selectively reporting data is a serious academic offence and will result in the account being suspended.',
        'Human participants' => 'If your study involves human participants, you must have approval from your department before collecting data, and you must not upload anything that could identify a participant.',
        'Data you do not own' => 'Do not upload datasets, papers or figures that you do not have the right to share. If a dataset has a licence, follow it, and state the licence on the publication record.',
        'Authorship' => 'List everyone who made a substantive contribution, and no one who did not. Disputes about authorship should be settled before a paper is uploaded, not after.',
    ]],
    'peer-review' => ['Peer Review Policy', [
        'Who reviews' => 'Publications marked as pre-prints are open for community comment. Faculty members may be invited to give a structured review on any paper hosted here.',
        'How to comment' => 'Comments should be specific and actionable. Point at the section, describe the problem, and where you can, suggest what would fix it.',
        'Conflicts of interest' => 'Do not review work by your own supervisor, your own team, or anyone you are collaborating with on a live project.',
        'Tone' => 'Criticise the work, never the person. A review that would embarrass you if the author read it aloud is not a review, it is a complaint.',
    ]],
    'terms' => ['Terms of Service', [
        'Who can register' => 'Accounts are for students, faculty and staff of United International University, and for invited external collaborators.',
        'Your account' => 'You are responsible for what is posted from your account. Do not share your password, and tell the administrator immediately if you think someone else has used your account.',
        'Content you post' => 'You keep the rights to everything you upload. By uploading it you allow other members of the hub to read, download and cite it.',
        'Suspension' => 'Accounts that break the ethical guidelines, harass other members, or upload material they do not own may be suspended without notice.',
    ]],
    'support' => ['Support', [
        'Forgotten password' => 'Password reset by email is not enabled on this installation. Contact the platform administrator, who can reset it for you from the admin panel.',
        'Something is broken' => 'If a page throws an error, note what you were doing and send it to the administrator. Include the page address.',
        'Account requests' => 'To add a department, change your role from student to faculty, or merge duplicate accounts, contact the administrator.',
        'Contact' => 'Administrator: admin@rchuiu.edu &middot; United International University, United City, Madani Avenue, Badda, Dhaka 1212.',
    ]],
];

if (!isset($pages[$slug])) { $slug = 'support'; }
[$title, $sections] = $pages[$slug];

$pageTitle = $title;
$layout    = isLoggedIn() ? 'app' : 'public';
require_once ROOT_PATH . '/includes/header.php';
?>

<?php if ($layout === 'public'): ?><div class="wrap" style="padding-top:40px;padding-bottom:50px"><?php endif; ?>

<div class="page-head">
  <div>
    <h1><?= e($title) ?></h1>
    <p class="sub">Last updated <?= date('F Y') ?> &middot; <?= e(SITE_NAME) ?></p>
  </div>
</div>

<div class="card" style="max-width:820px">
  <?php foreach ($sections as $heading => $body): ?>
    <h3><?= e($heading) ?></h3>
    <p class="muted" style="line-height:1.85;margin-bottom:26px"><?= $body ?></p>
  <?php endforeach; ?>

  <div class="flex" style="gap:8px;border-top:1px solid var(--line);padding-top:18px">
    <?php foreach ($pages as $k => [$t, $_]): ?>
      <a class="chip <?= $k === $slug ? 'chip-orange' : '' ?>" href="<?= url('page.php?p=' . $k) ?>"><?= e($t) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<?php if ($layout === 'public'): ?></div><?php endif; ?>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
