# V35 — base V33 réparée : sections isolées, liens de mots-clés, titres centrés

Base utilisée : V33 React-ready.

Objectifs appliqués :

- repartir de la V33 ;
- éviter les conflits entre anciens headers, menus et footers ;
- garder un seul shell global V35 pour le header, le menu burger et le footer ;
- isoler visuellement les sections ;
- centrer les titres principaux et les badges hors super cercle PSSR ;
- transformer les mots-clés/badges autonomes en liens vers les pages correspondantes ;
- ne pas modifier le contenu principal des textes ;
- ne pas modifier le super cercle PSSR.

Fichiers ajoutés :

- `assets/css/v35-v33-sections-liens-titres.css`
- `assets/js/v35-v33-sections-liens-titres.js`
- `src/components/SectionHeading.jsx`

Le super cercle PSSR n’a pas été modifié : le script et le CSS évitent explicitement `#pssr-cycle-iso`, `.pssr-ring`, `.pssr-legend` et `.pssr-panels`.
