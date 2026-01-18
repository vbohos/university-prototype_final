<?php
declare(strict_types=1);

require __DIR__ . '/../app/includes/header.php';

// μόνο καθηγητές
Auth::requireRole([2]);

$profId = (int)($_SESSION['user_id'] ?? 0);

$courseId = (int)($_GET['course_id'] ?? 0);
if ($courseId <= 0) {
  header('Location: courses.php');
  exit;
}

// Βεβαιώσου ότι το μάθημα ανήκει στον συνδεδεμένο καθηγητή
$stmt = $pdo->prepare('SELECT id, title, description FROM courses WHERE id = :cid AND professor_id = :pid LIMIT 1');
$stmt->execute([':cid' => $courseId, ':pid' => $profId]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
  // δεν υπάρχει ή δεν είναι δικό του -> Forbidden
  http_response_code(403);
  header('Location: ../forbidden.php');
  exit;
}

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = (string)($_POST['csrf_token'] ?? '');
  if (!Security::verifyCsrf($token)) {
    $errors[] = 'Μη έγκυρο CSRF token.';
  } else {
    $title = trim((string)($_POST['title'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $dueAt = trim((string)($_POST['due_at'] ?? ''));

    if ($title === '') {
      $errors[] = 'Ο τίτλος εργασίας είναι υποχρεωτικός.';
    } elseif (mb_strlen($title) > 150) {
      $errors[] = 'Ο τίτλος είναι πολύ μεγάλος (max 150 χαρακτήρες).';
    }

    // due_at optional, αλλά αν συμπληρωθεί να είναι έγκυρο datetime-local
    $dueAtDb = null;
    if ($dueAt !== '') {
      // datetime-local δίνει "YYYY-MM-DDTHH:MM"
      $dueAtDb = str_replace('T', ' ', $dueAt) . ':00';
      $dt = DateTime::createFromFormat('Y-m-d H:i:s', $dueAtDb);
      if (!$dt) {
        $errors[] = 'Μη έγκυρη ημερομηνία/ώρα παράδοσης.';
        $dueAtDb = null;
      }
    }

    if (!$errors) {
      $stmt = $pdo->prepare('
        INSERT INTO assignments (course_id, title, description, due_at)
        VALUES (:cid, :t, :d, :due)
      ');
      $stmt->execute([
        ':cid' => $courseId,
        ':t' => $title,
        ':d' => ($description === '' ? null : $description),
        ':due' => $dueAtDb
      ]);
      $success = 'Η εργασία αναρτήθηκε επιτυχώς.';
    }
  }
}

// Φόρτωση εργασιών του μαθήματος
$stmt = $pdo->prepare('
  SELECT id, title, description, due_at, created_at
  FROM assignments
  WHERE course_id = :cid
  ORDER BY id DESC
');
$stmt->execute([':cid' => $courseId]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$csrf = Security::csrfToken();
?>
<!doctype html>
<html lang="el">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Εργασίες Μαθήματος</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body>
<header class="topbar">
  <div class="container topbar__inner">
    <div class="brand"><a href="../index.php">University Prototype</a></div>
    <nav class="nav">
      <a class="btn btn--ghost" href="../dashboard.php">Dashboard</a>
      <a class="btn btn--ghost" href="courses.php">Μαθήματα</a>
      <a class="btn" href="../logout.php">Αποσύνδεση</a>
    </nav>
  </div>
</header>

<main class="container" style="max-width: 980px;">
  <section class="card">
    <h1>Εργασίες Μαθήματος</h1>
    <p><strong>Μάθημα:</strong> <?php echo Security::e((string)$course['title']); ?></p>

    <?php if (!empty($course['description'])): ?>
      <p><?php echo Security::e((string)$course['description']); ?></p>
    <?php endif; ?>

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

    <h2>Ανάρτηση νέας εργασίας</h2>
    <form class="form" method="post" action="">
      <input type="hidden" name="csrf_token" value="<?php echo Security::e($csrf); ?>" />

      <label>Τίτλος Εργασίας
        <input name="title" type="text" required maxlength="150" placeholder="π.χ. Project 1" />
      </label>

      <label>Περιγραφή (προαιρετικό)
        <input name="description" type="text" maxlength="800" placeholder="Οδηγίες/περιγραφή..." />
      </label>

      <label>Προθεσμία (προαιρετικό)
        <input name="due_at" type="datetime-local" />
      </label>

      <button class="btn" type="submit">Ανάρτηση</button>
    </form>
  </section>

  <section class="card">
    <h2>Εργασίες του μαθήματος</h2>

    <?php if (!$assignments): ?>
      <p>Δεν υπάρχουν εργασίες ακόμη για αυτό το μάθημα.</p>
    <?php else: ?>
      <div style="overflow:auto;">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">ID</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Τίτλος</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Προθεσμία</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Υποβολές</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($assignments as $a): ?>
            <tr>
              <td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?php echo (int)$a['id']; ?></td>
              <td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?php echo Security::e((string)$a['title']); ?></td>
              <td style="padding:10px; border-bottom:1px solid #f1f5f9;">
                <?php echo $a['due_at'] ? Security::e((string)$a['due_at']) : '—'; ?>
              </td>
              <td style="padding:10px; border-bottom:1px solid #f1f5f9;">
                <a class="btn btn--ghost" href="submissions.php?assignment_id=<?php echo (int)$a['id']; ?>">Προβολή</a>
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
