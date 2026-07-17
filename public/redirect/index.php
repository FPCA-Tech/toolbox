<?php
// Since this is a public gateway file for the congregation, bypass user session gates
define('REQUIRE_AUTH', false);

// 1. Establish environmental dependencies via the unified bootstrapper
require_once __DIR__ . '/../../config/bootstrap.php';

// THE FIX: Explicitly pull the connection variable from the new Database class layer
$db = Database::getConnection();

// 2. Extract and clean the incoming slug from the request context
$slug = $_GET['slug'] ?? '';
$slug = rtrim(trim($slug), '/');

// Fallback to the main church website if no identifier is provided
if (empty($slug)) {
  header("Location: https://fpcallentown.org/");
  exit;
}

$now = date('Y-m-d H:i:s');

try {
  // 3. Query the newly named target table for the most recent active match
  $stmt = $db->prepare("
        SELECT target_url FROM redirect_links 
        WHERE slug = ? 
        AND active_at <= ? 
        ORDER BY active_at DESC 
        LIMIT 1
    ");

  $stmt->execute([$slug, $now]);
  $link = $stmt->fetchColumn();

  if ($link) {
    // 4. Force strict HTTP anti-caching metrics across intermediate network layers
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");

    // 5. Issue a temporary 302 redirect so the browser repeats this check on next click
    header("Location: " . $link, true, 302);
    exit;
  } else {
    // Graceful fallback to the root domain if the slug isn't found
    header("Location: https://fpcallentown.org/");
    exit;
  }
} catch (PDOException $e) {
  // Graceful error logging backup if database connection hits an issue
  error_log("Public Redirect Engine Error: " . $e->getMessage());
  header("Location: https://fpcallentown.org/");
  exit;
}
