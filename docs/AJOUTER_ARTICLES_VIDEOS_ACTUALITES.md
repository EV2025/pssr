# Ajouter des articles et vidéos dans Actualités

## Ce qui a été fait en V5

- Les 7 articles fournis ont été repris depuis l’export WordPress XML du forum.
- Le sens et l’esprit des textes ont été conservés.
- Les textes ont été légèrement harmonisés : apostrophes, accents, lisibilité, paragraphes et titres.
- Les images trouvées dans l’export WordPress sont affichées via leurs URLs d’origine du forum.
- La chaîne YouTube officielle est liée : https://www.youtube.com/@EV.Bruxelles
- ID chaîne : `UCbjy5CO59GU5OzsjAszSb4Q`

> Important : un export WordPress `.xml` contient les contenus et URLs médias, mais pas les fichiers image binaires. Pour intégrer les images localement dans GitHub Pages, il faut aussi exporter/télécharger le dossier `wp-content/uploads`.

## Ajouter un nouvel article

1. Copie une page existante du dossier `actualites/`.
2. Renomme le fichier, par exemple `nouvel-article.html`.
3. Modifie le titre, la date, le texte, les images et le lien source.
4. Ajoute une carte correspondante dans `actualites.html`.
5. Ajoute l’URL dans `sitemap.xml`.

## Ajouter une vidéo YouTube dans un article

1. Prends l’URL de ta vidéo : `https://www.youtube.com/watch?v=ID_VIDEO`.
2. Garde seulement `ID_VIDEO`.
3. Ajoute ce bloc dans l’article :

```html
<div class="news-video-v60">
  <iframe src="https://www.youtube.com/embed/ID_VIDEO" title="Vidéo pédagogique Équilibre Vital" allowfullscreen loading="lazy"></iframe>
</div>
```

## Publication GitHub

1. Envoie les fichiers modifiés sur GitHub.
2. Clique sur **Commit changes**.
3. Vérifie la page : `https://ev2025.github.io/pssr/actualites.html`.
