-- Renforcement applicatif : limitation des tentatives publiques et de connexion.
-- Cette table ne conserve jamais un identifiant ou une adresse IP en clair :
-- la clé est une empreinte SHA-256 calculée côté application.

USE poissonnerie_saint_michel;

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
