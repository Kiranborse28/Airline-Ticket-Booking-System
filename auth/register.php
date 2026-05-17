<?php
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../config/db.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($name === '' || $email === '' || $password === '') $errors[] = 'All fields are required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
    if (!$errors) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = 'Email already registered.';
    }
    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'user')");
        $stmt->execute([$name, $email, $hash]);
        echo '<article class="success">Registration successful. <a href="login.php">Login</a></article>';
    }
}
?>
<h2>Create account</h2>
<?php foreach ($errors as $e): ?><article class="secondary"><?php echo htmlspecialchars($e); ?></article><?php endforeach; ?>
<form method="post">
  <label>Name <input name="name" required></label>
  <label>Email <input type="email" name="email" required></label>
  <label>Password <input type="password" name="password" required></label>
  <button type="submit">Register</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
