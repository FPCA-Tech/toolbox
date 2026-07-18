<?php
// public/index.php

// Initialize environment and load data
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../templates/dashboard/header.php';
?>

<h1 class="text-3xl font-bold mb-8 text-slate-800 tracking-tight">Available Tools</h1>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
  <?php foreach ($tools as $tool):
    // Get the tool's target attribute, defaulting to "_self" if not set
    $tool['target'] = $tool['target'] ?? '_self';
    // Fallback placeholder icon indicator if one isn't mapped out inside config.php
    $tool['icon'] = $tool['icon'] ?? '⚡';
  ?>
    <a href="<?= htmlspecialchars($tool['path']); ?>" target="<?= $tool['target']; ?>" rel="noopener noreferrer"
      class="block p-5 bg-white rounded-xl border border-slate-200/80 shadow-sm hover:shadow-md hover:border-slate-300/80 transition-all active:scale-[0.99] flex flex-col justify-between">

      <div>
        <div class="flex items-center space-x-3 mb-3">
          <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-50 border border-slate-100 text-lg shadow-sm text-slate-700 shrink-0">
            <?= $tool['icon']; ?>
          </div>
          <h5 class="text-base font-bold tracking-tight text-slate-900 leading-snug">
            <?= htmlspecialchars($tool['name']); ?>
          </h5>
        </div>

        <p class="font-normal text-xs text-slate-500 leading-relaxed line-clamp-3">
          <?= htmlspecialchars($tool['desc']); ?>
        </p>
      </div>

    </a>
  <?php endforeach; ?>
</div>

<?php
require_once __DIR__ . '/../templates/dashboard/footer.php';
?>