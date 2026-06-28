# Phase 1B — Virement bancaire avec QR code SEPA/EPC

Cette version ajoute un QR code bancaire SEPA/EPC après l’envoi d’une réservation.

## Objectif

Permettre à un membre/parent de payer la cotisation sans frais Stripe/Mollie :

1. Il remplit `reservation.html`.
2. Le site enregistre la réservation dans Firestore.
3. Le site génère un numéro de réservation unique.
4. Le site affiche :
   - le montant ;
   - le bénéficiaire ;
   - l’IBAN ;
   - le BIC ;
   - la communication structurée interne ;
   - un QR code SEPA/EPC à scanner avec l’application bancaire.
5. L’admin vérifie le virement sur le compte bancaire.
6. L’admin change le statut paiement en `payé` ou `virement reçu`.

## Fichiers ajoutés / modifiés

### Ajouté

- `assets/js/epc-qr.js`
- `README_PHASE1B_QR_SEPA.md`

### Modifié

- `assets/js/firebase-contact.js`
- `assets/css/common-v58.min.css`
- `reservation.html`

## Points importants

Le QR code est généré localement dans le navigateur. Aucune donnée de paiement n’est envoyée à un service externe de QR code.

Le QR code contient un ordre de virement SEPA avec :

- Bénéficiaire : `Equilibre Vital asbl`
- IBAN : `BE17 5230 8164 9221`
- BIC : `TRIOBEBB`
- Montant : `165.00 EUR`
- Communication : numéro de réservation, par exemple `PSSR-RES-20260628-ABCD`

## À vérifier avant publication

- Confirmer que l’IBAN est le bon compte officiel de l’ASBL.
- Faire un test avec une vraie application bancaire belge.
- Vérifier que l’application bancaire reprend correctement :
  - le bénéficiaire ;
  - le montant ;
  - l’IBAN ;
  - la communication.
- Ne pas valider de vrai virement pendant les tests, sauf si l’ASBL l’autorise.

## Limite volontaire

Cette version ne vérifie pas automatiquement les virements entrants. C’est volontaire pour éviter les frais de prestataires de paiement.

La confirmation reste manuelle dans l’admin.


## Correctif 1B.1 — blocage “Envoi en cours”

Cette version corrige deux points :

- le message “Envoi en cours…” est maintenant effacé dès que l’accusé de réception s’affiche ;
- une sécurité de 15 secondes a été ajoutée si Firebase ne répond pas, afin d’éviter un blocage visuel permanent.

Les boutons “Copier l’IBAN”, “Copier la communication” et “Copier tout” sont également initialisés proprement.

## Correctif Phase 1B+ — bouton “Ouvrir mon app bancaire”

Ajout d’un bouton placé avant “Copier l’IBAN” dans le reçu de réservation.

Fonctionnement :

1. le bouton copie automatiquement toutes les informations du virement ;
2. sur mobile, il tente d’ouvrir la feuille de partage du téléphone pour permettre à l’utilisateur de choisir son application bancaire si elle est proposée ;
3. si aucune application bancaire n’est proposée, le site affiche une instruction claire : ouvrir l’application bancaire manuellement, créer un virement SEPA, puis coller l’IBAN et la communication.

Limite importante : il n’existe pas de lien universel fiable qui ouvre automatiquement toutes les applications bancaires belges avec un virement SEPA déjà prérempli. Le QR code SEPA reste la solution la plus fiable pour éviter les frais de transaction tout en facilitant le paiement.
