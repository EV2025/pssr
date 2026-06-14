# PSSR — Firebase configuré

La configuration Web Firebase fournie a été intégrée dans :

```text
assets/js/firebase-config.js
```

Projet : `pssr-site-web`  
Site : `https://ev2025.github.io/pssr/`

## À faire dans Firebase Console

1. Authentication > Méthode de connexion > activer Email/Password.
2. Authentication > Paramètres > Domaines autorisés > ajouter `ev2025.github.io`.
3. Authentication > Utilisateurs > créer l'utilisateur admin.
4. UID admin récupéré : `aKDgnDd1ARXXFm2Pu7CT02HXzlJ2`.
5. Firestore Database > Créer une base de données en mode production.
6. Firestore > Données > créer la collection `admins`.
7. Dans `admins`, créer un document avec comme ID exact : `aKDgnDd1ARXXFm2Pu7CT02HXzlJ2`.
8. Ajouter les champs :

```text
email: equilibrevital.bruxelles@gmail.com
role: admin
createdAt: 2026-06-14
```

9. Firestore > Règles > coller le contenu de `firestore.rules` puis publier.
10. Tester le formulaire sur `https://ev2025.github.io/pssr/`.
11. Tester le dashboard sur `https://ev2025.github.io/pssr/admin/`.

## Collections utilisées

```text
messages       formulaires de contact
reservations   demandes de réservation
pages          contenu WordPress importé
admins         administrateurs autorisés
```


## Étape actuelle

Créer dans Firestore le document `admins/aKDgnDd1ARXXFm2Pu7CT02HXzlJ2` avec le rôle `admin`. Voir `ETAPE_ACTUELLE_FIRESTORE_ADMIN.md`.
