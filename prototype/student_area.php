<?php
require __DIR__ . '/app/includes/header.php';
Auth::requireRole([1]);
?>
<!doctype html>
<html lang="el">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Student Area</title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
<header class="topbar">
  <div class="container topbar__inner">
    <div class="brand"><a href="index.php">University Prototype</a></div>
    <nav class="nav">
      <a class="btn btn--ghost" href="dashboard.php">Dashboard</a>
      <a class="btn" href="logout.php">Αποσύνδεση</a>
    </nav>
  </div>
</header>

<main class="container" style="max-width:560px;">
  <section class="card">
    <h1>Student Area</h1>
    <p>Πρόσβαση μόνο για Φοιτητές (role_id=1).</p>
  </section>
</main>
</body>
</html>
