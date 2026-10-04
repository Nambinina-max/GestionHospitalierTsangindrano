<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$page_title = 'Rendez-vous';

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM rendez_vous WHERE id_rdv = ?")->execute([(int)$_GET['delete']]);
    header('Location: rendezvous.php?ok=1');
    exit;
}
if (isset($_GET['statut']) && isset($_GET['id'])) {
    $pdo->prepare("UPDATE rendez_vous SET statut = ? WHERE id_rdv = ?")->execute([$_GET['statut'], (int)$_GET['id']]);
    header('Location: rendezvous.php?ok=1');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->prepare("INSERT INTO rendez_vous (id_patient, id_medecin, date_rdv, heure_rdv, motif, statut) VALUES (?,?,?,?,?,?)")
        ->execute([$_POST['id_patient'], $_POST['id_medecin'] ?: null, $_POST['date_rdv'], $_POST['heure_rdv'], trim($_POST['motif']), 'planifie']);
    header('Location: rendezvous.php?ok=1');
    exit;
}

$patients_list = $pdo->query("SELECT id_patient, nom, prenom FROM patients ORDER BY nom")->fetchAll();
$medecins_list = $pdo->query("SELECT id_medecin, nom, prenom FROM medecins ORDER BY nom")->fetchAll();
$rdvs = $pdo->query("
    SELECT r.*, p.nom AS p_nom, p.prenom AS p_prenom, m.nom AS m_nom
    FROM rendez_vous r JOIN patients p ON p.id_patient=r.id_patient LEFT JOIN medecins m ON m.id_medecin=r.id_medecin
    ORDER BY r.date_rdv DESC, r.heure_rdv DESC
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?><div class="alert alert-success">Opération effectuée avec succès.</div><?php endif; ?>

<div class="card">
    <div class="toolbar">
        <strong>Liste des rendez-vous</strong>
        <button class="btn btn-primary" onclick="openModal('modalRdv')">+ Nouveau rendez-vous</button>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Patient</th><th>Médecin</th><th>Date</th><th>Heure</th><th>Motif</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($rdvs as $r):
                $badges = ['planifie'=>'info','confirme'=>'ok','termine'=>'ok','annule'=>'danger'];
                $labels = ['planifie'=>'Planifié','confirme'=>'Confirmé','termine'=>'Terminé','annule'=>'Annulé'];
            ?>
                <tr>
                    <td><?= e($r['p_nom'] . ' ' . $r['p_prenom']) ?></td>
                    <td><?= $r['m_nom'] ? 'Dr ' . e($r['m_nom']) : '-' ?></td>
                    <td><?= date('d/m/Y', strtotime($r['date_rdv'])) ?></td>
                    <td><?= substr($r['heure_rdv'], 0, 5) ?></td>
                    <td><?= e($r['motif']) ?></td>
                    <td><span class="badge <?= $badges[$r['statut']] ?>"><?= $labels[$r['statut']] ?></span></td>
                    <td style="white-space:nowrap;">
                        <?php if ($r['statut'] === 'planifie'): ?>
                            <a href="rendezvous.php?statut=confirme&id=<?= $r['id_rdv'] ?>" class="btn btn-success btn-sm">Confirmer</a>
                        <?php endif; ?>
                        <a href="rendezvous.php?delete=<?= $r['id_rdv'] ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete()">Suppr.</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rdvs): ?><tr><td colspan="7" style="text-align:center;color:#90a4ae;">Aucun rendez-vous enregistré</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="modalRdv">
    <div class="modal-box">
        <form method="POST">
            <div class="modal-header"><h3>Nouveau rendez-vous</h3><button type="button" class="close-btn" onclick="closeModal('modalRdv')">&times;</button></div>
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group"><label>Patient</label>
                        <select name="id_patient" required>
                            <option value="">-- Choisir --</option>
                            <?php foreach ($patients_list as $p): ?><option value="<?= $p['id_patient'] ?>"><?= e($p['nom'].' '.$p['prenom']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Médecin</label>
                        <select name="id_medecin">
                            <option value="">-- Non spécifié --</option>
                            <?php foreach ($medecins_list as $m): ?><option value="<?= $m['id_medecin'] ?>">Dr <?= e($m['nom'].' '.$m['prenom']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Date</label><input type="date" name="date_rdv" required></div>
                    <div class="form-group"><label>Heure</label><input type="time" name="heure_rdv" required></div>
                    <div class="form-group" style="grid-column:1/-1;"><label>Motif</label><input type="text" name="motif"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalRdv')">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
