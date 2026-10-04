<?php
/**
 * Fonctions d'authentification et de protection des pages
 */
require_once __DIR__ . '/config.php';

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function current_user() {
    global $pdo;
    if (!is_logged_in()) return null;
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_utilisateur = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        die("Accès refusé : cette page est réservée à l'administrateur.");
    }
}

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Renvoie le HTML d'une icône de menu (emoji ou image uploadée),
 * en se basant sur la table icones_menu. $fallback est utilisé
 * si la clé n'existe pas encore en base.
 */
/**
 * Gère l'upload sécurisé d'une image (photo de profil ou icône personnalisée).
 * $subdir : "users" ou "icons" (sous assets/uploads/)
 * Renvoie le chemin relatif (ex: assets/uploads/users/xxx.png) ou null si pas de fichier / erreur.
 */
function handle_image_upload($file, $subdir, $prefix = 'img') {
    if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => "Erreur lors de l'envoi du fichier."];
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $mime = mime_content_type($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        return ['error' => "Format non autorisé. Utilisez JPG, PNG, GIF ou WEBP."];
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        return ['error' => "L'image dépasse la taille maximale autorisée (2 Mo)."];
    }
    $ext = $allowed[$mime];
    $filename = $prefix . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $destDir = __DIR__ . '/../assets/uploads/' . $subdir . '/';
    if (!is_dir($destDir)) { mkdir($destDir, 0775, true); }
    $destPath = $destDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['error' => "Impossible d'enregistrer l'image sur le serveur."];
    }
    return ['path' => 'assets/uploads/' . $subdir . '/' . $filename];
}

function get_icon($cle_menu, $fallback = '•') {
    global $pdo;
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $rows = $pdo->query("SELECT cle_menu, type, valeur FROM icones_menu")->fetchAll();
            foreach ($rows as $r) { $cache[$r['cle_menu']] = $r; }
        } catch (Exception $ex) {
            $cache = [];
        }
    }
    if (isset($cache[$cle_menu])) {
        $icone = $cache[$cle_menu];
        if ($icone['type'] === 'image') {
            return '<img src="' . e($icone['valeur']) . '" class="menu-icon-img" alt="">';
        }
        return e($icone['valeur']);
    }
    return e($fallback);
}
