<?php

require_once dirname(__DIR__) . '/config/config.php';
requireRole('admin');

$me = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (input('action') === 'delete') {
        q('DELETE FROM events WHERE id = ?', [(int)input('event_id')]);
        setFlash('success', 'Event deleted.');

    } elseif (input('action') === 'create') {
        $title = trim((string)input('title'));
        $date  = (string)input('event_date');
        if ($title !== '' && $date !== '') {
            q('INSERT INTO events (title, description, event_type, venue, event_date, capacity, created_by)
               VALUES (?,?,?,?,?,?,?)',
              [$title, input('description') ?: null, input('event_type') ?: 'Seminar',
               input('venue') ?: null, $date, (int)input('capacity', 100) ?: 100, $me['id']]);
            setFlash('success', 'Event published.');
        } else {
            setFlash('error', 'An event needs a title and a date.');
        }
    }
    redirect('admin/events.php');
}

$events = fetchAll(
    'SELECT e.*, u.first_name, u.last_name,
       (SELECT COUNT(*) FROM event_rsvps r WHERE r.event_id = e.id) AS attendees
     FROM events e LEFT JOIN users u ON u.id = e.created_by
     ORDER BY e.event_date DESC');

$pageTitle = 'Manage Events';
require_once ROOT_PATH . '/includes/header.php';
require_once __DIR__ . '/nav.php';
?>

<div class="page-head">
  <div>
    <h1>Manage Events</h1>
    <p class="sub"><?= count($events) ?> event<?= count($events) === 1 ? '' : 's' ?> on record.</p>
  </div>
</div>

<div class="layout-main-side">
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>Event</th><th>Type</th><th>When</th><th>Venue</th><th>RSVPs</th><th>Host</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($events as $ev): $past = strtotime($ev['event_date']) < time(); ?>
        <tr style="<?= $past ? 'opacity:.62' : '' ?>">
          <td>
            <strong style="font-size:13px"><?= e($ev['title']) ?></strong>
            <div class="muted-sm"><?= e(excerpt($ev['description'], 60)) ?></div>
          </td>
          <td class="muted"><?= e($ev['event_type']) ?></td>
          <td class="muted nowrap"><?= fmtDate($ev['event_date'], 'd M Y, g:i A') ?>
            <?= $past ? '<div class="muted-sm">past</div>' : '' ?></td>
          <td class="muted"><?= e($ev['venue']) ?></td>
          <td class="muted"><?= (int)$ev['attendees'] ?> / <?= (int)$ev['capacity'] ?></td>
          <td class="muted"><?= e(trim(($ev['first_name'] ?? '') . ' ' . ($ev['last_name'] ?? '')) ?: '--') ?></td>
          <td>
            <form method="post"><?= csrfField() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="event_id" value="<?= (int)$ev['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit"
                      data-confirm="Delete this event?"><?= icon('trash', 13) ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <form class="card" method="post">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="create">
    <h3>Publish an event</h3>

    <div class="form-group"><label for="title">Title</label>
      <input type="text" id="title" name="title" required></div>
    <div class="form-group"><label for="event_type">Type</label>
      <select id="event_type" name="event_type">
        <?php foreach (['Seminar','Workshop','Meetup','Panel','Clinic','Datathon'] as $t): ?>
          <option value="<?= $t ?>"><?= $t ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="form-group"><label for="event_date">Date &amp; time</label>
      <input type="datetime-local" id="event_date" name="event_date" required></div>
    <div class="form-group"><label for="venue">Venue</label>
      <input type="text" id="venue" name="venue"></div>
    <div class="form-group"><label for="capacity">Capacity</label>
      <input type="number" id="capacity" name="capacity" min="1" value="100"></div>
    <div class="form-group"><label for="description">Description</label>
      <textarea id="description" name="description" style="min-height:90px"></textarea></div>

    <button class="btn btn-primary btn-block" type="submit">Publish Event</button>
  </form>
</div>

<?php require_once ROOT_PATH . '/includes/footer.php'; ?>
