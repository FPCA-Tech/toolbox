<?php
// cron/youtube_worship_services_sync.php
//
// Cron entry point — pulls the latest videos from YouTube and updates the
// database. This intentionally lives OUTSIDE /public so it's never reachable
// over HTTP; only run it from the command line / a scheduled task.
//
// Suggested crontab entry (runs every hour, matching the default CACHE_TIME):
//   0 * * * * php /path/to/project/cron/youtube_worship_services_sync.php >> /path/to/project/storage/youtube_worship_services_sync.log 2>&1

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/YoutubeWorshipServicesSync.php';

// Guard against overlapping runs — e.g. a slow YouTube API response causing
// one sync to still be in progress when the next cron tick fires.
$lockFile = fopen(__DIR__ . '/../storage/youtube_worship_services_sync.lock', 'c');
if (!$lockFile || !flock($lockFile, LOCK_EX | LOCK_NB)) {
  fwrite(STDERR, 'Sync already running — exiting.' . PHP_EOL);
  exit(1);
}

try {
  $pdo = getDatabaseConnection();

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
