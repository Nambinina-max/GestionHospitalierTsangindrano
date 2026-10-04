<?php
$current = basename($_SERVER['PHP_SELF']);
function nav_active($page, $current) { return $page === $current ? 'active' : ''; }
?>
<aside class="sidebar" id="sidebar">
    <div class="brand">
        <div class="cross">+</div>
        <span>CSB1<br>Tsangindrano</span>
    </div>
    <nav>
        <a href="dashboard.php" class="<?= nav_active('dashboard.php', $current) ?>"><span class="ic"><?= get_icon('dashboard', '🏠') ?></span> Dashboard</a>
        <a href="patients.php" class="<?= nav_active('patients.php', $current) ?>"><span class="ic"><?= get_icon('patients', '🧑‍🤝‍🧑') ?></span> Patient</a>
        <a href="dossiers.php" class="<?= nav_active('dossiers.php', $current) ?>"><span class="ic"><?= get_icon('dossiers', '📁') ?></span> Dossier medicaux</a>
        <a href="analyse_ia.php" class="<?= nav_active('analyse_ia.php', $current) ?>"><span class="ic"><?= get_icon('analyse_ia', '🤖') ?></span> Analyse IA</a>
        <a href="statistiques.php" class="<?= nav_active('statistiques.php', $current) ?>"><span class="ic"><?= get_icon('statistiques', '📊') ?></span> Statistiques</a>
        <a href="examens.php" class="<?= nav_active('examens.php', $current) ?>"><span class="ic"><?= get_icon('examens', '🧪') ?></span> Examens</a>
        <a href="medicaments.php" class="<?= nav_active('medicaments.php', $current) ?>"><span class="ic"><?= get_icon('medicaments', '💊') ?></span> Medicaments</a>
        <a href="rendezvous.php" class="<?= nav_active('rendezvous.php', $current) ?>"><span class="ic"><?= get_icon('rendezvous', '📅') ?></span> Rendez-vous</a>
        <a href="medecins.php" class="<?= nav_active('medecins.php', $current) ?>"><span class="ic"><?= get_icon('medecins', '🩺') ?></span> Medecins</a>
        <a href="parametres.php" class="<?= nav_active('parametres.php', $current) ?>"><span class="ic"><?= get_icon('parametres', '⚙️') ?></span> Parametres</a>
        <a href="logout.php" class="logout"><span class="ic"><?= get_icon('deconnexion', '🚪') ?></span> Deconnection</a>
    </nav>
</aside>
