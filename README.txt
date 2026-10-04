=========================================================
CSB1 TSANGINDRANO - Système de gestion hospitalière
=========================================================

INSTALLATION AVEC WAMPSERVER
-----------------------------
1. Démarrez WampServer (icône verte dans la barre des tâches).
2. Copiez le dossier "csb1_app" dans : C:\wamp64\www\
   (renommez-le si besoin, ex: C:\wamp64\www\csb1)
3. Ouvrez phpMyAdmin : http://localhost/phpmyadmin
4. Créez la base OU importez directement le fichier :
   sql/csb1_tsangindrano.sql
   (onglet "Importer" dans phpMyAdmin -> choisir le fichier -> Exécuter)
   Ce fichier crée automatiquement la base "csb1_tsangindrano",
   toutes les tables, un compte administrateur et quelques
   données de démonstration (médecins, médicaments).
5. Vérifiez includes/config.php :
   - DB_USER = 'root'
   - DB_PASS = ''
   (valeurs par défaut de WampServer ; à adapter si vous avez
   changé les identifiants MySQL)
6. Ouvrez votre navigateur : http://localhost/csb1_app/

CONNEXION
---------
Identifiant : admin
Mot de passe : Admin@2026

(Changez ce mot de passe dès la première connexion via
"Paramètres > Changer mon mot de passe")

FONCTIONNALITÉS INCLUSES
-------------------------
- Authentification avec sessions PHP + rôles (admin, médecin,
  réceptionniste, pharmacien)
- Dashboard : statistiques en temps réel, derniers patients,
  prochains rendez-vous
- Patients : fiche complète, recherche, numéro de dossier auto
- Dossier médicaux : historique des consultations par patient,
  constantes vitales (poids, taille, température, tension, pouls)
- Analyse IA : moteur de règles cliniques qui évalue un niveau
  de risque (faible/modéré/élevé) à partir des constantes et
  symptômes saisis, avec historique des analyses
- Statistiques : graphiques (Chart.js) consultations/mois,
  répartition par sexe, diagnostics fréquents, alertes de stock
- Examens : suivi des examens prescrits, statut (en attente/
  terminé/annulé), résultats
- Médicaments : gestion du stock pharmacie, seuils d'alerte,
  dates de péremption
- Rendez-vous : planification, confirmation, statuts
- Médecins : fiches du personnel médical (spécialité,
  disponibilité, contact)
- Paramètres : informations du centre, gestion des comptes
  utilisateurs (admin), changement de mot de passe
- Photo de profil : chaque utilisateur peut mettre sa propre photo
  (Paramètres > Ma photo de profil) ; l'admin peut aussi changer
  la photo de n'importe quel utilisateur depuis la liste des comptes
- Médecins : chaque fiche médecin peut aussi avoir sa propre photo
  (champ "Photo" dans le formulaire d'ajout/modification, page
  Medecins) — affichée sur sa carte à la place de l'icône par défaut
- Icônes personnalisables : l'admin peut, pour chaque élément du
  menu latéral, remplacer l'emoji par défaut par une image uploadée
  (Paramètres > Personnalisation des icônes du menu)

MISE A JOUR D'UNE INSTALLATION EXISTANTE
------------------------------------------
Si vous aviez déjà importé une ancienne version de la base avant
l'ajout des photos et des icônes personnalisables, importez en plus
le fichier sql/migration_photos_icones.sql dans phpMyAdmin (onglet
"Importer" ou "SQL", une seule fois). Si c'est une toute nouvelle
installation, ce n'est pas nécessaire : tout est déjà dans
sql/csb1_tsangindrano.sql.

Assurez-vous aussi que le dossier assets/uploads/ (et ses
sous-dossiers users/ et icons/) est accessible en écriture par
le serveur (normalement déjà le cas sous WampServer/Windows).

NOTE SUR "ANALYSE IA"
----------------------
Le module "Analyse IA" utilise un moteur de règles cliniques
(seuils médicaux) codé en PHP, et non un modèle d'intelligence
artificielle externe (aucune clé API n'est nécessaire, ça
fonctionne entièrement en local sur WampServer). C'est un outil
d'aide à la décision, pas un diagnostic médical automatisé.
Si vous voulez brancher une vraie API d'IA plus tard (OpenAI,
Claude, etc.), il suffit de remplacer la logique dans
analyse_ia.php par un appel API - demandez-moi si besoin.

STRUCTURE DU PROJET
--------------------
csb1_app/
├── includes/        (config, auth, header, footer, sidebar)
├── assets/css/js     (styles et scripts)
├── sql/               (script d'installation de la BDD)
├── login.php, logout.php, index.php
├── dashboard.php
├── patients.php
├── dossiers.php
├── analyse_ia.php
├── statistiques.php
├── examens.php
├── medicaments.php
├── rendezvous.php
├── medecins.php
└── parametres.php
