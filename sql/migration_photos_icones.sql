-- =====================================================================
-- MIGRATION : ajout des photos de profil et des icônes personnalisables
-- A exécuter UNE SEULE FOIS si votre base "csb1_tsangindrano" existe déjà
-- (si vous importez csb1_tsangindrano.sql pour la première fois, ce
-- fichier n'est pas nécessaire : tout est déjà inclus dedans)
-- =====================================================================

USE csb1_tsangindrano;

-- Ajoute la colonne photo aux utilisateurs si elle n'existe pas encore
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'csb1_tsangindrano' AND TABLE_NAME = 'utilisateurs' AND COLUMN_NAME = 'photo'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE utilisateurs ADD COLUMN photo VARCHAR(255) DEFAULT NULL AFTER telephone',
    'SELECT "Colonne photo déjà présente"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Crée la table des icônes personnalisables
CREATE TABLE IF NOT EXISTS icones_menu (
    id_icone INT AUTO_INCREMENT PRIMARY KEY,
    cle_menu VARCHAR(50) NOT NULL UNIQUE,
    libelle VARCHAR(100) NOT NULL,
    type ENUM('emoji','image') NOT NULL DEFAULT 'emoji',
    valeur VARCHAR(255) NOT NULL,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO icones_menu (cle_menu, libelle, type, valeur) VALUES
('dashboard', 'Dashboard', 'emoji', '🏠'),
('patients', 'Patient', 'emoji', '🧑‍🤝‍🧑'),
('dossiers', 'Dossier medicaux', 'emoji', '📁'),
('analyse_ia', 'Analyse IA', 'emoji', '🤖'),
('statistiques', 'Statistiques', 'emoji', '📊'),
('examens', 'Examens', 'emoji', '🧪'),
('medicaments', 'Medicaments', 'emoji', '💊'),
('rendezvous', 'Rendez-vous', 'emoji', '📅'),
('medecins', 'Medecins', 'emoji', '🩺'),
('parametres', 'Parametres', 'emoji', '⚙️'),
('deconnexion', 'Deconnection', 'emoji', '🚪');
