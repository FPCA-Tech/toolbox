<?php
// config/bootstrap.php
//
// Shared setup used by BOTH the web app (public/index.php) and the cron
// sync script (sync.php). Keeping this in one place means the two entry
// points can never drift out of sync on env handling, timezone, etc.

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// GLOBAL TIMEZONE LOCK: Forces execution to Eastern Time rules uniformly,
// regardless of server default.
date_default_timezone_set('America/New_York');

define('DB_HOST', $_ENV['DB_HOST']);
define('DB_NAME', $_ENV['DB_NAME']);
define('DB_USER', $_ENV['DB_USER']);
define('DB_PASS', $_ENV['DB_PASS']);

define('YT_API_KEY', $_ENV['YT_API_KEY']);
define('YT_PLAYLIST', $_ENV['YT_PLAYLIST']);

// Minimum seconds between YouTube syncs. Only consulted by sync.php now —
// the web app no longer triggers syncs itself.
define('CACHE_TIME', (int) ($_ENV['CACHE_TIME'] ?? 3600));
