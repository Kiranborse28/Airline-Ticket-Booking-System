<?php
require __DIR__ . '/../includes/auth_check.php';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../config/db.php';

/* ---- Pagination (latest 10) ---- */
$perPage = 10;
$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset  = ($page - 1) * $perPage;

/* ---- Count total for this user ---- */
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
$countStmt->execute([$_SESSION['user']['id']]);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

/* ---- Fetch bookings for current page (with seats via GROUP_CONCAT) ---- */
$stmt = $pdo->prepare("
  SELECT b.*, f.flight_no, f.departure_time, f.arrival_time,
         (
           SELECT GROUP_CONCAT(bs.seat_label ORDER BY bs.seat_label SEPARATOR ', ')
           FROM booking_seats bs
           WHERE bs.booking_id = b.id
         ) AS seats_text
  FROM bookings b
  JOIN flights f ON f.id = b.flight_id
  WHERE b.user_id = ?
  ORDER BY b.booked_at DESC
  LIMIT $perPage OFFSET $offset
");
$stmt->execute([$_SESSION['user']['id']]);
$rows = $stmt->fetchAll();
?>
<h2>My Bookings</h2>

<?php if (!$rows): ?>
  <article>No bookings yet.</article>
<?php else: ?>
  <p class="muted">
    Showing <strong><?= $total ? ($offset + 1) : 0 ?></strong>–<strong><?= min($offset + $perPage, $total) ?></strong>
    of <strong><?= $total ?></strong>
  </p>
<div class="table-wrapper">
  <table>
    <thead>
      <tr>
        <th>S.No</th>
        <th>Flight</th>
        <th>Seats</th>
        <th>Passengers</th>
        <th>Total</th>
        <th>Status</th>
        <th>Booked At</th>
        <th class="no-print">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php $serial = $offset + 1; ?>
      <?php foreach ($rows as $r): ?>
        <?php $inv = $base . '/flights/invoice.php?id=' . (int)$r['id']; ?>
        <tr>
          <td><?= $serial++; ?></td>
          <td><?= htmlspecialchars($r['flight_no']); ?> (<?= htmlspecialchars($r['departure_time']); ?>)</td>
          <td><?= htmlspecialchars($r['seats_text'] ?? ''); ?></td>
          <td><?= (int)$r['passengers']; ?></td>
          <td>₹<?= number_format($r['total_price'], 2); ?></td>
          <td><span class="badge"><?= htmlspecialchars($r['status']); ?></span></td>
          <td><?= htmlspecialchars($r['booked_at']); ?></td>
          <td class="no-print"><a class="btn" href="<?= $inv; ?>" target="_blank">Invoice / Print</a></td>
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
