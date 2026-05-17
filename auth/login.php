<?php
session_start();
require __DIR__ . '/../config/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $errors[] = 'Invalid credentials.';
    } else {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        // ✅ Role-based redirection
        if ($user['role'] === 'admin') {
            header('Location: ../admin/index.php');
        } else {
            header('Location: ../index.php');
        }
        exit;
    }
}

// After logic, include header (HTML)
require __DIR__ . '/../includes/header.php';
?>
<h2>Login</h2>
<?php foreach ($errors as $e): ?>
  <article class="secondary"><?php echo htmlspecialchars($e); ?></article>
<?php endforeach; ?>

<form method="post">
  <label>Email <input type="email" name="email" required></label>
  <label>Password <input type="password" name="password" required></label>
  <button type="submit">Login</button>
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>
