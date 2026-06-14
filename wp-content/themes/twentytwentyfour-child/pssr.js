// Boutons de mois -> remplissent l'input "session"
(function(){
  const list = document.getElementById('i-list');
  const input = document.getElementById('sess');
  if (!list || !input) return;

  const buttons = list.querySelectorAll('.session');
  let first = null;

  buttons.forEach(btn => {
    if (!first) first = btn;
    btn.addEventListener('click', () => {
      buttons.forEach(b => b.classList.remove('selected'));
      btn.classList.add('selected');
      input.value = btn.dataset.month || btn.textContent.trim();
    });
    btn.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); btn.click(); }
    });
  });

  if (first) first.click(); // pré-sélection
})();
