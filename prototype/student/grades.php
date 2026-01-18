<?php
declare(strict_types=1);

require __DIR__ . '/../app/includes/header.php';

Auth::requireRole([1]);
$studentId = (int)($_SESSION['user_id'] ?? 0);

// Χρησιμοποιούμε 2 διαφορετικά placeholders για να μην υπάρχει HY093
$stmt = $pdo->prepare('
  SELECT
    c.id AS course_id,
    c.title AS course_title,
    a.id AS assignment_id,
    a.title AS assignment_title,
    s.id AS submission_id,
    s.submitted_at,
    g.grade,
    g.feedback,
    g.graded_at
  FROM enrollments e
  JOIN courses c ON c.id = e.course_id
  JOIN assignments a ON a.course_id = c.id
  LEFT JOIN submissions s
    ON s.assignment_id = a.id AND s.student_id = :sid2
  LEFT JOIN grades g
    ON g.submission_id = s.id
  WHERE e.student_id = :sid1
  ORDER BY c.id DESC, a.id DESC
');

$stmt->execute([
  ':sid1' => $studentId,
  ':sid2' => $studentId,
]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by course
$byCourse = [];
foreach ($rows as $r) {
  $cid = (int)$r['course_id'];
  if (!isset($byCourse[$cid])) {
    $byCourse[$cid] = [
      'course_title' => (string)$r['course_title'],
      'items' => []
    ];
  }
  $byCourse[$cid]['items'][] = $r;
}
?>
<!doctype html>
<html lang="el">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Βαθμολογίες</title>
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

<main class="container" style="max-width: 1100px;">
  <section class="card">
    <h1>Βαθμολογίες</h1>
    <p>Εδώ βλέπετε τις βαθμολογίες σας ανά μάθημα και εργασία.</p>
  </section>

  <?php if (!$byCourse): ?>
    <section class="card">
      <p>Δεν είστε εγγεγραμμένος σε κάποιο μάθημα ή δεν υπάρχουν εργασίες.</p>
    </section>
  <?php else: ?>
    <?php foreach ($byCourse as $cid => $cdata): ?>
      <section class="card">
        <h2><?php echo Security::e($cdata['course_title']); ?></h2>

        <div style="overflow:auto;">
          <table style="width:100%; border-collapse:collapse;">
            <thead>
              <tr>
                <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Εργασία</th>
                <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Υποβολή</th>
                <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Βαθμός</th>
                <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Σχόλιο</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($cdata['items'] as $r): ?>
                <tr>
                  <td style="padding:10px; border-bottom:1px solid #f1f5f9;">
                    <?php echo Security::e((string)$r['assignment_title']); ?>
                  </td>

                  <td style="padding:10px; border-bottom:1px solid #f1f5f9;">
                    <?php if (!empty($r['submission_id'])): ?>
                      Υποβλήθηκε: <?php echo Security::e((string)$r['submitted_at']); ?>
                    <?php else: ?>
                      <span style="color:#64748b;">Δεν έχει γίνει υποβολή</span>
                    <?php endif; ?>
                  </td>

                  <td style="padding:10px; border-bottom:1px solid #f1f5f9;">
                    <?php if ($r['grade'] !== null): ?>
                      <strong><?php echo Security::e((string)$r['grade']); ?></strong>
                      <?php if (!empty($r['graded_at'])): ?>
                        <div style="color:#64748b; font-size:0.9em;">
                          (graded: <?php echo Security::e((string)$r['graded_at']); ?>)
                        </div>
                      <?php endif; ?>
                    <?php else: ?>
                      <span style="color:#64748b;">—</span>
                    <?php endif; ?>
                  </td>

                  <td style="padding:10px; border-bottom:1px solid #f1f5f9;">
                    <?php echo $r['feedback'] ? Security::e((string)$r['feedback']) : '—'; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div style="margin-top:10px;">
          <a class="btn btn--ghost" href="assignments.php?course_id=<?php echo (int)$cid; ?>">Προβολή εργασιών μαθήματος</a>
        </div>
      </section>
    <?php endforeach; ?>
  <?php endif; ?>
</main>
</body>
</html>
