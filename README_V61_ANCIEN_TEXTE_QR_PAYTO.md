# V61 — ancien texte + QR + bouton app bancaire

Cette version revient à l'ancien contenu/ancienne structure visuelle avant V60, tout en gardant :

- la génération du QR code SEPA/EPC ;
- la référence unique de paiement ;
- les informations de virement en clair ;
- un bouton `Ouvrir mon app bancaire` basé sur le lien standard `payto://`.

Important : le bouton peut ouvrir une application bancaire uniquement si le téléphone et l'application bancaire prennent en charge le schéma `payto://`. Sinon, le visiteur utilise le QR code ou copie l'IBAN + communication.
