# Phase 1 — Réservation + virement bancaire sans frais de transaction

Cette version ajoute une première brique de paiement sans Stripe/Mollie :

1. Le visiteur remplit `reservation.html`.
2. La réservation est enregistrée dans Firestore avec un `reservationCode`.
3. Le site affiche les instructions de virement bancaire : montant, bénéficiaire, IBAN, BIC, communication.
4. Dans l’admin, la réservation affiche maintenant le statut de paiement.
5. L’équipe vérifie le compte bancaire et passe manuellement le statut en `payé`.

## Fichiers modifiés

- `reservation.html`
- `assets/js/firebase-contact.js`
- `assets/js/admin.js`

## Données de paiement utilisées

- Bénéficiaire : Équilibre Vital asbl
- IBAN : BE17 5230 8164 9221
- BIC : TRIOBEBB
- Montant : 165 EUR
- Communication : numéro de réservation généré automatiquement

## Test à faire

1. Ouvrir `reservation.html`.
2. Remplir une réservation test.
3. Vérifier que l’accusé de réception affiche les informations de virement.
4. Ouvrir l’admin.
5. Vérifier la colonne `Paiement`.
6. Cliquer sur `Gérer`, changer `Statut paiement` en `payé`, puis enregistrer le suivi.

## Important

Cette phase ne confirme pas automatiquement le paiement : la validation reste manuelle après vérification du compte bancaire.

---

## Phase 1B ajoutée

La version Phase 1B ajoute un QR code SEPA/EPC généré localement dans le navigateur.

Fichier ajouté :

- `assets/js/epc-qr.js`
- `README_PHASE1B_QR_SEPA.md`

Le parcours reste sans Stripe/Mollie et sans frais de transaction. La confirmation bancaire reste manuelle dans l’admin.
