<?php
require __DIR__ . '/../includes/auth_check.php';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: search.php'); exit; }
$flight_id = (int)($_POST['flight_id'] ?? 0);
$seats_json = $_POST['seats_json'] ?? '[]';
$seats = json_decode($seats_json, true);
if (!is_array($seats) || count($seats) === 0) {
    echo '<article class="secondary">Please select at least one seat.</article>';
    require __DIR__ . '/../includes/footer.php'; exit;
}
$seats = array_values(array_unique(array_map('strval', $seats)));
$seatCount = count($seats);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id, base_price, seats_available FROM flights WHERE id = ? FOR UPDATE");
    $stmt->execute([$flight_id]);
    $flight = $stmt->fetch();
    if (!$flight) throw new Exception('Flight not found.');
    if ($flight['seats_available'] < $seatCount) throw new Exception('Not enough seats available.');

    $in = implode(',', array_fill(0, $seatCount, '?'));
    $check = $pdo->prepare("SELECT seat_label FROM booking_seats WHERE flight_id = ? AND seat_label IN ($in) FOR UPDATE");
    $check->execute(array_merge([$flight_id], $seats));
    $clash = $check->fetchAll();
    if ($clash) {
        $taken = implode(', ', array_column($clash, 'seat_label'));
        throw new Exception('Some seats were just booked: ' . $taken);
    }

    $total_price = $flight['base_price'] * $seatCount;
    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, flight_id, passengers, total_price, status, booked_at)
        VALUES (?, ?, ?, ?, 'CONFIRMED', NOW())");
    $stmt->execute([$_SESSION['user']['id'], $flight_id, $seatCount, $total_price]);
    $booking_id = (int)$pdo->lastInsertId();

    $ins = $pdo->prepare("INSERT INTO booking_seats (booking_id, flight_id, seat_label) VALUES (?, ?, ?)");
    foreach ($seats as $s) { $ins->execute([$booking_id, $flight_id, $s]); }

    $stmt = $pdo->prepare("UPDATE flights SET seats_available = seats_available - ? WHERE id = ?");
    $stmt->execute([$seatCount, $flight_id]);

    $pdo->commit();
    echo '<article class="success">Booking successful! Seats: ' . htmlspecialchars(implode(', ', $seats)) .
         ' — <a href="my_bookings.php">View bookings</a></article>';
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo '<article class="secondary">Booking failed: ' . htmlspecialchars($e->getMessage()) . '</article>';
}
require __DIR__ . '/../includes/footer.php';
