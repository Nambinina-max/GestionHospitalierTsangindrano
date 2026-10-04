<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['nom_utilisateur'] ?? '');
    $pass  = $_POST['mot_de_passe'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE nom_utilisateur = ? AND actif = 1");
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['mot_de_passe'])) {
        $_SESSION['user_id'] = $user['id_utilisateur'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nom_complet'] = $user['nom_complet'];
        header('Location: dashboard.php');
        exit;
    } else {
        $erreur = "Identifiant ou mot de passe incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion - CSB1 Tsangindrano</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-card">
        <div class="logo-cross">+</div>
        <h1>CSB1 Tsangindrano</h1>
        <p class="subtitle">Système de gestion hospitalière</p>

        <?php if ($erreur): ?>
            <div class="login-error"><?= e($erreur) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="nom_utilisateur" placeholder="Identifiant" required autofocus>
            <input type="password" name="mot_de_passe" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>
        <p style="font-size:11.5px;color:#90a4ae;margin-top:18px;">
    </div>
</body>
</html>
