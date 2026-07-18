// ==========================================
// 1. GLOBAL UTILITY AND DATETIME METHODS
// ==========================================
const formatUpcomingDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr.replace(/-/g, '/'));
  return (
    d.toLocaleDateString(undefined, {
      month: 'short',
      day: 'numeric',
    }) +
    ' @ ' +
    d.toLocaleTimeString(undefined, {
      hour: 'numeric',
      minute: '2-digit',
    })
  );
};

const checkIsUpcomingItem = (video, nowInstance) => {
  const scheduledTime = video.scheduledStart
    ? new Date(video.scheduledStart.replace(/-/g, '/'))
    : null;
  return (
    Number(video.isUpcoming) === 1 &&
    scheduledTime &&
    scheduledTime.getTime() > nowInstance.getTime()
  );
};

// Sorts values so that any entries appearing in `priorityOrder` come first
// (in that order), followed by everything else alphabetically.
const sortWithPriority = (values, priorityOrder) => {
  return values.sort((a, b) => {
    const indexA = priorityOrder.indexOf(a);
    const indexB = priorityOrder.indexOf(b);
    if (indexA !== -1 && indexB !== -1) return indexA - indexB;
    if (indexA !== -1) return -1;
    if (indexB !== -1) return 1;
    return a.localeCompare(b);
  });
};

// Escapes a string for safe insertion into an HTML attribute or text node.
const escapeHtml = (str) =>
  (str || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

// ==========================================
// 2. MASTER APPLICATION ENGINE
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
  const contentContainer = document.getElementById('video-grid-wrapper');
  const gridElement = document.getElementById('video-grid-element');
  const preacherSelect = document.getElementById('preacherFilter');
  const worshipTypeSelect = document.getElementById('worshipTypeFilter');
  const upcomingToggle = document.getElementById('upcomingToggle');
  const noResultsMsg = document.getElementById('no-results-msg');
  const sentinel = document.getElementById('infinite-scroll-sentinel');

  const ITEMS_PER_PAGE = 12;
  const PREACHER_ORDER = [
    'Rev. Stephanie Anthony',
    'Rev. Taylor Hall',
    'Rev. Dr. Kathryn Foster',
  ];
  const WORSHIP_TYPE_ORDER = [
    'Contemporary',
    'Traditional',
    'Special',
    'Arabic',
  ];

  let currentRenderedCount = 0;
  let filteredSermons = [];

  const sendHeightToParent = () => {
    if (!contentContainer) return;
    parent.postMessage(
      {
        type: 'resize-iframe',
        height: contentContainer.offsetHeight,
      },
      '*',
    );
  };

  // ==========================================
  // POPULATE OPTION MENUS
  // ==========================================
  // Collects unique values for a given field across all sermons, sorts them
  // with any preferred entries first, and appends them as <option> elements.
  const populateFilterOptions = (selectEl, extractValues, priorityOrder) => {
    const values = new Set();
    allSermons.forEach((sermon) =>
      extractValues(sermon).forEach((v) => values.add(v)),
    );

    const sortedValues = sortWithPriority(Array.from(values), priorityOrder);
    sortedValues.forEach((value) => {
      const opt = document.createElement('option');
      opt.value = value;
      opt.textContent = value;
      selectEl.appendChild(opt);
    });
  };

  populateFilterOptions(
    preacherSelect,
    (sermon) =>
      sermon.preacher ? sermon.preacher.split(',').map((p) => p.trim()) : [],
    PREACHER_ORDER,
  );

  populateFilterOptions(
    worshipTypeSelect,
    (sermon) => (sermon.worshipType ? [sermon.worshipType] : []),
    WORSHIP_TYPE_ORDER,
  );

  // ==========================================
  // CARD RENDERING
  // ==========================================
  const buildCardHTML = (video, nowInstance) => {
    const safeTitle = escapeHtml(video.title);
    const isUpcoming = checkIsUpcomingItem(video, nowInstance);
    const liveDateText = formatUpcomingDate(video.scheduledStart);

    const preacherHTML = video.preacher
      ? `
        <li class="video-preacher">
          ${video.preacher
            .split(',')
            .map((p) => `<span>${escapeHtml(p.trim())}</span>`)
            .join('')}
        </li>
      `
      : '';

    const worshipTypeHTML = video.worshipType
      ? `<li class="video-worship-type">${escapeHtml(video.worshipType)}</li>`
      : '';

    const upcomingHTML = isUpcoming
      ? `<li class="video-upcoming">Upcoming: ${liveDateText}</li>`
      : '';

    return `
      <a href="https://www.youtube.com/watch?v=${video.video_id}" target="_blank" rel="noopener">
        <div class="video-card">
          <div class="thumbnail-wrapper">
            <img src="${video.thumbnail}" alt="${safeTitle}" loading="lazy">
          </div>
          <div class="video-info">
            <h2 class="video-title">${safeTitle}</h2>
            <ul class="video-meta-details">
              ${upcomingHTML}
              ${preacherHTML}
              ${worshipTypeHTML}
            </ul>
          </div>
        </div>
      </a>
    `;
  };

  // ==========================================
  // BATCH RENDERING ENGINE
  // ==========================================
  const renderNextBatch = () => {
    const nextBatch = filteredSermons.slice(
      currentRenderedCount,
      currentRenderedCount + ITEMS_PER_PAGE,
    );
    if (nextBatch.length === 0) return;

    const currentNow = new Date();
    const cardsHTML = nextBatch
      .map((video) => buildCardHTML(video, currentNow))
      .join('');
    gridElement.insertAdjacentHTML('beforeend', cardsHTML);

    gridElement.querySelectorAll('img').forEach((img) => {
      img.addEventListener('load', sendHeightToParent);
    });

    currentRenderedCount += nextBatch.length;
    sendHeightToParent();
  };

  // ==========================================
  // RUNTIME INTERACTIVE FILTERING
  // ==========================================
  const handleFilterChange = () => {
    const selectedPreacher = preacherSelect.value;
    const selectedWorshipType = worshipTypeSelect.value;
    const showUpcoming = upcomingToggle.checked;
    const currentNow = new Date();

    gridElement.querySelectorAll('a').forEach((el) => el.remove());
    currentRenderedCount = 0;

    filteredSermons = allSermons.filter((sermon) => {
      const isItemUpcoming = checkIsUpcomingItem(sermon, currentNow);
      if (!showUpcoming && isItemUpcoming) return false;

      const preacherMatches =
        selectedPreacher === 'all' ||
        (sermon.preacher && sermon.preacher.includes(selectedPreacher));
      const worshipTypeMatches =
        selectedWorshipType === 'all' ||
        sermon.worshipType === selectedWorshipType;

      return preacherMatches && worshipTypeMatches;
    });

    noResultsMsg.style.display =
      filteredSermons.length === 0 ? 'block' : 'none';
    renderNextBatch();
  };

  preacherSelect.addEventListener('change', handleFilterChange);
  worshipTypeSelect.addEventListener('change', handleFilterChange);
  upcomingToggle.addEventListener('change', handleFilterChange);

  // Initialize display (also seeds filteredSermons from the checkbox's
  // initial state, so a separate pre-filter pass isn't needed).
  handleFilterChange();

  const observer = new IntersectionObserver(
    (entries) => {
      if (entries[0].isIntersecting) renderNextBatch();
    },
    { rootMargin: '150px' },
  );
  observer.observe(sentinel);
  window.addEventListener('resize', sendHeightToParent);
});
