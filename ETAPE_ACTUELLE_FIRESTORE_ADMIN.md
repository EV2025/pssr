# Étape actuelle — créer l'administrateur Firestore

Projet Firebase : `pssr-site-web`  
Site : `https://ev2025.github.io/pssr/`  
Email admin : `equilibrevital.bruxelles@gmail.com`  
UID admin : `aKDgnDd1ARXXFm2Pu7CT02HXzlJ2`

## 1. Créer Firestore si ce n'est pas encore fait

Dans Firebase Console :

```text
Créer / Build > Firestore Database > Créer une base de données
```

Choisissez :

```text
Mode production
Région : Europe, si disponible
```

## 2. Créer la collection admins

Dans Firestore Database > Données :

```text
Démarrer une collection
ID de collection : admins
```

## 3. Créer le document admin

ID du document, à copier exactement :

```text
aKDgnDd1ARXXFm2Pu7CT02HXzlJ2
```

Ajoutez les champs suivants :

```text
email      string   equilibrevital.bruxelles@gmail.com
role       string   admin
createdAt string   2026-06-14
```

Cliquez sur `Enregistrer`.

## 4. Publier les règles Firestore

Dans Firestore Database > Règles, remplacez le contenu par le fichier :

```text
firestore.rules
```

Puis cliquez sur `Publier`.

## 5. Test final

1. Ouvrez `https://ev2025.github.io/pssr/` et envoyez un message.
2. Vérifiez que Firestore crée un document dans `messages`.
3. Ouvrez `https://ev2025.github.io/pssr/admin/`.
4. Connectez-vous avec `equilibrevital.bruxelles@gmail.com`.
5. Le tableau de bord doit afficher les messages et réservations.
