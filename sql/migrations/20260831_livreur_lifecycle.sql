-- Complète le cycle opérationnel du livreur : une mission acceptée peut être
-- annulée avant départ. La commande revient alors à TRAITEE pour réaffectation.
-- Exécuter ce fichier dans la base poissonnerie_saint_michel.

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_changer_statut_commande$$

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
