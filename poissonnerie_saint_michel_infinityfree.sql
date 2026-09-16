-- Export compatible avec InfinityFree gratuit.
-- Données, tables, index et clés étrangères conservés.
-- Objets non pris en charge retirés : procédures, déclencheurs, vues et DEFINER.
-- La logique associée doit être exécutée par l'application PHP.

-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:8889
-- Généré le : sam. 12 sep. 2026 à 17:48
-- Version du serveur : 8.0.44
-- Version de PHP : 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `poissonnerie_saint_michel`
--


-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE `categories` (
  `id_categorie` bigint UNSIGNED NOT NULL,
  `libelle` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `est_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id_categorie`, `libelle`, `description`, `est_active`, `created_at`, `updated_at`) VALUES
(1, 'Poissons', 'Produits congelés de la catégorie poissons.', 1, '2026-08-31 17:41:06', '2026-08-31 17:41:06'),
(2, 'Viandes', 'Produits congelés de la catégorie viandes.', 1, '2026-08-31 17:41:06', '2026-08-31 17:41:06'),
(3, 'Œufs', 'Œufs de poule et produits associés.', 1, '2026-08-31 17:41:06', '2026-08-31 17:41:06');

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

CREATE TABLE `clients` (
  `id_client` bigint UNSIGNED NOT NULL,
  `nom_complet` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id_client`, `nom_complet`, `telephone`, `email`, `created_at`, `updated_at`) VALUES
(1, 'DOSSOU Martin', '0102030405', 'client@gmail.com', '2026-08-31 18:18:23', '2026-08-31 18:18:23'),
(2, 'MARTIN Diane', '0106000000', 'client1@gmail.com', '2026-08-31 19:15:04', '2026-08-31 19:15:04'),
(3, 'Dine Abdoul', '0162000000', 'client3@gmail.com', '2026-09-01 15:46:20', '2026-09-01 15:46:20'),
(5, 'Moustapha Gedeon', '0163000000', 'codex891016@outlook.fr', '2026-09-01 16:43:16', '2026-09-01 16:43:16'),
(8, 'Michael', '0196614753', 'akakposse@gmail.com', '2026-09-12 17:27:31', '2026-09-12 17:27:31');

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

CREATE TABLE `commandes` (
  `id_commande` bigint UNSIGNED NOT NULL,
  `id_client` bigint UNSIGNED NOT NULL,
  `numero_commande` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_commande` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `origine_commande` enum('EN_LIGNE','COMPTOIR') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EN_LIGNE',
  `statut_courant` enum('EN_ATTENTE','EN_COURS_TRAITEMENT','TRAITEE','EN_COURS_LIVRAISON','LIVREE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EN_ATTENTE',
  `mode_retrait` enum('LIVRAISON','RETRAIT_BOUTIQUE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_zone_livraison` bigint UNSIGNED DEFAULT NULL,
  `id_quartier_livraison` bigint UNSIGNED DEFAULT NULL,
  `adresse_livraison` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_produits` decimal(14,2) NOT NULL DEFAULT '0.00',
  `frais_livraison` decimal(14,2) NOT NULL DEFAULT '0.00',
  `montant_total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `note_client` text COLLATE utf8mb4_unicode_ci,
  `confirmation_reception` tinyint(1) NOT NULL DEFAULT '0',
  `date_confirmation_reception` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id_commande`, `id_client`, `numero_commande`, `date_commande`, `origine_commande`, `statut_courant`, `mode_retrait`, `id_zone_livraison`, `id_quartier_livraison`, `adresse_livraison`, `total_produits`, `frais_livraison`, `montant_total`, `note_client`, `confirmation_reception`, `date_confirmation_reception`, `created_at`, `updated_at`) VALUES
(1, 1, 'PSM-20260831-E3E030', '2026-08-31 18:18:23', 'COMPTOIR', 'LIVREE', 'RETRAIT_BOUTIQUE', NULL, NULL, NULL, 1700.00, 0.00, 1700.00, NULL, 0, NULL, '2026-08-31 18:18:23', '2026-09-01 15:43:28'),
(2, 1, 'PSM-20260831-BC353D', '2026-08-31 18:46:25', 'EN_LIGNE', 'LIVREE', 'LIVRAISON', NULL, NULL, 'Segbeya', 1600.00, 500.00, 2100.00, NULL, 1, '2026-08-31 19:13:14', '2026-08-31 18:46:25', '2026-08-31 19:13:14'),
(3, 2, 'PSM-20260831-B9ED45', '2026-08-31 19:15:04', 'EN_LIGNE', 'EN_ATTENTE', 'LIVRAISON', NULL, NULL, 'Aupiais', 4500.00, 500.00, 5000.00, NULL, 0, NULL, '2026-08-31 19:15:04', '2026-08-31 19:15:04'),
(4, 2, 'PSM-20260831-90AEFA', '2026-08-31 19:17:09', 'EN_LIGNE', 'EN_ATTENTE', 'LIVRAISON', NULL, NULL, 'Aupiais', 100.00, 500.00, 600.00, NULL, 0, NULL, '2026-08-31 19:17:09', '2026-08-31 19:17:09'),
(5, 3, 'PSM-20260901-C667B2', '2026-09-01 15:46:20', 'EN_LIGNE', 'EN_ATTENTE', 'LIVRAISON', NULL, NULL, 'KOWEGBO', 1700.00, 500.00, 2200.00, NULL, 0, NULL, '2026-09-01 15:46:20', '2026-09-01 15:46:20'),
(7, 3, 'PSM-20260901-A5D39A', '2026-09-01 16:25:27', 'EN_LIGNE', 'EN_ATTENTE', 'LIVRAISON', 3, NULL, 'Biosso', 1500.00, 1500.00, 3000.00, NULL, 0, NULL, '2026-09-01 16:25:27', '2026-09-01 16:25:27'),
(8, 5, 'PSM-20260901-0EDDE5', '2026-09-01 16:43:16', 'EN_LIGNE', 'EN_ATTENTE', 'LIVRAISON', 2, NULL, 'Saint-Michel', 1500.00, 1000.00, 2500.00, NULL, 0, NULL, '2026-09-01 16:43:16', '2026-09-01 16:43:16'),
(11, 5, 'PSM-20260901-7364F6', '2026-09-01 17:37:15', 'EN_LIGNE', 'EN_COURS_TRAITEMENT', 'LIVRAISON', 3, 103, 'Maison AKPAKA Ambroise', 2800.00, 1500.00, 4300.00, NULL, 0, NULL, '2026-09-01 17:37:15', '2026-09-12 18:22:34'),
(12, 8, 'PSM-20260912-B2F61D8684F22068', '2026-09-12 17:27:31', 'EN_LIGNE', 'LIVREE', 'LIVRAISON', 2, 55, 'Maison AKAKP', 2000.00, 1000.00, 3000.00, NULL, 1, '2026-09-12 18:25:13', '2026-09-12 17:27:31', '2026-09-12 18:25:13');

--
-- Déclencheurs `commandes`
--

-- --------------------------------------------------------

--
-- Structure de la table `historique_statuts_commande`
--

CREATE TABLE `historique_statuts_commande` (
  `id_historique_statut` bigint UNSIGNED NOT NULL,
  `id_commande` bigint UNSIGNED NOT NULL,
  `id_utilisateur` bigint UNSIGNED DEFAULT NULL,
  `ancien_statut` enum('EN_ATTENTE','EN_COURS_TRAITEMENT','TRAITEE','EN_COURS_LIVRAISON','LIVREE') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nouveau_statut` enum('EN_ATTENTE','EN_COURS_TRAITEMENT','TRAITEE','EN_COURS_LIVRAISON','LIVREE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `origine` enum('SYSTEME','CLIENT','GERANT','LIVREUR') COLLATE utf8mb4_unicode_ci NOT NULL,
  `commentaire` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_changement` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `historique_statuts_commande`
--

INSERT INTO `historique_statuts_commande` (`id_historique_statut`, `id_commande`, `id_utilisateur`, `ancien_statut`, `nouveau_statut`, `origine`, `commentaire`, `date_changement`) VALUES
(1, 1, NULL, NULL, 'EN_ATTENTE', 'SYSTEME', 'Création de la commande', '2026-08-31 18:18:23'),
(2, 1, 1, 'EN_ATTENTE', 'EN_COURS_TRAITEMENT', 'GERANT', NULL, '2026-08-31 18:18:30'),
(3, 1, 1, 'EN_COURS_TRAITEMENT', 'TRAITEE', 'GERANT', NULL, '2026-08-31 18:18:41'),
(4, 2, NULL, NULL, 'EN_ATTENTE', 'SYSTEME', 'Création de la commande', '2026-08-31 18:46:25'),
(5, 2, 2, 'EN_ATTENTE', 'EN_COURS_TRAITEMENT', 'GERANT', NULL, '2026-08-31 18:47:46'),
(6, 2, 2, 'EN_COURS_TRAITEMENT', 'TRAITEE', 'GERANT', NULL, '2026-08-31 18:47:51'),
(7, 2, 3, 'TRAITEE', 'EN_COURS_LIVRAISON', 'LIVREUR', 'Livraison acceptée par le livreur.', '2026-08-31 19:11:33'),
(8, 2, NULL, 'EN_COURS_LIVRAISON', 'LIVREE', 'CLIENT', 'Réception confirmée par le client.', '2026-08-31 19:13:14'),
(9, 3, NULL, NULL, 'EN_ATTENTE', 'SYSTEME', 'Création de la commande', '2026-08-31 19:15:04'),
(10, 4, NULL, NULL, 'EN_ATTENTE', 'SYSTEME', 'Création de la commande', '2026-08-31 19:17:09'),
(11, 1, 2, 'TRAITEE', 'LIVREE', 'GERANT', NULL, '2026-09-01 15:43:28'),
(12, 5, NULL, NULL, 'EN_ATTENTE', 'SYSTEME', 'Création de la commande', '2026-09-01 15:46:20'),
(14, 7, NULL, NULL, 'EN_ATTENTE', 'SYSTEME', 'Création de la commande', '2026-09-01 16:25:27'),
(15, 8, NULL, NULL, 'EN_ATTENTE', 'SYSTEME', 'Création de la commande', '2026-09-01 16:43:16'),
(18, 11, NULL, NULL, 'EN_ATTENTE', 'SYSTEME', 'Création de la commande', '2026-09-01 17:37:15'),
(19, 12, NULL, NULL, 'EN_ATTENTE', 'SYSTEME', 'Création de la commande', '2026-09-12 17:27:31'),
(20, 12, 1, 'EN_ATTENTE', 'EN_COURS_TRAITEMENT', 'GERANT', NULL, '2026-09-12 17:28:05'),
(21, 12, 1, 'EN_COURS_TRAITEMENT', 'TRAITEE', 'GERANT', NULL, '2026-09-12 18:22:29'),
(22, 11, 1, 'EN_ATTENTE', 'EN_COURS_TRAITEMENT', 'GERANT', NULL, '2026-09-12 18:22:34'),
(23, 12, 3, 'TRAITEE', 'EN_COURS_LIVRAISON', 'LIVREUR', 'Livraison acceptée par le livreur.', '2026-09-12 18:24:35'),
(24, 12, NULL, 'EN_COURS_LIVRAISON', 'LIVREE', 'CLIENT', 'Réception confirmée par le client.', '2026-09-12 18:25:13');

-- --------------------------------------------------------

--
-- Structure de la table `journal_audit`
--

CREATE TABLE `journal_audit` (
  `id_evenement` bigint UNSIGNED NOT NULL,
  `id_utilisateur` bigint UNSIGNED DEFAULT NULL,
  `type_evenement` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_evenement` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cible_type` enum('COMMANDE','PAIEMENT','PRODUIT','LIVRAISON','STOCK','PARAMETRAGE','UTILISATEUR') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cible_id` bigint UNSIGNED NOT NULL,
  `donnees_avant` json DEFAULT NULL,
  `donnees_apres` json DEFAULT NULL,
  `adresse_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `journal_audit`
--

INSERT INTO `journal_audit` (`id_evenement`, `id_utilisateur`, `type_evenement`, `date_evenement`, `cible_type`, `cible_id`, `donnees_avant`, `donnees_apres`, `adresse_ip`) VALUES
(1, 1, 'CREATION_UTILISATEUR', '2026-08-31 18:08:39', 'UTILISATEUR', 2, NULL, '{\"email\": \"akmultiservices2018@gmail.com\", \"id_role\": 2, \"est_actif\": 1, \"telephone\": \"0162183849\", \"nom_complet\": \"AHOCLOUNON Céline\", \"id_utilisateur\": 2}', '::1'),
(2, 1, 'CREATION_PRODUIT', '2026-08-31 18:11:55', 'PRODUIT', 1, NULL, '{\"libelle\": \"MTN\", \"est_actif\": 1, \"photo_url\": \"assets/uploads/produits/96a00cfff046296197648ac4c8a5e098.webp\", \"id_produit\": 1, \"description\": \"MTN est un poisson tres prisé et tres gouteux\", \"unite_vente\": \"KG\", \"id_categorie\": 1, \"seuil_alerte\": \"5.000\", \"prix_unitaire\": \"1700.00\", \"quantite_stock\": \"0.000\"}', '::1'),
(3, 1, 'CREATION_PRODUIT', '2026-08-31 18:13:29', 'PRODUIT', 2, NULL, '{\"libelle\": \"SALOMON\", \"est_actif\": 1, \"photo_url\": \"assets/uploads/produits/dbc37ecf8e01972d1301ed1577df8117.jpg\", \"id_produit\": 2, \"description\": \"Poisson tres prisé par les revendeuses pour differents mets locaux\", \"unite_vente\": \"KG\", \"id_categorie\": 1, \"seuil_alerte\": \"5.000\", \"prix_unitaire\": \"1500.00\", \"quantite_stock\": \"0.000\"}', '::1'),
(4, 1, 'CREATION_PRODUIT', '2026-08-31 18:14:13', 'PRODUIT', 3, NULL, '{\"libelle\": \"AILERON\", \"est_actif\": 1, \"photo_url\": \"assets/uploads/produits/af55951ce739066e929ee1523ce123a8.jpg\", \"id_produit\": 3, \"description\": \"Viande tres prisée pour les grillades\", \"unite_vente\": \"KG\", \"id_categorie\": 2, \"seuil_alerte\": \"3.000\", \"prix_unitaire\": \"2800.00\", \"quantite_stock\": \"0.000\"}', '::1'),
(5, 1, 'CREATION_PRODUIT', '2026-08-31 18:14:50', 'PRODUIT', 4, NULL, '{\"libelle\": \"OEUFS DE POULE\", \"est_actif\": 1, \"photo_url\": \"assets/uploads/produits/3600d815154a1752fdcc472fb02484a7.jpg\", \"id_produit\": 4, \"description\": \"Oeufs de poule locaux\", \"unite_vente\": \"UNITE\", \"id_categorie\": 3, \"seuil_alerte\": \"10.000\", \"prix_unitaire\": \"100.00\", \"quantite_stock\": \"0.000\"}', '::1'),
(6, 1, 'MOUVEMENT_STOCK', '2026-08-31 18:15:41', 'STOCK', 1, NULL, '{\"motif\": \"Hebdomadaire\", \"quantite\": \"48.000\", \"id_produit\": 4, \"stock_apres\": \"48.000\", \"stock_avant\": \"0.000\", \"date_mouvement\": \"2026-08-31 18:15:41\", \"id_utilisateur\": 1, \"type_mouvement\": \"AJUSTEMENT_POSITIF\", \"reference_source\": \"Sol des anges\", \"id_ligne_commande\": null, \"id_mouvement_stock\": 1}', '::1'),
(7, 1, 'MOUVEMENT_STOCK', '2026-08-31 18:16:12', 'STOCK', 2, NULL, '{\"motif\": \"Hebdomadaire\", \"quantite\": \"20.000\", \"id_produit\": 1, \"stock_apres\": \"20.000\", \"stock_avant\": \"0.000\", \"date_mouvement\": \"2026-08-31 18:16:12\", \"id_utilisateur\": 1, \"type_mouvement\": \"AJUSTEMENT_POSITIF\", \"reference_source\": \"Sol des anges\", \"id_ligne_commande\": null, \"id_mouvement_stock\": 2}', '::1'),
(8, 1, 'MOUVEMENT_STOCK', '2026-08-31 18:16:35', 'STOCK', 3, NULL, '{\"motif\": \"Mensuel\", \"quantite\": \"10.000\", \"id_produit\": 2, \"stock_apres\": \"10.000\", \"stock_avant\": \"0.000\", \"date_mouvement\": \"2026-08-31 18:16:35\", \"id_utilisateur\": 1, \"type_mouvement\": \"AJUSTEMENT_POSITIF\", \"reference_source\": \"Sol des anges\", \"id_ligne_commande\": null, \"id_mouvement_stock\": 3}', '::1'),
(9, 1, 'MOUVEMENT_STOCK', '2026-08-31 18:17:05', 'STOCK', 4, NULL, '{\"motif\": \"Hebdomadaire\", \"quantite\": \"20.000\", \"id_produit\": 3, \"stock_apres\": \"20.000\", \"stock_avant\": \"0.000\", \"date_mouvement\": \"2026-08-31 18:17:05\", \"id_utilisateur\": 1, \"type_mouvement\": \"AJUSTEMENT_POSITIF\", \"reference_source\": \"CDPA\", \"id_ligne_commande\": null, \"id_mouvement_stock\": 4}', '::1'),
(10, 1, 'CREATION_COMMANDE_COMPTOIR', '2026-08-31 18:18:23', 'COMMANDE', 1, NULL, '{\"total\": 1700, \"client\": 1, \"numero\": \"PSM-20260831-E3E030\", \"articles\": 1, \"paiement\": \"ESPECES\"}', '::1'),
(11, 1, 'CHANGEMENT_STATUT_COMMANDE', '2026-08-31 18:18:30', 'COMMANDE', 1, '{\"statut\": \"EN_ATTENTE\"}', '{\"role\": \"ADMINISTRATEUR\", \"statut\": \"EN_COURS_TRAITEMENT\"}', '::1'),
(12, 1, 'CHANGEMENT_STATUT_COMMANDE', '2026-08-31 18:18:41', 'COMMANDE', 1, '{\"statut\": \"EN_COURS_TRAITEMENT\"}', '{\"role\": \"ADMINISTRATEUR\", \"statut\": \"TRAITEE\"}', '::1'),
(13, 1, 'CONFIRMATION_PAIEMENT_ESPECES', '2026-08-31 18:19:21', 'PAIEMENT', 1, '{\"statut\": \"EN_ATTENTE\"}', '{\"recu\": \"REC-20260831-000001\", \"statut\": \"REUSSI\", \"reference\": \"ESP-20260831-000001\"}', '::1'),
(14, 1, 'MODIFICATION_PARAMETRAGE', '2026-08-31 18:25:47', 'PARAMETRAGE', 1, '{\"horaires\": null, \"updated_at\": \"2026-08-31 17:41:06\", \"id_parametre\": 1, \"nom_boutique\": \"Poissonnerie Saint-Michel\", \"email_boutique\": null, \"zones_livraison\": null, \"adresse_boutique\": \"Akpakpa, Cotonou\", \"telephone_boutique\": null, \"est_livraison_active\": 1, \"frais_livraison_defaut\": \"0.00\"}', '{\"horaires\": \"Lun-Sam:08h-19h30\", \"nom_boutique\": \"Poissonnerie Saint-Michel\", \"email_boutique\": \"akmultiservices2018@gmail.com\", \"zones_livraison\": \"AKPAKPA-GANKPODO\", \"adresse_boutique\": \"Akpakpa, Cotonou\", \"telephone_boutique\": \"0162183849\", \"est_livraison_active\": 1, \"frais_livraison_defaut\": 500}', '::1'),
(15, 1, 'CREATION_COMMANDE_EN_LIGNE', '2026-08-31 18:46:25', 'COMMANDE', 2, NULL, '{\"total\": 2100, \"client\": 1, \"numero\": \"PSM-20260831-BC353D\", \"articles\": 2, \"paiement\": \"MTN_MOMO\"}', '::1'),
(16, 1, 'CONFIRMATION_PAIEMENT_MOBILE_DEMO', '2026-08-31 18:46:40', 'PAIEMENT', 2, '{\"statut\": \"EN_ATTENTE\"}', '{\"statut\": \"REUSSI\", \"facture\": \"FAC-20260831-000002\", \"reference\": \"DEMO-20260831-000002\"}', '::1'),
(17, 2, 'CHANGEMENT_STATUT_COMMANDE', '2026-08-31 18:47:46', 'COMMANDE', 2, '{\"statut\": \"EN_ATTENTE\"}', '{\"role\": \"GERANT\", \"statut\": \"EN_COURS_TRAITEMENT\"}', '::1'),
(18, 2, 'CHANGEMENT_STATUT_COMMANDE', '2026-08-31 18:47:51', 'COMMANDE', 2, '{\"statut\": \"EN_COURS_TRAITEMENT\"}', '{\"role\": \"GERANT\", \"statut\": \"TRAITEE\"}', '::1'),
(19, 1, 'CREATION_UTILISATEUR', '2026-08-31 19:10:21', 'UTILISATEUR', 3, NULL, '{\"email\": \"livreur@gmail.com\", \"id_role\": 3, \"est_actif\": 1, \"telephone\": \"0103040708\", \"nom_complet\": \"AZIZ Mohamed\", \"id_utilisateur\": 3}', '::1'),
(20, 2, 'AFFECTATION_LIVRAISON', '2026-08-31 19:10:54', 'LIVRAISON', 1, NULL, '{\"livreur\": 3, \"commande\": 2}', '::1'),
(21, 3, 'ACCEPTATION_LIVRAISON', '2026-08-31 19:11:33', 'LIVRAISON', 1, '{\"statut\": \"AFFECTEE\"}', '{\"statut\": \"ACCEPTEE\"}', '::1'),
(22, 3, 'DEPART_LIVRAISON', '2026-08-31 19:11:47', 'LIVRAISON', 1, '{\"statut\": \"ACCEPTEE\"}', '{\"statut\": \"EN_COURS\"}', '::1'),
(23, 3, 'LIVRAISON_EFFECTUEE', '2026-08-31 19:12:45', 'LIVRAISON', 1, '{\"statut\": \"EN_COURS\"}', '{\"statut\": \"LIVREE\"}', '::1'),
(24, 3, 'CONFIRMATION_RECEPTION_CLIENT', '2026-08-31 19:13:14', 'COMMANDE', 2, '{\"statut\": \"EN_COURS_LIVRAISON\"}', '{\"statut\": \"LIVREE\", \"confirmation_reception\": true}', '::1'),
(25, 2, 'CREATION_COMMANDE_EN_LIGNE', '2026-08-31 19:15:04', 'COMMANDE', 3, NULL, '{\"total\": 5000, \"client\": 2, \"numero\": \"PSM-20260831-B9ED45\", \"articles\": 2, \"paiement\": \"MTN_MOMO\"}', '::1'),
(26, 2, 'CONFIRMATION_PAIEMENT_MOBILE_DEMO', '2026-08-31 19:15:37', 'PAIEMENT', 3, '{\"statut\": \"EN_ATTENTE\"}', '{\"statut\": \"REUSSI\", \"facture\": \"FAC-20260831-000003\", \"reference\": \"DEMO-20260831-000003\"}', '::1'),
(27, 2, 'CREATION_COMMANDE_EN_LIGNE', '2026-08-31 19:17:09', 'COMMANDE', 4, NULL, '{\"total\": 600, \"client\": 2, \"numero\": \"PSM-20260831-90AEFA\", \"articles\": 1, \"paiement\": \"MTN_MOMO\"}', '::1'),
(28, 2, 'CONFIRMATION_PAIEMENT_MOBILE_DEMO', '2026-08-31 19:17:14', 'PAIEMENT', 4, '{\"statut\": \"EN_ATTENTE\"}', '{\"statut\": \"REUSSI\", \"facture\": \"FAC-20260831-000004\", \"reference\": \"DEMO-20260831-000004\"}', '::1'),
(29, 2, 'CHANGEMENT_STATUT_COMMANDE', '2026-09-01 15:43:28', 'COMMANDE', 1, '{\"statut\": \"TRAITEE\"}', '{\"role\": \"GERANT\", \"statut\": \"LIVREE\"}', '::1'),
(30, 2, 'CREATION_COMMANDE_EN_LIGNE', '2026-09-01 15:46:20', 'COMMANDE', 5, NULL, '{\"total\": 2200, \"client\": 3, \"numero\": \"PSM-20260901-C667B2\", \"articles\": 1, \"paiement\": \"MTN_MOMO\"}', '::1'),
(31, 2, 'CONFIRMATION_PAIEMENT_MOBILE_DEMO', '2026-09-01 15:46:25', 'PAIEMENT', 5, '{\"statut\": \"EN_ATTENTE\"}', '{\"statut\": \"REUSSI\", \"facture\": \"FAC-20260901-000005\", \"reference\": \"DEMO-20260901-000005\"}', '::1'),
(32, 1, 'CREATION_COMMANDE_EN_LIGNE', '2026-09-01 16:25:27', 'COMMANDE', 7, NULL, '{\"total\": 3000, \"client\": 3, \"numero\": \"PSM-20260901-A5D39A\", \"articles\": 1, \"paiement\": \"MTN_MOMO\"}', '::1'),
(33, 1, 'CONFIRMATION_PAIEMENT_MOBILE_DEMO', '2026-09-01 16:25:45', 'PAIEMENT', 7, '{\"statut\": \"EN_ATTENTE\"}', '{\"statut\": \"REUSSI\", \"facture\": \"FAC-20260901-000007\", \"reference\": \"DEMO-20260901-000007\"}', '::1'),
(34, NULL, 'CREATION_COMMANDE_EN_LIGNE', '2026-09-01 16:43:16', 'COMMANDE', 8, NULL, '{\"total\": 2500, \"client\": 5, \"numero\": \"PSM-20260901-0EDDE5\", \"articles\": 1, \"paiement\": \"MTN_MOMO\"}', '::1'),
(35, NULL, 'CONFIRMATION_PAIEMENT_MOBILE_DEMO', '2026-09-01 16:43:20', 'PAIEMENT', 8, '{\"statut\": \"EN_ATTENTE\"}', '{\"statut\": \"REUSSI\", \"facture\": \"FAC-20260901-000008\", \"reference\": \"DEMO-20260901-000008\"}', '::1'),
(36, 1, 'CREATION_COMMANDE_EN_LIGNE', '2026-09-01 17:37:15', 'COMMANDE', 11, NULL, '{\"total\": 4300, \"client\": 5, \"numero\": \"PSM-20260901-7364F6\", \"articles\": 1, \"paiement\": \"MTN_MOMO\", \"quartier\": \"Kansounkpa\"}', '::1'),
(37, 1, 'CONFIRMATION_PAIEMENT_MOBILE_DEMO', '2026-09-01 17:37:19', 'PAIEMENT', 9, '{\"statut\": \"EN_ATTENTE\"}', '{\"statut\": \"REUSSI\", \"facture\": \"FAC-20260901-000009\", \"reference\": \"DEMO-20260901-000009\"}', '::1'),
(38, 1, 'CREATION_PRODUIT', '2026-09-12 17:23:37', 'PRODUIT', 5, NULL, '{\"libelle\": \"POULETS\", \"est_actif\": 1, \"photo_url\": \"assets/uploads/produits/787435fa7c036cc6ca505d69cf193807.webp\", \"id_produit\": 5, \"description\": \"Du bon poulet bien gros et de tres  bonne qualite\", \"unite_vente\": \"KG\", \"id_categorie\": 2, \"seuil_alerte\": \"4.000\", \"prix_unitaire\": \"2000.00\", \"quantite_stock\": \"0.000\"}', '::1'),
(39, 1, 'MODIFICATION_PRODUIT', '2026-09-12 17:24:06', 'PRODUIT', 4, '{\"libelle\": \"OEUFS DE POULE\", \"est_actif\": 1, \"photo_url\": \"assets/uploads/produits/3600d815154a1752fdcc472fb02484a7.jpg\", \"id_produit\": 4, \"description\": \"Oeufs de poule locaux\", \"unite_vente\": \"UNITE\", \"id_categorie\": 3, \"seuil_alerte\": \"10.000\", \"prix_unitaire\": \"100.00\", \"quantite_stock\": \"46.000\"}', '{\"libelle\": \"OEUFS DE POULE\", \"est_actif\": 1, \"photo_url\": \"assets/uploads/produits/ba257c3fced0dc68bedd56a55dccd60f.jpg\", \"id_produit\": 4, \"description\": \"Oeufs de poule locaux\", \"unite_vente\": \"UNITE\", \"id_categorie\": 3, \"seuil_alerte\": \"10.000\", \"prix_unitaire\": \"100.00\", \"quantite_stock\": \"46.000\"}', '::1'),
(40, 1, 'MODIFICATION_PRODUIT', '2026-09-12 17:24:24', 'PRODUIT', 3, '{\"libelle\": \"AILERON\", \"est_actif\": 1, \"photo_url\": \"assets/uploads/produits/af55951ce739066e929ee1523ce123a8.jpg\", \"id_produit\": 3, \"description\": \"Viande tres prisée pour les grillades\", \"unite_vente\": \"KG\", \"id_categorie\": 2, \"seuil_alerte\": \"3.000\", \"prix_unitaire\": \"2800.00\", \"quantite_stock\": \"18.000\"}', '{\"libelle\": \"AILERON\", \"est_actif\": 1, \"photo_url\": \"assets/uploads/produits/ad35681972a91b607262bd0bc7405fee.jpg\", \"id_produit\": 3, \"description\": \"Viande tres prisée pour les grillades\", \"unite_vente\": \"KG\", \"id_categorie\": 2, \"seuil_alerte\": \"3.000\", \"prix_unitaire\": \"2800.00\", \"quantite_stock\": \"18.000\"}', '::1'),
(41, 1, 'MODIFICATION_PRODUIT', '2026-09-12 17:24:50', 'PRODUIT', 2, '{\"libelle\": \"SALOMON\", \"est_actif\": 1, \"photo_url\": \"assets/uploads/produits/dbc37ecf8e01972d1301ed1577df8117.jpg\", \"id_produit\": 2, \"description\": \"Poisson tres prisé par les revendeuses pour differents mets locaux\", \"unite_vente\": \"KG\", \"id_categorie\": 1, \"seuil_alerte\": \"5.000\", \"prix_unitaire\": \"1500.00\", \"quantite_stock\": \"7.000\"}', '{\"libelle\": \"SALOMON\", \"est_actif\": 1, \"photo_url\": \"assets/uploads/produits/070dd3c97d2e83481783404981c0562d.jpg\", \"id_produit\": 2, \"description\": \"Poisson tres prisé par les revendeuses pour differents mets locaux\", \"unite_vente\": \"KG\", \"id_categorie\": 1, \"seuil_alerte\": \"5.000\", \"prix_unitaire\": \"1500.00\", \"quantite_stock\": \"7.000\"}', '::1'),
(42, 1, 'MODIFICATION_PRODUIT', '2026-09-12 17:25:04', 'PRODUIT', 1, '{\"libelle\": \"MTN\", \"est_actif\": 1, \"photo_url\": \"assets/uploads/produits/96a00cfff046296197648ac4c8a5e098.webp\", \"id_produit\": 1, \"description\": \"MTN est un poisson tres prisé et tres gouteux\", \"unite_vente\": \"KG\", \"id_categorie\": 1, \"seuil_alerte\": \"5.000\", \"prix_unitaire\": \"1700.00\", \"quantite_stock\": \"17.000\"}', '{\"libelle\": \"MTN\", \"est_actif\": 1, \"photo_url\": \"assets/uploads/produits/6d870e687f4d79bca9b53db7081a9c47.webp\", \"id_produit\": 1, \"description\": \"MTN est un poisson tres prisé et tres gouteux\", \"unite_vente\": \"KG\", \"id_categorie\": 1, \"seuil_alerte\": \"5.000\", \"prix_unitaire\": \"1700.00\", \"quantite_stock\": \"17.000\"}', '::1'),
(43, 1, 'MOUVEMENT_STOCK', '2026-09-12 17:26:18', 'STOCK', 15, NULL, '{\"motif\": \"Hebdomadaire\", \"quantite\": \"10.000\", \"id_produit\": 5, \"stock_apres\": \"10.000\", \"stock_avant\": \"0.000\", \"date_mouvement\": \"2026-09-12 17:26:18\", \"id_utilisateur\": 1, \"type_mouvement\": \"AJUSTEMENT_POSITIF\", \"reference_source\": \"CDPA\", \"id_ligne_commande\": null, \"id_mouvement_stock\": 15}', '::1'),
(44, 1, 'CREATION_COMMANDE_EN_LIGNE', '2026-09-12 17:27:31', 'COMMANDE', 12, NULL, '{\"total\": 3000, \"client\": 8, \"numero\": \"PSM-20260912-B2F61D8684F22068\", \"articles\": 1, \"paiement\": \"MTN_MOMO\", \"quartier\": \"Agontikon\"}', '::1'),
(45, 1, 'CONFIRMATION_PAIEMENT_MOBILE_DEMO', '2026-09-12 17:27:37', 'PAIEMENT', 10, '{\"statut\": \"EN_ATTENTE\"}', '{\"statut\": \"REUSSI\", \"facture\": \"FAC-20260912-000010\", \"reference\": \"DEMO-20260912-000010\"}', '::1'),
(46, 1, 'CHANGEMENT_STATUT_COMMANDE', '2026-09-12 17:28:05', 'COMMANDE', 12, '{\"statut\": \"EN_ATTENTE\"}', '{\"role\": \"ADMINISTRATEUR\", \"statut\": \"EN_COURS_TRAITEMENT\"}', '::1'),
(47, 1, 'CHANGEMENT_STATUT_COMMANDE', '2026-09-12 18:22:29', 'COMMANDE', 12, '{\"statut\": \"EN_COURS_TRAITEMENT\"}', '{\"role\": \"ADMINISTRATEUR\", \"statut\": \"TRAITEE\"}', '::1'),
(48, 1, 'CHANGEMENT_STATUT_COMMANDE', '2026-09-12 18:22:34', 'COMMANDE', 11, '{\"statut\": \"EN_ATTENTE\"}', '{\"role\": \"ADMINISTRATEUR\", \"statut\": \"EN_COURS_TRAITEMENT\"}', '::1'),
(49, 1, 'AFFECTATION_LIVRAISON', '2026-09-12 18:22:43', 'LIVRAISON', 2, NULL, '{\"livreur\": 3, \"commande\": 12}', '::1'),
(50, 3, 'ACCEPTATION_LIVRAISON', '2026-09-12 18:24:35', 'LIVRAISON', 2, '{\"statut\": \"AFFECTEE\"}', '{\"statut\": \"ACCEPTEE\"}', '::1'),
(51, 3, 'DEPART_LIVRAISON', '2026-09-12 18:24:40', 'LIVRAISON', 2, '{\"statut\": \"ACCEPTEE\"}', '{\"statut\": \"EN_COURS\"}', '::1'),
(52, 3, 'LIVRAISON_EFFECTUEE', '2026-09-12 18:24:44', 'LIVRAISON', 2, '{\"statut\": \"EN_COURS\"}', '{\"statut\": \"LIVREE\"}', '::1'),
(53, 3, 'CONFIRMATION_RECEPTION_CLIENT', '2026-09-12 18:25:13', 'COMMANDE', 12, '{\"statut\": \"EN_COURS_LIVRAISON\"}', '{\"statut\": \"LIVREE\", \"confirmation_reception\": true}', '::1');

-- --------------------------------------------------------

--
-- Structure de la table `justificatifs_paiement`
--

CREATE TABLE `justificatifs_paiement` (
  `id_justificatif` bigint UNSIGNED NOT NULL,
  `id_paiement` bigint UNSIGNED NOT NULL,
  `type_document` enum('FACTURE','RECU') COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_document` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_emission` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `chemin_document` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `justificatifs_paiement`
--

INSERT INTO `justificatifs_paiement` (`id_justificatif`, `id_paiement`, `type_document`, `numero_document`, `date_emission`, `chemin_document`) VALUES
(1, 1, 'RECU', 'REC-20260831-000001', '2026-08-31 18:19:21', 'gerant/justificatif.php?paiement=1'),
(2, 2, 'FACTURE', 'FAC-20260831-000002', '2026-08-31 18:46:40', 'facture.php?commande=PSM-20260831-BC353D'),
(3, 3, 'FACTURE', 'FAC-20260831-000003', '2026-08-31 19:15:37', 'facture.php?commande=PSM-20260831-B9ED45'),
(4, 4, 'FACTURE', 'FAC-20260831-000004', '2026-08-31 19:17:14', 'facture.php?commande=PSM-20260831-90AEFA'),
(5, 5, 'FACTURE', 'FAC-20260901-000005', '2026-09-01 15:46:25', 'facture.php?commande=PSM-20260901-C667B2'),
(6, 7, 'FACTURE', 'FAC-20260901-000007', '2026-09-01 16:25:45', 'facture.php?commande=PSM-20260901-A5D39A'),
(7, 8, 'FACTURE', 'FAC-20260901-000008', '2026-09-01 16:43:20', 'facture.php?commande=PSM-20260901-0EDDE5'),
(8, 9, 'FACTURE', 'FAC-20260901-000009', '2026-09-01 17:37:19', 'facture.php?commande=PSM-20260901-7364F6'),
(9, 10, 'FACTURE', 'FAC-20260912-000010', '2026-09-12 17:27:37', 'facture.php?commande=PSM-20260912-B2F61D8684F22068');

--
-- Déclencheurs `justificatifs_paiement`
--

-- --------------------------------------------------------

--
-- Structure de la table `lignes_commande`
--

CREATE TABLE `lignes_commande` (
  `id_ligne_commande` bigint UNSIGNED NOT NULL,
  `id_commande` bigint UNSIGNED NOT NULL,
  `id_produit` bigint UNSIGNED NOT NULL,
  `quantite` decimal(14,3) NOT NULL,
  `prix_unitaire_applique` decimal(12,2) NOT NULL,
  `sous_total` decimal(14,2) GENERATED ALWAYS AS (round((`quantite` * `prix_unitaire_applique`),2)) STORED,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Déchargement des données de la table `lignes_commande`
--

INSERT INTO `lignes_commande` (`id_ligne_commande`, `id_commande`, `id_produit`, `quantite`, `prix_unitaire_applique`, `created_at`) VALUES
(1, 1, 1, 1.000, 1700.00, '2026-08-31 18:18:23'),
(2, 2, 4, 1.000, 100.00, '2026-08-31 18:46:25'),
(3, 2, 2, 1.000, 1500.00, '2026-08-31 18:46:25'),
(4, 3, 1, 1.000, 1700.00, '2026-08-31 19:15:04'),
(5, 3, 3, 1.000, 2800.00, '2026-08-31 19:15:04'),
(6, 4, 4, 1.000, 100.00, '2026-08-31 19:17:09'),
(7, 5, 1, 1.000, 1700.00, '2026-09-01 15:46:20'),
(8, 7, 2, 1.000, 1500.00, '2026-09-01 16:25:27'),
(9, 8, 2, 1.000, 1500.00, '2026-09-01 16:43:16'),
(10, 11, 3, 1.000, 2800.00, '2026-09-01 17:37:15'),
(11, 12, 5, 1.000, 2000.00, '2026-09-12 17:27:31');

--
-- Déclencheurs `lignes_commande`
--

-- --------------------------------------------------------

--
-- Structure de la table `limites_securite`
--

CREATE TABLE `limites_securite` (
  `cle_rate_limite` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_limite` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `debut_fenetre` datetime NOT NULL,
  `tentatives` smallint UNSIGNED NOT NULL DEFAULT '0',
  `bloque_jusqua` datetime DEFAULT NULL,
  `derniere_tentative` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Déchargement des données de la table `limites_securite`
--

INSERT INTO `limites_securite` (`cle_rate_limite`, `type_limite`, `debut_fenetre`, `tentatives`, `bloque_jusqua`, `derniere_tentative`) VALUES
('ade174abee08002059deba0d6eb03b30699d80fafeb14131491b2faf9497ad1f', 'renvoi_facture_ip', '2026-09-12 18:10:56', 3, NULL, '2026-09-12 18:21:19'),
('ae9156758f9e3bdf61055e2d587f88dfc2766b8200372d2bcc88cd33dbf589b4', 'connexion_equipe_ip', '2026-09-12 18:23:25', 1, NULL, '2026-09-12 18:23:25'),
('b0d174e6431b2a780f985d6812f64bf5da17543a8d4472d7d7db496d4e3cdc46', 'renvoi_facture_email', '2026-09-12 18:10:56', 3, NULL, '2026-09-12 18:21:19'),
('c3fd543ba8f002fcd43d576b4024c8ba36c23f1e79a5e92a6a56ac7e6bea0329', 'suivi_commande', '2026-09-12 18:25:01', 2, NULL, '2026-09-12 18:25:13');

-- --------------------------------------------------------

--
-- Structure de la table `livraisons`
--

CREATE TABLE `livraisons` (
  `id_livraison` bigint UNSIGNED NOT NULL,
  `id_commande` bigint UNSIGNED NOT NULL,
  `id_livreur` bigint UNSIGNED NOT NULL,
  `id_affectant` bigint UNSIGNED NOT NULL,
  `adresse_livraison` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_livraison` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut_livraison` enum('AFFECTEE','ACCEPTEE','REFUSEE','EN_COURS','LIVREE','ANNULEE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AFFECTEE',
  `date_affectation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_acceptation` datetime DEFAULT NULL,
  `date_depart` datetime DEFAULT NULL,
  `date_livraison` datetime DEFAULT NULL,
  `motif_refus_annulation` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Déchargement des données de la table `livraisons`
--

INSERT INTO `livraisons` (`id_livraison`, `id_commande`, `id_livreur`, `id_affectant`, `adresse_livraison`, `contact_livraison`, `statut_livraison`, `date_affectation`, `date_acceptation`, `date_depart`, `date_livraison`, `motif_refus_annulation`, `created_at`, `updated_at`) VALUES
(1, 2, 3, 2, 'Segbeya', '0102030405', 'LIVREE', '2026-08-31 19:10:54', '2026-08-31 19:11:33', '2026-08-31 19:11:47', '2026-08-31 19:12:45', NULL, '2026-08-31 19:10:54', '2026-08-31 19:12:45'),
(2, 12, 3, 1, 'Maison AKAKP', '0196614753', 'LIVREE', '2026-09-12 18:22:43', '2026-09-12 18:24:35', '2026-09-12 18:24:40', '2026-09-12 18:24:44', NULL, '2026-09-12 18:22:43', '2026-09-12 18:24:44');

--
-- Déclencheurs `livraisons`
--

-- --------------------------------------------------------

--
-- Structure de la table `mouvements_stock`
--

CREATE TABLE `mouvements_stock` (
  `id_mouvement_stock` bigint UNSIGNED NOT NULL,
  `id_produit` bigint UNSIGNED NOT NULL,
  `id_ligne_commande` bigint UNSIGNED DEFAULT NULL,
  `id_utilisateur` bigint UNSIGNED DEFAULT NULL,
  `type_mouvement` enum('ENTREE','SORTIE','AJUSTEMENT_POSITIF','AJUSTEMENT_NEGATIF') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantite` decimal(14,3) NOT NULL,
  `stock_avant` decimal(14,3) NOT NULL,
  `stock_apres` decimal(14,3) NOT NULL,
  `date_mouvement` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `motif` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_source` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ;

--
-- Déchargement des données de la table `mouvements_stock`
--

INSERT INTO `mouvements_stock` (`id_mouvement_stock`, `id_produit`, `id_ligne_commande`, `id_utilisateur`, `type_mouvement`, `quantite`, `stock_avant`, `stock_apres`, `date_mouvement`, `motif`, `reference_source`) VALUES
(1, 4, NULL, 1, 'AJUSTEMENT_POSITIF', 48.000, 0.000, 48.000, '2026-08-31 18:15:41', 'Hebdomadaire', 'Sol des anges'),
(2, 1, NULL, 1, 'AJUSTEMENT_POSITIF', 20.000, 0.000, 20.000, '2026-08-31 18:16:12', 'Hebdomadaire', 'Sol des anges'),
(3, 2, NULL, 1, 'AJUSTEMENT_POSITIF', 10.000, 0.000, 10.000, '2026-08-31 18:16:35', 'Mensuel', 'Sol des anges'),
(4, 3, NULL, 1, 'AJUSTEMENT_POSITIF', 20.000, 0.000, 20.000, '2026-08-31 18:17:05', 'Hebdomadaire', 'CDPA'),
(5, 1, 1, 1, 'SORTIE', 1.000, 20.000, 19.000, '2026-08-31 18:18:23', 'Vente comptoir PSM-20260831-E3E030', 'PSM-20260831-E3E030'),
(6, 4, 2, NULL, 'SORTIE', 1.000, 48.000, 47.000, '2026-08-31 18:46:25', 'Commande en ligne PSM-20260831-BC353D', 'PSM-20260831-BC353D'),
(7, 2, 3, NULL, 'SORTIE', 1.000, 10.000, 9.000, '2026-08-31 18:46:25', 'Commande en ligne PSM-20260831-BC353D', 'PSM-20260831-BC353D'),
(8, 1, 4, NULL, 'SORTIE', 1.000, 19.000, 18.000, '2026-08-31 19:15:04', 'Commande en ligne PSM-20260831-B9ED45', 'PSM-20260831-B9ED45'),
(9, 3, 5, NULL, 'SORTIE', 1.000, 20.000, 19.000, '2026-08-31 19:15:04', 'Commande en ligne PSM-20260831-B9ED45', 'PSM-20260831-B9ED45'),
(10, 4, 6, NULL, 'SORTIE', 1.000, 47.000, 46.000, '2026-08-31 19:17:09', 'Commande en ligne PSM-20260831-90AEFA', 'PSM-20260831-90AEFA'),
(11, 1, 7, NULL, 'SORTIE', 1.000, 18.000, 17.000, '2026-09-01 15:46:20', 'Commande en ligne PSM-20260901-C667B2', 'PSM-20260901-C667B2'),
(12, 2, 8, NULL, 'SORTIE', 1.000, 9.000, 8.000, '2026-09-01 16:25:27', 'Commande en ligne PSM-20260901-A5D39A', 'PSM-20260901-A5D39A'),
(13, 2, 9, NULL, 'SORTIE', 1.000, 8.000, 7.000, '2026-09-01 16:43:16', 'Commande en ligne PSM-20260901-0EDDE5', 'PSM-20260901-0EDDE5'),
(14, 3, 10, NULL, 'SORTIE', 1.000, 19.000, 18.000, '2026-09-01 17:37:15', 'Commande en ligne PSM-20260901-7364F6', 'PSM-20260901-7364F6'),
(15, 5, NULL, 1, 'AJUSTEMENT_POSITIF', 10.000, 0.000, 10.000, '2026-09-12 17:26:18', 'Hebdomadaire', 'CDPA'),
(16, 5, 11, NULL, 'SORTIE', 1.000, 10.000, 9.000, '2026-09-12 17:27:31', 'Commande en ligne PSM-20260912-B2F61D8684F22068', 'PSM-20260912-B2F61D8684F22068');

--
-- Déclencheurs `mouvements_stock`
--

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id_notification` bigint UNSIGNED NOT NULL,
  `id_commande` bigint UNSIGNED NOT NULL,
  `id_client` bigint UNSIGNED DEFAULT NULL,
  `id_utilisateur` bigint UNSIGNED DEFAULT NULL,
  `type_notification` enum('NOUVELLE_COMMANDE','CHANGEMENT_STATUT','AFFECTATION_LIVRAISON','PAIEMENT') COLLATE utf8mb4_unicode_ci NOT NULL,
  `canal` enum('WEB','EMAIL','SMS') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'WEB',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_lecture` datetime DEFAULT NULL,
  `statut_envoi` enum('A_ENVOYER','ENVOYEE','ECHEC','LUE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A_ENVOYER'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id_notification`, `id_commande`, `id_client`, `id_utilisateur`, `type_notification`, `canal`, `message`, `date_creation`, `date_lecture`, `statut_envoi`) VALUES
(1, 1, 1, NULL, 'CHANGEMENT_STATUT', 'WEB', 'Votre commande PSM-20260831-E3E030 est désormais en cours de traitement.', '2026-08-31 18:18:30', NULL, 'A_ENVOYER'),
(2, 1, 1, NULL, 'CHANGEMENT_STATUT', 'WEB', 'Votre commande PSM-20260831-E3E030 est désormais traitée.', '2026-08-31 18:18:41', NULL, 'A_ENVOYER'),
(3, 1, 1, NULL, 'PAIEMENT', 'WEB', 'Le paiement en espèces de votre commande PSM-20260831-E3E030 a été confirmé. Reçu : REC-20260831-000001.', '2026-08-31 18:19:21', NULL, 'A_ENVOYER'),
(4, 2, NULL, 1, 'NOUVELLE_COMMANDE', 'WEB', 'Nouvelle commande en ligne PSM-20260831-BC353D à traiter (2 100 FCFA).', '2026-08-31 18:46:25', NULL, 'A_ENVOYER'),
(5, 2, NULL, 2, 'NOUVELLE_COMMANDE', 'WEB', 'Nouvelle commande en ligne PSM-20260831-BC353D à traiter (2 100 FCFA).', '2026-08-31 18:46:25', NULL, 'A_ENVOYER'),
(6, 2, 1, NULL, 'PAIEMENT', 'WEB', 'Le paiement de votre commande PSM-20260831-BC353D a été confirmé. Facture : FAC-20260831-000002.', '2026-08-31 18:46:40', NULL, 'A_ENVOYER'),
(7, 2, 1, NULL, 'CHANGEMENT_STATUT', 'WEB', 'Votre commande PSM-20260831-BC353D est désormais en cours de traitement.', '2026-08-31 18:47:46', NULL, 'A_ENVOYER'),
(8, 2, 1, NULL, 'CHANGEMENT_STATUT', 'WEB', 'Votre commande PSM-20260831-BC353D est désormais traitée.', '2026-08-31 18:47:51', NULL, 'A_ENVOYER'),
(9, 2, NULL, 3, 'AFFECTATION_LIVRAISON', 'WEB', 'Nouvelle livraison affectée : commande PSM-20260831-BC353D, client 0102030405.', '2026-08-31 19:10:54', NULL, 'A_ENVOYER'),
(10, 2, 1, NULL, 'CHANGEMENT_STATUT', 'WEB', 'Votre commande PSM-20260831-BC353D est prise en charge par le livreur.', '2026-08-31 19:11:33', NULL, 'A_ENVOYER'),
(11, 2, NULL, 2, 'AFFECTATION_LIVRAISON', 'WEB', 'Le livreur a accepté la commande PSM-20260831-BC353D.', '2026-08-31 19:11:33', NULL, 'A_ENVOYER'),
(12, 2, NULL, 1, 'AFFECTATION_LIVRAISON', 'WEB', 'Le livreur a accepté la commande PSM-20260831-BC353D.', '2026-08-31 19:11:33', NULL, 'A_ENVOYER'),
(13, 2, 1, NULL, 'CHANGEMENT_STATUT', 'WEB', 'Le livreur est en route pour votre commande PSM-20260831-BC353D.', '2026-08-31 19:11:47', NULL, 'A_ENVOYER'),
(14, 2, NULL, 2, 'AFFECTATION_LIVRAISON', 'WEB', 'Le livreur est parti pour la commande PSM-20260831-BC353D.', '2026-08-31 19:11:47', NULL, 'A_ENVOYER'),
(15, 2, NULL, 1, 'AFFECTATION_LIVRAISON', 'WEB', 'Le livreur est parti pour la commande PSM-20260831-BC353D.', '2026-08-31 19:11:47', NULL, 'A_ENVOYER'),
(16, 2, 1, NULL, 'CHANGEMENT_STATUT', 'WEB', 'Votre commande PSM-20260831-BC353D a été livrée. Confirmez sa réception depuis le suivi de commande.', '2026-08-31 19:12:45', NULL, 'A_ENVOYER'),
(17, 2, NULL, 2, 'AFFECTATION_LIVRAISON', 'WEB', 'La livraison de la commande PSM-20260831-BC353D est déclarée effectuée. En attente de confirmation du client.', '2026-08-31 19:12:45', NULL, 'A_ENVOYER'),
(18, 2, NULL, 1, 'AFFECTATION_LIVRAISON', 'WEB', 'La livraison de la commande PSM-20260831-BC353D est déclarée effectuée. En attente de confirmation du client.', '2026-08-31 19:12:45', NULL, 'A_ENVOYER'),
(19, 2, NULL, 1, 'CHANGEMENT_STATUT', 'WEB', 'Le client a confirmé la réception de la commande PSM-20260831-BC353D.', '2026-08-31 19:13:14', NULL, 'A_ENVOYER'),
(20, 2, NULL, 2, 'CHANGEMENT_STATUT', 'WEB', 'Le client a confirmé la réception de la commande PSM-20260831-BC353D.', '2026-08-31 19:13:14', NULL, 'A_ENVOYER'),
(21, 3, NULL, 1, 'NOUVELLE_COMMANDE', 'WEB', 'Nouvelle commande en ligne PSM-20260831-B9ED45 à traiter (5 000 FCFA).', '2026-08-31 19:15:04', NULL, 'A_ENVOYER'),
(22, 3, NULL, 2, 'NOUVELLE_COMMANDE', 'WEB', 'Nouvelle commande en ligne PSM-20260831-B9ED45 à traiter (5 000 FCFA).', '2026-08-31 19:15:04', NULL, 'A_ENVOYER'),
(23, 3, 2, NULL, 'PAIEMENT', 'WEB', 'Le paiement de votre commande PSM-20260831-B9ED45 a été confirmé. Facture : FAC-20260831-000003.', '2026-08-31 19:15:37', NULL, 'A_ENVOYER'),
(24, 4, NULL, 1, 'NOUVELLE_COMMANDE', 'WEB', 'Nouvelle commande en ligne PSM-20260831-90AEFA à traiter (600 FCFA).', '2026-08-31 19:17:09', NULL, 'A_ENVOYER'),
(25, 4, NULL, 2, 'NOUVELLE_COMMANDE', 'WEB', 'Nouvelle commande en ligne PSM-20260831-90AEFA à traiter (600 FCFA).', '2026-08-31 19:17:09', NULL, 'A_ENVOYER'),
(26, 4, 2, NULL, 'PAIEMENT', 'WEB', 'Le paiement de votre commande PSM-20260831-90AEFA a été confirmé. Facture : FAC-20260831-000004.', '2026-08-31 19:17:14', NULL, 'A_ENVOYER'),
(27, 1, 1, NULL, 'CHANGEMENT_STATUT', 'WEB', 'Votre commande PSM-20260831-E3E030 est désormais livrée.', '2026-09-01 15:43:28', NULL, 'A_ENVOYER'),
(28, 5, NULL, 1, 'NOUVELLE_COMMANDE', 'WEB', 'Nouvelle commande en ligne PSM-20260901-C667B2 à traiter (2 200 FCFA).', '2026-09-01 15:46:20', NULL, 'A_ENVOYER'),
(29, 5, NULL, 2, 'NOUVELLE_COMMANDE', 'WEB', 'Nouvelle commande en ligne PSM-20260901-C667B2 à traiter (2 200 FCFA).', '2026-09-01 15:46:20', NULL, 'A_ENVOYER'),
(30, 5, 3, NULL, 'PAIEMENT', 'WEB', 'Le paiement de votre commande PSM-20260901-C667B2 a été confirmé. Facture : FAC-20260901-000005.', '2026-09-01 15:46:25', NULL, 'A_ENVOYER'),
(31, 7, NULL, 1, 'NOUVELLE_COMMANDE', 'WEB', 'Nouvelle commande en ligne PSM-20260901-A5D39A à traiter (3 000 FCFA).', '2026-09-01 16:25:27', NULL, 'A_ENVOYER'),
(32, 7, NULL, 2, 'NOUVELLE_COMMANDE', 'WEB', 'Nouvelle commande en ligne PSM-20260901-A5D39A à traiter (3 000 FCFA).', '2026-09-01 16:25:27', NULL, 'A_ENVOYER'),
(33, 7, 3, NULL, 'PAIEMENT', 'WEB', 'Le paiement de votre commande PSM-20260901-A5D39A a été confirmé. Facture : FAC-20260901-000007.', '2026-09-01 16:25:45', NULL, 'A_ENVOYER'),
(34, 8, NULL, 1, 'NOUVELLE_COMMANDE', 'WEB', 'Nouvelle commande en ligne PSM-20260901-0EDDE5 à traiter (2 500 FCFA).', '2026-09-01 16:43:16', NULL, 'A_ENVOYER'),
(35, 8, NULL, 2, 'NOUVELLE_COMMANDE', 'WEB', 'Nouvelle commande en ligne PSM-20260901-0EDDE5 à traiter (2 500 FCFA).', '2026-09-01 16:43:16', NULL, 'A_ENVOYER'),
(36, 8, 5, NULL, 'PAIEMENT', 'WEB', 'Le paiement de votre commande PSM-20260901-0EDDE5 a été confirmé. Facture : FAC-20260901-000008.', '2026-09-01 16:43:20', NULL, 'A_ENVOYER'),
(37, 11, NULL, 1, 'NOUVELLE_COMMANDE', 'WEB', 'Nouvelle commande en ligne PSM-20260901-7364F6 à traiter (4 300 FCFA).', '2026-09-01 17:37:15', NULL, 'A_ENVOYER'),
(38, 11, NULL, 2, 'NOUVELLE_COMMANDE', 'WEB', 'Nouvelle commande en ligne PSM-20260901-7364F6 à traiter (4 300 FCFA).', '2026-09-01 17:37:15', NULL, 'A_ENVOYER'),
(39, 11, 5, NULL, 'PAIEMENT', 'WEB', 'Le paiement de votre commande PSM-20260901-7364F6 a été confirmé. Facture : FAC-20260901-000009.', '2026-09-01 17:37:19', NULL, 'A_ENVOYER'),
(40, 12, NULL, 1, 'NOUVELLE_COMMANDE', 'WEB', 'Nouvelle commande en ligne PSM-20260912-B2F61D8684F22068 à traiter (3 000 FCFA).', '2026-09-12 17:27:31', NULL, 'A_ENVOYER'),
(41, 12, NULL, 2, 'NOUVELLE_COMMANDE', 'WEB', 'Nouvelle commande en ligne PSM-20260912-B2F61D8684F22068 à traiter (3 000 FCFA).', '2026-09-12 17:27:31', NULL, 'A_ENVOYER'),
(42, 12, 8, NULL, 'PAIEMENT', 'WEB', 'Le paiement de votre commande PSM-20260912-B2F61D8684F22068 a été confirmé. Facture : FAC-20260912-000010.', '2026-09-12 17:27:37', NULL, 'A_ENVOYER'),
(43, 12, 8, NULL, 'CHANGEMENT_STATUT', 'WEB', 'Votre commande PSM-20260912-B2F61D8684F22068 est désormais en cours de traitement.', '2026-09-12 17:28:05', NULL, 'A_ENVOYER'),
(44, 12, 8, NULL, 'CHANGEMENT_STATUT', 'WEB', 'Votre commande PSM-20260912-B2F61D8684F22068 est désormais traitée.', '2026-09-12 18:22:29', NULL, 'A_ENVOYER'),
(45, 11, 5, NULL, 'CHANGEMENT_STATUT', 'WEB', 'Votre commande PSM-20260901-7364F6 est désormais en cours de traitement.', '2026-09-12 18:22:34', NULL, 'A_ENVOYER'),
(46, 12, NULL, 3, 'AFFECTATION_LIVRAISON', 'WEB', 'Nouvelle livraison affectée : commande PSM-20260912-B2F61D8684F22068, client 0196614753.', '2026-09-12 18:22:43', NULL, 'A_ENVOYER'),
(47, 12, 8, NULL, 'CHANGEMENT_STATUT', 'WEB', 'Votre commande PSM-20260912-B2F61D8684F22068 est prise en charge par le livreur.', '2026-09-12 18:24:35', NULL, 'A_ENVOYER'),
(48, 12, NULL, 1, 'AFFECTATION_LIVRAISON', 'WEB', 'Le livreur a accepté la commande PSM-20260912-B2F61D8684F22068.', '2026-09-12 18:24:35', NULL, 'A_ENVOYER'),
(49, 12, NULL, 2, 'AFFECTATION_LIVRAISON', 'WEB', 'Le livreur a accepté la commande PSM-20260912-B2F61D8684F22068.', '2026-09-12 18:24:35', NULL, 'A_ENVOYER'),
(50, 12, 8, NULL, 'CHANGEMENT_STATUT', 'WEB', 'Le livreur est en route pour votre commande PSM-20260912-B2F61D8684F22068.', '2026-09-12 18:24:40', NULL, 'A_ENVOYER'),
(51, 12, NULL, 1, 'AFFECTATION_LIVRAISON', 'WEB', 'Le livreur est parti pour la commande PSM-20260912-B2F61D8684F22068.', '2026-09-12 18:24:40', NULL, 'A_ENVOYER'),
(52, 12, NULL, 2, 'AFFECTATION_LIVRAISON', 'WEB', 'Le livreur est parti pour la commande PSM-20260912-B2F61D8684F22068.', '2026-09-12 18:24:40', NULL, 'A_ENVOYER'),
(53, 12, 8, NULL, 'CHANGEMENT_STATUT', 'WEB', 'Votre commande PSM-20260912-B2F61D8684F22068 a été livrée. Confirmez sa réception depuis le suivi de commande.', '2026-09-12 18:24:44', NULL, 'A_ENVOYER'),
(54, 12, NULL, 1, 'AFFECTATION_LIVRAISON', 'WEB', 'La livraison de la commande PSM-20260912-B2F61D8684F22068 est déclarée effectuée. En attente de confirmation du client.', '2026-09-12 18:24:44', NULL, 'A_ENVOYER'),
(55, 12, NULL, 2, 'AFFECTATION_LIVRAISON', 'WEB', 'La livraison de la commande PSM-20260912-B2F61D8684F22068 est déclarée effectuée. En attente de confirmation du client.', '2026-09-12 18:24:44', NULL, 'A_ENVOYER'),
(56, 12, NULL, 1, 'CHANGEMENT_STATUT', 'WEB', 'Le client a confirmé la réception de la commande PSM-20260912-B2F61D8684F22068.', '2026-09-12 18:25:13', NULL, 'A_ENVOYER'),
(57, 12, NULL, 2, 'CHANGEMENT_STATUT', 'WEB', 'Le client a confirmé la réception de la commande PSM-20260912-B2F61D8684F22068.', '2026-09-12 18:25:13', NULL, 'A_ENVOYER');

-- --------------------------------------------------------

--
-- Structure de la table `paiements`
--

CREATE TABLE `paiements` (
  `id_paiement` bigint UNSIGNED NOT NULL,
  `id_commande` bigint UNSIGNED NOT NULL,
  `mode_paiement` enum('MTN_MOMO','MOOV_MONEY','CELTIS_CASH','ESPECES') COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut_paiement` enum('EN_ATTENTE','REUSSI','ECHOUE','ANNULE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EN_ATTENTE',
  `montant` decimal(14,2) NOT NULL,
  `reference_transaction` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_initiation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_confirmation` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Déchargement des données de la table `paiements`
--

INSERT INTO `paiements` (`id_paiement`, `id_commande`, `mode_paiement`, `statut_paiement`, `montant`, `reference_transaction`, `date_initiation`, `date_confirmation`, `created_at`, `updated_at`) VALUES
(1, 1, 'ESPECES', 'REUSSI', 1700.00, 'ESP-20260831-000001', '2026-08-31 18:18:23', '2026-08-31 18:19:21', '2026-08-31 18:18:23', '2026-08-31 18:19:21'),
(2, 2, 'MTN_MOMO', 'REUSSI', 2100.00, 'DEMO-20260831-000002', '2026-08-31 18:46:25', '2026-08-31 18:46:40', '2026-08-31 18:46:25', '2026-08-31 18:46:40'),
(3, 3, 'MTN_MOMO', 'REUSSI', 5000.00, 'DEMO-20260831-000003', '2026-08-31 19:15:04', '2026-08-31 19:15:37', '2026-08-31 19:15:04', '2026-08-31 19:15:37'),
(4, 4, 'MTN_MOMO', 'REUSSI', 600.00, 'DEMO-20260831-000004', '2026-08-31 19:17:09', '2026-08-31 19:17:14', '2026-08-31 19:17:09', '2026-08-31 19:17:14'),
(5, 5, 'MTN_MOMO', 'REUSSI', 2200.00, 'DEMO-20260901-000005', '2026-09-01 15:46:20', '2026-09-01 15:46:25', '2026-09-01 15:46:20', '2026-09-01 15:46:25'),
(7, 7, 'MTN_MOMO', 'REUSSI', 3000.00, 'DEMO-20260901-000007', '2026-09-01 16:25:27', '2026-09-01 16:25:45', '2026-09-01 16:25:27', '2026-09-01 16:25:45'),
(8, 8, 'MTN_MOMO', 'REUSSI', 2500.00, 'DEMO-20260901-000008', '2026-09-01 16:43:16', '2026-09-01 16:43:20', '2026-09-01 16:43:16', '2026-09-01 16:43:20'),
(9, 11, 'MTN_MOMO', 'REUSSI', 4300.00, 'DEMO-20260901-000009', '2026-09-01 17:37:15', '2026-09-01 17:37:19', '2026-09-01 17:37:15', '2026-09-01 17:37:19'),
(10, 12, 'MTN_MOMO', 'REUSSI', 3000.00, 'DEMO-20260912-000010', '2026-09-12 17:27:31', '2026-09-12 17:27:37', '2026-09-12 17:27:31', '2026-09-12 17:27:37');

-- --------------------------------------------------------

--
-- Structure de la table `parametres_boutique`
--

CREATE TABLE `parametres_boutique` (
  `id_parametre` tinyint UNSIGNED NOT NULL,
  `nom_boutique` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `adresse_boutique` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone_boutique` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_boutique` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `horaires` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zones_livraison` text COLLATE utf8mb4_unicode_ci,
  `frais_livraison_defaut` decimal(14,2) NOT NULL DEFAULT '0.00',
  `est_livraison_active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Déchargement des données de la table `parametres_boutique`
--

INSERT INTO `parametres_boutique` (`id_parametre`, `nom_boutique`, `adresse_boutique`, `telephone_boutique`, `email_boutique`, `horaires`, `zones_livraison`, `frais_livraison_defaut`, `est_livraison_active`, `updated_at`) VALUES
(1, 'Poissonnerie Saint-Michel', 'Akpakpa, Cotonou', '0162183849', 'akmultiservices2018@gmail.com', 'Lun-Sam:08h-19h30', 'AKPAKPA-GANKPODO', 500.00, 1, '2026-08-31 18:25:47');

-- --------------------------------------------------------

--
-- Structure de la table `permissions`
--

CREATE TABLE `permissions` (
  `id_permission` smallint UNSIGNED NOT NULL,
  `code_permission` varchar(70) COLLATE utf8mb4_unicode_ci NOT NULL,
  `libelle` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `permissions`
--

INSERT INTO `permissions` (`id_permission`, `code_permission`, `libelle`, `description`) VALUES
(1, 'GERER_UTILISATEURS', 'Gérer les utilisateurs', 'Créer, modifier, désactiver les comptes internes.'),
(2, 'GERER_CATALOGUE', 'Gérer le catalogue', 'Gérer les catégories et les produits.'),
(3, 'GERER_STOCK', 'Gérer le stock', 'Enregistrer les entrées et ajustements de stock.'),
(4, 'TRAITER_COMMANDES', 'Traiter les commandes', 'Consulter et changer le statut des commandes.'),
(5, 'AFFECTER_LIVRAISONS', 'Affecter les livraisons', 'Affecter une commande traitée à un livreur.'),
(6, 'GERER_LIVRAISONS', 'Gérer les livraisons', 'Accepter, refuser et finaliser ses livraisons.'),
(7, 'CONSULTER_TABLEAU_BORD', 'Consulter le tableau de bord', 'Consulter recettes, ventes et historiques.'),
(8, 'GERER_CONFIGURATION', 'Gérer la configuration', 'Modifier les informations et services de la boutique.');

-- --------------------------------------------------------

--
-- Structure de la table `produits`
--

CREATE TABLE `produits` (
  `id_produit` bigint UNSIGNED NOT NULL,
  `id_categorie` bigint UNSIGNED NOT NULL,
  `libelle` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `prix_unitaire` decimal(12,2) NOT NULL,
  `unite_vente` enum('KG','CARTON','ALVEOLE','UNITE','PAQUET','AUTRE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantite_stock` decimal(14,3) NOT NULL DEFAULT '0.000',
  `seuil_alerte` decimal(14,3) NOT NULL DEFAULT '0.000',
  `photo_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Déchargement des données de la table `produits`
--

INSERT INTO `produits` (`id_produit`, `id_categorie`, `libelle`, `description`, `prix_unitaire`, `unite_vente`, `quantite_stock`, `seuil_alerte`, `photo_url`, `est_actif`, `created_at`, `updated_at`) VALUES
(1, 1, 'MTN', 'MTN est un poisson tres prisé et tres gouteux', 1700.00, 'KG', 17.000, 5.000, 'assets/uploads/produits/6d870e687f4d79bca9b53db7081a9c47.webp', 1, '2026-08-31 18:11:55', '2026-09-12 17:25:04'),
(2, 1, 'SALOMON', 'Poisson tres prisé par les revendeuses pour differents mets locaux', 1500.00, 'KG', 7.000, 5.000, 'assets/uploads/produits/070dd3c97d2e83481783404981c0562d.jpg', 1, '2026-08-31 18:13:29', '2026-09-12 17:24:50'),
(3, 2, 'AILERON', 'Viande tres prisée pour les grillades', 2800.00, 'KG', 18.000, 3.000, 'assets/uploads/produits/ad35681972a91b607262bd0bc7405fee.jpg', 1, '2026-08-31 18:14:13', '2026-09-12 17:24:24'),
(4, 3, 'OEUFS DE POULE', 'Oeufs de poule locaux', 100.00, 'UNITE', 46.000, 10.000, 'assets/uploads/produits/ba257c3fced0dc68bedd56a55dccd60f.jpg', 1, '2026-08-31 18:14:50', '2026-09-12 17:24:06'),
(5, 2, 'POULETS', 'Du bon poulet bien gros et de tres  bonne qualite', 2000.00, 'KG', 9.000, 4.000, 'assets/uploads/produits/787435fa7c036cc6ca505d69cf193807.webp', 1, '2026-09-12 17:23:37', '2026-09-12 17:27:31');

-- --------------------------------------------------------

--
-- Structure de la table `quartiers_livraison`
--

CREATE TABLE `quartiers_livraison` (
  `id_quartier_livraison` bigint UNSIGNED NOT NULL,
  `id_zone_livraison` bigint UNSIGNED NOT NULL,
  `libelle` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `arrondissement` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `quartiers_livraison`
--

INSERT INTO `quartiers_livraison` (`id_quartier_livraison`, `id_zone_livraison`, `libelle`, `arrondissement`, `est_actif`, `created_at`, `updated_at`) VALUES
(1, 1, 'Avotrou-Aïmonlonfidé', '1er arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(2, 1, 'Avotrou-Gbégo', '1er arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(3, 1, 'Avotrou-Houézèkomè', '1er arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(4, 1, 'Dandji', '1er arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(5, 1, 'Dandji-Hokanmè', '1er arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(6, 1, 'Donatin', '1er arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(7, 1, 'Finagnon', '1er arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(8, 1, 'N’vènamèdé', '1er arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(9, 1, 'Suru-Léré', '1er arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(10, 1, 'Tanto', '1er arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(11, 1, 'Tchanhounkpamè', '1er arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(12, 1, 'Tokplégbé', '1er arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(13, 1, 'Yagbé', '1er arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(14, 1, 'Ahouassa', '2e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(15, 1, 'Djèdjè-Layé', '2e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(16, 1, 'Gankpodo', '2e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(17, 1, 'Irédé', '2e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(18, 1, 'Kowègbo', '2e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(19, 1, 'Kpondéhou', '2e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(20, 1, 'Kpondéhou Tchémè', '2e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(21, 1, 'Lom-Nava', '2e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(22, 1, 'Minontchou', '2e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(23, 1, 'Sènandé', '2e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(24, 1, 'Sènadé Sékou', '2e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(25, 1, 'Yénawa', '2e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(26, 1, 'Yénawa Daho', '2e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(27, 1, 'Adjégounlè', '3e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(28, 1, 'Adogléta', '3e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(29, 1, 'Agbato', '3e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(30, 1, 'Agbodjèdo', '3e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(31, 1, 'Ayélawadjè', '3e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(32, 1, 'Ayélawadjè Agongomè', '3e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(33, 1, 'Fifatin', '3e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(34, 1, 'Gbénonkpo', '3e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(35, 1, 'Hlacomey', '3e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(36, 1, 'Kpankpan', '3e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(37, 1, 'Midombo', '3e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(38, 1, 'Sègbèya Nord', '3e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(39, 1, 'Sègbèya Sud', '3e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(40, 1, 'Abokicodji Centre', '4e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(41, 1, 'Abokicodji Lagune', '4e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(42, 1, 'Akpakpa Dodomè', '4e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(43, 1, 'Dédokpo', '4e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(44, 1, 'Enagnon', '4e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(45, 1, 'Fifadji Houto', '4e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(46, 1, 'Gbèdjèwin', '4e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(47, 1, 'Missessin', '4e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(48, 1, 'Ohe', '4e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(49, 1, 'Sodjèatinmè Centre', '4e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(50, 1, 'Sodjèatinmè Est', '4e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(51, 1, 'Sodjèatinmè Ouest', '4e arrondissement', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(52, 2, 'Aïbatin', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(53, 2, 'Aïdjèdo', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(54, 2, 'Agla', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(55, 2, 'Agontikon', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(56, 2, 'Ahouansori', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(57, 2, 'Ahouanlèko', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(58, 2, 'Avlékété-Jonquet', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(59, 2, 'Cadjèhoun', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(60, 2, 'Camp Guézo', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(61, 2, 'Casse-Auto', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(62, 2, 'Dantokpa', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(63, 2, 'Dégakon', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(64, 2, 'Djidjè', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(65, 2, 'Fidjrossè', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(66, 2, 'Fiyègnon', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(67, 2, 'Ganhi', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(68, 2, 'Ganhito', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(69, 2, 'Gbégamey', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(70, 2, 'Gbèdjromèdé', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(71, 2, 'Gbéto', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(72, 2, 'Guinkomey', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(73, 2, 'Haie Vive', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(74, 2, 'Houéyiho', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(75, 2, 'Jéricho', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(76, 2, 'Jonquet', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(77, 2, 'Ladji', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(78, 2, 'Les Cocotiers', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(79, 2, 'Mènontin', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(80, 2, 'Missebo', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(81, 2, 'Moulèrô', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(82, 2, 'Patte d’Oie', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(83, 2, 'Sainte-Cécile', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(84, 2, 'Sainte-Rita', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(85, 2, 'Saint-Jean', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(86, 2, 'Sikècodji', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(87, 2, 'Tokpa-Hoho', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(88, 2, 'Vèdoko', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(89, 2, 'Vossa', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(90, 2, 'Xwlacodji', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(91, 2, 'Zogbo', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(92, 2, 'Zongo', 'Cotonou', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(93, 3, 'Agamandin', 'Abomey-Calavi', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(94, 3, 'Agori', 'Abomey-Calavi', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(95, 3, 'Aîfa', 'Abomey-Calavi', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(96, 3, 'Aîtchédji', 'Abomey-Calavi', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(97, 3, 'Alédjo', 'Abomey-Calavi', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(98, 3, 'Cité la Victoire', 'Abomey-Calavi', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(99, 3, 'Cité les Palmiers', 'Abomey-Calavi', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(100, 3, 'Fandji', 'Abomey-Calavi', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(101, 3, 'Finafa', 'Abomey-Calavi', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(102, 3, 'Gbodjo', 'Abomey-Calavi', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(103, 3, 'Kansounkpa', 'Abomey-Calavi', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(104, 3, 'Sèmè', 'Abomey-Calavi', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(105, 3, 'Tankpê', 'Abomey-Calavi', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(106, 3, 'Tchinangbégbo', 'Abomey-Calavi', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(107, 3, 'Tokpa-Zoungo', 'Abomey-Calavi', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(108, 3, 'Tokpa-Zoungo Nord', 'Abomey-Calavi', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(109, 3, 'Tokpa-Zoungo Sud', 'Abomey-Calavi', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(110, 3, 'Zogbadjè', 'Abomey-Calavi', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(111, 3, 'Zopah', 'Abomey-Calavi', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(112, 3, 'Zoundja', 'Abomey-Calavi', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(113, 3, 'Adjagbo', 'Akassato', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(114, 3, 'Agassa-Godomey', 'Akassato', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(115, 3, 'Agonmé', 'Akassato', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(116, 3, 'Agonsoundja', 'Akassato', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(117, 3, 'Akassato-Centre', 'Akassato', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(118, 3, 'Gbétagbo', 'Akassato', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(119, 3, 'Glo-Tokpa', 'Akassato', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(120, 3, 'Houèkè-Gbo', 'Akassato', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(121, 3, 'Houèkè-Honou', 'Akassato', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(122, 3, 'Kolètin', 'Akassato', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(123, 3, 'Kpodji-les-Monts', 'Akassato', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(124, 3, 'Missessinto', 'Akassato', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(125, 3, 'Zekanmey-Domè', 'Akassato', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(126, 3, 'Zopah Palmeraie', 'Akassato', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(127, 3, 'Adjamè', 'Golo-Djigbé', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(128, 3, 'Agongbé', 'Golo-Djigbé', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(129, 3, 'Agonkessa', 'Golo-Djigbé', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(130, 3, 'Alladacomè', 'Golo-Djigbé', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(131, 3, 'Azonsa', 'Golo-Djigbé', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(132, 3, 'Djissoukpa', 'Golo-Djigbé', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(133, 3, 'Domey-Gbo', 'Golo-Djigbé', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(134, 3, 'Golo-Djigbé', 'Golo-Djigbé', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(135, 3, 'Golo-Fanto', 'Golo-Djigbé', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(136, 3, 'Lohoussa', 'Golo-Djigbé', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(137, 3, 'Missèbo-Espace Saint', 'Golo-Djigbé', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(138, 3, 'Yékon-Do', 'Golo-Djigbé', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(139, 3, 'Yékon-Aga', 'Golo-Djigbé', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(140, 3, 'Zèkanmey', 'Golo-Djigbé', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(141, 3, 'Abikouholi', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(142, 3, 'Agbo-Codji-Sèdégbé', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(143, 3, 'Agonkanmey', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(144, 3, 'Aïmevo', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(145, 3, 'Alègléta', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(146, 3, 'Amanhoun', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(147, 3, 'Assrossa', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(148, 3, 'Atrokpo-Codji', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(149, 3, 'Cococodji', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(150, 3, 'Cocotomey', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(151, 3, 'Dèkoungbé-Eglise', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(152, 3, 'Dèkoungbé-Usine', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(153, 3, 'Dénou', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(154, 3, 'Djèkpota', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(155, 3, 'Djoukpa-Togoudo', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(156, 3, 'Fignonhou', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(157, 3, 'Ganganzounmè', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(158, 3, 'Gbègnigan-Midokpo', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(159, 3, 'Gbodjè-Womey', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(160, 3, 'Gninkindji', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(161, 3, 'Godomey-N’Gbèho', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(162, 3, 'Godomey-Togoudo', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(163, 3, 'Hélouto', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(164, 3, 'Hèdomè', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(165, 3, 'Houakomey', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(166, 3, 'Hounsa-Agbodokpa', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(167, 3, 'La Paix', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(168, 3, 'Lobozounkpa', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(169, 3, 'Maria-Gléta', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(170, 3, 'Ningboto', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(171, 3, 'Nonhouénou', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(172, 3, 'Ounvènoumèdé', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(173, 3, 'Plateau', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(174, 3, 'Salamey', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(175, 3, 'Sèdjannanko', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(176, 3, 'Sèdomey', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(177, 3, 'Sèloli-Fandji', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(178, 3, 'Sodo', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(179, 3, 'Togbin-Daho', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(180, 3, 'Togbin-Fandji', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(181, 3, 'Togbin-Kpèvi', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(182, 3, 'Tokpa', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(183, 3, 'Womey Centre', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(184, 3, 'Yénandjro', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(185, 3, 'Yolomahouto', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(186, 3, 'Zounga', 'Godomey', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(187, 3, 'Adovié', 'Hêvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(188, 3, 'Ahossougbéta', 'Hêvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(189, 3, 'Akossavié', 'Hêvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(190, 3, 'Dossounou', 'Hêvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(191, 3, 'Hêvié Centre', 'Hêvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(192, 3, 'Houinmè', 'Hêvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(193, 3, 'Sabenou', 'Hêvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(194, 3, 'Sogan', 'Hêvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(195, 3, 'Zoungo', 'Hêvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(196, 3, 'Anagbo', 'Kpanroun', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(197, 3, 'Avagbé', 'Kpanroun', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(198, 3, 'Bozoun', 'Kpanroun', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(199, 3, 'Handjanahou', 'Kpanroun', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(200, 3, 'Kpanroun', 'Kpanroun', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(201, 3, 'Kpé', 'Kpanroun', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(202, 3, 'Kpaviédja', 'Kpanroun', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(203, 3, 'Adjagbo-Aïdjèdo', 'Ouèdo', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(204, 3, 'Ahouato', 'Ouèdo', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(205, 3, 'Alansankomè', 'Ouèdo', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(206, 3, 'Dassèkomey', 'Ouèdo', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(207, 3, 'Dessato', 'Ouèdo', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(208, 3, 'Kpossidja', 'Ouèdo', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(209, 3, 'Ouèdo Centre', 'Ouèdo', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(210, 3, 'Drabo', 'Togba', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(211, 3, 'Fifonsi', 'Togba', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(212, 3, 'Houèto', 'Togba', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(213, 3, 'Ouéga-Agué', 'Togba', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(214, 3, 'Ouéga-Tokpa', 'Togba', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(215, 3, 'Somè', 'Togba', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(216, 3, 'Togba', 'Togba', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(217, 3, 'Tokan', 'Togba', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(218, 3, 'Tokan Aîdégnon', 'Togba', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(219, 3, 'Adjogansa', 'Zinvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(220, 3, 'Dangbodji', 'Zinvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(221, 3, 'Dokomey', 'Zinvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(222, 3, 'Gbodjè', 'Zinvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(223, 3, 'Gbodjoko', 'Zinvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(224, 3, 'Houégoudo', 'Zinvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(225, 3, 'Kpotomey', 'Zinvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(226, 3, 'Sokan', 'Zinvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(227, 3, 'Wawata', 'Zinvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(228, 3, 'Wawata-Todja', 'Zinvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(229, 3, 'Yèvié', 'Zinvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(230, 3, 'Yèvié-Nougo', 'Zinvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(231, 3, 'Zinvié-Agolèdji', 'Zinvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(232, 3, 'Zinvié-Fandji', 'Zinvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(233, 3, 'Zinvié-Zounmè', 'Zinvié', 1, '2026-09-01 17:27:41', '2026-09-01 17:27:41'),
(256, 3, 'Fandji', 'Godomey', 1, '2026-09-01 17:28:27', '2026-09-01 17:28:27'),
(257, 3, 'Finafa', 'Godomey', 1, '2026-09-01 17:28:27', '2026-09-01 17:28:27'),
(258, 3, 'Tankpè', 'Godomey', 1, '2026-09-01 17:28:27', '2026-09-01 17:28:27'),
(259, 3, 'Adjagbo', 'Ouèdo', 1, '2026-09-01 17:28:27', '2026-09-01 17:28:27'),
(260, 3, 'Ahossougbéta', 'Togba', 1, '2026-09-01 17:28:27', '2026-09-01 17:28:27'),
(261, 3, 'Tankpê', 'Togba', 1, '2026-09-01 17:28:27', '2026-09-01 17:28:27'),
(262, 3, 'Maria-Gléta', 'Togba', 1, '2026-09-01 17:28:27', '2026-09-01 17:28:27');

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

CREATE TABLE `roles` (
  `id_role` tinyint UNSIGNED NOT NULL,
  `code_role` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `libelle` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id_role`, `code_role`, `libelle`, `description`, `est_actif`, `created_at`, `updated_at`) VALUES
(1, 'ADMINISTRATEUR', 'Administrateur', 'Pilotage global, paramétrage et gestion des comptes.', 1, '2026-08-31 17:41:06', '2026-08-31 17:41:06'),
(2, 'GERANT', 'Gérant', 'Traitement des commandes, stock et affectations.', 1, '2026-08-31 17:41:06', '2026-08-31 17:41:06'),
(3, 'LIVREUR', 'Livreur', 'Acceptation et exécution des livraisons affectées.', 1, '2026-08-31 17:41:06', '2026-08-31 17:41:06');

-- --------------------------------------------------------

--
-- Structure de la table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id_role` tinyint UNSIGNED NOT NULL,
  `id_permission` smallint UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `role_permissions`
--

INSERT INTO `role_permissions` (`id_role`, `id_permission`, `created_at`) VALUES
(1, 1, '2026-08-31 17:41:06'),
(1, 2, '2026-08-31 17:41:06'),
(1, 3, '2026-08-31 17:41:06'),
(1, 4, '2026-08-31 17:41:06'),
(1, 5, '2026-08-31 17:41:06'),
(1, 6, '2026-08-31 17:41:06'),
(1, 7, '2026-08-31 17:41:06'),
(1, 8, '2026-08-31 17:41:06'),
(2, 3, '2026-08-31 17:41:06'),
(2, 4, '2026-08-31 17:41:06'),
(2, 5, '2026-08-31 17:41:06'),
(2, 7, '2026-08-31 17:41:06'),
(3, 6, '2026-08-31 17:41:06');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id_utilisateur` bigint UNSIGNED NOT NULL,
  `id_role` tinyint UNSIGNED NOT NULL,
  `nom_complet` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mot_de_passe_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT '1',
  `dernier_acces` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id_utilisateur`, `id_role`, `nom_complet`, `email`, `telephone`, `mot_de_passe_hash`, `est_actif`, `dernier_acces`, `created_at`, `updated_at`) VALUES
(1, 1, 'AKAKPOSSE Michael', 'akmdejesus@icloud.com', '0197927434', '$2y$10$70PsMiKrj72eYFeg.FISXeHcbC84VWaJW4JNVydxwYnJEGcg2DpPa', 1, '2026-09-12 17:22:04', '2026-08-31 17:58:12', '2026-09-12 17:22:04'),
(2, 2, 'AHOCLOUNON Céline', 'akmultiservices2018@gmail.com', '0162183849', '$2y$10$Lx0QyiJEye1EUKtufNLqzujcPOLVxeZ1ScdmfdWxJHfmLVCvSCMMa', 1, '2026-09-01 16:43:45', '2026-08-31 18:08:39', '2026-09-01 16:43:45'),
(3, 3, 'AZIZ Mohamed', 'livreur@gmail.com', '0103040708', '$2y$10$Exdh/HQs7f6TWT2uyowqjedVwiMmfEtQW95o2zPRHfYZWftVXkTmG', 1, '2026-09-12 18:23:26', '2026-08-31 19:10:21', '2026-09-12 18:23:26');

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vue_produits_stock_bas`
-- (Voir ci-dessous la vue réelle)
--

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vue_recettes_journalieres`
-- (Voir ci-dessous la vue réelle)
--

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vue_ventes_par_produit`
-- (Voir ci-dessous la vue réelle)
--

-- --------------------------------------------------------

--
-- Structure de la table `zones_livraison`
--

CREATE TABLE `zones_livraison` (
  `id_zone_livraison` bigint UNSIGNED NOT NULL,
  `libelle` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frais_livraison` decimal(14,2) NOT NULL,
  `est_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Déchargement des données de la table `zones_livraison`
--

INSERT INTO `zones_livraison` (`id_zone_livraison`, `libelle`, `description`, `frais_livraison`, `est_active`, `created_at`, `updated_at`) VALUES
(1, 'Akpakpa', 'Zone d\'Akpakpa', 500.00, 1, '2026-09-01 16:12:37', '2026-09-01 16:12:37'),
(2, 'Cotonou', 'Autres quartiers de la ville de Cotonou', 1000.00, 1, '2026-09-01 16:12:37', '2026-09-01 16:12:37'),
(3, 'Calavi', 'Zone de Calavi', 1500.00, 1, '2026-09-01 16:12:37', '2026-09-01 16:12:37');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id_categorie`),
  ADD UNIQUE KEY `uq_categories_libelle` (`libelle`);

--
-- Index pour la table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id_client`),
  ADD UNIQUE KEY `uq_clients_telephone` (`telephone`),
  ADD KEY `idx_clients_nom` (`nom_complet`);

--
-- Index pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id_commande`),
  ADD UNIQUE KEY `uq_commandes_numero` (`numero_commande`),
  ADD KEY `idx_commandes_client_date` (`id_client`,`date_commande`),
  ADD KEY `idx_commandes_origine_date` (`origine_commande`,`date_commande`),
  ADD KEY `idx_commandes_statut_date` (`statut_courant`,`date_commande`),
  ADD KEY `idx_commandes_zone_livraison` (`id_zone_livraison`),
  ADD KEY `idx_commandes_quartier_livraison` (`id_quartier_livraison`);

--
-- Index pour la table `historique_statuts_commande`
--
ALTER TABLE `historique_statuts_commande`
  ADD PRIMARY KEY (`id_historique_statut`),
  ADD KEY `idx_historique_commande_date` (`id_commande`,`date_changement`),
  ADD KEY `idx_historique_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `journal_audit`
--
ALTER TABLE `journal_audit`
  ADD PRIMARY KEY (`id_evenement`),
  ADD KEY `idx_audit_cible` (`cible_type`,`cible_id`,`date_evenement`),
  ADD KEY `idx_audit_utilisateur_date` (`id_utilisateur`,`date_evenement`);

--
-- Index pour la table `justificatifs_paiement`
--
ALTER TABLE `justificatifs_paiement`
  ADD PRIMARY KEY (`id_justificatif`),
  ADD UNIQUE KEY `uq_justificatifs_paiement` (`id_paiement`),
  ADD UNIQUE KEY `uq_justificatifs_numero` (`numero_document`);

--
-- Index pour la table `lignes_commande`
--
ALTER TABLE `lignes_commande`
  ADD PRIMARY KEY (`id_ligne_commande`),
  ADD UNIQUE KEY `uq_lignes_commande_produit` (`id_commande`,`id_produit`),
  ADD KEY `idx_lignes_commande_produit` (`id_produit`);

--
-- Index pour la table `limites_securite`
--
ALTER TABLE `limites_securite`
  ADD PRIMARY KEY (`cle_rate_limite`),
  ADD KEY `idx_limites_securite_expiration` (`derniere_tentative`);

--
-- Index pour la table `livraisons`
--
ALTER TABLE `livraisons`
  ADD PRIMARY KEY (`id_livraison`),
  ADD KEY `idx_livraisons_commande` (`id_commande`),
  ADD KEY `idx_livraisons_livreur_statut` (`id_livreur`,`statut_livraison`),
  ADD KEY `idx_livraisons_affectant` (`id_affectant`);

--
-- Index pour la table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  ADD PRIMARY KEY (`id_mouvement_stock`),
  ADD KEY `idx_mouvements_stock_produit_date` (`id_produit`,`date_mouvement`),
  ADD KEY `idx_mouvements_stock_ligne` (`id_ligne_commande`),
  ADD KEY `idx_mouvements_stock_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id_notification`),
  ADD KEY `idx_notifications_commande` (`id_commande`),
  ADD KEY `idx_notifications_client` (`id_client`,`statut_envoi`),
  ADD KEY `idx_notifications_utilisateur` (`id_utilisateur`,`statut_envoi`);

--
-- Index pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD PRIMARY KEY (`id_paiement`),
  ADD UNIQUE KEY `uq_paiements_commande` (`id_commande`),
  ADD UNIQUE KEY `uq_paiements_reference` (`reference_transaction`),
  ADD KEY `idx_paiements_statut_date` (`statut_paiement`,`date_confirmation`);

--
-- Index pour la table `parametres_boutique`
--
ALTER TABLE `parametres_boutique`
  ADD PRIMARY KEY (`id_parametre`);

--
-- Index pour la table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id_permission`),
  ADD UNIQUE KEY `uq_permissions_code` (`code_permission`);

--
-- Index pour la table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`id_produit`),
  ADD UNIQUE KEY `uq_produits_categorie_libelle` (`id_categorie`,`libelle`),
  ADD KEY `idx_produits_catalogue` (`id_categorie`,`est_actif`,`libelle`),
  ADD KEY `idx_produits_stock` (`quantite_stock`,`seuil_alerte`);

--
-- Index pour la table `quartiers_livraison`
--
ALTER TABLE `quartiers_livraison`
  ADD PRIMARY KEY (`id_quartier_livraison`),
  ADD UNIQUE KEY `uq_quartiers_livraison_zone_arrondissement_libelle` (`id_zone_livraison`,`arrondissement`,`libelle`),
  ADD KEY `idx_quartiers_livraison_zone_actif` (`id_zone_livraison`,`est_actif`,`libelle`);

--
-- Index pour la table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_role`),
  ADD UNIQUE KEY `uq_roles_code` (`code_role`);

--
-- Index pour la table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id_role`,`id_permission`),
  ADD KEY `fk_role_permissions_permission` (`id_permission`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id_utilisateur`),
  ADD UNIQUE KEY `uq_utilisateurs_telephone` (`telephone`),
  ADD UNIQUE KEY `uq_utilisateurs_email` (`email`),
  ADD KEY `idx_utilisateurs_role_actif` (`id_role`,`est_actif`);

--
-- Index pour la table `zones_livraison`
--
ALTER TABLE `zones_livraison`
  ADD PRIMARY KEY (`id_zone_livraison`),
  ADD UNIQUE KEY `uq_zones_livraison_libelle` (`libelle`),
  ADD KEY `idx_zones_livraison_actives` (`est_active`,`libelle`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id_categorie` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `clients`
--
ALTER TABLE `clients`
  MODIFY `id_client` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id_commande` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `historique_statuts_commande`
--
ALTER TABLE `historique_statuts_commande`
  MODIFY `id_historique_statut` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT pour la table `journal_audit`
--
ALTER TABLE `journal_audit`
  MODIFY `id_evenement` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT pour la table `justificatifs_paiement`
--
ALTER TABLE `justificatifs_paiement`
  MODIFY `id_justificatif` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `lignes_commande`
--
ALTER TABLE `lignes_commande`
  MODIFY `id_ligne_commande` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `livraisons`
--
ALTER TABLE `livraisons`
  MODIFY `id_livraison` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  MODIFY `id_mouvement_stock` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id_notification` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT pour la table `paiements`
--
ALTER TABLE `paiements`
  MODIFY `id_paiement` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id_permission` smallint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `produits`
--
ALTER TABLE `produits`
  MODIFY `id_produit` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `quartiers_livraison`
--
ALTER TABLE `quartiers_livraison`
  MODIFY `id_quartier_livraison` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=264;

--
-- AUTO_INCREMENT pour la table `roles`
--
ALTER TABLE `roles`
  MODIFY `id_role` tinyint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id_utilisateur` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `zones_livraison`
--
ALTER TABLE `zones_livraison`
  MODIFY `id_zone_livraison` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------


-- --------------------------------------------------------


-- --------------------------------------------------------


--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `fk_commandes_client` FOREIGN KEY (`id_client`) REFERENCES `clients` (`id_client`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_commandes_quartier_livraison` FOREIGN KEY (`id_quartier_livraison`) REFERENCES `quartiers_livraison` (`id_quartier_livraison`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_commandes_zone_livraison` FOREIGN KEY (`id_zone_livraison`) REFERENCES `zones_livraison` (`id_zone_livraison`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Contraintes pour la table `historique_statuts_commande`
--
ALTER TABLE `historique_statuts_commande`
  ADD CONSTRAINT `fk_historique_commande` FOREIGN KEY (`id_commande`) REFERENCES `commandes` (`id_commande`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_historique_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `journal_audit`
--
ALTER TABLE `journal_audit`
  ADD CONSTRAINT `fk_audit_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `justificatifs_paiement`
--
ALTER TABLE `justificatifs_paiement`
  ADD CONSTRAINT `fk_justificatifs_paiement` FOREIGN KEY (`id_paiement`) REFERENCES `paiements` (`id_paiement`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Contraintes pour la table `lignes_commande`
--
ALTER TABLE `lignes_commande`
  ADD CONSTRAINT `fk_lignes_commande_commande` FOREIGN KEY (`id_commande`) REFERENCES `commandes` (`id_commande`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lignes_commande_produit` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Contraintes pour la table `livraisons`
--
ALTER TABLE `livraisons`
  ADD CONSTRAINT `fk_livraisons_affectant` FOREIGN KEY (`id_affectant`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_livraisons_commande` FOREIGN KEY (`id_commande`) REFERENCES `commandes` (`id_commande`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_livraisons_livreur` FOREIGN KEY (`id_livreur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Contraintes pour la table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  ADD CONSTRAINT `fk_mouvements_stock_ligne` FOREIGN KEY (`id_ligne_commande`) REFERENCES `lignes_commande` (`id_ligne_commande`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mouvements_stock_produit` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mouvements_stock_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_client` FOREIGN KEY (`id_client`) REFERENCES `clients` (`id_client`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notifications_commande` FOREIGN KEY (`id_commande`) REFERENCES `commandes` (`id_commande`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notifications_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD CONSTRAINT `fk_paiements_commande` FOREIGN KEY (`id_commande`) REFERENCES `commandes` (`id_commande`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Contraintes pour la table `produits`
--
ALTER TABLE `produits`
  ADD CONSTRAINT `fk_produits_categorie` FOREIGN KEY (`id_categorie`) REFERENCES `categories` (`id_categorie`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Contraintes pour la table `quartiers_livraison`
--
ALTER TABLE `quartiers_livraison`
  ADD CONSTRAINT `fk_quartiers_livraison_zone` FOREIGN KEY (`id_zone_livraison`) REFERENCES `zones_livraison` (`id_zone_livraison`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Contraintes pour la table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`id_permission`) REFERENCES `permissions` (`id_permission`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`id_role`) REFERENCES `roles` (`id_role`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Contraintes pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD CONSTRAINT `fk_utilisateurs_role` FOREIGN KEY (`id_role`) REFERENCES `roles` (`id_role`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

