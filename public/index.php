<?php
// public/index.php

// Initialize environment and load data
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../templates/dashboard/header.php';
?>

<main class="max-w-6xl mx-auto p-6">
  <h1 class="text-3xl font-bold mb-8">My Toolbelt</h1>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <?php foreach ($tools as $tool):
      // Get the tool's target attribute, defaulting to "_self" if not set
      $tool['target'] = $tool['target'] ?? '_self';
    ?>
      <a href="<?= htmlspecialchars($tool['path']); ?>" target="<?= $tool['target']; ?>" rel="noopener noreferrer"
        class="block p-6 bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
        <h5 class="mb-2 text-xl font-bold tracking-tight text-gray-900">
          <?= htmlspecialchars($tool['name']); ?>
        </h5>
        <p class="font-normal text-gray-700">
          <?= htmlspecialchars($tool['desc']); ?>
        </p>
      </a>
    <?php endforeach; ?>
  </div>
</main>

<?php
require_once __DIR__ . '/../templates/dashboard/footer.php';
?>