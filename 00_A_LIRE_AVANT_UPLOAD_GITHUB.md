# PSSR — Projet complet prêt pour GitHub Pages + Firebase

Ce dossier est la version propre à mettre dans le dépôt GitHub `ev2025/pssr`.

## Très important

Dans GitHub, il faut mettre **le contenu de ce dossier** à la racine du dépôt, pas le dossier ZIP lui-même.

Après upload, la racine GitHub doit contenir directement :

```text
index.html
reservation.html
merci.html
admin/
assets/
data/
wp-content/
firestore.rules
storage.rules
.nojekyll
```

Si `admin/` ou `assets/` n'apparaît pas dans GitHub, le tableau de bord ne pourra pas fonctionner.

## URL du site

Site public : https://ev2025.github.io/pssr/
Admin : https://ev2025.github.io/pssr/admin/

## Firebase déjà configuré dans ce dossier

Projet : `pssr-site-web`
Admin UID : `aKDgnDd1ARXXFm2Pu7CT02HXzlJ2`
Admin email : `equilibrevital.bruxelles@gmail.com`

## Images

Le dossier des images doit être placé ici :

```text
wp-content/uploads/
```

Tu peux copier plus tard le vrai dossier `uploads` récupéré depuis Hostinger dans ce chemin.
Le site contient déjà un système de fallback : si une image manque, il affiche une image de remplacement au lieu de casser toute la page.

## Ordre conseillé pour recommencer proprement

1. Dans GitHub `ev2025/pssr`, supprime les anciens fichiers.
2. Décompresse ce ZIP sur ton ordinateur.
3. Ouvre le dossier décompressé.
4. Sélectionne tout ce qu'il contient : fichiers + dossiers.
5. Glisse tout dans GitHub avec `Add file` → `Upload files`.
6. Clique sur `Commit changes`.
7. Attends 1 à 3 minutes.
8. Teste : https://ev2025.github.io/pssr/
9. Teste : https://ev2025.github.io/pssr/admin/

