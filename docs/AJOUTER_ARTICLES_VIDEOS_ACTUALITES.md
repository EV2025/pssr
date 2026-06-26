# Ajouter des articles et vidéos dans Actualités

La page publique est : `actualites.html`.

## Articles déjà intégrés

Les 7 articles fournis sont intégrés sous forme de fiches propres dans le dossier `actualites/`.
Chaque fiche contient :
- un résumé propre ;
- les points à retenir ;
- un bouton vers l’article original du forum ;
- un bouton vers la chaîne YouTube.

> Note : le forum peut bloquer la récupération automatique complète du texte. Pour publier le texte intégral d’un article, colle le contenu dans la fiche concernée ou fournis un export PDF/HTML.

## Ajouter un nouvel article

1. Copie une page existante dans le dossier `actualites/`.
2. Renomme le fichier, par exemple : `nouvel-article.html`.
3. Modifie le titre, la date, le résumé et le lien source.
4. Ouvre `actualites.html` et ajoute une nouvelle carte dans le bloc `news-grid-v60`.

## Ajouter une vidéo YouTube

Chaîne officielle : https://www.youtube.com/@EV.Bruxelles

ID chaîne : `UCbjy5CO59GU5OzsjAszSb4Q`

Pour intégrer une vidéo individuelle :

1. Ouvre la vidéo YouTube.
2. Copie l’identifiant après `watch?v=`.
3. Ajoute un iframe de ce type :

```html
<iframe src="https://www.youtube.com/embed/ID_DE_LA_VIDEO" title="Vidéo pédagogique Équilibre Vital" loading="lazy" allowfullscreen></iframe>
```

## Publication sur GitHub

Après modification, envoie les fichiers sur GitHub puis clique sur **Commit changes**.
