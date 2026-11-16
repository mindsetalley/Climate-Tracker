<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM countries WHERE id = ?');
$stmt->execute([$id]);
$country = $stmt->fetch();
if (!$country) not_found('Country not found.');

$types  = ['flood', 'drought', 'heatwave', 'cyclone', 'wildfire', 'sea_level', 'other'];
$errors = [];
$self   = url('admin/country_edit.php?id=' . $id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $raw     = trim($_POST['impact_score'] ?? '');
        $summary = trim($_POST['summary'] ?? '');
        $score   = null;
        if ($raw !== '') {
            if (ctype_digit($raw) && (int)$raw <= 100) $score = (int)$raw;
            else $errors[] = 'Score must be a whole number from 0 to 100.';
        }
        if (!$errors) {
            $pdo->prepare('UPDATE countries SET impact_score = ?, summary = ? WHERE id = ?')
                ->execute([$score, $summary !== '' ? $summary : null, $id]);
            set_flash('Country updated.');
            header('Location: ' . $self);
            exit;
        }
        $country['impact_score'] = $raw;
        $country['summary']      = $summary;

    } elseif ($action === 'add_event') {
        $type = $_POST['event_type'] ?? '';
        $date = trim($_POST['event_date'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if (!in_array($type, $types, true)) $errors[] = 'Choose a valid event type.';
        if ($desc === '') $errors[] = 'Event description is required.';
        if ($date !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $date);
            if (!$d || $d->format('Y-m-d') !== $date) $errors[] = 'Enter a valid event date.';
        }
        if (!$errors) {
            $pdo->prepare('INSERT INTO climate_events (country_id, event_type, event_date, description) VALUES (?,?,?,?)')
                ->execute([$id, $type, $date !== '' ? $date : null, $desc]);
            set_flash('Event added.');
            header('Location: ' . $self);
            exit;
        }

    } elseif ($action === 'delete_event') {
        $pdo->prepare('DELETE FROM climate_events WHERE id = ? AND country_id = ?')
            ->execute([(int)($_POST['event_id'] ?? 0), $id]);
        set_flash('Event deleted.');
        header('Location: ' . $self);
        exit;
    }
}

$stmt = $pdo->prepare('SELECT id, event_type, event_date, description FROM climate_events WHERE country_id = ? ORDER BY event_date DESC');
$stmt->execute([$id]);
$events = $stmt->fetchAll();

$pageTitle = 'Edit ' . $country['name'];
require __DIR__ . '/../includes/header.php';
?>
<section class="container section">
  <h1>Edit: <?= e($country['name']) ?></h1>
  <?php require __DIR__ . '/_nav.php'; ?>
  <?php foreach ($errors as $err): ?><div class="alert"><?= e($err) ?></div><?php endforeach; ?>

  <form method="post" class="form wide">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <label>Impact score <small>(0–100, blank = no data)</small>
      <input type="number" name="impact_score" min="0" max="100" value="<?= e((string)($country['impact_score'] ?? '')) ?>"></label>
    <label>Summary <textarea name="summary" rows="4"><?= e($country['summary']) ?></textarea></label>
    <button class="btn" type="submit">Save</button>
  </form>

  <h2>Climate events</h2>
  <div class="table-wrap">
  <table class="table">
    <tr><th>Type</th><th>Date</th><th>Description</th><th></th></tr>
    <?php foreach ($events as $ev): ?>
      <tr>
        <td><?= e(event_label($ev['event_type'])) ?></td>
        <td><?= e($ev['event_date'] ?? '—') ?></td>
        <td><?= e($ev['description']) ?></td>
        <td>
          <form method="post" onsubmit="return confirm('Delete this event?')">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete_event">
            <input type="hidden" name="event_id" value="<?= (int)$ev['id'] ?>">
            <button class="link-btn danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  </div>

  <h3>Add event</h3>
  <form method="post" class="form wide">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_event">
    <label>Type
      <select name="event_type">
        <?php foreach ($types as $t): ?><option value="<?= e($t) ?>"><?= e(event_label($t)) ?></option><?php endforeach; ?>
      </select></label>
    <label>Date <input type="date" name="event_date"></label>
    <label>Description <textarea name="description" rows="3" required></textarea></label>
    <button class="btn" type="submit">Add event</button>
  </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
