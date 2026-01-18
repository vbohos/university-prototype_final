<?php
require __DIR__ . '/app/includes/header.php';

$message = null;
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!Security::verifyCsrf($_POST['csrf_token'] ?? null)) {
    $message = 'Μη έγκυρο αίτημα (CSRF).';
  } else {
    $res = $auth->login($_POST['email'] ?? '', $_POST['password'] ?? '');
    $ok = $res['ok'];
    $message = $res['message'];

    if ($ok) {
      header('Location: dashboard.php');
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="el">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Σύνδεση</title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
<header class="topbar">
  <div class="container topbar__inner">
    <div class="brand"><a href="index.php">University Prototype</a></div>
    <nav class="nav">
      <a class="btn btn--ghost" href="register.php">Εγγραφή</a>
    </nav>
  </div>
</header>

<main class="container" style="max-width:560px;">
  <section class="card">
    <h1>Σύνδεση</h1>

    <?php if ($message): ?>
      <div class="alert <?php echo $ok ? 'alert--ok' : 'alert--error'; ?>">
        <?php echo Security::e($message); ?>
      </div>
    <?php endif; ?>

    <form method="post" class="form">
      <input type="hidden" name="csrf_token" value="<?php echo Security::e(Security::csrfToken()); ?>" />

      <label>Email
        <input name="email" type="email" required />
      </label>

      <label>Password
        <input name="password" type="password" required />
      </label>

      <button class="btn" type="submit">Σύνδεση</button>
    </form>
  </section>
</main>
</body>
</html>
