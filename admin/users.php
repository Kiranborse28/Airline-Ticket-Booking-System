<?php
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../config/db.php';

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    echo '<article>Admins only.</article>';
    require __DIR__ . '/../includes/footer.php'; exit;
}

// Fetch all users except admins
$stmt = $pdo->query("SELECT id, name, email, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>

<h2>Registered Users</h2>

<?php if (!$users): ?>
  <article>No users found.</article>
<?php else: ?>
  <div class="table-wrapper">
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Name</th>
        <th>Email</th>
        <th>Joined</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= (int)$u['id']; ?></td>
        <td><?= htmlspecialchars($u['name']); ?></td>
        <td><?= htmlspecialchars($u['email']); ?></td>
        <td><?= htmlspecialchars($u['created_at']); ?></td>
        <td>
          <a class="btn" href="user_view.php?id=<?= (int)$u['id']; ?>">View Details</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
