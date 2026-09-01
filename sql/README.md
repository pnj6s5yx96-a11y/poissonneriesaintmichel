# Base de données

Importez le fichier situé à la racine du projet :

`database_poissonnerie_saint_michel.sql`

Il crée la base `poissonnerie_saint_michel`, les tables, relations, vues et données de référence.

Pour une base existante, exécutez la migration
`sql/migrations/20260824_gerant_commandes.sql` avant le déploiement des commandes comptoir.
Elle conserve les commandes existantes comme des commandes en ligne et ajoute l'origine ainsi que l'adresse de livraison aux nouvelles ventes.

Ensuite, exécutez `sql/migrations/20260831_livreur_lifecycle.sql`.
Elle met à jour la procédure de changement de statut afin de permettre l’annulation d’une mission acceptée avant départ, puis la réaffectation de la commande à un autre livreur.

Enfin, exécutez `sql/migrations/20260901_zones_livraison_facture.sql`.
Elle ajoute les zones tarifaires Akpakpa (500 FCFA), Cotonou (1 000 FCFA) et Calavi (1 500 FCFA), ainsi que leur lien aux commandes.

Puis exécutez `sql/migrations/20260901_quartiers_livraison.sql`.
Elle ajoute les quartiers de livraison, les rattache aux zones tarifaires et conserve le quartier choisi avec chaque commande.

Enfin, exécutez `sql/migrations/20260901_securite_application.sql`.
Elle ajoute la protection contre les tentatives répétées de connexion, de suivi de commande et de renvoi de facture. Les clés stockées sont des empreintes irréversibles, pas des identifiants ou adresses IP en clair.
