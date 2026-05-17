<?php
require __DIR__ . '/../includes/auth_check.php';
require __DIR__ . '/../config/db.php';

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
  header('Location: ../auth/login.php'); exit;
}

/* ---------- Helpers ---------- */
function redirect_self() {
  $qs = $_GET;
  $url = 'airports.php';
  if (!empty($qs)) $url .= '?' . http_build_query($qs);
  header("Location: $url");
  exit; // IMPORTANT
}

/* ---------- CREATE / UPDATE / DELETE (run BEFORE any output) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'create') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    if ($code && $name) {
      try {
        $stmt = $pdo->prepare("INSERT INTO airports (code, name) VALUES (?, ?)");
        $stmt->execute([$code, $name]);
      } catch (PDOException $e) {
        // This stops the fatal error if a duplicate 'code' is entered
      }
    }
    redirect_self();
  }

  if ($action === 'update') {
    $id   = (int)($_POST['id'] ?? 0);
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $name = trim($_POST['name'] ?? '');
    if ($id && $code && $name) {
      $stmt = $pdo->prepare("UPDATE airports SET code = ?, name = ? WHERE id = ?");
      $stmt->execute([$code, $name, $id]);
    }
    redirect_self();
  }

  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
      $stmt = $pdo->prepare("DELETE FROM airports WHERE id = ?");
      $stmt->execute([$id]);
    }
    redirect_self();
  }
}

/* ---------- READ (still BEFORE output) ---------- */
$perPage = 10;
$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset  = ($page - 1) * $perPage;

$total = (int)$pdo->query("SELECT COUNT(*) FROM airports")->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$stmt = $pdo->query("SELECT * FROM airports ORDER BY id DESC LIMIT $perPage OFFSET $offset");
$rows = $stmt->fetchAll();

$edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;

/* ---------- NOW start output ---------- */
require __DIR__ . '/../includes/header.php';
?>
<h2>Airports</h2>

<form method="post">
  <input type="hidden" name="action" value="create">
  <div class="grid">
    <label>Code <input name="code" maxlength="3" required></label>
    <label>Name <input name="name" required></label>
  </div>
  <button type="submit" class="primary">Add Airport</button>
</form>

<hr>

<div class="table-wrapper">
  <table>
    <thead>
      <tr>
        <th>S.No</th>
        <th>Code</th>
        <th>Name</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="4">No airports found.</td></tr>
      <?php else: $serial = $offset + 1; foreach ($rows as $r): ?>
        <?php if ($edit_id === (int)$r['id']): ?>
          <tr>
            <form method="post">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <td><?= $serial++ ?></td>
              <td><input name="code" value="<?= htmlspecialchars($r['code']) ?>" maxlength="3" required></td>
              <td><input name="name" value="<?= htmlspecialchars($r['name']) ?>" required></td>
              <td>
                <div class="action-btns">
                  <button type="submit" class="btn primary sm">Save</button>
                  <a href="airports.php?page=<?= $page ?>" role="button" class="btn secondary sm">Cancel</a>
                </div>
              </td>
            </form>
          </tr>
        <?php else: ?>
          <tr>
            <td><?= $serial++ ?></td>
            <td><?= htmlspecialchars($r['code']) ?></td>
            <td><?= htmlspecialchars($r['name']) ?></td>
            <td>
              <div class="action-btns">
                <a href="airports.php?page=<?= $page ?>&edit_id=<?= (int)$r['id'] ?>"
                   role="button" class="btn primary sm">Edit</a>
                <form class="inline" method="post" onsubmit="return confirm('Delete this airport?');">
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
