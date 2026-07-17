<?php
// cron/purge_expired_links.php

define('REQUIRE_AUTH', false);

// 1. Enforce that this script can ONLY be executed via system terminal/CLI boundaries
if (php_sapi_name() !== 'cli') {
  header("HTTP/1.1 403 Forbidden");
  echo "Terminal CLI access execution vectors only.";
  exit;
}

// 2. Bootstrap system core layers (relative mapping tracking to config/bootstrap.php)
require_once __DIR__ . '/../config/bootstrap.php';

$db = Database::getConnection();

// Determine our retention cut-off barrier threshold date (Exactly 14 days ago)
$retentionCutoff = date('Y-m-d H:i:s', strtotime('-14 days'));

echo "[" . date('Y-m-d H:i:s') . "] Initializing automated data pruning pass...\n";

try {
  // 3. Find the currently active live record id for EVERY slug to establish an immunization block
  // This blocks our system from dropping the current live route if no upgrades occurred in 2 weeks.
  $slugStmt = $db->query("SELECT DISTINCT slug FROM redirect_links");
  $slugs = $slugStmt->fetchAll(PDO::FETCH_COLUMN);

  $protectedIds = [0]; // Seed array to prevent an empty SQL IN statement exception
  $now = date('Y-m-d H:i:s');

  foreach ($slugs as $slug) {
    $protectCheck = $db->prepare("
            SELECT id FROM redirect_links 
            WHERE slug = ? 
            AND active_at <= ? 
            ORDER BY active_at DESC 
            LIMIT 1
        ");
    $protectCheck->execute([$slug, $now]);
    $activeId = $protectCheck->fetchColumn();

    if ($activeId) {
      $protectedIds[] = (int)$activeId;
    }
  }

  // 4. Trace down physical PDF attachments tied to records about to be deleted
  // This keeps your storage space clean by purging the old files inside public/uploads/
  $protectedPlaceholders = implode(',', $protectedIds);
  $findFilesStmt = $db->prepare("
        SELECT target_url FROM redirect_links 
        WHERE active_at < ? 
        AND id NOT IN ($protectedPlaceholders) 
        AND target_url LIKE '%/uploads/%'
    ");
  $findFilesStmt->execute([$retentionCutoff]);
  $expiredLinks = $findFilesStmt->fetchAll(PDO::FETCH_COLUMN);

  $purgedFilesCount = 0;
  foreach ($expiredLinks as $linkUrl) {
    // Parse the filename from the end of the URL matching your uploads path
    $fileName = basename($linkUrl);
    $physicalFilePath = __DIR__ . '/../public/uploads/' . $fileName;

    // Verify the file physically exists on the disk array structure before running unlinking
    if (!empty($fileName) && file_exists($physicalFilePath) && is_file($physicalFilePath)) {
      if (unlink($physicalFilePath)) {
        $purgedFilesCount++;
      }
    }
  }

  // 5. Finalize execution block pass: Drop the expired database log entries
  $purgeStmt = $db->prepare("
        DELETE FROM redirect_links 
        WHERE active_at < ? 
        AND id NOT IN ($protectedPlaceholders)
    ");
  $purgeStmt->execute([$retentionCutoff]);
  $deletedRows = $purgeStmt->rowCount();

  echo "[" . date('Y-m-d H:i:s') . "] Success! Purged $deletedRows old rows database records and $purgedFilesCount detached uploaded files from disk storage.\n";
} catch (Exception $e) {
  echo "[" . date('Y-m-d H:i:s') . "] CRITICAL PRUNING EXCEPTION: " . $e->getMessage() . "\n";
  exit(1);
}
