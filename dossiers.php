<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$page_title = 'Dossiers médicaux';

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM dossiers_medicaux WHERE id_dossier = ?")->execute([(int)$_GET['delete']]);
    header('Location: dossiers.php' . (isset($_GET['patient']) ? '?patient=' . (int)$_GET['patient'] : ''));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "INSERT INTO dossiers_medicaux (id_patient, id_medecin, date_consultation, motif, symptomes, diagnostic, traitement, poids, taille, temperature, tension_arterielle, pouls, observations)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $pdo->prepare($sql)->execute([
        $_POST['id_patient'],
        $_POST['id_medecin'] ?: null,
        $_POST['date_consultation'] ?: date('Y-m-d H:i:s'),
        trim($_POST['motif']),
        trim($_POST['symptomes']),
        trim($_POST['diagnostic']),
        trim($_POST['traitement']),
        $_POST['poids'] ?: null,
        $_POST['taille'] ?: null,
        $_POST['temperature'] ?: null,
        trim($_POST['tension_arterielle']),
        $_POST['pouls'] ?: null,
        trim($_POST['observations']),
    ]);
    header('Location: dossiers.php?patient=' . (int)$_POST['id_patient'] . '&ok=1');
    exit;
}

$patient_id = $_GET['patient'] ?? null;
$patients_list = $pdo->query("SELECT id_patient, nom, prenom, numero_dossier FROM patients ORDER BY nom")->fetchAll();
$medecins_list = $pdo->query("SELECT id_medecin, nom, prenom FROM medecins ORDER BY nom")->fetchAll();

if ($patient_id) {
    $stmt = $pdo->prepare("SELECT d.*, m.nom AS m_nom, m.prenom AS m_prenom FROM dossiers_medicaux d LEFT JOIN medecins m ON m.id_medecin=d.id_medecin WHERE d.id_patient = ? ORDER BY d.date_consultation DESC");
    $stmt->execute([$patient_id]);
    $dossiers = $stmt->fetchAll();
    $stmt2 = $pdo->prepare("SELECT * FROM patients WHERE id_patient = ?");
    $stmt2->execute([$patient_id]);
    $patient_courant = $stmt2->fetch();
} else {
    $dossiers = $pdo->query("
        SELECT d.*, p.nom AS p_nom, p.prenom AS p_prenom, m.nom AS m_nom, m.prenom AS m_prenom
        FROM dossiers_medicaux d
        JOIN patients p ON p.id_patient = d.id_patient
        LEFT JOIN medecins m ON m.id_medecin = d.id_medecin
        ORDER BY d.date_consultation DESC LIMIT 100
    ")->fetchAll();
    $patient_courant = null;
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?><div class="alert alert-success">Consultation enregistrée avec succès.</div><?php endif; ?>

<div class="card">
    <div class="toolbar">
        <div>
            <?php if ($patient_courant): ?>
                <strong>Patient :</strong> <?= e($patient_courant['nom'] . ' ' . $patient_courant['prenom']) ?>
                (<?= e($patient_courant['numero_dossier']) ?>)
                &nbsp;<a href="dossiers.php" class="btn btn-secondary btn-sm">Voir tous les dossiers</a>
            <?php else: ?>
                <strong>Toutes les consultations récentes</strong>
            <?php endif; ?>
        </div>
        <button class="btn btn-primary" onclick="openModal('modalDossier')">+ Nouvelle consultation</button>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <?php if (!$patient_courant): ?><th>Patient</th><?php endif; ?>
                    <th>Date</th><th>Médecin</th><th>Motif</th><th>Diagnostic</th><th>Constantes</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($dossiers as $d): ?>
                <tr>
                    <?php if (!$patient_courant): ?><td><?= e($d['p_nom'] . ' ' . $d['p_prenom']) ?></td><?php endif; ?>
                    <td><?= date('d/m/Y H:i', strtotime($d['date_consultation'])) ?></td>
                    <td><?= $d['m_nom'] ? 'Dr ' . e($d['m_nom']) : '-' ?></td>
                    <td><?= e($d['motif']) ?></td>
                    <td><?= e($d['diagnostic']) ?></td>
                    <td style="white-space:nowrap;font-size:12px;">
                        <?= $d['poids'] ? $d['poids'].'kg ' : '' ?>
                        <?= $d['temperature'] ? $d['temperature'].'°C ' : '' ?>
                        <?= $d['tension_arterielle'] ? $d['tension_arterielle'] : '' ?>
                    </td>
                    <td><a href="dossiers.php?delete=<?= $d['id_dossier'] ?><?= $patient_id ? '&patient='.$patient_id : '' ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete()">Suppr.</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$dossiers): ?>
                <tr><td colspan="7" style="text-align:center;color:#90a4ae;">Aucune consultation enregistrée</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modale Consultation -->
<div class="modal-overlay" id="modalDossier">
    <div class="modal-box">
        <form method="POST">
            <div class="modal-header">
                <h3>Nouvelle consultation</h3>
                <button type="button" class="close-btn" onclick="closeModal('modalDossier')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Patient</label>
                        <select name="id_patient" required <?= $patient_courant ? '' : '' ?>>
                            <option value="">-- Choisir --</option>
                            <?php foreach ($patients_list as $p): ?>
                                <option value="<?= $p['id_patient'] ?>" <?= ($patient_id == $p['id_patient']) ? 'selected' : '' ?>>
                                    <?= e($p['nom'] . ' ' . $p['prenom'] . ' (' . $p['numero_dossier'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Médecin</label>
                        <select name="id_medecin">
                            <option value="">-- Non spécifié --</option>
                            <?php foreach ($medecins_list as $m): ?>
                                <option value="<?= $m['id_medecin'] ?>">Dr <?= e($m['nom'] . ' ' . $m['prenom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Date / heure</label><input type="datetime-local" name="date_consultation" value="<?= date('Y-m-d\TH:i') ?>"></div>
                    <div class="form-group"><label>Motif de consultation</label><input type="text" name="motif" required></div>
                    <div class="form-group" style="grid-column:1/-1;"><label>Symptômes</label><textarea name="symptomes" rows="2"></textarea></div>
                    <div class="form-group" style="grid-column:1/-1;"><label>Diagnostic</label><textarea name="diagnostic" rows="2"></textarea></div>
                    <div class="form-group" style="grid-column:1/-1;"><label>Traitement prescrit</label><textarea name="traitement" rows="2"></textarea></div>
                    <div class="form-group"><label>Poids (kg)</label><input type="number" step="0.1" name="poids"></div>
                    <div class="form-group"><label>Taille (cm)</label><input type="number" step="0.1" name="taille"></div>
                    <div class="form-group"><label>Température (°C)</label><input type="number" step="0.1" name="temperature"></div>
                    <div class="form-group"><label>Tension artérielle</label><input type="text" name="tension_arterielle" placeholder="ex: 12/8"></div>
                    <div class="form-group"><label>Pouls (bpm)</label><input type="number" name="pouls"></div>
                    <div class="form-group" style="grid-column:1/-1;"><label>Observations</label><textarea name="observations" rows="2"></textarea></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalDossier')">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer la consultation</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
