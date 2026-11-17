// Accessible mobile sidebar toggle; ordinary links still work with JS disabled.
(() => {
  const button = document.getElementById('adminMenuButton');
  const sidebar = document.getElementById('adminNav');
  if (!button || !sidebar) return;
  function setOpen(open) {
    sidebar.classList.toggle('is-open', open);
    button.setAttribute('aria-expanded', String(open));
  }
  button.addEventListener('click', () => setOpen(!sidebar.classList.contains('is-open')));
  document.addEventListener('keydown', event => { if (event.key === 'Escape') setOpen(false); });
  document.addEventListener('click', event => {
    if (!sidebar.contains(event.target) && !button.contains(event.target)) setOpen(false);
  });
  sidebar.querySelectorAll('a').forEach(link => link.addEventListener('click', () => setOpen(false)));
})();
