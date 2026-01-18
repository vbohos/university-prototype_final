<?php
declare(strict_types=1);

final class Database
{
  private static ?PDO $pdo = null;

  public static function get(array $config): PDO
  {
    if (self::$pdo !== null) {
      return self::$pdo;
    }

    $dsn = sprintf(
      'mysql:host=%s;dbname=%s;charset=%s',
      $config['host'],
      $config['name'],
      $config['charset']
    );

    self::$pdo = new PDO($dsn, $config['user'], $config['pass'], [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return self::$pdo;
  }
}
