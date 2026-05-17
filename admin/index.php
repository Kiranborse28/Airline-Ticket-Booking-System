<?php
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../config/db.php';

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    echo '<article>Admins only.</article>';
    require __DIR__ . '/../includes/footer.php'; exit;
}

// Get counts
$airports_count  = (int)$pdo->query("SELECT COUNT(*) FROM airports")->fetchColumn();
$aircraft_count  = (int)$pdo->query("SELECT COUNT(*) FROM aircraft")->fetchColumn();
$flights_count   = (int)$pdo->query("SELECT COUNT(*) FROM flights")->fetchColumn();
$bookings_count  = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$users_count     = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
?>

<h2>Admin Dashboard</h2>

<style>
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
  margin-top: 1.5rem;
}
.dashboard-card {
  background: #f9f9f9;
  border: 1px solid #e0e0e0;
  padding: 1.2rem;
  border-radius: 12px;
  text-align: center;
  transition: transform 0.2s, box-shadow 0.2s;
}
.dashboard-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.dashboard-card h3 {
  margin: 0;
  font-size: 1.8rem;
}
.dashboard-card p {
  margin: 0.3rem 0 0;
  font-size: 0.9rem;
  color: #666;
}
.dashboard-card a {
  display: block;
  margin-top: 0.8rem;
  text-decoration: none;
  font-size: 0.9rem;
  color: #0077cc;
}
.dashboard-card a:hover {
  text-decoration: underline;
}
</style>

<div class="dashboard-grid">
  <div class="dashboard-card">
    <h3><?= $airports_count ?></h3>
    <p>Airports</p>
    <a href="airports.php">Manage Airports →</a>
  </div>
  <div class="dashboard-card">
    <h3><?= $aircraft_count ?></h3>
    <p>Aircraft</p>
    <a href="aircraft.php">Manage Aircraft →</a>
  </div>
  <div class="dashboard-card">
    <h3><?= $flights_count ?></h3>
    <p>Flights</p>
    <a href="flights.php">Manage Flights →</a>
  </div>
  <div class="dashboard-card">
    <h3><?= $bookings_count ?></h3>
    <p>Total Bookings</p>
    <a href="bookings.php">View All Bookings →</a>
  </div>
<div class="dashboard-card">
  <h3><?= $users_count ?></h3>
  <p>Registered Users</p>
  <a href="users.php">View Users →</a>
</div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
