/* V60 — UX mobile-first progressive et non intrusive */
(function(){
  'use strict';

  function rel(path){
    const current = location.pathname.split('/pssr/')[1] || '';
    const depth = current.split('/').filter(Boolean).length - 1;
    const prefix = depth > 0 ? '../'.repeat(depth) : './';
    return prefix + path.replace(/^\.\//,'');
  }

  function enhanceImages(){
    document.querySelectorAll('img').forEach(img => {
      if(!img.hasAttribute('loading') && !img.closest('.ev39-brand')) img.loading = 'lazy';
      if(!img.hasAttribute('decoding')) img.decoding = 'async';
    });
    document.querySelectorAll('video').forEach(video => {
      if(!video.hasAttribute('preload')) video.preload = 'metadata';
      video.setAttribute('playsinline','');
    });
  }

  function addBottomCta(){
    if(document.querySelector('.ev60-bottom-cta')) return;
    if(location.pathname.includes('/admin') || location.pathname.includes('/member') || location.pathname.includes('/coach')) return;
    const nav = document.createElement('nav');
    nav.className = 'ev60-bottom-cta';
    nav.setAttribute('aria-label','Actions rapides');
    nav.innerHTML = `<a href="${rel('reservation.html')}">Réserver</a><a href="${rel('index.html#contact')}">Contact</a>`;
    document.body.appendChild(nav);
  }

  function enhanceReservationForm(){
    const form = document.getElementById('reservation-form');
    if(!form || form.dataset.ev60Ready === 'true') return;
    form.dataset.ev60Ready = 'true';

    const intro = document.createElement('div');
    intro.className = 'ev60-info-strip';
    intro.innerHTML = '<strong>Réservation en 2 minutes.</strong><br>Choisissez une activité, laissez vos coordonnées, puis recevez la référence de virement et le QR SEPA.';
    form.insertAdjacentElement('beforebegin', intro);

    const select = form.elements.creneau;
    if(select){
      const filters = document.createElement('div');
      filters.className = 'ev60-card-grid';
      filters.setAttribute('aria-label','Filtrer les activités par public');
      const groups = [
        ['6-12','Enfant 6–12','Kids Move'],
        ['13-17','Jeune 13–17','Cardio Fit Ados|Training Jeunes|Initiation Boxe'],
        ['18+','Adulte 18+','Fitness Loisirs|Boxing Loisir|Initiation Boxe'],
        ['40','Adulte 40+','Mobility']
      ];
      filters.innerHTML = groups.map(([key,label]) => `<button type="button" class="ev60-card" data-activity-filter="${key}"><strong>${label}</strong><span>Voir les activités adaptées</span></button>`).join('');
      select.closest('label')?.insertAdjacentElement('beforebegin', filters);
      filters.addEventListener('click', event => {
        const btn = event.target.closest('[data-activity-filter]');
        if(!btn) return;
        const group = groups.find(g => g[0] === btn.dataset.activityFilter);
        if(!group) return;
        const re = new RegExp(group[2], 'i');
        const option = Array.from(select.options).find(o => re.test(o.textContent));
        if(option){ select.value = option.value; select.dispatchEvent(new Event('change',{bubbles:true})); }
        select.focus({preventScroll:true});
      });
    }

    const tel = form.querySelector('input[type="tel"]');
    if(tel){ tel.autocomplete = 'tel'; tel.inputMode = 'tel'; }
    const email = form.querySelector('input[type="email"]');
    if(email) email.autocomplete = 'email';
    const name = form.querySelector('input[name="nom"]');
    if(name) name.autocomplete = 'name';
  }

  function makeLongSectionsProgressive(){
    document.querySelectorAll('.detail-card-v30, .activity-detail').forEach(card => {
      if(card.dataset.ev60Details === 'true') return;
      const list = card.querySelector('ul');
      if(!list || list.children.length <= 2) return;
      card.dataset.ev60Details = 'true';
      const details = document.createElement('details');
      details.className = 'ev60-details';
      details.innerHTML = '<summary>Voir les détails</summary>';
      details.appendChild(list);
      card.appendChild(details);
    });
  }

  function init(){
    document.documentElement.classList.add('ev60-js');
    enhanceImages();
    addBottomCta();
    enhanceReservationForm();
    makeLongSectionsProgressive();
  }

  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, {once:true});
  else init();
})();
