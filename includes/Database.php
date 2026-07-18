<?php
// includes/Database.php

class Database
{
  private static ?PDO $pdo = null;

  /**
   * Returns a single shared PDO connection for the current request.
   */
  public static function getConnection(): PDO
  {
    if (self::$pdo === null) {
      $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

      self::$pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [
          PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
      );
    }

    return self::$pdo;
  }
}
