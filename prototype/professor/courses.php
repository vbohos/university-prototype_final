<?php
declare(strict_types=1);

require __DIR__ . '/../app/includes/header.php';

// μόνο καθηγητές
Auth::requireRole([2]);

$profId = (int)($_SESSION['user_id'] ?? 0);

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = (string)($_POST['csrf_token'] ?? '');
  if (!Security::verifyCsrf($token)) {
    $errors[] = 'Μη έγκυρο CSRF token.';
  } else {
    $title = trim((string)($_POST['title'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));

    if ($title === '') {
      $errors[] = 'Ο τίτλος μαθήματος είναι υποχρεωτικός.';
    } elseif (mb_strlen($title) > 120) {
      $errors[] = 'Ο τίτλος είναι πολύ μεγάλος (max 120 χαρακτήρες).';
    }

    if (!$errors) {
      // $pdo έρχεται από header.php
      $stmt = $pdo->prepare('INSERT INTO courses (title, description, professor_id) VALUES (:t, :d, :p)');
      $stmt->execute([
        ':t' => $title,
        ':d' => ($description === '' ? null : $description),
        ':p' => $profId
      ]);
      $success = 'Το μάθημα δημιουργήθηκε επιτυχώς.';
    }
  }
}

// Φόρτωση μαθημάτων του καθηγητή
$stmt = $pdo->prepare('SELECT id, title, description, created_at FROM courses WHERE professor_id = :p ORDER BY id DESC');
$stmt->execute([':p' => $profId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$csrf = Security::csrfToken();
?>
<!doctype html>
<html lang="el">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Διαχείριση Μαθημάτων</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body>
<header class="topbar">
  <div class="container topbar__inner">
    <div class="brand"><a href="../index.php">University Prototype</a></div>
    <nav class="nav">
      <a class="btn btn--ghost" href="../dashboard.php">Dashboard</a>
      <a class="btn" href="../logout.php">Αποσύνδεση</a>
    </nav>
  </div>
</header>

<main class="container" style="max-width: 980px;">
  <section class="card">
    <h1>Μαθήματα (Καθηγητής)</h1>
    <p>Δημιουργήστε και δείτε τα μαθήματά σας.</p>

    <?php if ($success): ?>
      <div class="alert alert--ok"><?php echo Security::e($success); ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="alert alert--error">
        <ul style="margin:0; padding-left:18px;">
          <?php foreach ($errors as $e): ?>
            <li><?php echo Security::e($e); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form class="form" method="post" action="">
      <input type="hidden" name="csrf_token" value="<?php echo Security::e($csrf); ?>" />

      <label>Τίτλος Μαθήματος
        <input name="title" type="text" required maxlength="120" placeholder="π.χ. Web Development" />
      </label>

      <label>Περιγραφή (προαιρετικό)
        <input name="description" type="text" maxlength="500" placeholder="Σύντομη περιγραφή..." />
      </label>

      <button class="btn" type="submit">Δημιουργία Μαθήματος</button>
    </form>
  </section>

  <section class="card">
    <h2>Τα μαθήματά μου</h2>

    <?php if (!$courses): ?>
      <p>Δεν έχετε δημιουργήσει ακόμη κάποιο μάθημα.</p>
    <?php else: ?>
      <div style="overflow:auto;">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">ID</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Τίτλος</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Περιγραφή</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Ημ/νία</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Εργασίες</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($courses as $c): ?>
            <tr>
              <td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?php echo (int)$c['id']; ?></td>
              <td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?php echo Security::e((string)$c['title']); ?></td>
              <td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?php echo Security::e((string)($c['description'] ?? '')); ?></td>
              <td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?php echo Security::e((string)$c['created_at']); ?></td>
              <td style="padding:10px; border-bottom:1px solid #f1f5f9;">
                <a class="btn btn--ghost" href="assignments.php?course_id=<?php echo (int)$c['id']; ?>">Διαχείριση</a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</main>
</body>
</html>
