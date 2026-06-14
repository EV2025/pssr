# V5 — Nettoyage front-office, espace membre et design moderne

Cette version conserve le site GitHub Pages + Firebase existant et ne repart pas de zéro.

## Changements visibles

- Retrait du slogan public « Bouge ton corps, avance dans ta vie ! ».
- Retrait des blocs techniques visibles sur l’accueil : Vision, Roadmap, état réel, Firebase opérationnel, Phase 2A.
- Retrait du tableau horaire socio-professionnel côté visiteur.
- Remplacement par une sélection de modules simple et compréhensible.
- Les modules sélectionnés sont envoyés automatiquement vers `reservation.html` ou `inscription.html`.
- L’espace membre n’affiche plus l’agenda général : il affiche seulement les modules liés au compte ou aux demandes.
- Ajout d’un CSS V5 moderne dans `assets/css/v5-modern.css` et intégré dans `site-overrides.css`.

## Points de sécurité / réservation

- Statuts harmonisés : `en attente`, `traité`, `confirmé`, `annulé`, `payé` selon le suivi admin.
- Prix harmonisé : tarif solidaire 165€ / année académique.
- Le paiement en ligne reste non activé ; l’équipe confirme les modalités après validation.
- Protection contre les doubles clics / doublons immédiats côté navigateur.
- Les données restent stockées dans Firebase Firestore selon les règles V4 déjà publiées.

## Tests après push

1. Accueil : `https://ev2025.github.io/pssr/`
2. Réservation depuis un module sélectionné.
3. Inscription membre avec modules préremplis.
4. Espace membre : vérifier que l’agenda général n’est plus affiché.
5. Admin : vérifier messages/réservations/statuts.
