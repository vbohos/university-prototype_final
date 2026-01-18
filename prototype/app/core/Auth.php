<?php
declare(strict_types=1);

final class Auth
{
  private PDO $db;

  private const STUDENT_CODE = 'STUD2025';
  private const PROFESSOR_CODE = 'PROF2025';

  /**
   * Base path of the application in the URL.
   * For XAMPP: http://localhost/prototype/... => APP_BASE = '/prototype'
   */
  private const APP_BASE = '/prototype';

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  public function register(string $username, string $email, string $password, string $role, string $secretCode): array
  {
    $username   = trim($username);
    $email      = trim($email);
    $role       = trim($role);
    $secretCode = trim($secretCode);

    if ($username === '' || $email === '' || $password === '' || $role === '' || $secretCode === '') {
      return ['ok' => false, 'message' => 'Όλα τα πεδία είναι υποχρεωτικά.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return ['ok' => false, 'message' => 'Μη έγκυρο email.'];
    }
    if (mb_strlen($password) < 6) {
      return ['ok' => false, 'message' => 'Ο κωδικός πρέπει να έχει τουλάχιστον 6 χαρακτήρες.'];
    }

    $roleId = null;
    if ($role === 'student' && $secretCode === self::STUDENT_CODE) {
      $roleId = 1;
    } elseif ($role === 'professor' && $secretCode === self::PROFESSOR_CODE) {
      $roleId = 2;
    } else {
      return ['ok' => false, 'message' => 'Λάθος ειδικός κωδικός εγγραφής για τον επιλεγμένο ρόλο.'];
    }

    $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
      return ['ok' => false, 'message' => 'Το email χρησιμοποιείται ήδη.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $this->db->prepare('INSERT INTO users (username, email, password_hash, role_id) VALUES (?, ?, ?, ?)');
    $stmt->execute([$username, $email, $hash, $roleId]);

    return ['ok' => true, 'message' => 'Η εγγραφή ολοκληρώθηκε. Μπορείτε να συνδεθείτε.'];
  }

  public function login(string $email, string $password): array
  {
    $email = trim($email);

    if ($email === '' || $password === '') {
      return ['ok' => false, 'message' => 'Συμπληρώστε email και κωδικό.'];
    }

    $stmt = $this->db->prepare('SELECT id, username, password_hash, role_id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, (string)$user['password_hash'])) {
      return ['ok' => false, 'message' => 'Λάθος email ή κωδικός.'];
    }

    session_regenerate_id(true);
    $_SESSION['user_id']  = (int)$user['id'];
    $_SESSION['username'] = (string)$user['username'];
    $_SESSION['role_id']  = (int)$user['role_id'];

    return ['ok' => true, 'message' => 'Σύνδεση επιτυχής.'];
  }

  public static function check(): bool
  {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['role_id']);
  }

  /**
   * Require that the user is logged in.
   * If not authenticated -> redirect to login (root).
   */
  public static function requireLogin(): void
  {
    if (!self::check()) {
      self::redirect(self::APP_BASE . '/login.php');
    }
  }

  /**
   * Role-based access control (RBAC).
   * - If not logged in -> redirect to login.
   * - If role not allowed -> redirect to forbidden (403 Forbidden Action).
   *
   * @param int[] $allowedRoleIds
   */
  public static function requireRole(array $allowedRoleIds): void
  {
    if (!self::check()) {
      self::redirect(self::APP_BASE . '/login.php');
    }

    $roleId = (int)($_SESSION['role_id'] ?? 0);

    if (!in_array($roleId, $allowedRoleIds, true)) {
      http_response_code(403);
      self::redirect(self::APP_BASE . '/forbidden.php'); // ή unauthorized.php αν αυτό έχεις
    }
  }

  public static function logout(): void
  {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
      $params = session_get_cookie_params();
      setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        (bool)$params['secure'],
        (bool)$params['httponly']
      );
    }

    session_destroy();
  }

  private static function redirect(string $to): void
  {
    header('Location: ' . $to);
    exit;
  }
}
