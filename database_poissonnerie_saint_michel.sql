-- ============================================================================
-- POISSONNERIE SAINT-MICHEL — Base de données MySQL
-- Version : 1.0 | Compatible MySQL 8.0.16+ (MAMP / phpMyAdmin)
-- Source  : MCD de la plateforme de commande et gestion de poissonnerie
--
-- Ce script ne supprime aucune donnée existante.
-- Pour une installation neuve : importer ce fichier dans phpMyAdmin.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS poissonnerie_saint_michel
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE poissonnerie_saint_michel;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- 1. HABILITATIONS : ROLE, PERMISSION, UTILISATEUR
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS roles (
  id_role TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code_role VARCHAR(40) NOT NULL,
  libelle VARCHAR(80) NOT NULL,
  description VARCHAR(255) NULL,
  est_actif BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_role),
  UNIQUE KEY uq_roles_code (code_role)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permissions (
  id_permission SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code_permission VARCHAR(70) NOT NULL,
  libelle VARCHAR(120) NOT NULL,
  description VARCHAR(255) NULL,
  PRIMARY KEY (id_permission),
  UNIQUE KEY uq_permissions_code (code_permission)
) ENGINE=InnoDB;

-- Table d'association A15 : un rôle peut avoir plusieurs permissions et
-- une permission peut être accordée à plusieurs rôles.
CREATE TABLE IF NOT EXISTS role_permissions (
  id_role TINYINT UNSIGNED NOT NULL,
  id_permission SMALLINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_role, id_permission),
  CONSTRAINT fk_role_permissions_role
    FOREIGN KEY (id_role) REFERENCES roles (id_role)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_role_permissions_permission
    FOREIGN KEY (id_permission) REFERENCES permissions (id_permission)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS utilisateurs (
  id_utilisateur BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_role TINYINT UNSIGNED NOT NULL,
  nom_complet VARCHAR(150) NOT NULL,
  email VARCHAR(191) NULL,
  telephone VARCHAR(30) NOT NULL,
  mot_de_passe_hash VARCHAR(255) NOT NULL,
  est_actif BOOLEAN NOT NULL DEFAULT TRUE,
  dernier_acces DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_utilisateur),
  UNIQUE KEY uq_utilisateurs_email (email),
  UNIQUE KEY uq_utilisateurs_telephone (telephone),
  KEY idx_utilisateurs_role_actif (id_role, est_actif),
  CONSTRAINT fk_utilisateurs_role
    FOREIGN KEY (id_role) REFERENCES roles (id_role)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT ck_utilisateurs_contact
    CHECK (email IS NOT NULL OR telephone IS NOT NULL)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- 2. CATALOGUE : CATEGORIE, PRODUIT
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS categories (
  id_categorie BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  libelle VARCHAR(100) NOT NULL,
  description TEXT NULL,
  est_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_categorie),
  UNIQUE KEY uq_categories_libelle (libelle)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS produits (
  id_produit BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_categorie BIGINT UNSIGNED NOT NULL,
  libelle VARCHAR(150) NOT NULL,
  description TEXT NULL,
  prix_unitaire DECIMAL(12,2) NOT NULL,
  unite_vente ENUM('KG', 'CARTON', 'ALVEOLE', 'UNITE', 'PAQUET', 'AUTRE') NOT NULL,
  quantite_stock DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  seuil_alerte DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  photo_url VARCHAR(500) NULL,
  est_actif BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_produit),
  UNIQUE KEY uq_produits_categorie_libelle (id_categorie, libelle),
  KEY idx_produits_catalogue (id_categorie, est_actif, libelle),
  KEY idx_produits_stock (quantite_stock, seuil_alerte),
  CONSTRAINT fk_produits_categorie
    FOREIGN KEY (id_categorie) REFERENCES categories (id_categorie)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT ck_produits_prix CHECK (prix_unitaire >= 0),
  CONSTRAINT ck_produits_stock CHECK (quantite_stock >= 0),
  CONSTRAINT ck_produits_seuil CHECK (seuil_alerte >= 0)
) ENGINE=InnoDB;

-- Les zones sont paramétrables par l'administrateur. Le coût est repris dans
-- chaque commande afin de conserver le montant accepté par le client.
CREATE TABLE IF NOT EXISTS zones_livraison (
  id_zone_livraison BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  libelle VARCHAR(100) NOT NULL,
  description VARCHAR(500) NULL,
  frais_livraison DECIMAL(14,2) NOT NULL,
  est_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_zone_livraison),
  UNIQUE KEY uq_zones_livraison_libelle (libelle),
  KEY idx_zones_livraison_actives (est_active, libelle),
  CONSTRAINT ck_zones_livraison_frais CHECK (frais_livraison >= 0)
) ENGINE=InnoDB;

-- Les quartiers sont liés à une seule zone ; ils restent configurables par l'administrateur.
CREATE TABLE IF NOT EXISTS quartiers_livraison (
  id_quartier_livraison BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_zone_livraison BIGINT UNSIGNED NOT NULL,
  libelle VARCHAR(150) NOT NULL,
  arrondissement VARCHAR(120) NULL,
  est_actif BOOLEAN NOT NULL DEFAULT TRUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_quartier_livraison),
  UNIQUE KEY uq_quartiers_livraison_zone_arrondissement_libelle (id_zone_livraison, arrondissement, libelle),
  KEY idx_quartiers_livraison_zone_actif (id_zone_livraison, est_actif, libelle),
  CONSTRAINT fk_quartiers_livraison_zone
    FOREIGN KEY (id_zone_livraison) REFERENCES zones_livraison (id_zone_livraison)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- 3. CLIENTS ET COMMANDES
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS clients (
  id_client BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom_complet VARCHAR(150) NOT NULL,
  telephone VARCHAR(30) NOT NULL,
  email VARCHAR(191) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_client),
  UNIQUE KEY uq_clients_telephone (telephone),
  KEY idx_clients_nom (nom_complet)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS commandes (
  id_commande BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_client BIGINT UNSIGNED NOT NULL,
  numero_commande VARCHAR(40) NOT NULL,
  date_commande DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  origine_commande ENUM('EN_LIGNE', 'COMPTOIR') NOT NULL DEFAULT 'EN_LIGNE',
  statut_courant ENUM(
    'EN_ATTENTE',
    'EN_COURS_TRAITEMENT',
    'TRAITEE',
    'EN_COURS_LIVRAISON',
    'LIVREE'
  ) NOT NULL DEFAULT 'EN_ATTENTE',
  mode_retrait ENUM('LIVRAISON', 'RETRAIT_BOUTIQUE') NOT NULL,
  id_zone_livraison BIGINT UNSIGNED NULL,
  id_quartier_livraison BIGINT UNSIGNED NULL,
  adresse_livraison VARCHAR(500) NULL,
  total_produits DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  frais_livraison DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  montant_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  note_client TEXT NULL,
  confirmation_reception BOOLEAN NOT NULL DEFAULT FALSE,
  date_confirmation_reception DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_commande),
  UNIQUE KEY uq_commandes_numero (numero_commande),
  KEY idx_commandes_client_date (id_client, date_commande),
  KEY idx_commandes_origine_date (origine_commande, date_commande),
  KEY idx_commandes_statut_date (statut_courant, date_commande),
  KEY idx_commandes_zone_livraison (id_zone_livraison),
  KEY idx_commandes_quartier_livraison (id_quartier_livraison),
  CONSTRAINT fk_commandes_client
    FOREIGN KEY (id_client) REFERENCES clients (id_client)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_commandes_zone_livraison
    FOREIGN KEY (id_zone_livraison) REFERENCES zones_livraison (id_zone_livraison)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_commandes_quartier_livraison
    FOREIGN KEY (id_quartier_livraison) REFERENCES quartiers_livraison (id_quartier_livraison)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT ck_commandes_totaux CHECK (
    total_produits >= 0
    AND frais_livraison >= 0
    AND montant_total >= 0
  ),
  CONSTRAINT ck_commandes_reception CHECK (
    (confirmation_reception = FALSE AND date_confirmation_reception IS NULL)
    OR (confirmation_reception = TRUE AND date_confirmation_reception IS NOT NULL)
  )
) ENGINE=InnoDB;

-- A3 + A4 : une ligne appartient à une commande et référence un produit.
-- Le sous-total est calculé automatiquement afin de figer le détail de vente.
CREATE TABLE IF NOT EXISTS lignes_commande (
  id_ligne_commande BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_commande BIGINT UNSIGNED NOT NULL,
  id_produit BIGINT UNSIGNED NOT NULL,
  quantite DECIMAL(14,3) NOT NULL,
  prix_unitaire_applique DECIMAL(12,2) NOT NULL,
  sous_total DECIMAL(14,2)
    GENERATED ALWAYS AS (ROUND(quantite * prix_unitaire_applique, 2)) STORED,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_ligne_commande),
  UNIQUE KEY uq_lignes_commande_produit (id_commande, id_produit),
  KEY idx_lignes_commande_produit (id_produit),
  CONSTRAINT fk_lignes_commande_commande
    FOREIGN KEY (id_commande) REFERENCES commandes (id_commande)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_lignes_commande_produit
    FOREIGN KEY (id_produit) REFERENCES produits (id_produit)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT ck_lignes_commande_quantite CHECK (quantite > 0),
  CONSTRAINT ck_lignes_commande_prix CHECK (prix_unitaire_applique >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- 4. PAIEMENT ET FACTURATION
-- ---------------------------------------------------------------------------

-- A5 : relation 1,1 entre COMMANDE et PAIEMENT grâce à uq_paiements_commande.
CREATE TABLE IF NOT EXISTS paiements (
  id_paiement BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_commande BIGINT UNSIGNED NOT NULL,
  mode_paiement ENUM('MTN_MOMO', 'MOOV_MONEY', 'CELTIS_CASH', 'ESPECES') NOT NULL,
  statut_paiement ENUM('EN_ATTENTE', 'REUSSI', 'ECHOUE', 'ANNULE') NOT NULL DEFAULT 'EN_ATTENTE',
  montant DECIMAL(14,2) NOT NULL,
  reference_transaction VARCHAR(120) NULL,
  date_initiation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_confirmation DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_paiement),
  UNIQUE KEY uq_paiements_commande (id_commande),
  UNIQUE KEY uq_paiements_reference (reference_transaction),
  KEY idx_paiements_statut_date (statut_paiement, date_confirmation),
  CONSTRAINT fk_paiements_commande
    FOREIGN KEY (id_commande) REFERENCES commandes (id_commande)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT ck_paiements_montant CHECK (montant >= 0),
  CONSTRAINT ck_paiements_confirmation CHECK (
    (statut_paiement = 'REUSSI' AND date_confirmation IS NOT NULL)
    OR (statut_paiement <> 'REUSSI')
  )
) ENGINE=InnoDB;

-- A6 : un paiement peut ne pas encore avoir de document ; un justificatif
-- appartient obligatoirement à un seul paiement.
CREATE TABLE IF NOT EXISTS justificatifs_paiement (
  id_justificatif BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_paiement BIGINT UNSIGNED NOT NULL,
  type_document ENUM('FACTURE', 'RECU') NOT NULL,
  numero_document VARCHAR(60) NOT NULL,
  date_emission DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  chemin_document VARCHAR(500) NOT NULL,
  PRIMARY KEY (id_justificatif),
  UNIQUE KEY uq_justificatifs_paiement (id_paiement),
  UNIQUE KEY uq_justificatifs_numero (numero_document),
  CONSTRAINT fk_justificatifs_paiement
    FOREIGN KEY (id_paiement) REFERENCES paiements (id_paiement)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- 5. STOCK, SUIVI DES STATUTS ET LIVRAISONS
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS historique_statuts_commande (
  id_historique_statut BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_commande BIGINT UNSIGNED NOT NULL,
  id_utilisateur BIGINT UNSIGNED NULL,
  ancien_statut ENUM(
    'EN_ATTENTE',
    'EN_COURS_TRAITEMENT',
    'TRAITEE',
    'EN_COURS_LIVRAISON',
    'LIVREE'
  ) NULL,
  nouveau_statut ENUM(
    'EN_ATTENTE',
    'EN_COURS_TRAITEMENT',
    'TRAITEE',
    'EN_COURS_LIVRAISON',
    'LIVREE'
  ) NOT NULL,
  origine ENUM('SYSTEME', 'CLIENT', 'GERANT', 'LIVREUR') NOT NULL,
  commentaire VARCHAR(500) NULL,
  date_changement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_historique_statut),
  KEY idx_historique_commande_date (id_commande, date_changement),
  KEY idx_historique_utilisateur (id_utilisateur),
  CONSTRAINT fk_historique_commande
    FOREIGN KEY (id_commande) REFERENCES commandes (id_commande)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_historique_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id_utilisateur)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS mouvements_stock (
  id_mouvement_stock BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_produit BIGINT UNSIGNED NOT NULL,
  id_ligne_commande BIGINT UNSIGNED NULL,
  id_utilisateur BIGINT UNSIGNED NULL,
  type_mouvement ENUM(
    'ENTREE',
    'SORTIE',
    'AJUSTEMENT_POSITIF',
    'AJUSTEMENT_NEGATIF'
  ) NOT NULL,
  quantite DECIMAL(14,3) NOT NULL,
  stock_avant DECIMAL(14,3) NOT NULL,
  stock_apres DECIMAL(14,3) NOT NULL,
  date_mouvement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  motif VARCHAR(255) NOT NULL,
  reference_source VARCHAR(120) NULL,
  PRIMARY KEY (id_mouvement_stock),
  KEY idx_mouvements_stock_produit_date (id_produit, date_mouvement),
  KEY idx_mouvements_stock_ligne (id_ligne_commande),
  KEY idx_mouvements_stock_utilisateur (id_utilisateur),
  CONSTRAINT fk_mouvements_stock_produit
    FOREIGN KEY (id_produit) REFERENCES produits (id_produit)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_mouvements_stock_ligne
    FOREIGN KEY (id_ligne_commande) REFERENCES lignes_commande (id_ligne_commande)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_mouvements_stock_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id_utilisateur)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT ck_mouvements_stock_quantite CHECK (quantite > 0),
  CONSTRAINT ck_mouvements_stock_solde CHECK (stock_avant >= 0 AND stock_apres >= 0)
) ENGINE=InnoDB;

-- A7, A8 et A9 : une commande en livraison peut avoir plusieurs tentatives,
-- chacune affectée à un livreur par un gérant ou administrateur.
CREATE TABLE IF NOT EXISTS livraisons (
  id_livraison BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_commande BIGINT UNSIGNED NOT NULL,
  id_livreur BIGINT UNSIGNED NOT NULL,
  id_affectant BIGINT UNSIGNED NOT NULL,
  adresse_livraison VARCHAR(500) NOT NULL,
  contact_livraison VARCHAR(30) NOT NULL,
  statut_livraison ENUM('AFFECTEE', 'ACCEPTEE', 'REFUSEE', 'EN_COURS', 'LIVREE', 'ANNULEE') NOT NULL DEFAULT 'AFFECTEE',
  date_affectation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_acceptation DATETIME NULL,
  date_depart DATETIME NULL,
  date_livraison DATETIME NULL,
  motif_refus_annulation VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_livraison),
  KEY idx_livraisons_commande (id_commande),
  KEY idx_livraisons_livreur_statut (id_livreur, statut_livraison),
  KEY idx_livraisons_affectant (id_affectant),
  CONSTRAINT fk_livraisons_commande
    FOREIGN KEY (id_commande) REFERENCES commandes (id_commande)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_livraisons_livreur
    FOREIGN KEY (id_livreur) REFERENCES utilisateurs (id_utilisateur)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_livraisons_affectant
    FOREIGN KEY (id_affectant) REFERENCES utilisateurs (id_utilisateur)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT ck_livraisons_dates CHECK (
    (date_acceptation IS NULL OR date_acceptation >= date_affectation)
    AND (date_depart IS NULL OR date_acceptation IS NOT NULL)
    AND (date_livraison IS NULL OR date_depart IS NOT NULL)
  )
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- 6. NOTIFICATIONS, PARAMETRAGE ET JOURNAL D'AUDIT
-- ---------------------------------------------------------------------------

-- A16, A17 et A18. La règle XOR (un seul destinataire : client OU utilisateur)
-- est contrôlée côté PHP pour conserver la compatibilité avec les versions
-- anciennes de MySQL/MariaDB livrées avec certains MAMP.
CREATE TABLE IF NOT EXISTS notifications (
  id_notification BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_commande BIGINT UNSIGNED NOT NULL,
  id_client BIGINT UNSIGNED NULL,
  id_utilisateur BIGINT UNSIGNED NULL,
  type_notification ENUM('NOUVELLE_COMMANDE', 'CHANGEMENT_STATUT', 'AFFECTATION_LIVRAISON', 'PAIEMENT') NOT NULL,
  canal ENUM('WEB', 'EMAIL', 'SMS') NOT NULL DEFAULT 'WEB',
  message TEXT NOT NULL,
  date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_lecture DATETIME NULL,
  statut_envoi ENUM('A_ENVOYER', 'ENVOYEE', 'ECHEC', 'LUE') NOT NULL DEFAULT 'A_ENVOYER',
  PRIMARY KEY (id_notification),
  KEY idx_notifications_commande (id_commande),
  KEY idx_notifications_client (id_client, statut_envoi),
  KEY idx_notifications_utilisateur (id_utilisateur, statut_envoi),
  CONSTRAINT fk_notifications_commande
    FOREIGN KEY (id_commande) REFERENCES commandes (id_commande)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_notifications_client
    FOREIGN KEY (id_client) REFERENCES clients (id_client)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_notifications_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id_utilisateur)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- La plateforme n'a qu'un paramétrage actif : id_parametre = 1.
CREATE TABLE IF NOT EXISTS parametres_boutique (
  id_parametre TINYINT UNSIGNED NOT NULL,
  nom_boutique VARCHAR(150) NOT NULL,
  adresse_boutique VARCHAR(500) NOT NULL,
  telephone_boutique VARCHAR(30) NULL,
  email_boutique VARCHAR(191) NULL,
  horaires VARCHAR(500) NULL,
  zones_livraison TEXT NULL,
  frais_livraison_defaut DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  est_livraison_active BOOLEAN NOT NULL DEFAULT TRUE,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_parametre),
  CONSTRAINT ck_parametres_singleton CHECK (id_parametre = 1),
  CONSTRAINT ck_parametres_frais CHECK (frais_livraison_defaut >= 0)
) ENGINE=InnoDB;

-- Les limites d'accès conservent une empreinte SHA-256, jamais l'adresse IP
-- ni l'identifiant saisi. Elles freinent les essais répétés de connexion,
-- de suivi de commande et de renvoi de facture.
CREATE TABLE IF NOT EXISTS limites_securite (
  cle_rate_limite CHAR(64) NOT NULL,
  type_limite VARCHAR(60) NOT NULL,
  debut_fenetre DATETIME NOT NULL,
  tentatives SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  bloque_jusqua DATETIME NULL,
  derniere_tentative DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (cle_rate_limite),
  KEY idx_limites_securite_expiration (derniere_tentative),
  CONSTRAINT ck_limites_securite_tentatives CHECK (tentatives >= 0)
) ENGINE=InnoDB;

-- A20 : cible_type et cible_id forment une référence polymorphe vers l'objet
-- audité. Une clé étrangère unique n'est pas possible car plusieurs tables
-- peuvent être auditées ; l'intégrité est contrôlée par l'application.
CREATE TABLE IF NOT EXISTS journal_audit (
  id_evenement BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_utilisateur BIGINT UNSIGNED NULL,
  type_evenement VARCHAR(100) NOT NULL,
  date_evenement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cible_type ENUM('COMMANDE', 'PAIEMENT', 'PRODUIT', 'LIVRAISON', 'STOCK', 'PARAMETRAGE', 'UTILISATEUR') NOT NULL,
  cible_id BIGINT UNSIGNED NOT NULL,
  donnees_avant JSON NULL,
  donnees_apres JSON NULL,
  adresse_ip VARCHAR(45) NULL,
  PRIMARY KEY (id_evenement),
  KEY idx_audit_cible (cible_type, cible_id, date_evenement),
  KEY idx_audit_utilisateur_date (id_utilisateur, date_evenement),
  CONSTRAINT fk_audit_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id_utilisateur)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- 7. DECLENCHEURS DE COHERENCE
-- ---------------------------------------------------------------------------

DELIMITER $$

-- Le script peut être réimporté : ces objets de cohérence sont recréés sans
-- toucher aux tables ni aux données métier.
DROP TRIGGER IF EXISTS ai_commandes_historique_initial$$
DROP TRIGGER IF EXISTS ai_lignes_commande_recalculer_total$$
DROP TRIGGER IF EXISTS au_lignes_commande_recalculer_total$$
DROP TRIGGER IF EXISTS ad_lignes_commande_recalculer_total$$
DROP TRIGGER IF EXISTS bi_mouvements_stock_calculer_solde$$
DROP TRIGGER IF EXISTS ai_mouvements_stock_synchroniser_produit$$
DROP TRIGGER IF EXISTS bi_livraisons_controles$$
DROP TRIGGER IF EXISTS bi_justificatifs_controles$$
DROP PROCEDURE IF EXISTS sp_changer_statut_commande$$

-- Conserve systématiquement le premier événement de statut d'une commande.
CREATE TRIGGER ai_commandes_historique_initial
AFTER INSERT ON commandes
FOR EACH ROW
BEGIN
  INSERT INTO historique_statuts_commande (
    id_commande, id_utilisateur, ancien_statut, nouveau_statut, origine, commentaire, date_changement
  ) VALUES (
    NEW.id_commande, NULL, NULL, NEW.statut_courant, 'SYSTEME', 'Création de la commande', NEW.date_commande
  );
END$$

-- Maintient les totaux de la commande dès qu'une ligne est créée, modifiée ou supprimée.
CREATE TRIGGER ai_lignes_commande_recalculer_total
AFTER INSERT ON lignes_commande
FOR EACH ROW
BEGIN
  UPDATE commandes
     SET total_produits = (
           SELECT COALESCE(SUM(sous_total), 0.00)
             FROM lignes_commande
            WHERE id_commande = NEW.id_commande
         ),
         montant_total = (
           SELECT COALESCE(SUM(sous_total), 0.00)
             FROM lignes_commande
            WHERE id_commande = NEW.id_commande
         ) + frais_livraison
   WHERE id_commande = NEW.id_commande;
END$$

CREATE TRIGGER au_lignes_commande_recalculer_total
AFTER UPDATE ON lignes_commande
FOR EACH ROW
BEGIN
  UPDATE commandes
     SET total_produits = (
           SELECT COALESCE(SUM(sous_total), 0.00)
             FROM lignes_commande
            WHERE id_commande = NEW.id_commande
         ),
         montant_total = (
           SELECT COALESCE(SUM(sous_total), 0.00)
             FROM lignes_commande
            WHERE id_commande = NEW.id_commande
         ) + frais_livraison
   WHERE id_commande = NEW.id_commande;

  IF OLD.id_commande <> NEW.id_commande THEN
    UPDATE commandes
       SET total_produits = (
             SELECT COALESCE(SUM(sous_total), 0.00)
               FROM lignes_commande
              WHERE id_commande = OLD.id_commande
           ),
           montant_total = (
             SELECT COALESCE(SUM(sous_total), 0.00)
               FROM lignes_commande
              WHERE id_commande = OLD.id_commande
           ) + frais_livraison
     WHERE id_commande = OLD.id_commande;
  END IF;
END$$

CREATE TRIGGER ad_lignes_commande_recalculer_total
AFTER DELETE ON lignes_commande
FOR EACH ROW
BEGIN
  UPDATE commandes
     SET total_produits = (
           SELECT COALESCE(SUM(sous_total), 0.00)
             FROM lignes_commande
            WHERE id_commande = OLD.id_commande
         ),
         montant_total = (
           SELECT COALESCE(SUM(sous_total), 0.00)
             FROM lignes_commande
            WHERE id_commande = OLD.id_commande
         ) + frais_livraison
   WHERE id_commande = OLD.id_commande;
END$$

-- Calcule les soldes avant/après et refuse les sorties qui rendraient le stock négatif.
-- Le stock courant de PRODUITS est ensuite synchronisé dans le trigger suivant.
CREATE TRIGGER bi_mouvements_stock_calculer_solde
BEFORE INSERT ON mouvements_stock
FOR EACH ROW
BEGIN
  DECLARE v_stock DECIMAL(14,3);

  SELECT quantite_stock
    INTO v_stock
    FROM produits
   WHERE id_produit = NEW.id_produit;

  IF v_stock IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Produit introuvable pour le mouvement de stock';
  END IF;

  SET NEW.stock_avant = v_stock;

  IF NEW.type_mouvement IN ('ENTREE', 'AJUSTEMENT_POSITIF') THEN
    SET NEW.stock_apres = v_stock + NEW.quantite;
  ELSE
    IF v_stock < NEW.quantite THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stock insuffisant pour cette sortie';
    END IF;
    SET NEW.stock_apres = v_stock - NEW.quantite;
  END IF;
END$$

CREATE TRIGGER ai_mouvements_stock_synchroniser_produit
AFTER INSERT ON mouvements_stock
FOR EACH ROW
BEGIN
  UPDATE produits
     SET quantite_stock = NEW.stock_apres
   WHERE id_produit = NEW.id_produit;
END$$

-- Vérifie que seule une commande à livrer reçoit une livraison et que les
-- personnes choisies ont les rôles métier attendus.
CREATE TRIGGER bi_livraisons_controles
BEFORE INSERT ON livraisons
FOR EACH ROW
BEGIN
  DECLARE v_mode_retrait VARCHAR(30);
  DECLARE v_role_livreur VARCHAR(40);
  DECLARE v_role_affectant VARCHAR(40);
  DECLARE v_livreur_actif BOOLEAN;
  DECLARE v_affectant_actif BOOLEAN;

  SELECT mode_retrait
    INTO v_mode_retrait
    FROM commandes
   WHERE id_commande = NEW.id_commande;

  IF v_mode_retrait <> 'LIVRAISON' THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Une livraison exige une commande en mode LIVRAISON';
  END IF;

  SELECT r.code_role, u.est_actif
    INTO v_role_livreur, v_livreur_actif
    FROM utilisateurs u
    JOIN roles r ON r.id_role = u.id_role
   WHERE u.id_utilisateur = NEW.id_livreur;

  IF v_role_livreur <> 'LIVREUR' OR v_livreur_actif = FALSE THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Le livreur doit être un utilisateur LIVREUR actif';
  END IF;

  SELECT r.code_role, u.est_actif
    INTO v_role_affectant, v_affectant_actif
    FROM utilisateurs u
    JOIN roles r ON r.id_role = u.id_role
   WHERE u.id_utilisateur = NEW.id_affectant;

  IF v_role_affectant NOT IN ('GERANT', 'ADMINISTRATEUR') OR v_affectant_actif = FALSE THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'L affectant doit être un GERANT ou ADMINISTRATEUR actif';
  END IF;
END$$

-- Émet uniquement un justificatif cohérent avec un paiement réussi.
CREATE TRIGGER bi_justificatifs_controles
BEFORE INSERT ON justificatifs_paiement
FOR EACH ROW
BEGIN
  DECLARE v_mode_paiement VARCHAR(30);
  DECLARE v_statut_paiement VARCHAR(30);

  SELECT mode_paiement, statut_paiement
    INTO v_mode_paiement, v_statut_paiement
    FROM paiements
   WHERE id_paiement = NEW.id_paiement;

  IF v_statut_paiement <> 'REUSSI' THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Un justificatif nécessite un paiement réussi';
  END IF;

  IF (v_mode_paiement = 'ESPECES' AND NEW.type_document <> 'RECU')
     OR (v_mode_paiement <> 'ESPECES' AND NEW.type_document <> 'FACTURE') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Type de justificatif incompatible avec le mode de paiement';
  END IF;
END$$

-- Centralise les transitions autorisées de statut de commande.
CREATE PROCEDURE sp_changer_statut_commande (
  IN p_id_commande BIGINT UNSIGNED,
  IN p_nouveau_statut VARCHAR(30),
  IN p_origine VARCHAR(15),
  IN p_id_utilisateur BIGINT UNSIGNED,
  IN p_commentaire VARCHAR(500)
)
BEGIN
  DECLARE v_ancien_statut VARCHAR(30) DEFAULT NULL;
  DECLARE v_mode_retrait VARCHAR(30) DEFAULT NULL;

  SELECT statut_courant, mode_retrait
    INTO v_ancien_statut, v_mode_retrait
    FROM commandes
   WHERE id_commande = p_id_commande;

  IF v_ancien_statut IS NULL THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Commande introuvable';
  END IF;

  IF p_origine NOT IN ('SYSTEME', 'CLIENT', 'GERANT', 'LIVREUR') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Origine de changement de statut invalide';
  END IF;

  IF NOT (
       (v_ancien_statut = 'EN_ATTENTE' AND p_nouveau_statut = 'EN_COURS_TRAITEMENT')
    OR (v_ancien_statut = 'EN_COURS_TRAITEMENT' AND p_nouveau_statut = 'TRAITEE')
    OR (v_ancien_statut = 'TRAITEE' AND p_nouveau_statut = 'EN_COURS_LIVRAISON')
    OR (v_ancien_statut = 'EN_COURS_LIVRAISON' AND p_nouveau_statut = 'LIVREE')
    OR (v_ancien_statut = 'EN_COURS_LIVRAISON' AND p_nouveau_statut = 'TRAITEE' AND p_origine = 'LIVREUR')
    OR (v_ancien_statut = 'TRAITEE' AND v_mode_retrait = 'RETRAIT_BOUTIQUE' AND p_nouveau_statut = 'LIVREE')
  ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Transition de statut non autorisée';
  END IF;

  UPDATE commandes
     SET statut_courant = p_nouveau_statut,
         confirmation_reception = IF(
           p_nouveau_statut = 'LIVREE' AND p_origine = 'CLIENT',
           TRUE,
           confirmation_reception
         ),
         date_confirmation_reception = IF(
           p_nouveau_statut = 'LIVREE' AND p_origine = 'CLIENT',
           NOW(),
           date_confirmation_reception
         )
   WHERE id_commande = p_id_commande;

  INSERT INTO historique_statuts_commande (
    id_commande, id_utilisateur, ancien_statut, nouveau_statut, origine, commentaire
  ) VALUES (
    p_id_commande, p_id_utilisateur, v_ancien_statut, p_nouveau_statut, p_origine, p_commentaire
  );
END$$

DELIMITER ;

-- ---------------------------------------------------------------------------
-- 8. DONNEES INITIALES
-- ---------------------------------------------------------------------------

INSERT IGNORE INTO roles (code_role, libelle, description) VALUES
  ('ADMINISTRATEUR', 'Administrateur', 'Pilotage global, paramétrage et gestion des comptes.'),
  ('GERANT', 'Gérant', 'Traitement des commandes, stock et affectations.'),
  ('LIVREUR', 'Livreur', 'Acceptation et exécution des livraisons affectées.');

INSERT IGNORE INTO permissions (code_permission, libelle, description) VALUES
  ('GERER_UTILISATEURS', 'Gérer les utilisateurs', 'Créer, modifier, désactiver les comptes internes.'),
  ('GERER_CATALOGUE', 'Gérer le catalogue', 'Gérer les catégories et les produits.'),
  ('GERER_STOCK', 'Gérer le stock', 'Enregistrer les entrées et ajustements de stock.'),
  ('TRAITER_COMMANDES', 'Traiter les commandes', 'Consulter et changer le statut des commandes.'),
  ('AFFECTER_LIVRAISONS', 'Affecter les livraisons', 'Affecter une commande traitée à un livreur.'),
  ('GERER_LIVRAISONS', 'Gérer les livraisons', 'Accepter, refuser et finaliser ses livraisons.'),
  ('CONSULTER_TABLEAU_BORD', 'Consulter le tableau de bord', 'Consulter recettes, ventes et historiques.'),
  ('GERER_CONFIGURATION', 'Gérer la configuration', 'Modifier les informations et services de la boutique.');

-- Permissions administrateur : toutes les permissions.
INSERT IGNORE INTO role_permissions (id_role, id_permission)
SELECT r.id_role, p.id_permission
  FROM roles r
 CROSS JOIN permissions p
 WHERE r.code_role = 'ADMINISTRATEUR';

-- Permissions gérant.
INSERT IGNORE INTO role_permissions (id_role, id_permission)
SELECT r.id_role, p.id_permission
  FROM roles r
  JOIN permissions p ON p.code_permission IN (
    'GERER_STOCK', 'TRAITER_COMMANDES', 'AFFECTER_LIVRAISONS', 'CONSULTER_TABLEAU_BORD'
  )
 WHERE r.code_role = 'GERANT';

-- Permissions livreur.
INSERT IGNORE INTO role_permissions (id_role, id_permission)
SELECT r.id_role, p.id_permission
  FROM roles r
  JOIN permissions p ON p.code_permission = 'GERER_LIVRAISONS'
 WHERE r.code_role = 'LIVREUR';

INSERT IGNORE INTO categories (libelle, description) VALUES
  ('Poissons', 'Produits congelés de la catégorie poissons.'),
  ('Viandes', 'Produits congelés de la catégorie viandes.'),
  ('Œufs', 'Œufs de poule et produits associés.');

INSERT IGNORE INTO parametres_boutique (
  id_parametre, nom_boutique, adresse_boutique, telephone_boutique, email_boutique,
  horaires, zones_livraison, frais_livraison_defaut, est_livraison_active
) VALUES (
  1, 'Poissonnerie Saint-Michel', 'Akpakpa, Cotonou', NULL, NULL,
  NULL, NULL, 0.00, TRUE
);

INSERT IGNORE INTO zones_livraison (libelle, description, frais_livraison, est_active) VALUES
  ('Akpakpa', 'Zone d''Akpakpa', 500.00, TRUE),
  ('Cotonou', 'Autres quartiers de la ville de Cotonou', 1000.00, TRUE),
  ('Calavi', 'Zone de Calavi', 1500.00, TRUE);
INSERT IGNORE INTO quartiers_livraison (id_zone_livraison, libelle, arrondissement, est_actif)
SELECT z.id_zone_livraison, source.libelle, source.arrondissement, TRUE
  FROM zones_livraison z
  JOIN (
  SELECT 'Akpakpa' AS zone_libelle, 'Avotrou-Aïmonlonfidé' AS libelle, '1er arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Avotrou-Gbégo' AS libelle, '1er arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Avotrou-Houézèkomè' AS libelle, '1er arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Dandji' AS libelle, '1er arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Dandji-Hokanmè' AS libelle, '1er arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Donatin' AS libelle, '1er arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Finagnon' AS libelle, '1er arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'N’vènamèdé' AS libelle, '1er arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Suru-Léré' AS libelle, '1er arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Tanto' AS libelle, '1er arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Tchanhounkpamè' AS libelle, '1er arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Tokplégbé' AS libelle, '1er arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Yagbé' AS libelle, '1er arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Ahouassa' AS libelle, '2e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Djèdjè-Layé' AS libelle, '2e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Gankpodo' AS libelle, '2e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Irédé' AS libelle, '2e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Kowègbo' AS libelle, '2e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Kpondéhou' AS libelle, '2e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Kpondéhou Tchémè' AS libelle, '2e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Lom-Nava' AS libelle, '2e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Minontchou' AS libelle, '2e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Sènandé' AS libelle, '2e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Sènadé Sékou' AS libelle, '2e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Yénawa' AS libelle, '2e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Yénawa Daho' AS libelle, '2e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Adjégounlè' AS libelle, '3e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Adogléta' AS libelle, '3e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Agbato' AS libelle, '3e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Agbodjèdo' AS libelle, '3e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Ayélawadjè' AS libelle, '3e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Ayélawadjè Agongomè' AS libelle, '3e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Fifatin' AS libelle, '3e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Gbénonkpo' AS libelle, '3e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Hlacomey' AS libelle, '3e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Kpankpan' AS libelle, '3e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Midombo' AS libelle, '3e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Sègbèya Nord' AS libelle, '3e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Sègbèya Sud' AS libelle, '3e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Abokicodji Centre' AS libelle, '4e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Abokicodji Lagune' AS libelle, '4e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Akpakpa Dodomè' AS libelle, '4e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Dédokpo' AS libelle, '4e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Enagnon' AS libelle, '4e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Fifadji Houto' AS libelle, '4e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Gbèdjèwin' AS libelle, '4e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Missessin' AS libelle, '4e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Ohe' AS libelle, '4e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Sodjèatinmè Centre' AS libelle, '4e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Sodjèatinmè Est' AS libelle, '4e arrondissement' AS arrondissement
  UNION ALL SELECT 'Akpakpa' AS zone_libelle, 'Sodjèatinmè Ouest' AS libelle, '4e arrondissement' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Aïbatin' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Aïdjèdo' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Agla' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Agontikon' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Ahouansori' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Ahouanlèko' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Avlékété-Jonquet' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Cadjèhoun' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Camp Guézo' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Casse-Auto' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Dantokpa' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Dégakon' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Djidjè' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Fidjrossè' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Fiyègnon' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Ganhi' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Ganhito' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Gbégamey' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Gbèdjromèdé' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Gbéto' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Guinkomey' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Haie Vive' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Houéyiho' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Jéricho' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Jonquet' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Ladji' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Les Cocotiers' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Mènontin' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Missebo' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Moulèrô' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Patte d’Oie' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Sainte-Cécile' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Sainte-Rita' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Saint-Jean' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Sikècodji' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Tokpa-Hoho' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Vèdoko' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Vossa' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Xwlacodji' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Zogbo' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Cotonou' AS zone_libelle, 'Zongo' AS libelle, 'Cotonou' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Agamandin' AS libelle, 'Abomey-Calavi' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Agori' AS libelle, 'Abomey-Calavi' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Aîfa' AS libelle, 'Abomey-Calavi' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Aîtchédji' AS libelle, 'Abomey-Calavi' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Alédjo' AS libelle, 'Abomey-Calavi' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Cité la Victoire' AS libelle, 'Abomey-Calavi' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Cité les Palmiers' AS libelle, 'Abomey-Calavi' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Fandji' AS libelle, 'Abomey-Calavi' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Finafa' AS libelle, 'Abomey-Calavi' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Gbodjo' AS libelle, 'Abomey-Calavi' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Kansounkpa' AS libelle, 'Abomey-Calavi' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Sèmè' AS libelle, 'Abomey-Calavi' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Tankpê' AS libelle, 'Abomey-Calavi' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Tchinangbégbo' AS libelle, 'Abomey-Calavi' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Tokpa-Zoungo' AS libelle, 'Abomey-Calavi' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Tokpa-Zoungo Nord' AS libelle, 'Abomey-Calavi' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Tokpa-Zoungo Sud' AS libelle, 'Abomey-Calavi' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Zogbadjè' AS libelle, 'Abomey-Calavi' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Zopah' AS libelle, 'Abomey-Calavi' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Zoundja' AS libelle, 'Abomey-Calavi' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Adjagbo' AS libelle, 'Akassato' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Agassa-Godomey' AS libelle, 'Akassato' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Agonmé' AS libelle, 'Akassato' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Agonsoundja' AS libelle, 'Akassato' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Akassato-Centre' AS libelle, 'Akassato' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Gbétagbo' AS libelle, 'Akassato' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Glo-Tokpa' AS libelle, 'Akassato' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Houèkè-Gbo' AS libelle, 'Akassato' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Houèkè-Honou' AS libelle, 'Akassato' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Kolètin' AS libelle, 'Akassato' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Kpodji-les-Monts' AS libelle, 'Akassato' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Missessinto' AS libelle, 'Akassato' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Zekanmey-Domè' AS libelle, 'Akassato' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Zopah Palmeraie' AS libelle, 'Akassato' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Adjamè' AS libelle, 'Golo-Djigbé' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Agongbé' AS libelle, 'Golo-Djigbé' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Agonkessa' AS libelle, 'Golo-Djigbé' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Alladacomè' AS libelle, 'Golo-Djigbé' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Azonsa' AS libelle, 'Golo-Djigbé' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Djissoukpa' AS libelle, 'Golo-Djigbé' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Domey-Gbo' AS libelle, 'Golo-Djigbé' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Golo-Djigbé' AS libelle, 'Golo-Djigbé' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Golo-Fanto' AS libelle, 'Golo-Djigbé' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Lohoussa' AS libelle, 'Golo-Djigbé' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Missèbo-Espace Saint' AS libelle, 'Golo-Djigbé' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Yékon-Do' AS libelle, 'Golo-Djigbé' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Yékon-Aga' AS libelle, 'Golo-Djigbé' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Zèkanmey' AS libelle, 'Golo-Djigbé' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Abikouholi' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Agbo-Codji-Sèdégbé' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Agonkanmey' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Aïmevo' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Alègléta' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Amanhoun' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Assrossa' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Atrokpo-Codji' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Cococodji' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Cocotomey' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Dèkoungbé-Eglise' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Dèkoungbé-Usine' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Dénou' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Djèkpota' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Djoukpa-Togoudo' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Fandji' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Fignonhou' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Finafa' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Ganganzounmè' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Gbègnigan-Midokpo' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Gbodjè-Womey' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Gninkindji' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Godomey-N’Gbèho' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Godomey-Togoudo' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Hélouto' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Hèdomè' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Houakomey' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Hounsa-Agbodokpa' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'La Paix' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Lobozounkpa' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Maria-Gléta' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Ningboto' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Nonhouénou' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Ounvènoumèdé' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Plateau' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Salamey' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Sèdjannanko' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Sèdomey' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Sèloli-Fandji' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Sodo' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Tankpè' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Togbin-Daho' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Togbin-Fandji' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Togbin-Kpèvi' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Tokpa' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Womey Centre' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Yénandjro' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Yolomahouto' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Zounga' AS libelle, 'Godomey' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Adovié' AS libelle, 'Hêvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Ahossougbéta' AS libelle, 'Hêvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Akossavié' AS libelle, 'Hêvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Dossounou' AS libelle, 'Hêvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Hêvié Centre' AS libelle, 'Hêvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Houinmè' AS libelle, 'Hêvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Sabenou' AS libelle, 'Hêvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Sogan' AS libelle, 'Hêvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Zoungo' AS libelle, 'Hêvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Anagbo' AS libelle, 'Kpanroun' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Avagbé' AS libelle, 'Kpanroun' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Bozoun' AS libelle, 'Kpanroun' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Handjanahou' AS libelle, 'Kpanroun' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Kpanroun' AS libelle, 'Kpanroun' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Kpé' AS libelle, 'Kpanroun' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Kpaviédja' AS libelle, 'Kpanroun' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Adjagbo' AS libelle, 'Ouèdo' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Adjagbo-Aïdjèdo' AS libelle, 'Ouèdo' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Ahouato' AS libelle, 'Ouèdo' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Alansankomè' AS libelle, 'Ouèdo' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Dassèkomey' AS libelle, 'Ouèdo' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Dessato' AS libelle, 'Ouèdo' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Kpossidja' AS libelle, 'Ouèdo' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Ouèdo Centre' AS libelle, 'Ouèdo' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Ahossougbéta' AS libelle, 'Togba' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Drabo' AS libelle, 'Togba' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Fifonsi' AS libelle, 'Togba' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Houèto' AS libelle, 'Togba' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Ouéga-Agué' AS libelle, 'Togba' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Ouéga-Tokpa' AS libelle, 'Togba' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Somè' AS libelle, 'Togba' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Tankpê' AS libelle, 'Togba' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Togba' AS libelle, 'Togba' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Maria-Gléta' AS libelle, 'Togba' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Tokan' AS libelle, 'Togba' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Tokan Aîdégnon' AS libelle, 'Togba' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Adjogansa' AS libelle, 'Zinvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Dangbodji' AS libelle, 'Zinvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Dokomey' AS libelle, 'Zinvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Gbodjè' AS libelle, 'Zinvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Gbodjoko' AS libelle, 'Zinvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Houégoudo' AS libelle, 'Zinvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Kpotomey' AS libelle, 'Zinvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Sokan' AS libelle, 'Zinvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Wawata' AS libelle, 'Zinvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Wawata-Todja' AS libelle, 'Zinvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Yèvié' AS libelle, 'Zinvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Yèvié-Nougo' AS libelle, 'Zinvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Zinvié-Agolèdji' AS libelle, 'Zinvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Zinvié-Fandji' AS libelle, 'Zinvié' AS arrondissement
  UNION ALL SELECT 'Calavi' AS zone_libelle, 'Zinvié-Zounmè' AS libelle, 'Zinvié' AS arrondissement
  ) AS source ON source.zone_libelle = z.libelle;

-- ---------------------------------------------------------------------------
-- 9. VUES UTILES POUR LE TABLEAU DE BORD
-- ---------------------------------------------------------------------------

CREATE OR REPLACE VIEW vue_recettes_journalieres AS
SELECT
  DATE(date_confirmation) AS jour,
  COUNT(*) AS nombre_paiements,
  SUM(montant) AS recettes
FROM paiements
WHERE statut_paiement = 'REUSSI'
GROUP BY DATE(date_confirmation);

CREATE OR REPLACE VIEW vue_ventes_par_produit AS
SELECT
  p.id_produit,
  p.libelle AS produit,
  c.libelle AS categorie,
  SUM(lc.quantite) AS quantite_vendue,
  SUM(lc.sous_total) AS chiffre_affaires
FROM lignes_commande lc
JOIN produits p ON p.id_produit = lc.id_produit
JOIN categories c ON c.id_categorie = p.id_categorie
JOIN commandes co ON co.id_commande = lc.id_commande
JOIN paiements pa ON pa.id_commande = co.id_commande
WHERE pa.statut_paiement = 'REUSSI'
GROUP BY p.id_produit, p.libelle, c.libelle;

CREATE OR REPLACE VIEW vue_produits_stock_bas AS
SELECT
  id_produit,
  libelle,
  quantite_stock,
  seuil_alerte,
  unite_vente
FROM produits
WHERE est_actif = TRUE
  AND quantite_stock <= seuil_alerte;

-- ============================================================================
-- FIN DU SCRIPT
-- Après import : renseigner les produits, leurs prix, unités de vente et stocks
-- initiaux depuis phpMyAdmin ou le back-office administrateur.
-- ============================================================================
