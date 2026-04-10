-- ============================================================
-- StockSmart Pro v2 — Base de données complète
-- Importer dans phpMyAdmin : http://localhost/phpmyadmin
-- ============================================================

CREATE DATABASE IF NOT EXISTS stocksmart_v2
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE stocksmart_v2;

-- ── Enseignes ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS enseignes (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  nom       VARCHAR(100) NOT NULL,
  logo_url  VARCHAR(255) DEFAULT '',
  couleur   VARCHAR(20)  DEFAULT '#e94560',
  actif     TINYINT(1)   DEFAULT 1,
  created_at TIMESTAMP   DEFAULT CURRENT_TIMESTAMP
);

-- ── Utilisateurs ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS utilisateurs (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  nom          VARCHAR(100)  NOT NULL,
  prenom       VARCHAR(100)  NOT NULL,
  email        VARCHAR(150)  NOT NULL UNIQUE,
  mot_de_passe VARCHAR(255)  NOT NULL,
  role         ENUM('admin','gerant','employe') DEFAULT 'employe',
  enseigne_id  INT           DEFAULT 1,
  actif        TINYINT(1)    DEFAULT 1,
  created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (enseigne_id) REFERENCES enseignes(id)
);

-- ── Catégories ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  nom  VARCHAR(100) NOT NULL UNIQUE
);

-- ── Produits ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS produits (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  reference     VARCHAR(50)   NOT NULL UNIQUE,
  nom           VARCHAR(150)  NOT NULL,
  marque        VARCHAR(100)  DEFAULT '',
  fournisseur   VARCHAR(100)  DEFAULT '',
  categorie_id  INT,
  quantite      INT           DEFAULT 0,
  seuil_alerte  INT           DEFAULT 5,
  prix          DECIMAL(10,2) DEFAULT 0.00,
  image         VARCHAR(255)  DEFAULT '',
  created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (categorie_id) REFERENCES categories(id)
);

-- ── Mouvements ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS mouvements (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  produit_id      INT NOT NULL,
  utilisateur_id  INT,
  type_mouvement  ENUM('entree','vente','perte','casse','sortie') NOT NULL,
  quantite        INT NOT NULL,
  commentaire     TEXT,
  date_mouvement  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (produit_id)     REFERENCES produits(id),
  FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
);

-- ── TRIGGER : mise à jour stock automatique ───────────────────
DROP TRIGGER IF EXISTS after_mouvement_insert;

DELIMITER $$
CREATE TRIGGER after_mouvement_insert
AFTER INSERT ON mouvements
FOR EACH ROW
BEGIN
  IF NEW.type_mouvement = 'entree' THEN
    UPDATE produits SET quantite = quantite + NEW.quantite WHERE id = NEW.produit_id;
  ELSE
    UPDATE produits SET quantite = GREATEST(0, quantite - NEW.quantite) WHERE id = NEW.produit_id;
  END IF;
END$$
DELIMITER ;

-- ── DONNÉES DE DÉMONSTRATION ───────────────────────────────────

-- Enseignes
INSERT INTO enseignes (id, nom, logo_url, couleur) VALUES
(1, 'Carrefour Paris-Est',    'https://ui-avatars.com/api/?name=CA&background=003189&color=fff', '#003189'),
(2, 'Leclerc Lyon-Sud',       'https://ui-avatars.com/api/?name=LE&background=009900&color=fff', '#009900'),
(3, 'Auchan Bordeaux',        'https://ui-avatars.com/api/?name=AU&background=e30613&color=fff', '#e30613')
ON DUPLICATE KEY UPDATE nom = VALUES(nom);

-- Utilisateurs (mot de passe = "admin123" pour tous)
INSERT INTO utilisateurs (id, nom, prenom, email, mot_de_passe, role, enseigne_id) VALUES
(1, 'Diawara',  'Niakale',  'admin@stocksmart.pro',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',   1),
(2, 'Renault',  'Thomas',   'thomas@carrefour.fr',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employe', 1),
(3, 'Lambert',  'Marie',    'marie@carrefour.fr',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employe', 1),
(4, 'Rousseau', 'Marc',     'marc@leclerc.fr',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gerant',  2)
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- Catégories
INSERT INTO categories (id, nom) VALUES
(1, 'Boissons'),
(2, 'Snacks'),
(3, 'Produits frais'),
(4, 'Hygiène'),
(5, 'Papeterie'),
(6, 'Poissonnerie'),
(7, 'Boulangerie'),
(8, 'Laitages')
ON DUPLICATE KEY UPDATE nom = VALUES(nom);

-- Produits
INSERT INTO produits (id, reference, nom, marque, categorie_id, quantite, seuil_alerte, prix) VALUES
(1, 'P001', 'Jus Orange 1L',       'Herta',         1, 5,  10, 1.29),
(2, 'P002', 'Eau minérale 1.5L',   'Évian',         1, 20,  5, 0.89),
(3, 'P003', 'Chips salés 200g',    'Lay\'s',        2, 3,   5, 1.80),
(4, 'P004', 'Yaourt nature x8',    'Danone',        8, 12,  5, 1.89),
(5, 'P005', 'Savon liquide 500ml', 'Sanytol',       4, 8,   3, 3.20),
(6, 'P006', 'Biscuit chocolat',    'Lu',            2, 0,   5, 2.10),
(7, 'P007', 'Soda cola 33cl',      'Coca-Cola',     1, 25,  8, 1.20),
(8, 'P008', 'Cahier 96p',          'Clairefontaine',5, 12, 15, 2.49),
(9, 'P009', 'Filets cabillaud 400g','Pescanova',    6, 26,  5, 4.99),
(10,'P010', 'Pain de mie spécial', 'Harry\'s',      7, 8,  15, 1.65)
ON DUPLICATE KEY UPDATE nom = VALUES(nom);

-- Mouvements (sans trigger, on insère les données brutes)
-- Note: le trigger s'appliquera aux nouveaux mouvements seulement
INSERT INTO mouvements (produit_id, utilisateur_id, type_mouvement, quantite, commentaire, date_mouvement) VALUES
(9, 2, 'entree',  6, 'Réception commande fournisseur',  NOW() - INTERVAL 2 HOUR),
(8, 3, 'sortie',  5, 'Vente en caisse',                 NOW() - INTERVAL 5 HOUR),
(4, 2, 'entree', 12, 'Approvisionnement yaourts',       NOW() - INTERVAL 1 DAY),
(1, 3, 'sortie',  2, 'Vente directe',                   NOW() - INTERVAL 1 DAY),
(7, 2, 'entree', 25, 'Livraison hebdomadaire',          NOW() - INTERVAL 2 DAY),
(6, 3, 'vente',   5, 'Vente biscuits chocolat',         NOW() - INTERVAL 3 DAY),
(3, 2, 'perte',   2, 'Emballage abîmé',                 NOW() - INTERVAL 4 DAY),
(5, 3, 'entree',  8, 'Réassort hygiène',                NOW() - INTERVAL 5 DAY);
