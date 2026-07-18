<?php
// templates/worship-services-gallery.php
//
// Expects $all_videos and $loadError to already be set by the including
// script. $loadError is distinct from "no sermons match" — it means the
// data couldn't be loaded at all (e.g. DB was unreachable).

$all_videos = $all_videos ?? [];
$loadError = $loadError ?? false;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Worship Services Gallery</title>
  <link rel="stylesheet" href="/assets/css/recent-services.css">
</head>

<body>

  <div class="container" id="video-grid-wrapper">
    <?php if ($loadError): ?>
      <p class="load-error-msg">
        Sorry, we couldn't load the worship services list right now. Please try again shortly.
      </p>
    <?php else: ?>
      <div class="filter-panel">
        <h3>Filter By:</h3>
        <div class="filter-controls-wrap">
          <div class="filter-group">
            <label for="preacherFilter">Preacher</label>
            <select id="preacherFilter">
              <option value="all">All Preachers</option>
            </select>
          </div>
          <div class="filter-group">
            <label for="worshipTypeFilter">Worship Type</label>
            <select id="worshipTypeFilter">
              <option value="all">All Worship Types</option>
            </select>
          </div>
        </div>
        <div class="toggle-group">
          <label class="switch">
            <input type="checkbox" id="upcomingToggle" checked>
            <span class="slider"></span>
          </label>
          <label for="upcomingToggle" class="toggle-label">Show Upcoming Services</label>
        </div>
      </div>

      <div class="video-grid" id="video-grid-element">
        <p id="no-results-msg">No worship services match the selected filters.</p>
      </div>
      <div id="infinite-scroll-sentinel"></div>
    <?php endif; ?>
  </div>

  <?php if (!$loadError): ?>
    <script>
      const allSermons = <?php echo json_encode($all_videos, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    </script>
    <script src="/assets/js/recent-services.js"></script>
  <?php endif; ?>
</body>

</html>