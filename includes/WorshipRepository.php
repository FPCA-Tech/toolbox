<?php
// includes/SermonRepository.php
//
// Data access for the sermon gallery. Kept separate from public/index.php
// so the query can be reused (e.g. from a future API endpoint) and tested
// independently of the HTTP request flow.
//
// Note: methods here intentionally do NOT catch PDOException — they let it
// bubble up so the caller can decide how to handle a failure (e.g. render
// an error state vs. log and retry).

class WorshipRepository
{
  /**
   * Returns sermons eligible for display: published within the last year,
   * or upcoming, excluding anything private/deleted/rejected.
   */
  public static function getDisplayableServices(PDO $pdo): array
  {
    $stmt = $pdo->prepare("
            SELECT video_id, title, preacher, thumbnail, published_at,
                   worship_type AS worshipType,
                   is_upcoming AS isUpcoming,
                   scheduled_start AS scheduledStart
            FROM youtube_worship_services
            WHERE (published_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR) OR scheduled_start > NOW() OR is_upcoming = 1)
              AND privacy_status != 'private'
              AND upload_status NOT IN ('deleted', 'failed', 'rejected')
              AND title NOT LIKE '%Deleted video%'
              AND title NOT LIKE '%Private video%'
            ORDER BY CASE WHEN is_upcoming = 1 THEN 0 ELSE 1 END, published_at DESC
        ");
    $stmt->execute();

    return $stmt->fetchAll();
  }
}
