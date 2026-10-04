<?php
require_once __DIR__ . '/auth.php';
require_login();
$me = current_user();
$page_title = $page_title ?? 'Tableau de bord';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title) ?> - CSB1 Tsangindrano</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="main-content">
        <div class="topbar">
            <h2><?= e($page_title) ?></h2>
            <div class="user-chip">
                <div class="avatar">
                    <?php if (!empty($me['photo']) && file_exists(__DIR__ . '/../' . $me['photo'])): ?>
                        <img src="<?= e($me['photo']) ?>" alt="">
                    <?php else: ?>
                        <?= e(strtoupper(substr($me['nom_complet'] ?? 'A', 0, 1))) ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div style="font-weight:600;"><?= e($me['nom_complet'] ?? '') ?></div>
                    <div style="font-size:11.5px; color:#90a4ae;"><?= e(ucfirst($me['role'] ?? '')) ?></div>
                </div>
            </div>
        </div>
        <div class="page-content">
