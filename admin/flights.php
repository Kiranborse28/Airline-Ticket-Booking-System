<?php
require __DIR__ . '/../includes/auth_check.php';
require __DIR__ . '/../config/db.php';

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
  header('Location: ../auth/login.php'); exit;
}

/* ---------- Helper for redirect ---------- */
function redirect_self() {
  $qs = $_GET; $url = 'flights.php';
  if (!empty($qs)) $url .= '?' . http_build_query($qs);
  header("Location: $url"); exit;
}

/* ---------- Lookup lists ---------- */
$airports = $pdo->query("SELECT id, code FROM airports ORDER BY code")->fetchAll();
$aircraft = $pdo->query("SELECT id, model, seats FROM aircraft ORDER BY id DESC")->fetchAll();

/* ---------- POST HANDLERS ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  /* CREATE */
  if ($action === 'create') {
    $flight_no = trim($_POST['flight_no'] ?? '');
    $from_id = (int)($_POST['from_airport_id'] ?? 0);
    $to_id   = (int)($_POST['to_airport_id'] ?? 0);
    $air_id  = (int)($_POST['aircraft_id'] ?? 0);
    $dep     = $_POST['departure_time'] ?? '';
    $arr     = $_POST['arrival_time'] ?? '';
    $price   = (float)($_POST['base_price'] ?? 0);

    if ($flight_no && $from_id && $to_id && $air_id && $dep && $arr && $price > 0) {
      $st = $pdo->prepare("SELECT seats FROM aircraft WHERE id = ?");
      $st->execute([$air_id]);
      $seats_total = (int)$st->fetchColumn();

      $ins = $pdo->prepare("
        INSERT INTO flights 
        (flight_no, from_airport_id, to_airport_id, aircraft_id, departure_time, arrival_time, base_price, seats_available)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $ins->execute([$flight_no, $from_id, $to_id, $air_id, $dep, $arr, $price, $seats_total]);
    }
    redirect_self();
  }

  /* UPDATE */
  if ($action === 'update') {
    $id      = (int)($_POST['id'] ?? 0);
    $flight_no = trim($_POST['flight_no'] ?? '');
    $from_id = (int)($_POST['from_airport_id'] ?? 0);
    $to_id   = (int)($_POST['to_airport_id'] ?? 0);
    $air_id  = (int)($_POST['aircraft_id'] ?? 0);
    $dep     = $_POST['departure_time'] ?? '';
    $arr     = $_POST['arrival_time'] ?? '';
    $price   = (float)($_POST['base_price'] ?? 0);

    if ($id && $flight_no && $from_id && $to_id && $air_id && $dep && $arr && $price > 0) {
      $curAir = $pdo->prepare("SELECT aircraft_id FROM flights WHERE id = ?");
      $curAir->execute([$id]);
      $old_air_id = (int)$curAir->fetchColumn();

      $upd = $pdo->prepare("
        UPDATE flights 
        SET flight_no=?, from_airport_id=?, to_airport_id=?, aircraft_id=?, departure_time=?, arrival_time=?, base_price=? 
        WHERE id=?
      ");
      $upd->execute([$flight_no, $from_id, $to_id, $air_id, $dep, $arr, $price, $id]);

      if ($old_air_id !== $air_id) {
        $st = $pdo->prepare("SELECT seats FROM aircraft WHERE id = ?");
        $st->execute([$air_id]);
        $new_total = (int)$st->fetchColumn();

        $booked = $pdo->prepare("SELECT COUNT(*) FROM booking_seats WHERE flight_id = ?");
        $booked->execute([$id]);
        $occupied = (int)$booked->fetchColumn();

        $new_available = max(0, $new_total - $occupied);
        $pdo->prepare("UPDATE flights SET seats_available = ? WHERE id = ?")->execute([$new_available, $id]);
      }
    }
    redirect_self();
  }

  /* DELETE */
  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
      try {
        $pdo->prepare("DELETE FROM flights WHERE id = ?")->execute([$id]);
      } catch (Throwable $e) {
        echo '<article class="secondary">Cannot delete flight (has bookings).</article>'; exit;
      }
    }
    redirect_self();
  }
}

/* ---------- FETCH for display ---------- */
$perPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

$total = (int)$pdo->query("SELECT COUNT(*) FROM flights")->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$rows = $pdo->query("
  SELECT f.*, a1.code AS from_code, a2.code AS to_code, ac.model AS aircraft_model
  FROM flights f
  JOIN airports a1 ON a1.id = f.from_airport_id
  JOIN airports a2 ON a2.id = f.to_airport_id
  JOIN aircraft ac ON ac.id = f.aircraft_id
  ORDER BY f.id DESC
  LIMIT $perPage OFFSET $offset
")->fetchAll();

$edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;

/* ---------- OUTPUT STARTS HERE ---------- */
require __DIR__ . '/../includes/header.php';
?>
<h2>Flights</h2>

<!-- CREATE FORM -->
<form method="post">
  <input type="hidden" name="action" value="create">
  <div class="grid">
    <label>Flight No <input name="flight_no" required></label>
    <label>From
      <select name="from_airport_id" required>
        <option value="">--</option>
        <?php foreach ($airports as $a): ?>
          <option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['code']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>To
      <select name="to_airport_id" required>
        <option value="">--</option>
        <?php foreach ($airports as $a): ?>
          <option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['code']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
  <div class="grid">
    <label>Aircraft
      <select name="aircraft_id" required>
        <option value="">--</option>
        <?php foreach ($aircraft as $ac): ?>
          <option value="<?= (int)$ac['id'] ?>"><?= htmlspecialchars($ac['model']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Departure <input type="datetime-local" name="departure_time" required></label>
    <label>Arrival <input type="datetime-local" name="arrival_time" required></label>
    <label>Base Price (₹) <input type="number" step="0.01" name="base_price" required></label>
  </div>
  <button type="submit" class="primary">Add Flight</button>
</form>

<hr>

<div class="table-wrapper">
  <table>
    <thead>
      <tr>
        <th>S.No</th><th>ID</th><th>Flight</th><th>From</th><th>To</th>
        <th>Depart</th><th>Arrive</th><th>Aircraft</th><th>Price</th><th>Seats Avail.</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="11">No flights found.</td></tr>
      <?php else: $serial = $offset + 1; foreach ($rows as $r): ?>
        <?php if ($edit_id === (int)$r['id']): ?>
          <tr>
            <form method="post">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <td><?= $serial++ ?></td>
              <td><?= (int)$r['id'] ?></td>
              <td><input name="flight_no" value="<?= htmlspecialchars($r['flight_no']) ?>" required></td>
              <td>
                <select name="from_airport_id" required>
                  <?php foreach ($airports as $a): ?>
                    <option value="<?= (int)$a['id'] ?>" <?= $a['id']==$r['from_airport_id']?'selected':''; ?>>
                      <?= htmlspecialchars($a['code']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td>
                <select name="to_airport_id" required>
                  <?php foreach ($airports as $a): ?>
                    <option value="<?= (int)$a['id'] ?>" <?= $a['id']==$r['to_airport_id']?'selected':''; ?>>
                      <?= htmlspecialchars($a['code']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td><input type="datetime-local" name="departure_time" value="<?= str_replace(' ', 'T', htmlspecialchars($r['departure_time'])) ?>" required></td>
              <td><input type="datetime-local" name="arrival_time" value="<?= str_replace(' ', 'T', htmlspecialchars($r['arrival_time'])) ?>" required></td>
              <td>
                <select name="aircraft_id" required>
                  <?php foreach ($aircraft as $ac): ?>
                    <option value="<?= (int)$ac['id'] ?>" <?= $ac['id']==$r['aircraft_id']?'selected':''; ?>>
                      <?= htmlspecialchars($ac['model']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td><input type="number" step="0.01" name="base_price" value="<?= htmlspecialchars($r['base_price']) ?>" required></td>
              <td><?= (int)$r['seats_available'] ?></td>
              <td>
                <div class="action-btns">
                  <button type="submit" class="btn primary sm">Save</button>
                  <a href="flights.php?page=<?= $page ?>" role="button" class="btn secondary sm">Cancel</a>
                </div>
              </td>
            </form>
          </tr>
        <?php else: ?>
          <tr>
            <td><?= $serial++ ?></td>
            <td><?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars($r['flight_no']) ?></td>
            <td><?= htmlspecialchars($r['from_code']) ?></td>
            <td><?= htmlspecialchars($r['to_code']) ?></td>
            <td><?= htmlspecialchars($r['departure_time']) ?></td>
            <td><?= htmlspecialchars($r['arrival_time']) ?></td>
            <td><?= htmlspecialchars($r['aircraft_model']) ?></td>
            <td>₹<?= number_format($r['base_price'], 2) ?></td>
            <td><?= (int)$r['seats_available'] ?></td>
            <td>
              <div class="action-btns">
                <a href="flights.php?page=<?= $page ?>&edit_id=<?= (int)$r['id'] ?>"
                   role="button" class="btn primary sm">Edit</a>
                <form class="inline" method="post" onsubmit="return confirm('Delete this flight?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button type="submit" class="btn danger sm">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endif; endforeach; endif; ?>
    </tbody>
  </table>
</div>

<nav aria-label="Pagination" style="display:flex; gap:.5rem; justify-content:center; margin-top:1rem;">
  <a class="contrast btn" href="?page=1" <?= $page<=1?'aria-disabled="true"':''; ?>>« First</a>
  <a class="contrast btn" href="?page=<?= max(1,$page-1) ?>" <?= $page<=1?'aria-disabled="true"':''; ?>>‹ Prev</a>
  <span class="muted" style="align-self:center;">Page <?= $page ?> of <?= $totalPages ?></span>
  <a class="contrast btn" href="?page=<?= min($totalPages,$page+1) ?>" <?= $page>=$totalPages?'aria-disabled="true"':''; ?>>Next ›</a>
  <a class="contrast btn" href="?page=<?= $totalPages ?>" <?= $page>=$totalPages?'aria-disabled="true"':''; ?>>Last »</a>
</nav>

<?php require __DIR__ . '/../includes/footer.php'; ?>
