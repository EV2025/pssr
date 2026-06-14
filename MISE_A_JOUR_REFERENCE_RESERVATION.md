# Mise à jour — Référence de réservation

Cette version ajoute une référence unique pour chaque demande de réservation.

## Fonctionnement

Quand une personne envoie le formulaire `reservation.html`, le site génère une référence du type :

```text
PSSR-20260614-ABCD
```

Cette référence est :

- affichée à l'écran après l'envoi ;
- enregistrée dans Firestore dans la collection `reservations`, champ `reservationCode` ;
- visible dans le tableau de bord admin, onglet `Réservations`.

Important : cette référence ne donne pas accès au tableau de bord. Le tableau de bord reste réservé à l'admin Firebase.

## Fichiers modifiés

```text
assets/js/firebase-contact.js
reservation.html
index.html
```

Le lien public “Tableau de bord” a été retiré du menu public pour éviter que les participant·es pensent devoir recevoir un code admin.
