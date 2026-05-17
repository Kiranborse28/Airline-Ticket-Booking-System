<?php
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/auth_check.php';
require __DIR__ . '/../config/db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $current = $_POST['current'] ?? '';
  $new     = $_POST['new'] ?? '';
  $confirm = $_POST['confirm'] ?? '';

  if ($new === '' || $confirm === '' || $current === '') $errors[] = 'All fields are required.';
  if ($new !== $confirm) $errors[] = 'New passwords do not match.';

  if (!$errors) {
    $uid = (int)$_SESSION['user']['id'];
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($current, $hash)) {
      $errors[] = 'Current password is incorrect.';
    } else {
      $newHash = password_hash($new, PASSWORD_DEFAULT);
      $upd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
      $upd->execute([$newHash, $uid]);
      $success = 'Password updated successfully.';
    }
  }
}
?>
<h2>Change Password</h2>
<?php foreach ($errors as $e): ?><article class="secondary"><?= htmlspecialchars($e); ?></article><?php endforeach; ?>
<?php if ($success): ?><article class="success"><?= htmlspecialchars($success); ?></article><?php endif; ?>
<form method="post">
  <label>Current Password
    <input type="password" name="current" required>
  </label>
  <label>New Password
    <input type="password" name="new" required>
  </label>
  <label>Confirm New Password
    <input type="password" name="confirm" required>
  </label>
  <button type="submit">Update Password</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
