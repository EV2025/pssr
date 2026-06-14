# PSSR — Amélioration professionnelle V3

Cette mise à jour améliore le site existant sans repartir de zéro. Elle conserve le design, les pages principales, Firebase, le back-office et les formulaires déjà fonctionnels.

## Ce qui a été ajouté proprement

### Front office
- Page `services.html` : services, tarifs, accompagnement.
- Page `calendrier.html` : créneaux sportifs et ateliers socio-professionnels.
- Page `faq.html` : questions fréquentes.
- Page `avis.html` : retours et témoignages.
- Pages `mentions-legales.html` et `confidentialite-rgpd.html`.
- Liens de navigation plus clairs.
- Consentement RGPD sur les formulaires.

### Back-office
- Onglets enrichis : messages, réservations, clients, services, calendrier, paiements, notifications, pages, présences, emails, statistiques.
- Actions rapides : traiter, confirmer, annuler, supprimer, activer/désactiver.
- Import `services & tarifs` depuis `data/services-seed.json`.
- Statistiques simples sans backend supplémentaire.

### Synchronisation
- `services`, `slots`, `reservations`, `messages`, `users`, `payments`, `notifications`, `emailLogs` prévus dans Firestore.
- Règles Firestore V3 à publier depuis `firestore.rules`.

### SEO / professionnalisation
- Balises SEO améliorées.
- `robots.txt` et `sitemap.xml`.
- JSON-LD Organisation / lieu sportif sur l’accueil.

## Ce qui n’a pas été ajouté volontairement

- Paiement en ligne réel : il nécessite un prestataire sécurisé et idéalement un backend.
- Envoi email réel : la version actuelle crée des logs `emailLogs`, mais l’envoi automatique demande une Cloud Function ou un service externe.
- Backend React/FastAPI/MongoDB : non nécessaire pour cette étape et non compatible directement avec GitHub Pages seul.

## Après upload GitHub

1. Pousser tous les fichiers avec GitHub Desktop.
2. Publier les règles `firestore.rules` dans Firebase.
3. Se connecter à `/admin/`.
4. Cliquer sur :
   - Importer les créneaux officiels
   - Importer services & tarifs
5. Tester :
   - `/reservation.html`
   - `/services.html`
   - `/calendrier.html`
   - `/faq.html`
   - `/admin/`

Admin actuel : `equilibrevital.bruxelles@gmail.com` / UID `aKDgnDd1ARXXFm2Pu7CT02HXzlJ2`.
