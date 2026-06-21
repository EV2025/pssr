export const navItems = [
  { label: 'Activités', href: '/activites' },
  { label: 'Dispositifs', href: '/dispositifs' },
  { label: 'PSSR', href: '/pssr' },
];

export const actionItems = [
  { label: 'Connexion', href: '/connexion', variant: 'secondary' },
  { label: 'Je m’inscris', href: '/inscription', variant: 'primary' },
];

export const footerColumns = [
  {
    title: 'Menu principal',
    links: [
      { label: 'Accueil', href: '/' },
      { label: 'Activités', href: '/activites' },
      { label: 'Dispositifs', href: '/dispositifs' },
      { label: 'PSSR', href: '/pssr' },
    ],
  },
  {
    title: 'Ressources',
    links: [
      { label: 'Présentation', href: '/presentation-pssr' },
      { label: 'Réserver', href: '/reservation' },
      { label: 'Documents', href: '/documents' },
      { label: 'FAQ', href: '/faq' },
      { label: 'Ressources emploi & insertion', href: '/ressources-emploi' },
      { label: 'Écoles & ATL', href: '/ecoles-atl' },
      { label: 'Avis', href: '/avis' },
    ],
  },
  {
    title: 'Parcours & thèmes',
    links: [
      { label: 'Ateliers', href: '/dispositifs#ateliers' },
      { label: 'Axes prioritaires', href: '/presentation-pssr' },
      { label: 'Partenaires', href: '/ecoles-atl' },
      { label: 'Sources formation & emploi', href: '/sources-formation-emploi' },
      { label: 'Mot de passe oublié', href: '/mot-de-passe-oublie' },
    ],
  },
];
