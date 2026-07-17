<?php
// includes/Auth.php

class Auth
{
  private $pdo;

  public function __construct(PDO $pdo)
  {
    $this->pdo = $pdo;
  }

  public function login($username, $password)
  {
    $stmt = $this->pdo->prepare("SELECT id, password_hash, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['user_role'] = $user['role'];
      return true;
    }
    return false;
  }

  public function isLoggedIn()
  {
    return isset($_SESSION['user_id']);
  }

  public function hasRole($role)
  {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
  }

  public function logout()
  {
    session_destroy();
  }
}
