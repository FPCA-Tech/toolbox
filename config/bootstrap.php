<?php
// config/bootstrap.php

// Force server's runtime environment to match FPCA Timezone for consistent date/time operations
date_default_timezone_set('America/New_York');


if (!defined('REQUIRE_AUTH')) {
  define('REQUIRE_AUTH', true);
}
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/config.php';

// PHP dotenv library
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// GLOBAL TIMEZONE LOCK
date_default_timezone_set('America/New_York');

define('DB_HOST', $_ENV['DB_HOST']);
define('DB_NAME', $_ENV['DB_NAME']);
define('DB_USER', $_ENV['DB_USER']);
define('DB_PASS', $_ENV['DB_PASS']);

define('YT_API_KEY', $_ENV['YT_API_KEY']);
define('YT_PLAYLIST', $_ENV['YT_PLAYLIST']);

// FIXED: Removed the extra '$' and ensured clean cast
define('CACHE_TIME', (int) ($_ENV['CACHE_TIME'] ?? 3600));

// --- AUTHENTICATION & DATABASE INITIALIZATION ---

// 1. Initialize Session
session_start();

try {
  // 2. Initialize Database and Auth using the new class structure
  $pdo = Database::getConnection();
  $auth = new Auth($pdo);
} catch (Exception $e) {
  die("Application setup failed: " . $e->getMessage());
}

// 3. Conditional Authentication Guard
if (defined('REQUIRE_AUTH') && REQUIRE_AUTH === true) {
  if (!$auth->isLoggedIn()) {
    header('Location: /login.php');
    exit;
  }
}

function getVersion()
{
  global $version;
  return $version;
}

// Get Page Details for the current tool based on the request URI
$currentPath = $_SERVER['REQUEST_URI'];
$currentTool = null;

foreach ($tools as $tool) {
  if ($tool['path'] === $currentPath) {
    $currentTool = $tool;
    break;
  }
}

$pageTitle = $currentTool ? $currentTool['name'] : 'FPCA Tech Tools';
$pageDescription = $currentTool ? $currentTool['desc'] : 'FPCA Tech Tools Dashboard';
$pageIcon = $currentTool ? $currentTool['icon'] : '🛠️';

function getPageTitle()
{
  global $pageTitle;
  return $pageTitle;
}

function getPageDescription()
{
  global $pageDescription;
  return $pageDescription;
}

function getPageIcon()
{
  global $pageIcon;
  return $pageIcon;
}
