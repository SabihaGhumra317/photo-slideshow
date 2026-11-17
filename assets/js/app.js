/* Theme and language preferences apply to all visitor/admin pages. */
(() => {
  const root = document.documentElement;
  const chosen = localStorage.getItem('photoflow-theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  root.dataset.theme = chosen || (prefersDark ? 'dark' : 'light');
  document.getElementById('themeToggle')?.addEventListener('click', () => {
    const theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    root.dataset.theme = theme;
    localStorage.setItem('photoflow-theme', theme);
  });
  document.getElementById('lang')?.addEventListener('change', e => {
    const lang = e.target.value;
    if (!['en', 'hi', 'gu'].includes(lang)) return;
    document.cookie = `lang=${lang};path=/photo-slideshow/;max-age=31536000;SameSite=Lax`;
    location.reload();
  });
  document.querySelectorAll('.btn').forEach(btn => btn.addEventListener('click', function (event) {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const circle = document.createElement('span');
    circle.className = 'ripple';
    const bounds = btn.getBoundingClientRect();
    circle.style.left = `${event.clientX - bounds.left}px`;
    circle.style.top = `${event.clientY - bounds.top}px`;
    btn.append(circle);
    setTimeout(() => circle.remove(), 650);
  }));
})();
