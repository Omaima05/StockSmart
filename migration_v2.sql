-- ============================================================
-- MIGRATION — Ajouter code_invitation à la table enseignes
-- À exécuter UNE FOIS dans phpMyAdmin > SQL
-- ============================================================

USE stocksmart_v2;

-- Ajouter la colonne code_invitation si elle n'existe pas
ALTER TABLE enseignes
  ADD COLUMN IF NOT EXISTS code_invitation VARCHAR(20) UNIQUE DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS actif TINYINT(1) DEFAULT 1;

-- Ajouter actif aux utilisateurs si manquant
ALTER TABLE utilisateurs
  ADD COLUMN IF NOT EXISTS actif TINYINT(1) DEFAULT 1;

-- Générer des codes invitation pour les enseignes existantes
UPDATE enseignes SET code_invitation = CONCAT(
  UPPER(SUBSTR(REPLACE(nom, ' ', ''), 1, 6)),
  '-',
  FLOOR(1000 + RAND() * 9000)
) WHERE code_invitation IS NULL;

-- Vérification
SELECT id, nom, code_invitation FROM enseignes;
