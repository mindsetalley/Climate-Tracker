const toggle = document.getElementById('navToggle');
toggle?.addEventListener('click', () => {
  const open = document.getElementById('mainNav').classList.toggle('open');
  toggle.setAttribute('aria-expanded', String(open));
});
