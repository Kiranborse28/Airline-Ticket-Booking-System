<?php
require __DIR__ . '/../includes/auth_check.php';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../config/db.php';

$booking_id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
  SELECT b.*, u.name AS user_name, u.email AS user_email,
         f.flight_no, f.departure_time, f.arrival_time,
         a1.code AS from_code, a2.code AS to_code
  FROM bookings b
  JOIN users u ON u.id = b.user_id
  JOIN flights f ON f.id = b.flight_id
  JOIN airports a1 ON a1.id = f.from_airport_id
  JOIN airports a2 ON a2.id = f.to_airport_id
  WHERE b.id = ?
");
$stmt->execute([$booking_id]);
$inv = $stmt->fetch();

if (!$inv) { echo '<article>Invoice not found.</article>'; require __DIR__ . '/../includes/footer.php'; exit; }

// Only owner or admin can view
if (($_SESSION['user']['role'] ?? 'user') !== 'admin' && $inv['user_id'] != $_SESSION['user']['id']) {
    echo '<article>Unauthorized.</article>'; require __DIR__ . '/../includes/footer.php'; exit;
}

// seat list
$s = $pdo->prepare("SELECT seat_label FROM booking_seats WHERE booking_id = ? ORDER BY seat_label");
$s->execute([$booking_id]);
$seatList = $s->fetchAll();
$seats = implode(', ', array_map(fn($x)=>$x['seat_label'], $seatList));

$gst_rate = 0.18; // 18% tax (example)
$subtotal = (float)$inv['total_price'];
$tax = $subtotal * $gst_rate;
$grand = $subtotal + $tax;
$invoiceNo = 'INV-' . str_pad((string)$booking_id, 6, '0', STR_PAD_LEFT);
?>
<section class="invoice">
  <div class="inv-head">
    <div>
      <h3>Airline Ticket Invoice</h3>
      <p class="muted">Invoice for confirmed booking</p>
    </div>
    <div class="inv-meta">
      <strong><?= htmlspecialchars($invoiceNo); ?></strong><br>
      Date: <?= htmlspecialchars(date('Y-m-d', strtotime($inv['booked_at']))); ?><br>
      Status: <span class="badge"><?= htmlspecialchars($inv['status']); ?></span>
    </div>
  </div>
  <hr>
  <div class="inv-grid">
    <div>
      <h4>Bill To</h4>
      <p>
        <?= htmlspecialchars($inv['user_name']); ?><br>
        <?= htmlspecialchars($inv['user_email']); ?>
      </p>
    </div>
    <div>
      <h4>Flight</h4>
      <p>
        Flight No: <strong><?= htmlspecialchars($inv['flight_no']); ?></strong><br>
        Route: <?= htmlspecialchars($inv['from_code']); ?> → <?= htmlspecialchars($inv['to_code']); ?><br>
        Departure: <?= htmlspecialchars($inv['departure_time']); ?><br>
        Arrival: <?= htmlspecialchars($inv['arrival_time']); ?><br>
        Seats: <?= htmlspecialchars($seats ?: 'N/A'); ?>
      </p>
    </div>
  </div>
  <hr>
  <table>
    <thead>
      <tr><th>Description</th><th>Qty</th><th>Unit</th><th>Total</th></tr>
    </thead>
    <tbody>
      <tr>
        <td>Passenger Fare</td>
        <td><?= (int)$inv['passengers']; ?></td>
        <td>₹<?= number_format($subtotal / max(1,(int)$inv['passengers']), 2); ?></td>
        <td>₹<?= number_format($subtotal, 2); ?></td>
      </tr>
      <tr>
        <td colspan="3" style="text-align:right">Tax (18%)</td>
        <td>₹<?= number_format($tax, 2); ?></td>
      </tr>
      <tr>
        <td colspan="3" style="text-align:right"><strong>Grand Total</strong></td>
        <td><strong>₹<?= number_format($grand, 2); ?></strong></td>
      </tr>
    </tbody>
  </table>
  <p class="muted">This is a system-generated invoice for your records.</p>
  <div class="no-print">
    <a class="btn" href="#" onclick="window.print(); return false;">Print Invoice</a>
    <a class="btn" href="<?= $base ?>/flights/my_bookings.php">Back to Bookings</a>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
