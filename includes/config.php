<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'csb1_tsangindrano');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage() .
        "<br>Vérifiez que WampServer est démarré et que la base 'csb1_tsangindrano' a bien été importée (fichier sql/csb1_tsangindrano.sql).");
}

session_start();
date_default_timezone_set('Indian/Antananarivo');
