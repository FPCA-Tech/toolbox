<?php
// templates/dashboard/header.php

// 1. Force structural scope visibility across nested subfolder execution depths
global $tools;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FPCA Toolbelt Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="/assets/js/dashboard.js" defer></script>
</head>

<body class="bg-gray-100 flex min-h-screen">

  <aside id="sidebar" class="w-64 h-screen bg-gray-900 text-white fixed hidden md:flex flex-col transition-all duration-300 z-20">
    <div class="p-6 flex justify-between items-center">
      <a href="/" class="block">
        <img src="/assets/images/logo.png" alt="FPCA Tech Tools" class="w-40 h-auto">
      </a>
      <button onclick="toggleSidebar()" class="text-gray-400 focus:outline-none">☰</button>
    </div>
    <nav class="mt-6 flex-grow overflow-y-auto">

      <a href="/" class="block py-2.5 px-4 <?php echo ($_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] === '/index.php') ? 'bg-gray-700 border-l-4 border-blue-500' : 'hover:bg-gray-700'; ?>">
        <span class="text-xl mr-4 flex-shrink-0">🏠</span>
        <span class="sidebar-text transition-opacity duration-300 opacity-100 whitespace-nowrap overflow-hidden">
          Home
        </span>
      </a>

      <?php if (!empty($tools)): ?>
        <?php foreach ($tools as $tool):
          $tool['target'] = $tool['target'] ?? '_self';
          $isActive = ($_SERVER['REQUEST_URI'] === $tool['path'] || strpos($_SERVER['REQUEST_URI'], rtrim($tool['path'], '/')) === 0);
          $activeClass = $isActive ? 'bg-gray-700 border-l-4 border-blue-500' : 'hover:bg-gray-700';
        ?>
          <a href="<?php echo htmlspecialchars($tool['path']); ?>"
            target="<?php echo htmlspecialchars($tool['target']); ?>"
            class="flex items-center py-2.5 px-4 <?php echo $activeClass; ?> whitespace-nowrap overflow-hidden transition-colors">
            <span class="text-xl mr-4 flex-shrink-0 shrink-0"><?php echo $tool['icon']; ?></span>
            <span class="sidebar-text transition-opacity duration-300 opacity-100 whitespace-nowrap overflow-hidden truncate">
              <?php echo htmlspecialchars($tool['name']); ?>
            </span>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>

      <a href="/logout.php" class="block py-2.5 px-4 hover:bg-gray-700 border-t border-gray-800 mt-4 transition-colors">
        <span class="text-xl mr-4 flex-shrink-0">🚪</span>
        <span class="sidebar-text transition-opacity duration-300 opacity-100 whitespace-nowrap overflow-hidden">
          Logout
        </span>
      </a>
    </nav>
    <!-- Print version number in the bottom, left corner of the sidebar -->
    <div class="p-4 text-xs text-gray-400 border-t border-gray-800">
      Version: <?php echo htmlspecialchars(getVersion()); ?>
    </div>
  </aside>

  <div id="main-content" class="flex-1 md:ml-64 flex flex-col transition-all duration-300">

    <header class="sticky top-0 bg-white shadow-sm p-4 z-10 flex justify-between items-center h-16">
      <span class="font-semibold text-lg text-gray-800">FPCA Tech Tools</span>
      <button class="md:hidden text-gray-600 hover:text-gray-900 font-medium text-sm border border-gray-200 px-3 py-1.5 rounded-lg bg-gray-50 focus:outline-none"
        onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
        Menu
      </button>
    </header>

    <nav id="mobile-menu" class="hidden md:hidden bg-gray-900 text-white p-4 shadow-inner">
      <a href="/" class="flex items-center px-4 py-2.5 hover:bg-gray-700 transition-colors rounded-md text-sm">
        <span class="mr-3">🏠</span>
        <span class="sidebar-text">Home</span>
      </a>
      <?php if (!empty($tools)): ?>
        <?php foreach ($tools as $tool): ?>
          <a href="<?php echo htmlspecialchars($tool['path']); ?>"
            target="<?php echo htmlspecialchars($tool['target'] ?? '_self'); ?>"
            class="flex items-center px-4 py-2.5 hover:bg-gray-700 transition-colors rounded-md text-sm">
            <span class="mr-3 shrink-0"><?php echo $tool['icon'] ?? ''; ?></span>
            <span class="sidebar-text truncate"><?php echo htmlspecialchars($tool['name']); ?></span>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
      <a href="/logout.php" class="flex items-center px-4 py-2.5 hover:bg-red-600 transition-colors border-t border-gray-800 mt-2 rounded-md text-sm text-red-300 hover:text-white">
        <span class="mr-3">🚪</span>
        <span class="sidebar-text">Logout</span>
      </a>
    </nav>

    <main class="p-8 flex-grow">