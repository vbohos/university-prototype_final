<?php
require __DIR__ . '/app/includes/header.php';
http_response_code(403);
?>
<!doctype html>
<html lang="el">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Forbidden Action</title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
<header class="topbar">
  <div class="container topbar__inner">
    <div class="brand"><a href="index.php">University Prototype</a></div>
    <nav class="nav">
      <a class="btn btn--ghost" href="index.php">Αρχική</a>
      <?php if (Auth::check()): ?>
        <a class="btn btn--ghost" href="dashboard.php">Dashboard</a>
        <a class="btn" href="logout.php">Αποσύνδεση</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="container" style="max-width:650px;">
  <section class="card">
    <h1>Forbidden Action</h1>
    <p>Δεν έχετε δικαίωμα πρόσβασης σε αυτή τη λειτουργία.</p>
  </section>
</main>
</body>
</html>
