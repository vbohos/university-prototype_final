<?php
declare(strict_types=1);

require __DIR__ . '/app/includes/header.php';

// κοινό dashboard για student(1) και professor(2)
Auth::requireRole([1, 2]);

$roleId = (int)($_SESSION['role_id'] ?? 0);
$username = (string)($_SESSION['username'] ?? '');

$roleLabel = $roleId === 1 ? 'Φοιτητής' : 'Καθηγητής';

// Για student: βρίσκουμε (προαιρετικά) ένα enrolled course για quick link στις εργασίες
$firstCourseId = null;
if ($roleId === 1) {
  $stmt = $pdo->prepare('SELECT course_id FROM enrollments WHERE student_id = :sid ORDER BY course_id ASC LIMIT 1');
  $stmt->execute([':sid' => (int)($_SESSION['user_id'] ?? 0)]);
  $firstCourseId = $stmt->fetchColumn();
  $firstCourseId = $firstCourseId ? (int)$firstCourseId : null;
}
?>
<!doctype html>
<html lang="el">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard</title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
<header class="topbar">
  <div class="container topbar__inner">
    <div class="brand"><a href="index.php">University Prototype</a></div>
    <nav class="nav">
      <a class="btn btn--ghost" href="index.php">Αρχική</a>
      <?php if ($roleId === 1): ?>
        <a class="btn btn--ghost" href="student/courses.php">Μαθήματα</a>
        <a class="btn btn--ghost" href="student/grades.php">Βαθμολογίες</a>
      <?php else: ?>
        <a class="btn btn--ghost" href="professor/courses.php">Διαχείριση Μαθημάτων</a>
      <?php endif; ?>
      <a class="btn" href="logout.php">Αποσύνδεση</a>
    </nav>
  </div>
</header>

<main class="container" style="max-width: 920px;">
  <section class="card">
    <h1>Dashboard</h1>

    <p>Καλώς ήρθες, <strong><?php echo Security::e($username); ?></strong></p>
    <p>Ρόλος: <strong><?php echo Security::e($roleLabel); ?></strong> (role_id=<?php echo $roleId; ?>)</p>

    <?php if ($roleId === 2): ?>
      <div class="alert alert--ok">
        Περιβάλλον Καθηγητή: δημιουργία/διαχείριση μαθημάτων, ανάρτηση εργασιών, προβολή υποβολών, βαθμολόγηση.
      </div>
    <?php else: ?>
      <div class="alert alert--ok">
        Περιβάλλον Φοιτητή: εγγραφή σε μαθήματα, προβολή εργασιών, υποβολή εργασιών, προβολή βαθμολογιών.
      </div>
    <?php endif; ?>
  </section>

  <section class="card">
    <h2>Γρήγορες ενέργειες</h2>

    <?php if ($roleId === 1): ?>
      <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;">
        <a class="btn" href="student/courses.php">Προβολή / Εγγραφή σε Μαθήματα</a>
        <a class="btn btn--ghost" href="student/grades.php">Προβολή Βαθμολογιών</a>

        <?php if ($firstCourseId !== null): ?>
          <a class="btn btn--ghost" href="student/assignments.php?course_id=<?php echo $firstCourseId; ?>">
            Προβολή Εργασιών (ενδεικτικό)
          </a>
        <?php else: ?>
          <span style="color:#64748b; align-self:center;">
            (Για “Εργασίες”, κάνε πρώτα εγγραφή σε μάθημα.)
          </span>
        <?php endif; ?>
      </div>

    <?php else: ?>
      <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;">
        <a class="btn" href="professor/courses.php">Διαχείριση Μαθημάτων</a>
        <a class="btn btn--ghost" href="professor/courses.php">Δημιουργία Μαθήματος</a>
        <a class="btn btn--ghost" href="professor/courses.php">Ανάρτηση Εργασιών (μέσα από μάθημα)</a>
      </div>
    <?php endif; ?>
  </section>
</main>
</body>
</html>
