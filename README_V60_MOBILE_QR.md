# V60 — Correctif QR SEPA + amélioration mobile-first

Cette version corrige le problème d’affichage du QR code dans la réservation et ajoute une couche d’amélioration UX/UI mobile-first.

## Correction QR

Le QR code SEPA/EPC n’utilise plus de CDN externe et n’utilise plus de canvas comme rendu principal.

Nouvelle logique :

1. La réservation est enregistrée dans Firestore.
2. Une référence unique est générée : `PSSR-RES-YYYYMMDD-XXXX`.
3. Le montant est récupéré depuis le formulaire : `data-payment-amount`, `priceAmount`, etc.
4. Le payload SEPA/EPC est créé localement.
5. Le QR code est généré en SVG directement dans la page.
6. Les informations restent affichées en clair : bénéficiaire, IBAN, BIC, montant, communication.

Fichier principal modifié :

- `assets/js/firebase-contact.js`

## Améliorations mobile-first

Nouveaux fichiers :

- `assets/css/mobile-first-v60.css`
- `assets/js/mobile-ux-v60.js`

Améliorations incluses :

- barre d’action mobile fixe : Réserver / Contact ;
- boutons plus grands et plus faciles à toucher ;
- formulaires en une colonne sur téléphone ;
- champs plus lisibles ;
- meilleure hiérarchie des titres ;
- cartes plus compactes ;
- amélioration des pages Accueil, Activités, Réservation et Présentation PSSR ;
- lazy-loading des images ;
- vidéos en `preload="metadata"` ;
- focus visible renforcé ;
- respect de `prefers-reduced-motion`.

## Pages ajustées

- `index.html`
- `activites.html`
- `reservation.html`
- `presentation-pssr.html`
- injection CSS/JS V60 dans les pages HTML du site

## Test conseillé

1. Publier le ZIP sur une branche test.
2. Ouvrir `reservation.html` en navigation privée.
3. Faire une réservation test.
4. Vérifier que le message affiche : `QR code SEPA/EPC prêt à scanner.`
5. Scanner le QR code avec une application bancaire compatible.
6. Vérifier que le montant, l’IBAN et la communication sont corrects.
7. Ne pas valider de vrai virement pendant le test.

## Important

La confirmation du virement reste manuelle en Phase 1. L’admin doit vérifier le compte bancaire puis marquer le paiement comme reçu/payé.
