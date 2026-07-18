// public/assets/js/dashboard.js
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const content = document.getElementById('main-content');
  const title = document.getElementById('sidebar-title');
  const texts = document.querySelectorAll('.sidebar-text');

  // Check current state
  const isCollapsed = sidebar.classList.contains('w-16');

  if (isCollapsed) {
    // EXPANDING: Sidebar width expands first, then text fades in
    sidebar.classList.remove('w-16');
    sidebar.classList.add('w-64');
    content.classList.remove('md:ml-16');
    content.classList.add('md:ml-64');
    title.classList.remove('hidden');

    texts.forEach((text) => text.classList.remove('hidden'));
    setTimeout(() => {
      texts.forEach((text) => {
        text.classList.remove('opacity-0');
        text.classList.add('opacity-100');
      });
    }, 150);
  } else {
    // COLLAPSING: Text hides/fades immediately to prevent wrapping
    texts.forEach((text) => {
      text.classList.add('hidden');
      text.classList.remove('opacity-100');
      text.classList.add('opacity-0');
    });

    sidebar.classList.remove('w-64');
    sidebar.classList.add('w-16');
    content.classList.remove('md:ml-64');
    content.classList.add('md:ml-16');
    title.classList.add('hidden');
  }
}
