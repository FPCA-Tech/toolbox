<?php
// cron/youtube_worship_services_sync.php

define('REQUIRE_AUTH', false);

// 1. Require bootstrap - this now handles autoload, env, DB connection, and constants
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/YoutubeWorshipServicesSync.php';

// Guard against overlapping runs
$lockFile = fopen(__DIR__ . '/../storage/youtube_worship_services_sync.lock', 'c');
if (!$lockFile || !flock($lockFile, LOCK_EX | LOCK_NB)) {
  fwrite(STDERR, 'Sync already running — exiting.' . PHP_EOL);
  exit(1);
}

try {
  // 2. Use the $pdo initialized in bootstrap.php
  // No need for 'getDatabaseConnection()' here anymore!

  $metaStmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'last_youtube_worship_services_sync' LIMIT 1");
  $metaStmt->execute();
  $lastSync = (int) $metaStmt->fetchColumn();

  if ((time() - $lastSync) < CACHE_TIME) {
    echo 'Skipping — last sync was less than ' . CACHE_TIME . 's ago.' . PHP_EOL;
    exit(0);
  }

  YoutubeWorshipServicesSync::run($pdo);
} catch (Throwable $e) {
  error_log('Sync Error: ' . $e->getMessage());
  fwrite(STDERR, 'Sync failed: ' . $e->getMessage() . PHP_EOL);
  exit(1);
} finally {
  flock($lockFile, LOCK_UN);
  fclose($lockFile);
}
