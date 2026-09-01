-- Quartiers de livraison liés aux zones tarifaires.
-- Migration relançable : les commandes existantes conservent un quartier NULL.

SET @schema_name := DATABASE();

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

-- Une même localité peut exister dans plusieurs arrondissements de Calavi.
SET @old_quarter_unique_exists := (
  SELECT COUNT(*)
    FROM information_schema.statistics
   WHERE table_schema = @schema_name
     AND table_name = 'quartiers_livraison'
     AND index_name = 'uq_quartiers_livraison_zone_libelle'
);
SET @old_quarter_unique_sql := IF(
  @old_quarter_unique_exists > 0,
  'ALTER TABLE quartiers_livraison DROP INDEX uq_quartiers_livraison_zone_libelle',
  'SELECT ''ancienne contrainte quartier absente'' AS information'
);
PREPARE old_quarter_unique_statement FROM @old_quarter_unique_sql;
EXECUTE old_quarter_unique_statement;
DEALLOCATE PREPARE old_quarter_unique_statement;

SET @quarter_unique_exists := (
  SELECT COUNT(*)
    FROM information_schema.statistics
   WHERE table_schema = @schema_name
     AND table_name = 'quartiers_livraison'
     AND index_name = 'uq_quartiers_livraison_zone_arrondissement_libelle'
);
SET @quarter_unique_sql := IF(
  @quarter_unique_exists = 0,
  'ALTER TABLE quartiers_livraison ADD UNIQUE KEY uq_quartiers_livraison_zone_arrondissement_libelle (id_zone_livraison, arrondissement, libelle)',
  'SELECT ''contrainte quartier déjà présente'' AS information'
);
PREPARE quarter_unique_statement FROM @quarter_unique_sql;
EXECUTE quarter_unique_statement;
DEALLOCATE PREPARE quarter_unique_statement;

SET @quartier_column_exists := (
  SELECT COUNT(*)
    FROM information_schema.columns
   WHERE table_schema = @schema_name
     AND table_name = 'commandes'
     AND column_name = 'id_quartier_livraison'
);
SET @quartier_column_sql := IF(
  @quartier_column_exists = 0,
  'ALTER TABLE commandes ADD COLUMN id_quartier_livraison BIGINT UNSIGNED NULL AFTER id_zone_livraison',
  'SELECT ''id_quartier_livraison déjà présent'' AS information'
);
PREPARE quartier_column_statement FROM @quartier_column_sql;
EXECUTE quartier_column_statement;
DEALLOCATE PREPARE quartier_column_statement;

SET @quartier_index_exists := (
  SELECT COUNT(*)
    FROM information_schema.statistics
   WHERE table_schema = @schema_name
     AND table_name = 'commandes'
     AND index_name = 'idx_commandes_quartier_livraison'
);
SET @quartier_index_sql := IF(
  @quartier_index_exists = 0,
  'ALTER TABLE commandes ADD KEY idx_commandes_quartier_livraison (id_quartier_livraison)',
  'SELECT ''idx_commandes_quartier_livraison déjà présent'' AS information'
);
PREPARE quartier_index_statement FROM @quartier_index_sql;
EXECUTE quartier_index_statement;
DEALLOCATE PREPARE quartier_index_statement;

SET @quartier_fk_exists := (
  SELECT COUNT(*)
    FROM information_schema.table_constraints
   WHERE constraint_schema = @schema_name
     AND table_name = 'commandes'
     AND constraint_name = 'fk_commandes_quartier_livraison'
     AND constraint_type = 'FOREIGN KEY'
);
SET @quartier_fk_sql := IF(
  @quartier_fk_exists = 0,
  'ALTER TABLE commandes ADD CONSTRAINT fk_commandes_quartier_livraison FOREIGN KEY (id_quartier_livraison) REFERENCES quartiers_livraison (id_quartier_livraison) ON UPDATE CASCADE ON DELETE RESTRICT',
  'SELECT ''fk_commandes_quartier_livraison déjà présent'' AS information'
);
PREPARE quartier_fk_statement FROM @quartier_fk_sql;
EXECUTE quartier_fk_statement;
DEALLOCATE PREPARE quartier_fk_statement;

-- PDF fourni : 51 quartiers d'Akpakpa et 148 de la commune d'Abomey-Calavi.
-- La liste Cotonou couvre les quartiers usuels hors zone Akpakpa.
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
