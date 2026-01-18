<?php
declare(strict_types=1);

require __DIR__ . '/../app/includes/header.php';

Auth::requireRole([1]);
$studentId = (int)($_SESSION['user_id'] ?? 0);

$assignmentId = (int)($_GET['assignment_id'] ?? 0);
if ($assignmentId <= 0) {
  header('Location: courses.php');
  exit;
}

// Βρες την εργασία + course_id
$stmt = $pdo->prepare('
  SELECT a.id, a.title, a.description, a.course_id
  FROM assignments a
  WHERE a.id = :aid
  LIMIT 1
');
$stmt->execute([':aid' => $assignmentId]);
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {
  header('Location: courses.php');
  exit;
}

$courseId = (int)$assignment['course_id'];

// Έλεγχος ότι ο student είναι enrolled στο course
$stmt = $pdo->prepare('SELECT 1 FROM enrollments WHERE course_id = :c AND student_id = :s LIMIT 1');
$stmt->execute([':c' => $courseId, ':s' => $studentId]);
if (!$stmt->fetchColumn()) {
  http_response_code(403);
  header('Location: ../forbidden.php');
  exit;
}

// Έλεγχος ότι ΔΕΝ έχει ήδη υποβάλει
$stmt = $pdo->prepare('SELECT id FROM submissions WHERE assignment_id = :a AND student_id = :s LIMIT 1');
$stmt->execute([':a' => $assignmentId, ':s' => $studentId]);
if ($stmt->fetch()) {
  // ήδη υποβλημένη -> γύρνα πίσω
  header('Location: assignments.php?course_id=' . $courseId);
  exit;
}

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = (string)($_POST['csrf_token'] ?? '');
  if (!Security::verifyCsrf($token)) {
    $errors[] = 'Μη έγκυρο CSRF token.';
  } else {
    $content = trim((string)($_POST['content'] ?? ''));

    if ($content === '') {
      $errors[] = 'Το κείμενο υποβολής είναι υποχρεωτικό.';
    } elseif (mb_strlen($content) < 10) {
      $errors[] = 'Γράψτε λίγο πιο αναλυτική υποβολή (min 10 χαρακτήρες).';
    }

    if (!$errors) {
      $stmt = $pdo->prepare('
        INSERT INTO submissions (assignment_id, student_id, content)
        VALUES (:a, :s, :c)
      ');
      $stmt->execute([':a' => $assignmentId, ':s' => $studentId, ':c' => $content]);

      $success = 'Η υποβολή καταχωρήθηκε επιτυχώς.';
    }
  }
}

$csrf = Security::csrfToken();
?>
<!doctype html>
<html lang="el">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Υποβολή Εργασίας</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body>
<header class="topbar">
  <div class="container topbar__inner">
    <div class="brand"><a href="../index.php">University Prototype</a></div>
    <nav class="nav">
      <a class="btn btn--ghost" href="../dashboard.php">Dashboard</a>
      <a class="btn btn--ghost" href="assignments.php?course_id=<?php echo $courseId; ?>">Πίσω</a>
      <a class="btn" href="../logout.php">Αποσύνδεση</a>
    </nav>
  </div>
</header>

<main class="container" style="max-width: 820px;">
  <section class="card">
    <h1>Υποβολή Εργασίας</h1>
    <p><strong>Εργασία:</strong> <?php echo Security::e((string)$assignment['title']); ?></p>
    <?php if (!empty($assignment['description'])): ?>
      <p><?php echo Security::e((string)$assignment['description']); ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert--ok">
        <?php echo Security::e($success); ?>
        <div style="margin-top:10px;">
          <a class="btn btn--ghost" href="assignments.php?course_id=<?php echo $courseId; ?>">Επιστροφή στις εργασίες</a>
        </div>
      </div>
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

    <?php if (!$success): ?>
      <form class="form" method="post" action="">
        <input type="hidden" name="csrf_token" value="<?php echo Security::e($csrf); ?>" />

        <label>Κείμενο Υποβολής
          <textarea name="content" rows="6" required style="padding:10px; border-radius:10px; border:1px solid #d1d5db; font:inherit;"></textarea>
        </label>

        <button class="btn" type="submit">Υποβολή</button>
      </form>
    <?php endif; ?>
  </section>
</main>
</body>
</html>
