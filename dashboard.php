<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$nb_patients   = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$nb_consult_jour = $pdo->query("SELECT COUNT(*) FROM dossiers_medicaux WHERE DATE(date_consultation) = CURDATE()")->fetchColumn();
$nb_rdv_jour   = $pdo->query("SELECT COUNT(*) FROM rendez_vous WHERE date_rdv = CURDATE()")->fetchColumn();
$nb_medoc_bas  = $pdo->query("SELECT COUNT(*) FROM medicaments WHERE quantite_stock <= seuil_alerte")->fetchColumn();
$nb_medecins   = $pdo->query("SELECT COUNT(*) FROM medecins")->fetchColumn();
$nb_examens_attente = $pdo->query("SELECT COUNT(*) FROM examens WHERE statut = 'en_attente'")->fetchColumn();

$derniers_patients = $pdo->query("SELECT * FROM patients ORDER BY date_enregistrement DESC LIMIT 5")->fetchAll();
$prochains_rdv = $pdo->query("
    SELECT r.*, p.nom AS p_nom, p.prenom AS p_prenom, m.nom AS m_nom, m.prenom AS m_prenom
    FROM rendez_vous r
    JOIN patients p ON p.id_patient = r.id_patient
    LEFT JOIN medecins m ON m.id_medecin = r.id_medecin
    WHERE r.date_rdv >= CURDATE()
    ORDER BY r.date_rdv ASC, r.heure_rdv ASC LIMIT 5
")->fetchAll();
?>

<div class="grid-cards">
    <div class="stat-card">
        <div class="icon-box">🧑‍🤝‍🧑</div>
        <div><div class="num"><?= $nb_patients ?></div><div class="lbl">Patients enregistrés</div></div>
    </div>
    <div class="stat-card c-green">
        <div class="icon-box">🩺</div>
        <div><div class="num"><?= $nb_consult_jour ?></div><div class="lbl">Consultations aujourd'hui</div></div>
    </div>
    <div class="stat-card c-orange">
        <div class="icon-box">📅</div>
        <div><div class="num"><?= $nb_rdv_jour ?></div><div class="lbl">Rendez-vous aujourd'hui</div></div>
    </div>
    <div class="stat-card c-red">
        <div class="icon-box">💊</div>
        <div><div class="num"><?= $nb_medoc_bas ?></div><div class="lbl">Médicaments en alerte stock</div></div>
    </div>
    <div class="stat-card">
        <div class="icon-box">👨‍⚕️</div>
        <div><div class="num"><?= $nb_medecins ?></div><div class="lbl">Médecins actifs</div></div>
    </div>
    <div class="stat-card c-orange">
        <div class="icon-box">🧪</div>
        <div><div class="num"><?= $nb_examens_attente ?></div><div class="lbl">Examens en attente</div></div>
    </div>
</div>

<div class="form-grid" style="grid-template-columns: 1.2fr 1fr; align-items:start;">
    <div class="card">
        <h3>Derniers patients enregistrés</h3>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>N° dossier</th><th>Nom</th><th>Sexe</th><th>Téléphone</th><th>Enregistré le</th></tr></thead>
                <tbody>
                <?php foreach ($derniers_patients as $p): ?>
                    <tr>
                        <td><?= e($p['numero_dossier']) ?></td>
                        <td><?= e($p['nom'] . ' ' . $p['prenom']) ?></td>
                        <td><?= e($p['sexe']) ?></td>
                        <td><?= e($p['telephone']) ?></td>
                        <td><?= date('d/m/Y', strtotime($p['date_enregistrement'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$derniers_patients): ?>
                    <tr><td colspan="5" style="text-align:center;color:#90a4ae;">Aucun patient enregistré</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h3>Prochains rendez-vous</h3>
        <?php foreach ($prochains_rdv as $r): ?>
            <div style="padding:10px 0;border-bottom:1px solid #eceff1;">
                <div style="font-weight:600;font-size:13.5px;"><?= e($r['p_nom'] . ' ' . $r['p_prenom']) ?></div>
                <div style="font-size:12px;color:#78909c;">
                    <?= date('d/m/Y', strtotime($r['date_rdv'])) ?> à <?= substr($r['heure_rdv'],0,5) ?>
                    <?php if ($r['m_nom']): ?> — Dr <?= e($r['m_nom']) ?><?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$prochains_rdv): ?>
            <p style="color:#90a4ae;font-size:13px;">Aucun rendez-vous à venir</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
