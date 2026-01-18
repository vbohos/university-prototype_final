<?php
declare(strict_types=1);

require __DIR__ . '/../app/includes/header.php';

Auth::requireRole([2]);
$profId = (int)($_SESSION['user_id'] ?? 0);

$assignmentId = (int)($_GET['assignment_id'] ?? 0);
if ($assignmentId <= 0) {
  header('Location: courses.php');
  exit;
}

// Εργασία + μάθημα (μόνο αν ανήκει στον καθηγητή)
$stmt = $pdo->prepare('
  SELECT
    a.id AS assignment_id,
    a.title AS assignment_title,
    a.description AS assignment_desc,
    a.course_id,
    c.title AS course_title
  FROM assignments a
  JOIN courses c ON c.id = a.course_id
  WHERE a.id = :aid AND c.professor_id = :pid
  LIMIT 1
');
$stmt->execute([':aid' => $assignmentId, ':pid' => $profId]);
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {
  http_response_code(403);
  header('Location: ../forbidden.php');
  exit;
}

$errors = [];
$success = null;

// Grade submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = (string)($_POST['csrf_token'] ?? '');
  if (!Security::verifyCsrf($token)) {
    $errors[] = 'Μη έγκυρο CSRF token.';
  } else {
    $submissionId = (int)($_POST['submission_id'] ?? 0);
    $studentId = (int)($_POST['student_id'] ?? 0);
    $gradeRaw = trim((string)($_POST['grade'] ?? ''));
    $feedback = trim((string)($_POST['feedback'] ?? ''));

    if ($submissionId <= 0 || $studentId <= 0) {
      $errors[] = 'Μη έγκυρη υποβολή.';
    }

    if ($gradeRaw === '' || !is_numeric($gradeRaw)) {
      $errors[] = 'Ο βαθμός είναι υποχρεωτικός και πρέπει να είναι αριθμός.';
    } else {
      $grade = (float)$gradeRaw;
      if ($grade < 0 || $grade > 100) {
        $errors[] = 'Ο βαθμός πρέπει να είναι από 0 έως 100.';
      }
    }

    if (!$errors) {
      // επιβεβαίωση ότι το submission ανήκει στο assignment
      $stmt = $pdo->prepare('
        SELECT s.id
        FROM submissions s
        WHERE s.id = :sid AND s.assignment_id = :aid AND s.student_id = :st
        LIMIT 1
      ');
      $stmt->execute([':sid' => $submissionId, ':aid' => $assignmentId, ':st' => $studentId]);

      if (!$stmt->fetchColumn()) {
        $errors[] = 'Η υποβολή δεν αντιστοιχεί στην εργασία.';
      } else {
        // Insert/Update grade (με βάση τη δική σου δομή)
        $stmt = $pdo->prepare('
          INSERT INTO grades (submission_id, professor_id, grade, feedback)
			VALUES (:sub, :prof, :grade, :fb)

          ON DUPLICATE KEY UPDATE
            grade = VALUES(grade),
            feedback = VALUES(feedback),
            graded_at = CURRENT_TIMESTAMP
        ');
        $stmt->execute([
          ':sub' => $submissionId,
          ':prof' => $profId,
          ':grade' => (float)$grade,
          ':fb' => ($feedback === '' ? null : $feedback),
        ]);

        $success = 'Ο βαθμός καταχωρήθηκε/ενημερώθηκε.';
      }
    }
  }
}

// Υποβολές + grade
$stmt = $pdo->prepare('
  SELECT
    s.id AS submission_id,
    s.student_id,
    u.username,
    u.email,
    s.content,
    s.submitted_at,
    g.grade,
    g.feedback,
    g.graded_at
  FROM submissions s
  JOIN users u ON u.id = s.student_id
  LEFT JOIN grades g ON g.submission_id = s.id
  WHERE s.assignment_id = :aid
  ORDER BY s.id DESC
');
$stmt->execute([':aid' => $assignmentId]);
$subs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$csrf = Security::csrfToken();
?>
<!doctype html>
<html lang="el">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Υποβολές & Βαθμολογία</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body>
<header class="topbar">
  <div class="container topbar__inner">
    <div class="brand"><a href="../index.php">University Prototype</a></div>
    <nav class="nav">
      <a class="btn btn--ghost" href="../dashboard.php">Dashboard</a>
      <a class="btn btn--ghost" href="courses.php">Μαθήματα</a>
      <a class="btn btn--ghost" href="assignments.php?course_id=<?php echo (int)$assignment['course_id']; ?>">Πίσω</a>
      <a class="btn" href="../logout.php">Αποσύνδεση</a>
    </nav>
  </div>
</header>

<main class="container" style="max-width: 1100px;">
  <section class="card">
    <h1>Υποβολές Φοιτητών</h1>
    <p><strong>Μάθημα:</strong> <?php echo Security::e((string)$assignment['course_title']); ?></p>
    <p><strong>Εργασία:</strong> <?php echo Security::e((string)$assignment['assignment_title']); ?></p>

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
  </section>

  <section class="card">
    <h2>Λίστα Υποβολών</h2>

    <?php if (!$subs): ?>
      <p>Δεν υπάρχουν υποβολές ακόμη.</p>
    <?php else: ?>
      <?php foreach ($subs as $s): ?>
        <div class="card" style="margin-top:12px;">
          <p><strong>Φοιτητής:</strong> <?php echo Security::e((string)$s['username']); ?> (<?php echo Security::e((string)$s['email']); ?>)</p>
          <p><strong>Υποβλήθηκε:</strong> <?php echo Security::e((string)$s['submitted_at']); ?></p>

          <div style="background:#f8fafc; border:1px solid #e5e7eb; border-radius:12px; padding:12px; margin:10px 0;">
            <?php echo nl2br(Security::e((string)$s['content'])); ?>
          </div>

          <p>
            <strong>Τρέχων βαθμός:</strong>
            <?php echo ($s['grade'] !== null) ? Security::e((string)$s['grade']) : '—'; ?>
            <?php if ($s['graded_at']): ?>
              <span style="color:#64748b;">(graded: <?php echo Security::e((string)$s['graded_at']); ?>)</span>
            <?php endif; ?>
          </p>

          <form class="form" method="post" action="">
            <input type="hidden" name="csrf_token" value="<?php echo Security::e($csrf); ?>" />
            <input type="hidden" name="submission_id" value="<?php echo (int)$s['submission_id']; ?>" />
            <input type="hidden" name="student_id" value="<?php echo (int)$s['student_id']; ?>" />

            <label>Βαθμός (0-100)
              <input name="grade" type="number" min="0" max="100" step="0.01"
                     value="<?php echo ($s['grade'] !== null) ? Security::e((string)$s['grade']) : ''; ?>" required />
            </label>

            <label>Σχόλιο (προαιρετικό)
              <input name="feedback" type="text" maxlength="500"
                     value="<?php echo Security::e((string)($s['feedback'] ?? '')); ?>" />
            </label>

            <button class="btn" type="submit">Καταχώρηση Βαθμού</button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>
</main>
</body>
</html>
