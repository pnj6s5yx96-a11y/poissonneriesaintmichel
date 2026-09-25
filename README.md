# Poissonnerie Saint-Michel

Structure PHP classique, volontairement **sans MVC**. Chaque page est un fichier PHP accessible directement ; les éléments partagés sont rangés dans `includes/`, la configuration dans `config/` et les traitements de formulaires dans `actions/`.

```text
poissonnerie-saint-michel/
├── index.php                         # Accueil client
├── catalogue.php                     # Catalogue et recherche des produits
├── panier.php                        # Panier anonyme
├── validation-commande.php           # Coordonnées, livraison/retrait et paiement
├── paiement-commande.php             # Confirmation du paiement Mobile Money
├── facture.php                        # Facture client téléchargeable
├── retrouver-facture.php              # Renvoi de facture par e-mail
├── suivi-commande.php                # Suivi par numéro de commande
│
├── auth/                             # Connexion des comptes internes
│   ├── connexion.php
│   ├── deconnexion.php
│   └── mot-de-passe-oublie.php
│
├── admin/                            # Espace administrateur
│   ├── index.php                     # Tableau de bord et statistiques
│   ├── utilisateurs.php
│   ├── categories.php
│   ├── produits.php
│   ├── historique.php              # Clients, commandes, ventes, livraisons, stock
│   ├── rapports.php                # Prévisualisation et exports CSV
│   └── parametres.php
│
├── gerant/                           # Espace gérant
│   ├── index.php
│   ├── commandes.php
│   ├── nouvelle-commande.php          # Vente comptoir (gérant / administrateur)
│   ├── stock.php
│   ├── livraisons.php
│   └── paiements.php
│
├── livreur/                          # Espace livreur
│   ├── index.php
│   ├── livraisons.php
│   └── historique.php
│
├── actions/                          # Traitements POST/AJAX, sans affichage HTML
│   ├── connexion.php
│   ├── commande.php
│   ├── produit.php
│   ├── parametres.php
│   ├── export-rapport.php
│   ├── stock.php
│   ├── paiement.php
│   ├── paiement-client.php
│   ├── retrouver-facture.php
│   ├── panier.php
│   ├── livraison.php
│   ├── confirmer-reception.php
│   └── statut-commande.php
│
├── api/                              # Réponses JSON pour JavaScript
│   ├── produits.php
│   └── notifications.php
│
├── config/
│   ├── constants.php                 # Chemins et URL de l'application
│   ├── database.php                  # Connexion PDO MySQL
│   ├── database.local.php.example    # Identifiants locaux à copier puis adapter
│   └── session.php                   # Démarrage sécurisé de session
│
├── includes/
│   ├── header.php                    # En-tête HTML et navigation
│   ├── footer.php                    # Pied de page et scripts communs
│   ├── auth.php                      # Contrôle d'accès par rôle
│   ├── functions.php                 # Fonctions réutilisables
│   ├── invoice_pdf.php               # Génération des factures PDF
│   ├── invoice_mail.php              # Envoi des factures PDF par e-mail
│   └── flash.php                     # Messages succès/erreur en session
│
├── assets/
│   ├── css/app.css
│   ├── js/app.js
│   ├── images/
│   └── uploads/                      # Doit être protégé contre l'exécution PHP
│       ├── produits/
│       ├── factures/
│       └── recus/
│
├── sql/
│   ├── migrations/
│   └── README.md                     # Référence vers le script SQL à importer
├── .htaccess                         # Protection des dossiers sensibles (Apache/MAMP)
└── .gitignore
```

## Principe de fonctionnement

- Une page affiche l’interface, par exemple `gerant/commandes.php`.
- Un formulaire envoie ses données vers un fichier de `actions/`, par exemple `actions/statut-commande.php`.
- Le traitement valide les données, utilise `db()` de `config/database.php`, puis redirige vers la page concernée.
- Les droits sont contrôlés avec `requireRole()` avant d’afficher ou de traiter une action interne.

Cette organisation n’utilise ni contrôleur, ni modèle, ni routeur MVC. Elle reste cependant lisible et maintenable pour un projet PHP/MySQL de cette taille.

## Installation locale MAMP

1. Placer le dossier dans le répertoire `htdocs` de MAMP.
2. Importer le script [`database_poissonnerie_saint_michel.sql`](database_poissonnerie_saint_michel.sql) dans phpMyAdmin.
3. Copier `config/database.local.php.example` vers `config/database.local.php`, puis saisir les identifiants MySQL locaux.
4. Ouvrir `http://localhost:8888/poissonnerie-saint-michel/` (adapter le port si nécessaire).

> Le déploiement public est prévu à la racine de `httpdocs`. Pour un test MAMP
> dans le sous-dossier `poissonnerie-saint-michel`, définir la variable Apache
> `APP_URL=/poissonnerie-saint-michel` dans votre hôte local. Ne définissez pas
> cette variable sur le serveur si le domaine pointe directement vers `httpdocs`.

## Référencement (SEO)

- Les deux pages destinées aux moteurs sont l’accueil (`/`) et le catalogue
  (`/catalogue.php`). Elles disposent d’un titre propre, d’une meta description,
  d’une URL canonique, des balises de partage social et du balisage `Store`.
- Les paniers, paiements, factures, connexions et espaces d’équipe sont en
  `noindex` : ils ne doivent pas apparaître dans une recherche ni exposer de
  données client.
- Avant la mise en production, remplacez la valeur de repli de
  `PUBLIC_SITE_URL` dans `config/constants.php` par le domaine canonique exact
  si `https://poissonnerie-saint-michel.yes.bj` n’est pas votre adresse publique.
  Utilisez toujours une seule version du domaine, en HTTPS et sans slash final.
- Après déploiement, déclarez ce domaine dans Google Search Console puis envoyez
  `https://votre-domaine/sitemap.xml`. Le fichier `robots.txt` est déjà fourni.

## Google Merchant Center

Le flux produits se génère automatiquement depuis le catalogue à l’adresse :

`https://poissonnerie-saint-michel.yes.bj/google-merchant-feed.xml`

Dans Merchant Center, créez une **source de données via récupération planifiée**
et utilisez cette URL. Le flux comprend les produits et catégories actifs qui
ont une photo téléversée. Le prix, le stock et la disponibilité sont lus en
temps réel depuis la base ; ne créez donc pas de fichier Excel séparé.

Chaque produit doit avoir sa propre photo nette et réelle. Les articles sans
photo sont volontairement exclus, car Google exige une image propre au produit.
Les produits bruts ou sans identifiant fabricant sont déclarés sans GTIN. Pour
un produit emballé disposant d'un code-barres fabricant, ne l'inventez pas :
ajoutez son GTIN réel avant de le publier dans Merchant Center.

Ne versionnez jamais `config/database.local.php`, ni le contenu réel de `assets/uploads/`.

## Modules déjà fonctionnels

- Authentification de l’administrateur, du gérant et du livreur ; création sécurisée du premier administrateur.
- Gestion des comptes internes : création, modification, activation et désactivation.
- Gestion des catégories et des produits, incluant le téléversement sécurisé de photos.
- Gestion des mouvements de stock, niveau courant et alertes de seuil.
- Catalogue client responsive avec filtres par catégorie et recherche.
- Espace administrateur complet : configuration de la boutique, des zones tarifaires de livraison et de leurs quartiers,
  indicateurs de recettes, ventes par produit/catégorie, historiques filtrables et
  exports CSV sécurisés des ventes, commandes, clients, livraisons, stocks et du journal d’audit.
- Profil administrateur autonome, gestion limitée aux comptes gérant/livreur et contrôle de session
  vérifiant à chaque requête le rôle et l’état actif du compte.
- Journalisation des modifications de paramètres, comptes, catégories, produits et mouvements de stock.
- Gestion des commandes par le gérant et l’administrateur : liste unifiée des commandes en ligne et au comptoir, création d’une vente physique, déstockage transactionnel, encaissement espèces avec reçu, correction d’une zone avant paiement et affectation des livraisons réglées.
- Parcours client anonyme complet : ajout au panier, sélection d’une zone puis d’un quartier de livraison lié avec coût automatique, description complète de l’adresse, validation sans compte, e-mail obligatoire, retrait ou livraison, paiement, facture Mobile Money téléchargeable ou renvoyée à l’adresse e-mail du client, suivi autonome et confirmation de réception.
- Espace livreur opérationnel : tableau de bord privé, acceptation ou refus motivé d’une mission, départ, annulation avant départ, livraison effectuée et historique personnel.
- Cycle de livraison tracé : affectation par le gérant, notifications client/équipe et historique des statuts dans la base.

Les paiements MTN Mobile Money, Moov Money et Celtis Cash sont simulés en local : une intégration réelle devra utiliser les identifiants API fournis par chaque opérateur.

## Renvoi des factures par e-mail

L’adresse e-mail est obligatoire pour toute commande en ligne. Depuis `retrouver-facture.php`, le client indique uniquement cette adresse : toutes ses factures Mobile Money confirmées sont renvoyées en pièces jointes PDF, sans afficher d’information sur la page publique.

Le projet utilise SMTP lorsque `config/mail.local.php` est présent. Le fichier est déjà préparé pour `akmultiservices2018@gmail.com` et est ignoré par Git. Pour un envoi Gmail réel :

1. Activez la validation en deux étapes sur le compte Gmail.
2. Créez un **mot de passe d’application** de type « Mail » dans la sécurité du compte Google.
3. Collez les 16 caractères fournis dans la clé `password` de `config/mail.local.php`, sans modifier les autres paramètres.

Le mot de passe principal Gmail ne doit jamais être inscrit dans le projet. Sans le mot de passe d’application, Gmail refusera l’authentification SMTP.
