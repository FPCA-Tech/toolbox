<?php
// includes/YoutubeSync.php
//
// Pulls the full playlist history from the YouTube Data API and upserts it
// into the youtube_worship_services table. This is the exact logic that used to live
// inline in index.php — it's unchanged in behavior, just organized and run
// from sync.php (cron) instead of on the request path.

class YoutubeWorshipServicesSync
{
  public static function run(PDO $pdo): void
  {
    $items = self::fetchPlaylistItems();

    if (empty($items)) {
      echo "No playlist items returned; nothing to sync." . PHP_EOL;
      return;
    }

    $detailsLookup = self::fetchVideoDetails($items);

    $upsertStmt = $pdo->prepare("
            INSERT INTO youtube_worship_services (video_id, title, preacher, worship_type, is_upcoming, privacy_status, upload_status, scheduled_start, thumbnail, published_at)
            VALUES (:video_id, :title, :preacher, :worshipType, :is_upcoming, :privacy_status, :upload_status, :scheduled_start, :thumbnail, :published_at)
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                preacher = VALUES(preacher),
                worship_type = VALUES(worship_type),
                is_upcoming = VALUES(is_upcoming),
                privacy_status = VALUES(privacy_status),
                upload_status = VALUES(upload_status),
                scheduled_start = VALUES(scheduled_start),
                thumbnail = VALUES(thumbnail)
        ");
    $deleteStmt = $pdo->prepare('DELETE FROM youtube_worship_services WHERE video_id = :video_id');

    // Oldest published first, so inserts happen in a stable, readable order.
    $items = array_reverse($items);

    foreach ($items as $item) {
      self::upsertVideo($item, $detailsLookup, $upsertStmt, $deleteStmt);
    }

    $updateSyncStmt = $pdo->prepare("UPDATE site_settings SET setting_value = :current_time WHERE setting_key = 'last_youtube_worship_services_sync'");
    $updateSyncStmt->execute([':current_time' => time()]);

    echo 'Synced ' . count($items) . ' playlist items.' . PHP_EOL;
  }

  /**
   * Pages through the full playlist via playlistItems.list.
   */
  private static function fetchPlaylistItems(): array
  {
    $nextPageToken = '';
    $allItems = [];

    do {
      $tokenParam = !empty($nextPageToken) ? '&pageToken=' . $nextPageToken : '';
      $apiUrl = 'https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=50'
        . $tokenParam . '&playlistId=' . YT_PLAYLIST . '&key=' . YT_API_KEY;

      $data = self::fetchJson($apiUrl);

      if ($data === null) {
        error_log('YoutubeSync: playlist page fetch failed, stopping pagination early');
        break;
      }

      if (!empty($data['items'])) {
        $allItems = array_merge($allItems, $data['items']);
      }

      $nextPageToken = $data['nextPageToken'] ?? '';
    } while (!empty($nextPageToken));

    return $allItems;
  }

  /**
   * Batches video IDs into groups of 50 and fetches snippet/liveStreamingDetails/status
   * for each batch via videos.list.
   */
  private static function fetchVideoDetails(array $items): array
  {
    $detailsLookup = [];

    foreach (array_chunk($items, 50) as $chunk) {
      $videoIds = array_map(
        static fn(array $item) => $item['snippet']['resourceId']['videoId'],
        $chunk
      );

      $url = 'https://www.googleapis.com/youtube/v3/videos?part=snippet,liveStreamingDetails,status&id='
        . implode(',', $videoIds) . '&key=' . YT_API_KEY;

      $data = self::fetchJson($url);

      if ($data === null) {
        error_log('YoutubeSync: video details fetch failed for a batch, skipping it');
        continue;
      }

      foreach ($data['items'] ?? [] as $videoItem) {
        $detailsLookup[$videoItem['id']] = [
          'tags'                => $videoItem['snippet']['tags'] ?? [],
          'liveBroadcastContent' => $videoItem['snippet']['liveBroadcastContent'] ?? 'none',
          'scheduledStartTime'  => $videoItem['liveStreamingDetails']['scheduledStartTime'] ?? null,
          'privacyStatus'       => $videoItem['status']['privacyStatus'] ?? 'public',
          'uploadStatus'        => $videoItem['status']['uploadStatus'] ?? 'processed',
        ];
      }
    }

    return $detailsLookup;
  }

  private static function upsertVideo(array $item, array $detailsLookup, PDOStatement $upsertStmt, PDOStatement $deleteStmt): void
  {
    $snippet    = $item['snippet'];
    $videoId    = $snippet['resourceId']['videoId'];
    $titleLower = strtolower($snippet['title'] ?? '');

    // HARD SAFETY EXCLUSION: scrub anything YouTube reports as a deleted/private placeholder.
    if (strpos($titleLower, 'deleted video') !== false || strpos($titleLower, 'private video') !== false) {
      $deleteStmt->execute([':video_id' => $videoId]);
      return;
    }

    // HARD DATA LOOKUP SAFETY CHECK: no details returned at all means an orphaned item.
    if (!isset($detailsLookup[$videoId])) {
      $deleteStmt->execute([':video_id' => $videoId]);
      return;
    }

    $details = $detailsLookup[$videoId];
    [$preacherString, $worshipType] = self::parseTags($details['tags']);

    $isUpcoming = ($details['liveBroadcastContent'] === 'upcoming') ? 1 : 0;

    $scheduledStart = null;
    if (!empty($details['scheduledStartTime'])) {
      $utcDateTime = new DateTime($details['scheduledStartTime']);
      $utcDateTime->setTimezone(new DateTimeZone('America/New_York'));
      $scheduledStart = $utcDateTime->format('Y-m-d H:i:s');
    }

    $upsertStmt->execute([
      ':video_id'        => $videoId,
      ':title'           => $snippet['title'],
      ':preacher'        => $preacherString,
      ':worshipType'     => $worshipType,
      ':is_upcoming'     => $isUpcoming,
      ':privacy_status'  => $details['privacyStatus'],
      ':upload_status'   => $details['uploadStatus'],
      ':scheduled_start' => $scheduledStart,
      ':thumbnail'       => $snippet['thumbnails']['high']['url'] ?? $snippet['thumbnails']['medium']['url'] ?? '',
      ':published_at'    => date('Y-m-d H:i:s', strtotime($snippet['publishedAt'])),
    ]);
  }

  /**
   * Reads preacher:/worshipType: tags off a video's tag list.
   * @return array{0: ?string, 1: ?string} [preacherString, worshipType]
   */
  private static function parseTags(array $tags): array
  {
    $preacherArray = [];
    $worshipType   = null;

    foreach ($tags as $tag) {
      $tagLower = strtolower($tag);
      if (strpos($tagLower, 'preacher:') === 0) {
        $preacherArray[] = trim(substr($tag, 9));
      }
      if (strpos($tagLower, 'worshiptype:') === 0) {
        $worshipType = trim(substr($tag, 12));
      }
    }

    $preacherString = !empty($preacherArray) ? implode(', ', $preacherArray) : null;

    return [$preacherString, $worshipType];
  }

  private static function fetchJson(string $url): ?array
  {
    $response = @file_get_contents($url);

    if ($response === false) {
      return null;
    }

    $data = json_decode($response, true);

    return is_array($data) ? $data : null;
  }
}
