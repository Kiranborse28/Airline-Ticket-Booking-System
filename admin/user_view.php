<?php
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../config/db.php';

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    echo '<article>Admins only.</article>';
    require __DIR__ . '/../includes/footer.php'; exit;
}

$user_id = (int)($_GET['id'] ?? 0);
if ($user_id <= 0) {
    echo '<article>Invalid user.</article>';
    require __DIR__ . '/../includes/footer.php'; exit;
}

// Fetch user info
$stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo '<article>User not found.</article>';
    require __DIR__ . '/../includes/footer.php'; exit;
}

// Fetch user's bookings
$stmt = $pdo->prepare("
  SELECT b.*, f.flight_no, f.departure_time, f.arrival_time,
         a1.code AS from_code, a2.code AS to_code
  FROM bookings b
  JOIN flights f ON f.id = b.flight_id
  JOIN airports a1 ON a1.id = f.from_airport_id
  JOIN airports a2 ON a2.id = f.to_airport_id
  WHERE b.user_id = ?
  ORDER BY b.booked_at DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();
?>

<h2>User Details</h2>
<section>
  <p><strong>Name:</strong> <?= htmlspecialchars($user['name']); ?></p>
  <p><strong>Email:</strong> <?= htmlspecialchars($user['email']); ?></p>
  <p><strong>Joined:</strong> <?= htmlspecialchars($user['created_at']); ?></p>
  <p><strong>Role:</strong> <?= htmlspecialchars($user['role']); ?></p>
</section>

<hr>
<h3>Bookings by <?= htmlspecialchars($user['name']); ?></h3>

<?php if (!$bookings): ?>
  <article>No bookings found for this user.</article>
<?php else: ?>
  <div class="table-wrapper">
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Flight</th>
        <th>Route</th>
        <th>Departure</th>
        <th>Seats</th>
        <th>Total</th>
        <th>Status</th>
        <th>Booked At</th>
        <th>Invoice</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($bookings as $b): ?>
      <?php
        // Seat list
        $s = $pdo->prepare("SELECT seat_label FROM booking_seats WHERE booking_id = ? ORDER BY seat_label");
        $s->execute([$b['id']]);
        $seatText = implode(', ', array_map(fn($x) => $x['seat_label'], $s->fetchAll()));
        $inv = $base . '/flights/invoice.php?id=' . (int)$b['id'];
      ?>
      <tr>
        <td><?= (int)$b['id']; ?></td>
        <td><?= htmlspecialchars($b['flight_no']); ?></td>
        <td><?= htmlspecialchars($b['from_code']); ?> → <?= htmlspecialchars($b['to_code']); ?></td>
        <td><?= htmlspecialchars($b['departure_time']); ?></td>
        <td><?= htmlspecialchars($seatText ?: '—'); ?></td>
        <td>₹<?= number_format($b['total_price'], 2); ?></td>
        <td><?= htmlspecialchars($b['status']); ?></td>
        <td><?= htmlspecialchars($b['booked_at']); ?></td>
        <td><a class="btn" href="<?= $inv; ?>" target="_blank">Invoice</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
<?php endif; ?>

<p><a class="btn" href="users.php">← Back to Users</a></p>

<?php require __DIR__ . '/../includes/footer.php'; ?>
