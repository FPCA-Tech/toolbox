<?php
// public/tools/links/index.php

// 1. Force explicit user-session protection prior to framework initialization
define('REQUIRE_AUTH', true);

// 2. Load the global Umbrella Bootstrapper
require_once __DIR__ . '/../../../config/bootstrap.php';

// --- THE NAVIGATION FIX ---
// Assign the factory connection to the expected legacy variable so templates/dashboard/header.php can read it
$db = Database::getConnection();

// --- 3. Handle Actions (PRG Pattern) ---
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  $stmt = $db->prepare("DELETE FROM redirect_links WHERE id = ?");
  $stmt->execute([$id]);
  header("Location: ./index.php?msg=deleted");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $slug = $_POST['slug'];
  $active_at = date('Y-m-d H:i:s', strtotime($_POST['active_at']));
  $target_url = $_POST['target_url'] ?? '';

  if ($slug === 'livestream' && !empty($_POST['youtube_id'])) {
    $videoId = trim($_POST['youtube_id']);
    $target_url = "https://www.youtube.com/embed/{$videoId}?autoplay=1";
  } elseif (!empty($_FILES['pdf']['name'])) {
    $uploadDir = '../../../public/uploads/'; // Adjusted path for umbrella layout
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $fileName = time() . '_' . basename($_FILES['pdf']['name']);
    if (move_uploaded_file($_FILES['pdf']['tmp_name'], $uploadDir . $fileName)) {
      $target_url = "https://tools.fpca.tech/uploads/" . $fileName;
    }
  }

  if (!empty($target_url)) {
    $stmt = $db->prepare("INSERT INTO redirect_links (slug, target_url, active_at) VALUES (?, ?, ?)");
    $stmt->execute([$slug, $target_url, $active_at]);
    header("Location: ./index.php?msg=scheduled");
    exit;
  }
}

// --- 4. Prepare Status and Message States ---
$displayMsg = "";
if (isset($_GET['msg'])) {
  if ($_GET['msg'] === 'scheduled') $displayMsg = "Redirect scheduled successfully.";
  if ($_GET['msg'] === 'deleted')   $displayMsg = "Link removed from database.";
}

$now = date('Y-m-d H:i:s');
$links = $db->query("SELECT * FROM redirect_links ORDER BY active_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);

// 5. Render Shared Layout UI Header
require_once __DIR__ . '/../../../templates/dashboard/header.php';
?>

<main class="max-w-6xl mx-auto">

  <?php if ($displayMsg): ?>
    <div class="bg-emerald-100 border-l-4 border-emerald-500 text-emerald-800 p-4 mb-6 shadow-sm flex justify-between rounded-r">
      <span><?php echo htmlspecialchars($displayMsg); ?></span>
      <a href="./index.php" class="px-2 font-bold hover:text-emerald-600">×</a>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">

    <div class="lg:col-span-1">
      <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
        <h2 class="text-lg font-bold mb-4 text-slate-800 border-b pb-2">Schedule Link</h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-4" id="linkForm">
          <div>
            <label class="block text-xs font-bold text-slate-500 uppercase">Slug</label>
            <select name="slug" id="slugSelect" required onchange="toggleInputs()" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm p-2.5 border focus:ring-2 focus:ring-blue-500 outline-none bg-white">
              <option value="livestream">livestream</option>
              <option value="announcements">announcements</option>
            </select>
          </div>

          <div id="youtubeInputGroup">
            <label class="block text-xs font-bold text-slate-500 uppercase">YouTube Video ID</label>
            <input type="text" name="youtube_id" placeholder="e.g. dQw4w9WgXcQ" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm p-2.5 border focus:ring-2 focus:ring-blue-500 outline-none text-sm font-mono">
            <p class="text-[10px] text-slate-400 mt-1 italic">Auto-generates the autoplay embed link.</p>
          </div>

          <div id="urlInputGroup" class="hidden">
            <label class="block text-xs font-bold text-slate-500 uppercase">Destination URL</label>
            <input type="url" name="target_url" placeholder="https://..." class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm p-2.5 border focus:ring-2 focus:ring-blue-500 outline-none text-sm">
          </div>

          <div class="bg-slate-50 p-4 rounded-lg border border-dashed border-slate-300 text-center">
            <label class="block text-xs font-bold text-slate-500 uppercase mb-2 italic">— OR — PDF Upload</label>
            <input type="file" name="pdf" accept="application/pdf" class="block w-full text-xs text-slate-500 cursor-pointer">
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-500 uppercase">Go Live (EDT)</label>
            <input type="datetime-local" name="active_at" required class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm p-2.5 border focus:ring-2 focus:ring-blue-500 outline-none text-sm">
          </div>

          <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 transition-all shadow-md active:scale-95">
            Update Redirect
          </button>
        </form>
      </div>
    </div>

    <div class="lg:col-span-3">
      <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm text-left">
          <thead class="bg-slate-50 text-slate-400 font-bold text-[10px] uppercase tracking-widest">
            <tr>
              <th class="px-6 py-4 w-32">Status</th>
              <th class="px-6 py-4">Slug / Time</th>
              <th class="px-6 py-4">Target Destination</th>
              <th class="px-6 py-4 text-center w-24">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <?php
            $activeFound = [];
            foreach ($links as $row):
              $slug = $row['slug'];
              $isPast = strtotime($row['active_at']) <= strtotime($now);
              $status = 'pending';

              if ($isPast) {
                if (!isset($activeFound[$slug])) {
                  $status = 'live';
                  $activeFound[$slug] = true;
                } else {
                  $status = 'archived';
                }
              }
            ?>
              <tr class="<?php
                          if ($status === 'live') echo 'bg-emerald-50/40';
                          elseif ($status === 'pending') echo 'bg-blue-50/30';
                          else echo 'bg-white opacity-60';
                          ?> transition-colors">

                <td class="px-6 py-4 whitespace-nowrap">
                  <?php if ($status === 'live'): ?>
                    <span class="px-3 py-1 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800 uppercase italic tracking-tighter">Live</span>
                  <?php elseif ($status === 'pending'): ?>
                    <span class="px-3 py-1 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 uppercase italic tracking-tighter">Future</span>
                  <?php else: ?>
                    <span class="px-3 py-1 text-[10px] font-bold rounded-full bg-slate-100 text-slate-500 uppercase italic tracking-tighter">Past</span>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="font-bold <?php echo ($status === 'live') ? 'text-emerald-900' : 'text-blue-900'; ?>">/<?php echo htmlspecialchars($row['slug']); ?></div>
                  <div class="text-[10px] text-slate-400 font-mono"><?php echo date('M j — g:i a', strtotime($row['active_at'])); ?></div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-[11px] truncate w-48 hover:w-auto hover:whitespace-normal">
                    <a href="<?php echo htmlspecialchars($row['target_url']); ?>" target="_blank" class="hover:underline italic <?php echo ($status === 'live') ? 'text-emerald-600' : 'text-blue-400 opacity-70'; ?>">
                      <?php echo htmlspecialchars($row['target_url']); ?>
                    </a>
                  </div>
                </td>
                <td class="px-6 py-4 text-center whitespace-nowrap">
                  <div class="flex items-center justify-center space-x-3">
                    <button onclick="copySlug('<?php echo htmlspecialchars($row['slug']); ?>', this)" class="text-slate-400 hover:text-blue-600" title="Copy Short Link">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                      </svg>
                    </button>
                    <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this record?');" class="text-slate-300 hover:text-red-500">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<script>
  // Form Input View Toggle Controller
  function toggleInputs() {
    const slug = document.getElementById('slugSelect').value;
    const youtubeGroup = document.getElementById('youtubeInputGroup');
    const urlGroup = document.getElementById('urlInputGroup');

    if (slug === 'livestream') {
      youtubeGroup.classList.remove('hidden');
      urlGroup.classList.add('hidden');
    } else {
      youtubeGroup.classList.add('hidden');
      urlGroup.classList.remove('hidden');
    }
  }

  // Extend window.onload safety trigger cleanly without overriding framework scripts
  if (window.onload) {
    var currentOnLoad = window.onload;
    window.onload = function() {
      currentOnLoad();
      toggleInputs();
    };
  } else {
    window.onload = toggleInputs;
  }
</script>

<?php
// 6. Render Shared Layout UI Footer
require_once __DIR__ . '/../../../templates/dashboard/footer.php';
?>