/* V35 — shell unique issu de la V33 : navigation/footer centralisés + titres/mots-clés. */
(function(){
  const navItems = [
    { label: 'Activités', href: 'calendrier.html' },
    { label: 'Dispositifs', href: 'dispositifs.html' },
    { label: 'PSSR', href: 'pssr.html' }
  ];
  const actionItems = [
    { label: 'Connexion', href: 'dashboard.html', variant: 'secondary' },
    { label: 'Je m’inscris', href: 'inscription.html', variant: 'primary' }
  ];
  const footerColumns = [
    {
      title: 'Menu principal',
      links: [
        { label: 'Accueil', href: 'index.html' },
        { label: 'Activités', href: 'calendrier.html' },
        { label: 'Dispositifs', href: 'dispositifs.html' },
        { label: 'PSSR', href: 'pssr.html' },
        { label: 'Connexion', href: 'dashboard.html' },
        { label: 'Je m’inscris', href: 'inscription.html' }
      ]
    },
    {
      title: 'Ressources',
      links: [
        { label: 'Présentation', href: 'presentation-pssr.html' },
        { label: 'Réserver', href: 'reservation.html' },
        { label: 'Documents', href: 'documents.html' },
        { label: 'FAQ', href: 'faq.html' },
        { label: 'Ressources emploi & insertion', href: 'ressources-emploi-insertion.html' },
        { label: 'Écoles & ATL', href: 'ecoles-atl.html' },
        { label: 'Avis', href: 'avis.html' }
      ]
    },
    {
      title: 'Parcours & thèmes',
      links: [
        { label: 'Ateliers', href: 'dispositifs.html' },
        { label: 'Axes prioritaires', href: 'presentation-pssr.html' },
        { label: 'Partenaires', href: 'ecoles-atl.html' },
        { label: 'Sources formation & emploi', href: 'sources-formation-emploi.html' },
        { label: 'Mot de passe oublié', href: 'mot-de-passe-oublie.html' }
      ]
    }
  ];
  const keywordMap = [
    { test: /^(activit[eé]s?|sport|créneaux|calendrier)$/i, href: 'calendrier.html' },
    { test: /^(dispositifs?|ateliers?)$/i, href: 'dispositifs.html' },
    { test: /^(pssr|parcours|méthode pssr|parcours pssr)$/i, href: 'pssr.html' },
    { test: /^(connexion|espace|tableau de bord)$/i, href: 'dashboard.html' },
    { test: /^(inscription|je m’inscris|s’inscrire)$/i, href: 'inscription.html' },
    { test: /^(ressources|emploi|insertion|ressources emploi & insertion)$/i, href: 'ressources-emploi-insertion.html' },
    { test: /^(documents?)$/i, href: 'documents.html' },
    { test: /^(faq|questions)$/i, href: 'faq.html' },
    { test: /^(écoles & atl|ecoles & atl|partenaires|atl)$/i, href: 'ecoles-atl.html' },
    { test: /^(contact)$/i, href: 'mailto:equilibrevital.bruxelles@gmail.com' }
  ];
  function depthPrefix(){
    const path = window.location.pathname || '';
    const marker = '/pssr/';
    let relative = path.includes(marker) ? path.split(marker)[1] : path.split('/').filter(Boolean).slice(-2).join('/');
    if(!relative || relative.endsWith('/')) relative += 'index.html';
    const parts = relative.split('/').filter(Boolean);
    const depth = Math.max(0, parts.length - 1);
    return depth ? '../'.repeat(depth) : './';
  }
  function href(path){
    if(/^https?:|^mailto:|^tel:|^#/.test(path)) return path;
    return depthPrefix() + path.replace(/^\.\//,'');
  }
  function linkHTML(item, className=''){
    return `<a${className ? ` class="${className}"` : ''} href="${href(item.href)}">${item.label}</a>`;
  }
  function headerHTML(){
    const desktopNav = navItems.map(item => linkHTML(item)).join('');
    const desktopActions = actionItems.map(item => linkHTML(item, `ev35-btn ev35-btn--${item.variant}`)).join('');
    const mobileLinks = navItems.map(item => linkHTML(item)).join('') + actionItems.map(item => linkHTML(item, item.variant === 'primary' ? 'ev35-mobile-signup' : 'ev35-mobile-login')).join('');
    return `
      <header class="ev35-header" role="banner">
        <div class="ev35-container ev35-header__inner">
          <a class="ev35-brand" href="${href('index.html')}" aria-label="Retour à l’accueil Équilibre Vital">
            <img class="ev35-brand__logo" src="${href('wp-content/uploads/2025/09/equilibre-vital-logo-transparent.png')}" alt="Logo Équilibre Vital" loading="lazy">
            <span class="ev35-brand__text"><strong class="ev35-brand__name">Équilibre Vital</strong><small class="ev35-brand__sub">PSSR · Bruxelles</small></span>
          </a>
          <nav class="ev35-navbar" aria-label="Navigation principale">${desktopNav}</nav>
          <div class="ev35-actions">${desktopActions}</div>
          <button class="ev35-menu-toggle" type="button" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="mobile-menu-v35">
            <span class="ev35-menu-toggle__bars" aria-hidden="true"><span></span><span></span><span></span></span>
          </button>
        </div>
        <nav id="mobile-menu-v35" class="ev35-mobile-menu" aria-label="Navigation mobile">${mobileLinks}</nav>
      </header>`;
  }
  function footerHTML(){
    return `
      <footer class="ev35-footer" role="contentinfo">
        <div class="ev35-container ev35-footer__inner">
          <div class="ev35-footer__grid">
            ${footerColumns.map(column => `
              <section>
                <h2 class="ev35-footer__title">${column.title}</h2>
                <nav class="ev35-footer__links" aria-label="${column.title}">
                  ${column.links.map(item => linkHTML(item)).join('')}
                </nav>
              </section>
            `).join('')}
            <section>
              <h2 class="ev35-footer__title">Contact & informations</h2>
              <p>Email : <a href="mailto:equilibrevital.bruxelles@gmail.com">equilibrevital.bruxelles@gmail.com</a></p>
              <p>Téléphone : <a href="tel:+32492691070">0492/691.070</a></p>
              <p>BCE : 1019487618</p>
              <div class="ev35-footer__payment">
                <p>IBAN : BE17 5230 8164 9221</p>
                <p>BIC : TRIOBEBB</p>
              </div>
            </section>
          </div>
          <div class="ev35-footer__bottom">
            <span>EV© 2026 — Tous droits réservés.</span>
            <nav class="ev35-footer__legal" aria-label="Liens légaux">
              <a href="${href('mentions-legales.html')}">Mentions légales</a>
              <a href="${href('confidentialite-rgpd.html')}">RGPD</a>
              <a href="${href('confidentialite-rgpd.html')}">Politique de confidentialité</a>
            </nav>
          </div>
        </div>
      </footer>`;
  }
  function removeOldShell(){
    document.querySelectorAll('body > header, body > footer, body > nav#nav').forEach(el => el.remove());
  }
  function mountShell(){
    document.body.classList.add('ev35-page');
    removeOldShell();
    if(!document.querySelector('.ev35-skip-link')){
      document.body.insertAdjacentHTML('afterbegin','<a class="ev35-skip-link" href="#main">Aller au contenu</a>');
    }
    document.body.insertAdjacentHTML('afterbegin', headerHTML());
    document.body.insertAdjacentHTML('beforeend', footerHTML());
    initMobileMenu();
    centerHeadingData();
    linkKeywordBadges();
    setActiveLinks();
    enhanceMediaAndForms();
    addBackToTop();
  }
  function initMobileMenu(){
    const button = document.querySelector('.ev35-menu-toggle');
    const menu = document.getElementById('mobile-menu-v35');
    if(!button || !menu) return;
    function close(){
      menu.classList.remove('is-open');
      button.setAttribute('aria-expanded','false');
      button.setAttribute('aria-label','Ouvrir le menu');
      document.body.classList.remove('ev35-menu-open');
    }
    function open(){
      menu.classList.add('is-open');
      button.setAttribute('aria-expanded','true');
      button.setAttribute('aria-label','Fermer le menu');
      document.body.classList.add('ev35-menu-open');
    }
    button.addEventListener('click', event => { event.stopPropagation(); menu.classList.contains('is-open') ? close() : open(); });
    menu.addEventListener('click', event => { if(event.target.closest('a')) close(); });
    document.addEventListener('click', event => { if(!menu.contains(event.target) && !button.contains(event.target)) close(); });
    document.addEventListener('keydown', event => { if(event.key === 'Escape') close(); });
  }

  function setActiveLinks(){
    const path = (window.location.pathname.split('/').pop() || 'index.html');
    document.querySelectorAll('.ev35-header nav a[href], .ev35-footer nav a[href]').forEach(a => {
      const href = a.getAttribute('href') || '';
      const clean = href.split('#')[0].split('?')[0].split('/').pop() || 'index.html';
      if(clean === path) a.setAttribute('aria-current','page');
    });
  }
  function enhanceMediaAndForms(){
    document.querySelectorAll('img').forEach(img => {
      if(!img.hasAttribute('loading')) img.setAttribute('loading','lazy');
      if(!img.hasAttribute('decoding')) img.setAttribute('decoding','async');
    });
    document.querySelectorAll('form').forEach(form => {
      form.addEventListener('submit', () => {
        const submit = form.querySelector('button[type="submit"], input[type="submit"]');
        if(submit){
          submit.setAttribute('aria-busy','true');
          setTimeout(() => submit.removeAttribute('aria-busy'), 3500);
        }
      });
    });
  }
  function addBackToTop(){
    if(document.querySelector('.ev35-backtop')) return;
    const back = document.createElement('a');
    back.href = '#';
    back.className = 'ev35-backtop';
    back.setAttribute('aria-label','Retour en haut');
    back.textContent = '↑';
    document.body.appendChild(back);
    window.addEventListener('scroll', () => {
      back.classList.toggle('is-visible', window.scrollY > 700);
    }, {passive:true});
  }

  function centerHeadingData(){
    document.querySelectorAll('main > section').forEach((section, index) => {
      section.setAttribute('data-ev35-section', String(index + 1));
      if(section.querySelector('#pssr-cycle-iso')) section.setAttribute('data-ev35-pssr-section','true');
      const wrap = section.querySelector(':scope > .section-wrap, :scope > .container, :scope > .ev-container');
      if(wrap) wrap.classList.add('ev35-section-inner');
    });
  }
  function linkKeywordBadges(){
    const selector = '.eyebrow-v30,.sec-kicker,.kicker,.badge,.tag,.pill,[class*="badge"],[class*="eyebrow"]';
    document.querySelectorAll(selector).forEach(el => {
      if(el.closest('header,footer,#pssr-cycle-iso,.pssr-ring,.pssr-legend,.pssr-panels,form,button,a')) return;
      const text = (el.textContent || '').trim().replace(/\s+/g,' ');
      if(!text || text.length > 56) return;
      const match = keywordMap.find(item => item.test.test(text));
      if(!match) return;
      const content = el.innerHTML;
      el.innerHTML = `<a class="ev35-keyword-link" href="${href(match.href)}" aria-label="Ouvrir la page ${text}">${content}</a>`;
      el.classList.add('ev35-linked-keyword');
    });
  }
  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mountShell); else mountShell();
})();
