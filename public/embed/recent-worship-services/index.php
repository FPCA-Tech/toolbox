<?php
// public/embed/recent-worship-services/index.php

// 1. Explicitly opt-out of authentication for this embed page
define('REQUIRE_AUTH', false);

// 2. Load the bootstrap (handles environment and global $pdo initialization)
require_once __DIR__ . '/../../../config/bootstrap.php';

// 3. Load the repository
require_once __DIR__ . '/../../../includes/WorshipRepository.php';

$all_videos = [];
$loadError = false;

try {
  // Use the global $pdo automatically created by bootstrap.php
  // You do NOT need to call getDatabaseConnection() again here
  $all_videos = WorshipRepository::getDisplayableServices($pdo);
} catch (Throwable $e) {
  error_log('Worship Services Gallery Error: ' . $e->getMessage());
  $loadError = true;
}

require __DIR__ . '/../../../templates/pages/worship-services-gallery.php';
