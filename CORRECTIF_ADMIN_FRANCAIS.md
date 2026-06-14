# Correctif affichage français — Back-office PSSR

Ce correctif ne change pas la structure Firebase.

Il améliore uniquement l'affichage du tableau de bord :

- libellés Firestore traduits en français ;
- valeurs techniques traduites quand c'est possible ;
- champs techniques (`userAgent`, `source`, `uid`, etc.) rangés dans “Détails techniques” ;
- onglet “Emails logs” renommé en “Journaux d’e-mails” ;
- export CSV avec en-têtes français.

Les noms techniques des champs dans Firestore restent en anglais pour préserver la compatibilité du code.
