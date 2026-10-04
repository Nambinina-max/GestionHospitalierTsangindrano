-- =====================================================================
-- Base de données : CSB1 Tsangindrano - Système de gestion hospitalière
-- Généré pour WampServer (MySQL / MariaDB)
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS csb1_tsangindrano CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE csb1_tsangindrano;

-- ---------------------------------------------------------------------
-- Table : utilisateurs (comptes du personnel / admin)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS utilisateurs;
CREATE TABLE utilisateurs (
    id_utilisateur INT AUTO_INCREMENT PRIMARY KEY,
    nom_complet VARCHAR(100) NOT NULL,
    nom_utilisateur VARCHAR(50) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin','medecin','receptionniste','pharmacien') NOT NULL DEFAULT 'receptionniste',
    email VARCHAR(100),
    telephone VARCHAR(20),
    photo VARCHAR(255) DEFAULT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table : medecins
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS medecins;
CREATE TABLE medecins (
    id_medecin INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    specialite VARCHAR(100) DEFAULT 'Médecine générale',
    telephone VARCHAR(20),
    email VARCHAR(100),
    disponibilite VARCHAR(150) DEFAULT 'Lundi - Vendredi, 08h-16h',
    photo VARCHAR(255) DEFAULT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table : patients
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS patients;
CREATE TABLE patients (
    id_patient INT AUTO_INCREMENT PRIMARY KEY,
    numero_dossier VARCHAR(20) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE,
    sexe ENUM('M','F') NOT NULL DEFAULT 'M',
    adresse VARCHAR(255),
    fokontany VARCHAR(100),
    telephone VARCHAR(20),
    groupe_sanguin VARCHAR(5),
    allergies TEXT,
    personne_contact VARCHAR(150),
    date_enregistrement TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table : dossiers_medicaux (consultations / historique médical)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS dossiers_medicaux;
CREATE TABLE dossiers_medicaux (
    id_dossier INT AUTO_INCREMENT PRIMARY KEY,
    id_patient INT NOT NULL,
    id_medecin INT,
    date_consultation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    motif VARCHAR(255),
    symptomes TEXT,
    diagnostic TEXT,
    traitement TEXT,
    poids DECIMAL(5,2),
    taille DECIMAL(5,2),
    temperature DECIMAL(4,1),
    tension_arterielle VARCHAR(15),
    pouls INT,
    observations TEXT,
    FOREIGN KEY (id_patient) REFERENCES patients(id_patient) ON DELETE CASCADE,
    FOREIGN KEY (id_medecin) REFERENCES medecins(id_medecin) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table : medicaments (stock pharmacie)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS medicaments;
CREATE TABLE medicaments (
    id_medicament INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    forme VARCHAR(50) DEFAULT 'Comprimé',
    dosage VARCHAR(50),
    quantite_stock INT NOT NULL DEFAULT 0,
    seuil_alerte INT NOT NULL DEFAULT 10,
    prix_unitaire DECIMAL(10,2) DEFAULT 0,
    date_peremption DATE,
    fournisseur VARCHAR(150),
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table : examens (types d'examens + résultats liés à un dossier)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS examens;
CREATE TABLE examens (
    id_examen INT AUTO_INCREMENT PRIMARY KEY,
    id_patient INT NOT NULL,
    id_medecin INT,
    type_examen VARCHAR(150) NOT NULL,
    date_examen DATE NOT NULL,
    resultat TEXT,
    statut ENUM('en_attente','termine','annule') NOT NULL DEFAULT 'en_attente',
    fichier_joint VARCHAR(255),
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_patient) REFERENCES patients(id_patient) ON DELETE CASCADE,
    FOREIGN KEY (id_medecin) REFERENCES medecins(id_medecin) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table : rendez_vous
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS rendez_vous;
CREATE TABLE rendez_vous (
    id_rdv INT AUTO_INCREMENT PRIMARY KEY,
    id_patient INT NOT NULL,
    id_medecin INT,
    date_rdv DATE NOT NULL,
    heure_rdv TIME NOT NULL,
    motif VARCHAR(255),
    statut ENUM('planifie','confirme','termine','annule') NOT NULL DEFAULT 'planifie',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_patient) REFERENCES patients(id_patient) ON DELETE CASCADE,
    FOREIGN KEY (id_medecin) REFERENCES medecins(id_medecin) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table : parametres (informations générales du CSB, configurables)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS parametres;
CREATE TABLE parametres (
    cle VARCHAR(50) PRIMARY KEY,
    valeur VARCHAR(255)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table : analyses_ia (historique des analyses assistées)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS analyses_ia;
CREATE TABLE analyses_ia (
    id_analyse INT AUTO_INCREMENT PRIMARY KEY,
    id_patient INT NOT NULL,
    date_analyse TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    donnees_entree TEXT,
    resultat_analyse TEXT,
    niveau_risque ENUM('faible','modere','eleve') DEFAULT 'faible',
    FOREIGN KEY (id_patient) REFERENCES patients(id_patient) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table : icones_menu (icônes personnalisables du menu latéral)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS icones_menu;
CREATE TABLE icones_menu (
    id_icone INT AUTO_INCREMENT PRIMARY KEY,
    cle_menu VARCHAR(50) NOT NULL UNIQUE,
    libelle VARCHAR(100) NOT NULL,
    type ENUM('emoji','image') NOT NULL DEFAULT 'emoji',
    valeur VARCHAR(255) NOT NULL,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- Données initiales
-- =====================================================================

-- Compte administrateur : identifiant "admin" / mot de passe "Admin@2026"
-- (hash bcrypt généré par password_hash() en PHP)
INSERT INTO utilisateurs (nom_complet, nom_utilisateur, mot_de_passe, role, email, telephone, actif) VALUES
('Administrateur CSB1', 'admin', '$2b$10$VAdONP0pxtgiVV2WqWySCeP4ArfsVTCouUFTZro19DvZRPd3tFxPy', 'admin', 'admin@csb1-tsangindrano.mg', '0340000000', 1);

INSERT INTO parametres (cle, valeur) VALUES
('nom_hopital', 'CSB1 Tsangindrano'),
('adresse', 'Tsangindrano, Madagascar'),
('telephone', '034 00 000 00'),
('email', 'contact@csb1-tsangindrano.mg'),
('theme_couleur', 'bleu-medical');

INSERT INTO medecins (nom, prenom, specialite, telephone, email, disponibilite) VALUES
('RAKOTO', 'Hery', 'Médecine générale', '0341234567', 'h.rakoto@csb1-tsangindrano.mg', 'Lundi - Vendredi, 08h-16h'),
('RASOA', 'Voahangy', 'Pédiatrie', '0347654321', 'v.rasoa@csb1-tsangindrano.mg', 'Lundi, Mercredi, Vendredi, 08h-12h');

INSERT INTO medicaments (nom, forme, dosage, quantite_stock, seuil_alerte, prix_unitaire, date_peremption, fournisseur) VALUES
('Paracétamol', 'Comprimé', '500mg', 250, 30, 50.00, '2027-06-30', 'SALAMA'),
('Amoxicilline', 'Gélule', '500mg', 120, 20, 150.00, '2027-03-15', 'SALAMA'),
('Sérum physiologique', 'Solution injectable', '0.9%', 80, 15, 300.00, '2026-12-31', 'PHARMALIS'),
('Artésunate-Amodiaquine', 'Comprimé', '25mg/67.5mg', 60, 10, 200.00, '2027-01-20', 'SALAMA');

-- Icônes par défaut du menu (modifiables ensuite depuis Paramètres)
INSERT INTO icones_menu (cle_menu, libelle, type, valeur) VALUES
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
