# Ajouter un article ou une vidéo sur la page Actualités

La page à modifier est :

```text
actualites.html
```

## Ajouter un article

1. Ouvre `actualites.html`.
2. Trouve la zone :

```html
<div class="news-grid-v60">
```

3. Copie un bloc qui commence par :

```html
<article class="news-card-v60">
```

4. Colle-le au-dessus des anciens articles pour afficher le plus récent en premier.
5. Modifie le titre, la date, le résumé et le lien.

## Ajouter une vidéo YouTube

1. Va sur ta vidéo YouTube.
2. Copie l’adresse. Exemple :

```text
https://www.youtube.com/watch?v=ABC123
```

3. Dans `actualites.html`, remplace uniquement l’identifiant après `/embed/` :

```html
<iframe src="https://www.youtube.com/embed/ABC123"></iframe>
```

## Publier sur GitHub

1. Va dans le dépôt GitHub `ev2025/pssr`.
2. Clique sur `actualites.html`.
3. Clique sur le crayon pour modifier.
4. Colle tes changements.
5. Clique sur `Commit changes`.

La page sera disponible ici :

```text
https://ev2025.github.io/pssr/actualites.html
```
