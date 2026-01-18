<?php
require __DIR__ . '/app/includes/header.php';
Auth::requireRole([2]);
?>
<!doctype html>
<html lang="el">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Professor Area</title>
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
    <h1>Professor Area</h1>
    <p>Πρόσβαση μόνο για Καθηγητές (role_id=2).</p>
  </section>
</main>
</body>
</html>
