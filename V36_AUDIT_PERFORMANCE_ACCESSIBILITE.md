# V36 — Performance & accessibilité

Base : V33.

Corrections appliquées :

- Chargement CSS moins bloquant : style critique minimal inline + feuille V36 préchargée.
- Un seul fichier CSS global `assets/css/v36-performance-accessibilite.css`.
- Un seul shell de navigation/footer injecté par `assets/js/v36-performance-accessibilite.js`.
- Scripts non critiques en `defer` quand possible.
- Palette renforcée pour corriger les contrastes faibles : textes, liens, badges, boutons, footer et focus clavier.
- Suppression des anciens liens d’évitement visuellement cachés qui créaient des alertes de contraste.
- Menu burger avec `aria-expanded`, `aria-controls`, `aria-label`, fermeture après clic et touche Escape.
- Titres centrés et hiérarchie améliorée : un `h1` par page quand possible, puis `h2`/`h3`.
- Images non critiques en `loading=lazy`, `decoding=async`, dimensions `width`/`height`, et WebP utilisé quand disponible.
- Footer plus contrasté et mieux structuré.
- Mots-clés/badges autonomes reliés à leurs pages quand une page existe.
- Vérification locale : 0 lien interne cassé détecté.

Limites :

- GitHub Pages ne permet pas de contrôler finement les en-têtes HTTP de cache. Un `firebase.json` avec règles de cache est présent pour un futur déploiement Firebase Hosting.
- Le super cercle PSSR est exclu des corrections internes : forme, étapes, couleurs, interactions, logique et contenu conservés.
