<?php
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../config/db.php';
$from = strtoupper(trim($_GET['from'] ?? ''));
$to = strtoupper(trim($_GET['to'] ?? ''));
$date = trim($_GET['date'] ?? '');
?>
<h2>Search Flights</h2>
<form method="get">
  <div class="grid">
    <label>From <input name="from" value="<?= htmlspecialchars($from) ?>"></label>
    <label>To <input name="to" value="<?= htmlspecialchars($to) ?>"></label>
    <label>Date <input type="date" name="date" value="<?= htmlspecialchars($date) ?>"></label>
  </div>
  <button type="submit">Search</button>
</form>
<?php
if ($from && $to && $date) {
    $start = $date . " 00:00:00";
    $end   = $date . " 23:59:59";
    $stmt = $pdo->prepare("
        SELECT f.*, a1.code AS from_code, a2.code AS to_code
        FROM flights f
        JOIN airports a1 ON a1.id = f.from_airport_id
        JOIN airports a2 ON a2.id = f.to_airport_id
        WHERE a1.code = ? AND a2.code = ? AND f.departure_time BETWEEN ? AND ?
        ORDER BY f.departure_time
    ");
    $stmt->execute([$from, $to, $start, $end]);
    $flights = $stmt->fetchAll();
    if (!$flights) {
        echo '<article>No flights found.</article>';
    } else {
        echo '<table><thead><tr><th>Flight</th><th>From</th><th>To</th><th>Depart</th><th>Arrive</th><th>Price</th><th>Seats</th><th></th></tr></thead><tbody>';
        foreach ($flights as $f) {
            $link = $base . '/flights/view.php?id=' . (int)$f['id'];
            echo '<tr>';
            echo '<td>' . htmlspecialchars($f['flight_no']) . '</td>';
            echo '<td>' . htmlspecialchars($f['from_code']) . '</td>';
            echo '<td>' . htmlspecialchars($f['to_code']) . '</td>';
            echo '<td>' . htmlspecialchars($f['departure_time']) . '</td>';
            echo '<td>' . htmlspecialchars($f['arrival_time']) . '</td>';
            echo '<td>₹' . number_format($f['base_price'], 2) . '</td>';
            echo '<td>' . (int)$f['seats_available'] . '</td>';
            echo '<td><a href="'. $link .'">Select Seats</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
}
require __DIR__ . '/../includes/footer.php';
