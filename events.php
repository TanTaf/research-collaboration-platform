<?php

require_once __DIR__ . '/config/config.php';
requireLogin();

$me  = currentUser();
$uid = (int)$me['id'];

$canCreate = in_array($me['role'], ['faculty', 'admin'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string)input('action');
    $eid    = (int)input('event_id');

    if ($action === 'rsvp') {
        q('INSERT IGNORE INTO event_rsvps (event_id, user_id) VALUES (?,?)', [$eid, $uid]);
        setFlash('success', 'You are registered. See you there.');

    } elseif ($action === 'cancel') {
        q('DELETE FROM event_rsvps WHERE event_id = ? AND user_id = ?', [$eid, $uid]);
        setFlash('info', 'Registration cancelled.');

    } elseif ($action === 'create' && $canCreate) {
        $title = trim((string)input('title'));
        $date  = (string)input('event_date');
        if ($title !== '' && $date !== '') {
            q('INSERT INTO events (title, description, event_type, venue, event_date, capacity, created_by)
               VALUES (?,?,?,?,?,?,?)',
              [$title, input('description') ?: null, input('event_type') ?: 'Seminar',
               input('venue') ?: null, $date, (int)input('capacity', 100) ?: 100, $uid]);
            setFlash('success', 'Event published.');
        } else {
            setFlash('error', 'An event needs at least a title and a date.');
        }

    } elseif ($action === 'delete') {
        $ev = fetchOne('SELECT * FROM events WHERE id = ?', [$eid]);
        if ($ev && ((int)$ev['created_by'] === $uid || isAdmin())) {
            q('DELETE FROM events WHERE id = ?', [$eid]);
            setFlash('success', 'Event removed.');
        }
    }
    redirect('events.php');
}

$upcoming = fetchAll(
    'SELECT e.*, u.first_name, u.last_name,
            (SELECT COUNT(*) FROM event_rsvps r WHERE r.event_id = e.id) AS attendees
     FROM events e LEFT JOIN users u ON u.id = e.created_by
     WHERE e.event_date >= NOW() ORDER BY e.event_date');

$past = fetchAll(
    'SELECT e.*, (SELECT COUNT(*) FROM event_rsvps r WHERE r.event_id = e.id) AS attendees
     FROM events e WHERE e.event_date < NOW() ORDER BY e.event_date DESC LIMIT 6');

$myRsvps = array_map('intval', array_column(
    fetchAll('SELECT event_id FROM event_rsvps WHERE user_id = ?', [$uid]), 'event_id'));

$pageTitle = 'Events & Seminars';
require_once ROOT_PATH . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>Events &amp; Seminars</h1>
    <p class="sub">Workshops, seminars, datathons and proposal clinics hosted across UIU.</p>
  </div>
  <?php if ($canCreate): ?>
    <a class="btn btn-primary" href="#create-event"><?= icon('plus', 16) ?> Create Event</a>
  <?php endif; ?>
</div>

<div class="layout-main-side">
  <div>
    <h2 class="section-title">Upcoming</h2>

    <?php if (!$upcoming): ?>
      <div class="empty"><h3>Nothing scheduled</h3><p>Check back soon for new seminars and workshops.</p></div>
    <?php else: foreach ($upcoming as $ev):
        $going = in_array((int)$ev['id'], $myRsvps, true);
        $full  = (int)$ev['attendees'] >= (int)$ev['capacity']; ?>
      <div class="card mb-2">
        <div class="flex flex-wrap" style="gap:16px;align-items:flex-start">
          <div class="text-center" style="min-width:66px">
            <div style="background:var(--orange-soft);border-radius:10px;padding:9px 6px">
              <div style="font-size:20px;font-weight:800;color:var(--orange);line-height:1">
                <?= fmtDate($ev['event_date'], 'd') ?></div>
              <div style="font-size:11px;font-weight:700;color:var(--orange-dark);text-transform:uppercase">
                <?= fmtDate($ev['event_date'], 'M') ?></div>
            </div>
          </div>

          <div class="flex-1">
            <div class="flex mb-1" style="gap:8px">
              <span class="chip chip-blue"><?= e($ev['event_type']) ?></span>
              <span class="muted-sm"><?= icon('clock', 12) ?> <?= fmtDate($ev['event_date'], 'g:i A') ?></span>
            </div>
            <h3 style="font-family:var(--serif);font-size:18px;margin-bottom:6px"><?= e($ev['title']) ?></h3>
            <p class="muted-sm mb-1"><?= e($ev['description']) ?></p>
            <div class="flex muted-sm flex-wrap" style="gap:16px">
              <span><?= icon('pin', 12) ?> <?= e($ev['venue']) ?></span>
              <span><?= icon('users', 12) ?> <?= (int)$ev['attendees'] ?>/<?= (int)$ev['capacity'] ?> registered</span>
              <?php if ($ev['first_name']): ?>
                <span><?= icon('user', 12) ?> <?= e($ev['first_name'] . ' ' . $ev['last_name']) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="flex-col" style="gap:7px;min-width:124px">
            <form method="post"><?= csrfField() ?>
              <input type="hidden" name="event_id" value="<?= (int)$ev['id'] ?>">
              <input type="hidden" name="action" value="<?= $going ? 'cancel' : 'rsvp' ?>">
              <button class="btn <?= $going ? 'btn-outline' : 'btn-primary' ?> btn-sm btn-block"
                      type="submit" <?= (!$going && $full) ? 'disabled' : '' ?>>
                <?= $going ? 'Cancel RSVP' : ($full ? 'Full' : 'Register') ?>
              </button>
            </form>
            <?php if ((int)$ev['created_by'] === $uid || isAdmin()): ?>
              <form method="post"><?= csrfField() ?>
                <input type="hidden" name="event_id" value="<?= (int)$ev['id'] ?>">
                <input type="hidden" name="action" value="delete">
                <button class="btn btn-outline btn-sm btn-block" type="submit"
                        data-confirm="Delete this event?">Delete</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; endif; ?>

    <?php if ($past): ?>
      <h2 class="section-title mt-3">Past Events</h2>
      <div class="table-wrap">
        <table class="data">
          <thead><tr><th>Event</th><th>Type</th><th>Date</th><th>Attended</th></tr></thead>
          <tbody>
          <?php foreach ($past as $ev): ?>
            <tr>
              <td><strong><?= e($ev['title']) ?></strong></td>
              <td class="muted"><?= e($ev['event_type']) ?></td>
              <td class="muted"><?= fmtDate($ev['event_date'], 'd M Y') ?></td>
              <td class="muted"><?= (int)$ev['attendees'] ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="flex-col" style="gap:18px">
    <div class="card">
      <h5 class="eyebrow eyebrow-muted">My Registrations</h5>
      <?php
        $mine = array_filter($upcoming, fn($e) => in_array((int)$e['id'], $myRsvps, true));
        if (!$mine): ?>
        <p class="muted-sm mb-0">You have not registered for anything yet.</p>
      <?php else: foreach ($mine as $ev): ?>
        <div style="border-left:3px solid var(--orange);padding-left:11px;margin-bottom:13px">
          <strong style="font-size:13px;display:block"><?= e($ev['title']) ?></strong>
          <span class="muted-sm"><?= fmtDate($ev['event_date'], 'd M Y, g:i A') ?></span>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <?php if ($canCreate): ?>
      <form class="card" method="post" id="create-event">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="create">
        <h3>Create an event</h3>

        <div class="form-group">
          <label for="title">Title</label>
          <input type="text" id="title" name="title" required>
        </div>
        <div class="form-group">
          <label for="event_type">Type</label>
          <select id="event_type" name="event_type">
            <?php foreach (['Seminar','Workshop','Meetup','Panel','Clinic','Datathon'] as $t): ?>
              <option value="<?= $t ?>"><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="event_date">Date &amp; time</label>
          <input type="datetime-local" id="event_date" name="event_date" required>
        </div>
        <div class="form-group">
          <label for="venue">Venue</label>
          <input type="text" id="venue" name="venue" placeholder="e.g. UIU Auditorium, Level 5">
        </div>
        <div class="form-group">
          <label for="capacity">Capacity</label>
          <input type="number" id="capacity" name="capacity" min="1" value="60">
        </div>
        <div class="form-group">
          <label for="description">Description</label>
          <textarea id="description" name="description" style="min-height:80px"></textarea>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Publish Event</button>
      </form>
    <?php else: ?>
      <div class="card">
        <h5 class="eyebrow eyebrow-muted">Hosting an event?</h5>
        <p class="muted-sm mb-0">Faculty members and administrators can publish events here. Ask your supervisor
          to post it, or contact the admin.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
