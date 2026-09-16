-- Zones tarifaires de livraison et liaison avec les commandes.
-- Cette migration est relançable sans modifier les commandes déjà existantes.

SET @schema_name := DATABASE();

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

SET @zone_column_exists := (
  SELECT COUNT(*)
    FROM information_schema.columns
   WHERE table_schema = @schema_name
     AND table_name = 'commandes'
     AND column_name = 'id_zone_livraison'
);
SET @zone_column_sql := IF(
  @zone_column_exists = 0,
  'ALTER TABLE commandes ADD COLUMN id_zone_livraison BIGINT UNSIGNED NULL AFTER mode_retrait',
  'SELECT ''id_zone_livraison déjà présente'' AS information'
);
PREPARE zone_column_statement FROM @zone_column_sql;
EXECUTE zone_column_statement;
DEALLOCATE PREPARE zone_column_statement;

SET @zone_index_exists := (
  SELECT COUNT(*)
    FROM information_schema.statistics
   WHERE table_schema = @schema_name
     AND table_name = 'commandes'
     AND index_name = 'idx_commandes_zone_livraison'
);
SET @zone_index_sql := IF(
  @zone_index_exists = 0,
  'ALTER TABLE commandes ADD KEY idx_commandes_zone_livraison (id_zone_livraison)',
  'SELECT ''idx_commandes_zone_livraison déjà présent'' AS information'
);
PREPARE zone_index_statement FROM @zone_index_sql;
EXECUTE zone_index_statement;
DEALLOCATE PREPARE zone_index_statement;

SET @zone_fk_exists := (
  SELECT COUNT(*)
    FROM information_schema.table_constraints
   WHERE constraint_schema = @schema_name
     AND table_name = 'commandes'
     AND constraint_name = 'fk_commandes_zone_livraison'
     AND constraint_type = 'FOREIGN KEY'
);
SET @zone_fk_sql := IF(
  @zone_fk_exists = 0,
  'ALTER TABLE commandes ADD CONSTRAINT fk_commandes_zone_livraison FOREIGN KEY (id_zone_livraison) REFERENCES zones_livraison (id_zone_livraison) ON UPDATE CASCADE ON DELETE RESTRICT',
  'SELECT ''fk_commandes_zone_livraison déjà présente'' AS information'
);
PREPARE zone_fk_statement FROM @zone_fk_sql;
EXECUTE zone_fk_statement;
DEALLOCATE PREPARE zone_fk_statement;

INSERT IGNORE INTO zones_livraison (libelle, description, frais_livraison, est_active) VALUES
  ('Akpakpa', 'Zone d''Akpakpa', 500.00, TRUE),
  ('Cotonou', 'Autres quartiers de la ville de Cotonou', 1000.00, TRUE),
  ('Calavi', 'Zone de Calavi', 1500.00, TRUE);
