<?php
declare(strict_types=1);

require __DIR__ . '/../app/includes/header.php';

// μόνο φοιτητές
Auth::requireRole([1]);

$studentId = (int)($_SESSION['user_id'] ?? 0);

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = (string)($_POST['csrf_token'] ?? '');
  if (!Security::verifyCsrf($token)) {
    $errors[] = 'Μη έγκυρο CSRF token.';
  } else {
    $courseId = (int)($_POST['course_id'] ?? 0);
    if ($courseId <= 0) {
      $errors[] = 'Μη έγκυρο μάθημα.';
    } else {
      // Εγγραφή (αν υπάρχει ήδη, δεν θα μπει λόγω PK)
      try {
        $stmt = $pdo->prepare('INSERT INTO enrollments (course_id, student_id) VALUES (:c, :s)');
        $stmt->execute([':c' => $courseId, ':s' => $studentId]);
        $success = 'Η εγγραφή στο μάθημα ολοκληρώθηκε.';
      } catch (PDOException $ex) {
        // 23000 = duplicate key (ή constraint)
        if ($ex->getCode() === '23000') {
          $errors[] = 'Είστε ήδη εγγεγραμμένος σε αυτό το μάθημα.';
        } else {
          $errors[] = 'Σφάλμα εγγραφής στο μάθημα.';
        }
      }
    }
  }
}

// Φέρνουμε όλα τα courses + αν είναι enrolled ο student
$stmt = $pdo->prepare('
  SELECT
    c.id,
    c.title,
    c.description,
    c.created_at,
    u.username AS professor_name,
    (e.student_id IS NOT NULL) AS is_enrolled
  FROM courses c
  JOIN users u ON u.id = c.professor_id
  LEFT JOIN enrollments e ON e.course_id = c.id AND e.student_id = :sid
  ORDER BY c.id DESC
');
$stmt->execute([':sid' => $studentId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$csrf = Security::csrfToken();
?>
<!doctype html>
<html lang="el">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Τα Μαθήματά μου</title>
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
    <h1>Μαθήματα (Φοιτητής)</h1>
    <p>Δείτε τα διαθέσιμα μαθήματα και κάντε εγγραφή.</p>

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
    <h2>Διαθέσιμα Μαθήματα</h2>

    <?php if (!$courses): ?>
      <p>Δεν υπάρχουν διαθέσιμα μαθήματα ακόμη.</p>
    <?php else: ?>
      <div style="overflow:auto;">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">ID</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Τίτλος</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Καθηγητής</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Κατάσταση</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Ενέργεια</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Εργασίες</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($courses as $c): ?>
            <tr>
              <td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?php echo (int)$c['id']; ?></td>
              <td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?php echo Security::e((string)$c['title']); ?></td>
              <td style="padding:10px; border-bottom:1px solid #f1f5f9;"><?php echo Security::e((string)$c['professor_name']); ?></td>
              <td style="padding:10px; border-bottom:1px solid #f1f5f9;">
                <?php echo ((int)$c['is_enrolled'] === 1) ? 'Εγγεγραμμένος' : 'Μη εγγεγραμμένος'; ?>
              </td>
              <td style="padding:10px; border-bottom:1px solid #f1f5f9;">
                <?php if ((int)$c['is_enrolled'] === 1): ?>
                  <span>—</span>
                <?php else: ?>
                  <form method="post" action="" style="margin:0;">
                    <input type="hidden" name="csrf_token" value="<?php echo Security::e($csrf); ?>" />
                    <input type="hidden" name="course_id" value="<?php echo (int)$c['id']; ?>" />
                    <button class="btn btn--ghost" type="submit">Εγγραφή</button>
                  </form>
                <?php endif; ?>
              </td>
              <td style="padding:10px; border-bottom:1px solid #f1f5f9;">
                <?php if ((int)$c['is_enrolled'] === 1): ?>
                  <a class="btn btn--ghost" href="assignments.php?course_id=<?php echo (int)$c['id']; ?>">Προβολή</a>
                <?php else: ?>
                  <span>—</span>
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
