<?php
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../config/db.php';

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
  echo '<article>Admins only.</article>';
  require __DIR__ . '/../includes/footer.php'; exit;
}

/* ---- Pagination (latest 10) ---- */
$perPage = 10;
$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset  = ($page - 1) * $perPage;

/* ---- Count total ---- */
$total = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

/* ---- Fetch bookings for current page ---- */
$stmt = $pdo->prepare("
  SELECT b.*, u.name AS user_name, u.email AS user_email,
         f.flight_no, f.departure_time, f.arrival_time,
         a1.code AS from_code, a2.code AS to_code
  FROM bookings b
  JOIN users u   ON u.id = b.user_id
  JOIN flights f ON f.id = b.flight_id
  JOIN airports a1 ON a1.id = f.from_airport_id
  JOIN airports a2 ON a2.id = f.to_airport_id
  ORDER BY b.booked_at DESC
  LIMIT $perPage OFFSET $offset
");
$stmt->execute();
$rows = $stmt->fetchAll();
?>
<h2>All Bookings</h2>

<?php if (!$rows): ?>
  <article>No bookings yet.</article>
<?php else: ?>
  <p class="muted">Showing
    <strong><?= $total ? ($offset + 1) : 0 ?></strong>–
    <strong><?= min($offset + $perPage, $total) ?></strong> of
    <strong><?= $total ?></strong>
  </p>
<div class="table-wrapper">
  <table>
    <thead>
      <tr>
        <th>S.No</th>
        <th>User</th>
        <th>Flight</th>
       
        <th>Passengers</th>
        <th>Total</th>
        <th>Status</th>
        <th>Booked At</th>
        <th>Invoice</th>
      </tr>
    </thead>
    <tbody>
    <?php
      $serial = $offset + 1;
      foreach ($rows as $r):
        // seat list for each booking
        $s = $pdo->prepare("SELECT seat_label FROM booking_seats WHERE booking_id = ? ORDER BY seat_label");
        $s->execute([$r['id']]);
        $seatText = implode(', ', array_map(fn($x) => $x['seat_label'], $s->fetchAll()));
        $inv = $base . '/flights/invoice.php?id=' . (int)$r['id'];
    ?>
      <tr>
        <td><?= $serial++; ?></td>
        <td><?= htmlspecialchars($r['user_name']); ?><br><small class="muted"><?= htmlspecialchars($r['user_email']); ?></small></td>
        <td><?= htmlspecialchars($r['flight_no']); ?></td>
     
        <td><?= (int)$r['passengers']; ?></td>
        <td>₹<?= number_format($r['total_price'], 2); ?></td>
        <td><?= htmlspecialchars($r['status']); ?></td>
        <td><?= htmlspecialchars($r['booked_at']); ?></td>
        <td><a class="btn" href="<?= $inv; ?>" target="_blank">View / Print</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>

  <!-- Pagination controls -->
  <nav aria-label="Pagination" style="display:flex; gap:.5rem; justify-content:center; margin-top:1rem;">
    <a class="contrast btn" href="?page=1" <?= $page<=1?'aria-disabled="true"':''; ?>>« First</a>
    <a class="contrast btn" href="?page=<?= max(1,$page-1) ?>" <?= $page<=1?'aria-disabled="true"':''; ?>>‹ Prev</a>
    <span class="muted" style="align-self:center;">Page <?= $page ?> of <?= $totalPages ?></span>
    <a class="contrast btn" href="?page=<?= min($totalPages,$page+1) ?>" <?= $page>=$totalPages?'aria-disabled="true"':''; ?>>Next ›</a>
    <a class="contrast btn" href="?page=<?= $totalPages ?>" <?= $page>=$totalPages?'aria-disabled="true"':''; ?>>Last »</a>
  </nav>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
