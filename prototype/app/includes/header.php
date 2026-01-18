<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Security.php';

// Start session safely
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_name($config['app']['session_name']);
  session_start();
}

// PDO connection
$pdo = Database::get($config['db']);   // $pdo is a PDO instance
$auth = new Auth($pdo);
