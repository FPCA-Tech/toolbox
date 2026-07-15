<?php
// public/recent-worship-services/index.php
//
// Renders the sermon gallery from whatever is already in the database.
// This file no longer talks to the YouTube API — that happens on a
// schedule via sync.php (see the project root). Keeping the request path
// DB-only means page loads are fast and consistent regardless of when the
// last sync happened.

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/WorshipRepository.php';

$all_videos = [];
$loadError = false;

try {
  $pdo = getDatabaseConnection();
  $all_videos = WorshipRepository::getDisplayableServices($pdo);
} catch (Throwable $e) {
  // Catches PDO connection failures as well as query failures, so a DB
  // outage degrades to a friendly error message instead of a fatal error
  // or a silently empty page.
  error_log('Worship Services Gallery Error: ' . $e->getMessage());
  $loadError = true;
}

require __DIR__ . '/../../templates/worship-services-gallery.php';
