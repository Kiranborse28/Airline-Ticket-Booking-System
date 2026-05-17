<?php
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../config/db.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT f.*, a1.code AS from_code, a2.code AS to_code, ac.seats AS aircraft_seats
    FROM flights f
    JOIN airports a1 ON a1.id = f.from_airport_id
    JOIN airports a2 ON a2.id = f.to_airport_id
    JOIN aircraft ac ON ac.id = f.aircraft_id
    WHERE f.id = ?
");
$stmt->execute([$id]);
$flight = $stmt->fetch();
if (!$flight) { echo '<article>Flight not found.</article>'; require __DIR__ . '/../includes/footer.php'; exit; }

$occStmt = $pdo->prepare("SELECT seat_label FROM booking_seats WHERE flight_id = ?");
$occStmt->execute([$id]);
$occupied = array_column($occStmt->fetchAll(), 'seat_label');

$seatsTotal = (int)$flight['aircraft_seats'];
$perRow = 6; $rows = (int)ceil($seatsTotal / $perRow);
$letters = ['A','B','C','D','E','F'];
?>
<h2>Select Seats — <?= htmlspecialchars($flight['flight_no']); ?></h2>
<p class="muted">From <?= htmlspecialchars($flight['from_code']); ?> to <?= htmlspecialchars($flight['to_code']); ?> | Depart: <?= htmlspecialchars($flight['departure_time']); ?></p>

<div class="legend">
  <span style="background:#f7f7f7;"></span> Available
  <span style="outline:2px solid #0a7;"></span> Selected
  <span style="background:#ddd;"></span> Occupied
</div>
<br>

<form action="../flights/book.php" method="post" id="seatForm">
  <input type="hidden" name="flight_id" value="<?= (int)$flight['id']; ?>">
  <input type="hidden" name="seats_json" id="seats_json" value="[]">
  <div class="seatmap" id="seatmap">
    <?php
    for ($r=1; $r <= $rows; $r++) {
        for ($c=0; $c < $perRow; $c++) {
            $index = ($r-1)*$perRow + $c + 1;
            if ($index > $seatsTotal) break;
            $label = $r . $letters[$c];
            $isOccupied = in_array($label, $occupied, true);
            $classes = 'seat ' . ($isOccupied ? 'occupied' : 'available');
            echo '<div class="'. $classes .'" data-seat="'. $label .'">'. $label .'</div>';
            if ($c == 2) { echo '<div class="seat aisle"></div>'; }
        }
    }
    ?>
  </div>
  <br>
  <button type="submit">Book Selected Seats</button>
</form>

<script>
const selected = new Set();
document.querySelectorAll('.seat.available').forEach(el => {
  el.addEventListener('click', () => {
    const s = el.dataset.seat;
    if (selected.has(s)) { selected.delete(s); el.classList.remove('selected'); }
    else { selected.add(s); el.classList.add('selected'); }
    document.getElementById('seats_json').value = JSON.stringify(Array.from(selected));
  });
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
