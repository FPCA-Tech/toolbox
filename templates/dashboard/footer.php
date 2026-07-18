</main>

<footer class="sticky bottom-0 bg-white border-t border-slate-200 p-4 shrink-0 text-xs text-slate-400 font-medium z-30 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
  <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
    <div>
      &copy; <?php echo date('Y'); ?> First Presbyterian Church Allentown.
    </div>
    <div class="flex items-center space-x-2 bg-gray-50 border border-gray-200 px-3 py-1.5 rounded-md text-gray-600 font-mono text-[11px]">
      <span class="relative flex h-2 w-2">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
      </span>
      <span>Server Time:</span>
      <span class="font-bold text-blue-900">
        <?php echo date('l, M j, Y — g:i a T'); ?>
      </span>
    </div>
  </div>
</footer>

</div>
</body>

</html>