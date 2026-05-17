<?php
require __DIR__ . '/../includes/auth_check.php';
require __DIR__ . '/../config/db.php';

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
  header('Location: ../auth/login.php'); exit;
}

/* ---------- Helpers ---------- */
function redirect_self() {
  $qs = $_GET;
  $url = 'aircraft.php';
  if (!empty($qs)) $url .= '?' . http_build_query($qs);
  header("Location: $url"); exit; // IMPORTANT
}

/* ---------- CREATE / UPDATE / DELETE (run BEFORE any output) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'create') {
    $model = trim($_POST['model'] ?? '');
    $seats = max(1, (int)($_POST['seats'] ?? 0));
    if ($model && $seats) {
      $stmt = $pdo->prepare("INSERT INTO aircraft (model, seats) VALUES (?, ?)");
      $stmt->execute([$model, $seats]);
    }
    redirect_self();
  }

  if ($action === 'update') {
    $id    = (int)($_POST['id'] ?? 0);
    $model = trim($_POST['model'] ?? '');
    $seats = max(1, (int)($_POST['seats'] ?? 0));
    if ($id && $model && $seats) {
      $stmt = $pdo->prepare("UPDATE aircraft SET model = ?, seats = ? WHERE id = ?");
      $stmt->execute([$model, $seats, $id]);
    }
    redirect_self();
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
      try {
        $stmt = $pdo->prepare("DELETE FROM aircraft WHERE id = ?");
        $stmt->execute([$id]);
      } catch (Throwable $e) {
        // likely referenced by flights
        $_SESSION['flash_error'] = 'Cannot delete: aircraft is used by flights.';
      }
    }
    redirect_self();
  }
}

/* ---------- READ (still BEFORE output) ---------- */
$perPage = 10;
$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset  = ($page - 1) * $perPage;

$total = (int)$pdo->query("SELECT COUNT(*) FROM aircraft")->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$rows = $pdo->query("SELECT * FROM aircraft ORDER BY id DESC LIMIT $perPage OFFSET $offset")->fetchAll();

$edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;

/* ---------- NOW start output ---------- */
require __DIR__ . '/../includes/header.php';
?>
<h2>Aircraft</h2>

<?php if (!empty($_SESSION['flash_error'])): ?>
  <article class="secondary"><?= htmlspecialchars($_SESSION['flash_error']); ?></article>
  <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- Add form -->
<form method="post">
  <input type="hidden" name="action" value="create">
  <div class="grid">
    <label>Model <input name="model" required></label>
    <label>Seats <input type="number" name="seats" min="1" required></label>
  </div>
  <button type="submit" class="primary">Add Aircraft</button>
</form>

<hr>

<div class="table-wrapper">
  <table>
    <thead>
      <tr>
        <th>S.No</th>
        <th>ID</th>
        <th>Model</th>
        <th>Seats</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="5">No aircraft found.</td></tr>
      <?php else: $serial = $offset + 1; foreach ($rows as $r): ?>
        <?php if ($edit_id === (int)$r['id']): ?>
          <tr>
            <form method="post">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <td><?= $serial++ ?></td>
              <td><?= (int)$r['id'] ?></td>
              <td><input name="model" value="<?= htmlspecialchars($r['model']) ?>" required></td>
              <td><input type="number" name="seats" min="1" value="<?= (int)$r['seats'] ?>" required></td>
              <td>
                <div class="action-btns">
                  <button type="submit" class="btn primary sm">Save</button>
                  <a href="aircraft.php?page=<?= $page ?>" role="button" class="btn secondary sm">Cancel</a>
                </div>
              </td>
            </form>
          </tr>
        <?php else: ?>
          <tr>
            <td><?= $serial++ ?></td>
            <td><?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars($r['model']) ?></td>
            <td><?= (int)$r['seats'] ?></td>
            <td>
              <div class="action-btns">
                <a href="aircraft.php?page=<?= $page ?>&edit_id=<?= (int)$r['id'] ?>"
                   role="button" class="btn primary sm">Edit</a>
                <form class="inline" method="post" onsubmit="return confirm('Delete this aircraft?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button type="submit" class="btn danger sm">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      <?php endforeach; endif; ?>
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
