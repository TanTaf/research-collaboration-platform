<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me = currentUser();

$types = [
    'student_academic' => ['cap',         'Student or Academic',  'Affiliated with a university, college, or research institute.'],
    'corporate'        => ['building',    'Corporate',            'Conducting research within a private company or industry R&D.'],
    'gov_ngo'          => ['bank',        'Gov or NGO',           'Working with government agencies, policy units, or non-profit organisations.'],
    'medical'          => ['stethoscope', 'Medical Professional', 'Doctors, nurses, and healthcare practitioners involved in clinical research.'],
    'non_researcher'   => ['user',        'Non-Researcher',       'Administrative staff, support roles, or individuals facilitating research activities.'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $type = (string)input('researcher_type', 'student_academic');
    if (!isset($types[$type])) { $type = 'student_academic'; }

    q('UPDATE users SET researcher_type = ? WHERE id = ?', [$type, $me['id']]);

    $picked = array_map('intval', (array)($_POST['interests'] ?? []));
    q('DELETE FROM user_interests WHERE user_id = ?', [$me['id']]);
    foreach (array_slice($picked, 0, 8) as $iid) {
        q('INSERT IGNORE INTO user_interests (user_id, interest_id) VALUES (?,?)', [$me['id'], $iid]);
    }

    setFlash('success', 'Great, your profile is set up. Add a photo and bio any time from My Profile.');
    redirect('dashboard.php');
}

$pageTitle = 'Choose your researcher type';
$layout    = 'blank';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="auth-wrap" style="align-items:flex-start;padding-top:40px">
  <div style="width:100%;max-width:760px">

    <div class="text-center mb-3">
      <h1 class="serif" style="color:var(--orange);font-size:30px">Choose Your Researcher Type</h1>
      <p class="muted" style="max-width:480px;margin:0 auto">Select the category that best describes your
        professional or academic affiliation to help us personalise your experience.</p>
    </div>

    <form method="post">
      <?= csrfField() ?>

      <div class="grid grid-2 mb-3">
        <?php foreach ($types as $key => [$ico, $title, $desc]): ?>
          <label class="type-card">
            <input type="radio" name="researcher_type" value="<?= e($key) ?>"
                   <?= $key === 'student_academic' ? 'checked' : '' ?>>
            <span class="ico"><?= icon($ico, 20) ?></span>
            <span>
              <h4><?= e($title) ?></h4>
              <p><?= e($desc) ?></p>
            </span>
          </label>
        <?php endforeach; ?>
      </div>

      <div class="card mb-3">
        <h3>Pick your research interests</h3>
        <p class="muted-sm">Choose up to 8. These power your recommendations on the Smart Matchmaking page.</p>
        <div class="tag-picker" data-max-select="8">
          <?php foreach (allInterests() as $i): ?>
            <label>
              <input type="checkbox" name="interests[]" value="<?= (int)$i['id'] ?>">
              <span><?= e($i['name']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="text-center">
        <button class="btn btn-primary btn-lg" type="submit">Continue <?= icon('arrow-right', 16) ?></button>
        <p class="mt-2"><a class="muted-sm" href="<?= url('dashboard.php') ?>">Skip for now</a></p>
      </div>
    </form>
  </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
