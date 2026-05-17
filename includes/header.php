<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$config = require __DIR__ . '/../config/config.php';
$base = $config['app']['base_url'];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Airline Ticket Booking</title>
  <link rel="stylesheet" href="assets/pico.min.css">
   <link rel="stylesheet" href="../assets/pico.min.css">
  <style>
    /* Page background with airline image + gradient overlay */
    :root { --overlay: linear-gradient(180deg, rgba(0,0,0,.45), rgba(0,0,0,.45)); }
    body {
      min-height: 100vh;
      background: var(--overlay), url("<?= $base ?>/assets/airline.jpg") center/cover no-repeat fixed;
      color: #111;
    }

    /* Glassy navbar */
    nav.site-nav {
      position: sticky; top: 0; z-index: 100;
      backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
      background: rgba(255,255,255,.55);
      border-bottom: 1px solid rgba(255,255,255,.3);
      border-radius: 0 0 14px 14px;
      padding: .6rem 1rem; margin-bottom: 1rem;
    }
    nav.site-nav a { text-decoration: none; }
    nav.site-nav .brand a { font-weight: 700; }
    .container { max-width: 1100px; margin: 0 auto; }

    /* Content card */
    main.container {
      background: rgba(255,255,255,.80);
      border: 1px solid rgba(255,255,255,.5);
      border-radius: 16px;
      box-shadow: 0 8px 30px rgba(0,0,0,.12);
      padding: 1.2rem; margin-top: 1rem; margin-bottom: 2rem;
    }

    /* Dropdown polish */
    details.dropdown > summary { cursor: pointer; }
    details.dropdown ul[role="listbox"] { min-width: 200px; }
    .nav-list { display: flex; gap: .8rem; align-items: center; }
    .nav-list li { list-style: none; }

    .muted { color: #666; font-size: .9rem; }
    form.inline { display: inline; }

    /* Seat map (unchanged) */
    .seatmap { display: grid; grid-template-columns: repeat(7, minmax(28px, 40px)); gap: 8px; }
    .seat { border: 1px solid #ccc; padding: .4rem; text-align: center; border-radius: 8px; cursor: pointer; user-select: none; }
    .seat.aisle { border: none; cursor: default; }
    .seat.available { background: #f7f7f7; }
    .seat.selected { outline: 2px solid #0a7; }
    .seat.occupied { background: #ddd; color: #777; cursor: not-allowed; text-decoration: line-through; }

    /* ========= Table wrapper to keep tables inside the card ========= */
    .table-wrapper {
      width: 100%;
      overflow-x: auto;              /* horizontal scroll on small screens */
      background: #fff;
      border-radius: 12px;
      border: 1px solid #eee;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      margin-top: 1rem;
    }
    .table-wrapper table {
      width: 100%;
      border-collapse: collapse;
      min-width: 820px;              /* ensures columns don’t squish too much */
    }
    .table-wrapper th, .table-wrapper td {
      padding: .65rem .85rem;
      text-align: left;
      border-bottom: 1px solid #eee;
      vertical-align: top;
    }
    .table-wrapper thead th {
      background: #f8f9fa;
      font-weight: 600;
      position: sticky; top: 0; z-index: 1; /* sticky header */
    }
    .table-wrapper tbody tr:hover { background: #f9f9f9; }
    .table-wrapper tr:last-child td { border-bottom: none; }

    /* Footer */
    footer.container { background: rgba(255,255,255,.7); border-radius: 12px; padding: .6rem 1rem; }

    /* ================================
   Stylish Action Buttons (Global)
   ================================ */
.action-btns {
  display: flex;
  gap: .5rem;
  justify-content: flex-end;
  align-items: center;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  border-radius: 8px;
  padding: .35rem .8rem;
  border: 1px solid transparent;
  font-size: .85rem;
  line-height: 1;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn.primary   { background: #106ebe; border-color: #106ebe; color: #fff; }
.btn.secondary { background: #eef3f7; border-color: #d7e0e7; color: #1f2937; }
.btn.danger    { background: #d72d2d; border-color: #d72d2d; color: #fff; }

.btn:hover     { filter: brightness(.96); }
.btn:active    { transform: translateY(1px); }

.btn.sm { padding: .3rem .65rem; font-size: .8rem; }

form.inline { display: inline; }

  </style>
</head>
<body>

<nav class="site-nav">
  <div class="container" style="display:flex; justify-content:space-between; align-items:center; gap:1rem;">
    <ul class="nav-list brand">
      <?php if (!empty($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin'): ?>
      <li><a href="<?= $base ?>/admin/index.php">✈️ Airline Ticket Booking Sytem</a></li>
      <?php else: ?>
      <li><a href="<?= $base ?>/">✈️ Airline Ticket Booking Sytem</a></li><?php endif; ?>
    </ul>

    <ul class="nav-list">
      <?php
      $isAdmin = !empty($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin';
      ?>

      <?php if (!$isAdmin): ?>
        <li><a href="<?= $base ?>/flights/search.php">Search</a></li>
      <?php endif; ?>

      <?php if (!empty($_SESSION['user'])): ?>
        <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
          <li><a href="<?= $base ?>/admin/index.php">Dashboard</a></li>
          <li>
            <details class="dropdown">
              <summary>Manage</summary>
              <ul role="listbox">
                <li><a href="<?= $base ?>/admin/airports.php">Manage Airports</a></li>
                <li><a href="<?= $base ?>/admin/aircraft.php">Manage Aircrafts</a></li>
                <li><a href="<?= $base ?>/admin/flights.php">Manage Flights</a></li>
              </ul>
            </details>
          </li>
          <li><a href="<?= $base ?>/admin/bookings.php"> Bookings</a></li>
          
        <?php else: ?>
          <li><a href="<?= $base ?>/flights/my_bookings.php">My Bookings</a></li>
        <?php endif; ?>

        <li>
          <details class="dropdown">
            <summary>My Account (<?= htmlspecialchars($_SESSION['user']['name'] ?? 'User') ?>)</summary>
            <ul role="listbox">
              <li><a href="<?= $base ?>/admin/profile.php">Profile</a></li>
              <li><a href="<?= $base ?>/admin/change_password.php">Change Password</a></li>
              <li><a href="<?= $base ?>/auth/logout.php">Logout</a></li>
            </ul>
          </details>
        </li>
      <?php else: ?>
        <li><a href="<?= $base ?>/auth/login.php">Login</a></li>
        <li><a href="<?= $base ?>/auth/register.php">Register</a></li>
      <?php endif; ?>
    </ul>
  </div>
</nav>

<main class="container">
