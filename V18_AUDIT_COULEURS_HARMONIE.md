# V18 — Audit couleurs & harmonie visuelle

Objectif : corriger uniquement la gestion des couleurs, contrastes et rôles visuels du site Équilibre Vital / PSSR.

## Audit rapide

- Plusieurs palettes coexistaient encore : violet clair historique, dégradés V16, palette sombre V17, accents orange/menthe/lilas.
- Certains textes gris hérités pouvaient manquer de contraste sur fonds sombres.
- Les cartes translucides se confondaient parfois avec les arrière-plans dégradés.
- Les boutons secondaires et liens pouvaient manquer de hiérarchie.
- Les champs de formulaire nécessitaient des placeholders et focus plus visibles.
- Les pictogrammes avaient besoin d’un fond/badge stable pour rester lisibles.

## Palette V18

- Fond principal : `#13091f`
- Fond secondaire / sections : `rgba(26,15,48,.86)`
- Texte principal : `#faf7ff`
- Texte secondaire : `#ddd2f4`
- Accent principal : `#ffb15f`
- Accent fort : `#ff8f3d`
- Accent secondaire : `#8ee6c9`
- Bordure : `rgba(255,255,255,.22)`
- Champs : `rgba(9,5,22,.74)`

## Respect du schéma PSSR

Le fichier `assets/css/v18-couleurs-harmonie.css` évite de cibler `#parcours` et `#pssr-cycle-iso`. Le schéma interactif PSSR est considéré comme validé et n’a pas été modifié.
