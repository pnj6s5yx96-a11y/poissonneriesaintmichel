-- Mise à niveau de l'espace gérant.
-- Cette migration peut être relancée sans erreur : elle ne crée que les éléments absents.

SET @schema_name := DATABASE();

-- Les commandes créées avant cette évolution sont assimilées à des commandes en ligne.
SET @origin_column_exists := (
  SELECT COUNT(*)
    FROM information_schema.columns
   WHERE table_schema = @schema_name
     AND table_name = 'commandes'
     AND column_name = 'origine_commande'
);
SET @origin_column_sql := IF(
  @origin_column_exists = 0,
  'ALTER TABLE commandes ADD COLUMN origine_commande ENUM(''EN_LIGNE'', ''COMPTOIR'') NOT NULL DEFAULT ''EN_LIGNE'' AFTER date_commande',
  'SELECT ''origine_commande déjà présente'' AS information'
);
PREPARE origin_column_statement FROM @origin_column_sql;
EXECUTE origin_column_statement;
DEALLOCATE PREPARE origin_column_statement;

SET @origin_index_exists := (
  SELECT COUNT(*)
    FROM information_schema.statistics
   WHERE table_schema = @schema_name
     AND table_name = 'commandes'
     AND index_name = 'idx_commandes_origine_date'
);
SET @origin_index_sql := IF(
  @origin_index_exists = 0,
  'ALTER TABLE commandes ADD KEY idx_commandes_origine_date (origine_commande, date_commande)',
  'SELECT ''idx_commandes_origine_date déjà présent'' AS information'
);
PREPARE origin_index_statement FROM @origin_index_sql;
EXECUTE origin_index_statement;
DEALLOCATE PREPARE origin_index_statement;

-- Conserve l'adresse de livraison avec la commande, avant son affectation au livreur.
SET @address_column_exists := (
  SELECT COUNT(*)
    FROM information_schema.columns
   WHERE table_schema = @schema_name
     AND table_name = 'commandes'
     AND column_name = 'adresse_livraison'
);
SET @address_column_sql := IF(
  @address_column_exists = 0,
  'ALTER TABLE commandes ADD COLUMN adresse_livraison VARCHAR(500) NULL AFTER mode_retrait',
  'SELECT ''adresse_livraison déjà présente'' AS information'
);
PREPARE address_column_statement FROM @address_column_sql;
EXECUTE address_column_statement;
DEALLOCATE PREPARE address_column_statement;
