<?php
// includes/WorshipRepository.php

class WorshipRepository
{
  /**
   * Returns services eligible for display: published within the last year,
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
