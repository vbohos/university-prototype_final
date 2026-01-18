<?php
declare(strict_types=1);

require __DIR__ . '/../app/includes/header.php';

Auth::requireRole([1]); // μόνο φοιτητές
$studentId = (int)($_SESSION['user_id'] ?? 0);

$courseId = (int)($_GET['course_id'] ?? 0);
if ($courseId <= 0) {
  header('Location: courses.php');
  exit;
}

// Έλεγχος ότι ο student είναι εγγεγραμμένος στο μάθημα
$stmt = $pdo->prepare('SELECT 1 FROM enrollments WHERE course_id = :c AND student_id = :s LIMIT 1');
$stmt->execute([':c' => $courseId, ':s' => $studentId]);
if (!$stmt->fetchColumn()) {
  http_response_code(403);
  header('Location: ../forbidden.php');
  exit;
}

// Φέρνουμε στοιχεία μαθήματος
$stmt = $pdo->prepare('
  SELECT c.id, c.title, c.description, u.username AS professor_name
  FROM courses c
  JOIN users u ON u.id = c.professor_id
  WHERE c.id = :c
  LIMIT 1
');
$stmt->execute([':c' => $courseId]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
  header('Location: courses.php');
  exit;
}

// Φέρνουμε εργασίες + αν έχει ήδη υποβάλει ο student
$stmt = $pdo->prepare('
  SELECT
    a.id,
    a.title,
    a.description,
    a.due_at,
    a.created_at,
    s.id AS submission_id,
    s.submitted_at
  FROM assignments a
  LEFT JOIN submissions s
    ON s.assignment_id = a.id AND s.student_id = :sid
  WHERE a.course_id = :cid
  ORDER BY a.id DESC
');
$stmt->execute([':sid' => $studentId, ':cid' => $courseId]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <p><strong>Καθηγητής:</strong> <?php echo Security::e((string)$course['professor_name']); ?></p>
    <?php if (!empty($course['description'])): ?>
      <p><?php echo Security::e((string)$course['description']); ?></p>
    <?php endif; ?>
  </section>

  <section class="card">
    <h2>Λίστα Εργασιών</h2>

    <?php if (!$assignments): ?>
      <p>Δεν υπάρχουν εργασίες ακόμη.</p>
    <?php else: ?>
      <div style="overflow:auto;">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">ID</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Τίτλος</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Προθεσμία</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Κατάσταση</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Ενέργεια</th>
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
                <?php if (!empty($a['submission_id'])): ?>
                  Υποβλημένη (<?php echo Security::e((string)$a['submitted_at']); ?>)
                <?php else: ?>
                  Μη υποβλημένη
                <?php endif; ?>
              </td>
              <td style="padding:10px; border-bottom:1px solid #f1f5f9;">
                <?php if (!empty($a['submission_id'])): ?>
                  <span>—</span>
                <?php else: ?>
                  <a class="btn btn--ghost" href="submit.php?assignment_id=<?php echo (int)$a['id']; ?>">Υποβολή</a>
                <?php endif; ?>
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
