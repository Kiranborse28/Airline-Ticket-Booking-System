<?php
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/auth_check.php';
require __DIR__ . '/../config/db.php';

$uid = (int)$_SESSION['user']['id'];
$stmt = $pdo->prepare("SELECT name, email, created_at FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();
?>
<h2>My Profile</h2>
<article>
  <p><strong>Name:</strong> <?= htmlspecialchars($user['name']); ?></p>
  <p><strong>Email:</strong> <?= htmlspecialchars($user['email']); ?></p>
  <p><strong>Joined:</strong> <?= htmlspecialchars($user['created_at']); ?></p>
</article>
<?php require __DIR__ . '/../includes/footer.php'; ?>
