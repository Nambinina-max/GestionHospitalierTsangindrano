<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$page_title = 'Statistiques';

$par_mois = $pdo->query("
    SELECT DATE_FORMAT(date_consultation, '%Y-%m') AS mois, COUNT(*) AS total
    FROM dossiers_medicaux
    WHERE date_consultation >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY mois ORDER BY mois
")->fetchAll();

$par_sexe = $pdo->query("SELECT sexe, COUNT(*) AS total FROM patients GROUP BY sexe")->fetchAll();
$top_diag = $pdo->query("SELECT diagnostic, COUNT(*) AS total FROM dossiers_medicaux WHERE diagnostic <> '' GROUP BY diagnostic ORDER BY total DESC LIMIT 5")->fetchAll();
$medocs_bas = $pdo->query("SELECT nom, quantite_stock, seuil_alerte FROM medicaments WHERE quantite_stock <= seuil_alerte ORDER BY quantite_stock ASC")->fetchAll();
$rdv_statut = $pdo->query("SELECT statut, COUNT(*) AS total FROM rendez_vous GROUP BY statut")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="form-grid" style="grid-template-columns: 1fr 1fr;">
    <div class="card">
        <h3>Consultations par mois (6 derniers mois)</h3>
        <canvas id="chartMois" height="180"></canvas>
    </div>
    <div class="card">
        <h3>Répartition des patients par sexe</h3>
        <?php $total_patients = array_sum(array_column($par_sexe, 'total')); ?>
        <div style="display:flex; align-items:center; gap:24px;">
            <div style="flex:0 0 60%; max-width:60%;">
                <canvas id="chartSexe"></canvas>
            </div>
            <div style="flex:1;">
                <?php $couleurs = ['M' => '#1565c0', 'F' => '#2e9e6c']; ?>
                <?php foreach ($par_sexe as $s): ?>
                    <?php $pct = $total_patients ? round($s['total'] / $total_patients * 100) : 0; ?>
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
                        <span style="width:14px; height:14px; border-radius:4px; background:<?= $couleurs[$s['sexe']] ?? '#90a4ae' ?>; flex-shrink:0;"></span>
                        <span style="flex:1; font-size:13.5px;"><?= $s['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?></span>
                        <span style="font-weight:700; font-size:13.5px;"><?= $s['total'] ?></span>
                        <span style="color:#90a4ae; font-size:12px; width:38px; text-align:right;"><?= $pct ?>%</span>
                    </div>
                <?php endforeach; ?>
                <?php if (!$par_sexe): ?><p style="color:#90a4ae;font-size:13px;">Aucun patient enregistré</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="form-grid" style="grid-template-columns: 1fr 1fr;">
    <div class="card">
        <h3>Diagnostics les plus fréquents</h3>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Diagnostic</th><th>Nombre de cas</th></tr></thead>
                <tbody>
                <?php foreach ($top_diag as $d): ?>
                    <tr><td><?= e($d['diagnostic']) ?></td><td><?= $d['total'] ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$top_diag): ?><tr><td colspan="2" style="text-align:center;color:#90a4ae;">Pas encore de données</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <h3>Médicaments en alerte de stock</h3>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Médicament</th><th>Stock</th><th>Seuil</th></tr></thead>
                <tbody>
                <?php foreach ($medocs_bas as $m): ?>
                    <tr><td><?= e($m['nom']) ?></td><td><span class="badge danger"><?= $m['quantite_stock'] ?></span></td><td><?= $m['seuil_alerte'] ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$medocs_bas): ?><tr><td colspan="3" style="text-align:center;color:#90a4ae;">Aucune alerte de stock</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="assets/vendor/chart.umd.js"></script>
<script>
const moisLabels = <?= json_encode(array_map(fn($r) => $r['mois'], $par_mois)) ?>;
const moisData = <?= json_encode(array_map(fn($r) => (int)$r['total'], $par_mois)) ?>;
new Chart(document.getElementById('chartMois'), {
    type: 'bar',
    data: { labels: moisLabels, datasets: [{ label: 'Consultations', data: moisData, backgroundColor: '#1565c0' }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

const sexeLabels = <?= json_encode(array_map(fn($r) => $r['sexe'] === 'M' ? 'Masculin' : 'Féminin', $par_sexe)) ?>;
const sexeData = <?= json_encode(array_map(fn($r) => (int)$r['total'], $par_sexe)) ?>;
new Chart(document.getElementById('chartSexe'), {
    type: 'doughnut',
    data: { labels: sexeLabels, datasets: [{ data: sexeData, backgroundColor: ['#1565c0', '#2e9e6c'] }] },
    options: {
        maintainAspectRatio: true,
        aspectRatio: 1,
        cutout: '55%',
        plugins: { legend: { display: false } }
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
