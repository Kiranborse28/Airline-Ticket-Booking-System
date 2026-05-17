<?php require __DIR__ . '/includes/header.php';
require __DIR__ . '/config/db.php';
$stmt = $pdo->query("SELECT code, name FROM airports ORDER BY code ASC");
$airports = $stmt->fetchAll(PDO::FETCH_ASSOC);

 ?>

<section>
  <h2>Find your next flight</h2>
  <form action="flights/search.php" method="get">
    <div class="grid">
      <label>From (Airport Code)
        <input name="from" placeholder="e.g. DEL" required>
      </label>
      <label>To (Airport Code)
        <input name="to" placeholder="e.g. BOM" required>
      </label>
      <label>Departure Date
        <input type="date" name="date" required>
      </label>
    </div>
    <button type="submit">Search Flights</button>
  </form>

<h3>Available Airports</h3>
  <table border="1" cellpadding="8" cellspacing="0">
    <thead>
      <tr>
        <th>Airport Code</th>
        <th>Airport Name</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($airports): ?>
        <?php foreach ($airports as $a): ?>
          <tr>
            <td><?= htmlspecialchars($a['code']) ?></td>
            <td><?= htmlspecialchars($a['name']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="2">No airports found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
