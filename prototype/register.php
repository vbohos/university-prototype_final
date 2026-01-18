<?php
require __DIR__ . '/app/includes/header.php';

$message = null;
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!Security::verifyCsrf($_POST['csrf_token'] ?? null)) {
    $message = 'Μη έγκυρο αίτημα (CSRF).';
  } else {
    $res = $auth->register(
      $_POST['username'] ?? '',
      $_POST['email'] ?? '',
      $_POST['password'] ?? '',
      $_POST['role'] ?? '',
      $_POST['secret_code'] ?? ''
    );
    $ok = $res['ok'];
    $message = $res['message'];
  }
}
?>
<!doctype html>
<html lang="el">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Εγγραφή</title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
<header class="topbar">
  <div class="container topbar__inner">
    <div class="brand"><a href="index.php">University Prototype</a></div>
    <nav class="nav">
      <a class="btn btn--ghost" href="login.php">Σύνδεση</a>
    </nav>
  </div>
</header>

<main class="container" style="max-width:560px;">
  <section class="card">
    <h1>Εγγραφή Χρήστη</h1>

    <?php if ($message): ?>
      <div class="alert <?php echo $ok ? 'alert--ok' : 'alert--error'; ?>">
        <?php echo Security::e($message); ?>
      </div>
    <?php endif; ?>

    <form method="post" class="form">
      <input type="hidden" name="csrf_token" value="<?php echo Security::e(Security::csrfToken()); ?>" />

      <label>Username
        <input name="username" type="text" maxlength="50" required />
      </label>

      <label>Email
        <input name="email" type="email" maxlength="120" required />
      </label>

      <label>Password
        <input name="password" type="password" minlength="6" required />
      </label>

      <label>Ρόλος
        <select name="role" required>
          <option value="">-- Επιλέξτε --</option>
          <option value="student">Φοιτητής</option>
          <option value="professor">Καθηγητής</option>
        </select>
      </label>

      <label>Ειδικός Κωδικός Εγγραφής
        <input name="secret_code" type="text" required placeholder="STUD2025 ή PROF2025" />
      </label>

      <button class="btn" type="submit">Εγγραφή</button>
    </form>
  </section>
</main>
</body>
</html>
