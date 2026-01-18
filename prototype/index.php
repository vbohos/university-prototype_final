<?php require __DIR__ . '/app/includes/header.php'; ?>
<!doctype html>
<html lang="el">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>University Campus</title>

  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>
<header class="topbar">
  <div class="container topbar__inner">
    <div class="brand">University Prototype</div>
   <nav class="nav">
  <?php if (!Auth::check()): ?>
    <a class="btn btn--ghost" href="login.php">Σύνδεση</a>
    <a class="btn" href="register.php">Εγγραφή</a>
  <?php else: ?>
    <a class="btn btn--ghost" href="dashboard.php">Dashboard</a>
    <a class="btn" href="logout.php">Αποσύνδεση</a>
  <?php endif; ?>
</nav>

  </div>
</header>

<main class="container">
  <section class="hero">
    <div class="hero__text">
      <h1>Καλωσορίσατε στο Πανεπιστήμιο</h1>
      <p>
        Δημόσια αρχική σελίδα με πληροφορίες για το campus, εικόνες και χάρτη.
        Στο σύστημα υπάρχει εγγραφή/σύνδεση χρηστών με ρόλους (Φοιτητής/Καθηγητής).
      </p>
      <div class="hero__cta">
        <a class="btn" href="register.php">Ξεκίνα με Εγγραφή</a>
        <a class="btn btn--ghost" href="login.php">Έχω λογαριασμό</a>
      </div>
    </div>

    <div class="hero__images">
  <img src="assets/pictures/campus1.jpg" alt="Campus" />
  <img src="assets/pictures/campus2.jpg" alt="Library / Lecture Hall" />
</div>

  </section>

  <section class="card">
    <h2>Τοποθεσία Campus</h2>
    <p>Ο χάρτης φορτώνει με Leaflet.js και εμφανίζει marker στο campus.</p>
    <div id="map" class="map"></div>
  </section>
</main>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/js/map.js"></script>
</body>
</html>
