<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$page_title = 'Examens';

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM examens WHERE id_examen = ?")->execute([(int)$_GET['delete']]);
    header('Location: examens.php?ok=1');
    exit;
}

if (isset($_GET['statut']) && isset($_GET['id'])) {
    $pdo->prepare("UPDATE examens SET statut = ? WHERE id_examen = ?")->execute([$_GET['statut'], (int)$_GET['id']]);
    header('Location: examens.php?ok=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "INSERT INTO examens (id_patient, id_medecin, type_examen, date_examen, resultat, statut) VALUES (?,?,?,?,?,?)";
    $pdo->prepare($sql)->execute([
        $_POST['id_patient'], $_POST['id_medecin'] ?: null, trim($_POST['type_examen']),
        $_POST['date_examen'], trim($_POST['resultat']), $_POST['statut']
    ]);
    header('Location: examens.php?ok=1');
    exit;
}

$patients_list = $pdo->query("SELECT id_patient, nom, prenom FROM patients ORDER BY nom")->fetchAll();
$medecins_list = $pdo->query("SELECT id_medecin, nom, prenom FROM medecins ORDER BY nom")->fetchAll();
$examens = $pdo->query("
    SELECT e.*, p.nom AS p_nom, p.prenom AS p_prenom, m.nom AS m_nom
    FROM examens e JOIN patients p ON p.id_patient=e.id_patient LEFT JOIN medecins m ON m.id_medecin=e.id_medecin
    ORDER BY e.date_examen DESC
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?><div class="alert alert-success">Opération effectuée avec succès.</div><?php endif; ?>

<div class="card">
    <div class="toolbar">
        <strong>Liste des examens</strong>
        <button class="btn btn-primary" onclick="openModal('modalExamen')">+ Nouvel examen</button>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Patient</th><th>Type d'examen</th><th>Date</th><th>Médecin</th><th>Résultat</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($examens as $ex): ?>
                <tr>
                    <td><?= e($ex['p_nom'] . ' ' . $ex['p_prenom']) ?></td>
                    <td><?= e($ex['type_examen']) ?></td>
                    <td><?= date('d/m/Y', strtotime($ex['date_examen'])) ?></td>
                    <td><?= $ex['m_nom'] ? 'Dr ' . e($ex['m_nom']) : '-' ?></td>
                    <td style="max-width:220px;"><?= e($ex['resultat']) ?></td>
                    <td>
                        <?php $badges = ['en_attente'=>'warn','termine'=>'ok','annule'=>'danger'];
                        $labels = ['en_attente'=>'En attente','termine'=>'Terminé','annule'=>'Annulé']; ?>
                        <span class="badge <?= $badges[$ex['statut']] ?>"><?= $labels[$ex['statut']] ?></span>
                    </td>
                    <td style="white-space:nowrap;">
                        <?php if ($ex['statut'] !== 'termine'): ?>
                            <a href="examens.php?statut=termine&id=<?= $ex['id_examen'] ?>" class="btn btn-success btn-sm">Terminer</a>
                        <?php endif; ?>
                        <a href="examens.php?delete=<?= $ex['id_examen'] ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete()">Suppr.</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$examens): ?><tr><td colspan="7" style="text-align:center;color:#90a4ae;">Aucun examen enregistré</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="modalExamen">
    <div class="modal-box">
        <form method="POST">
            <div class="modal-header"><h3>Nouvel examen</h3><button type="button" class="close-btn" onclick="closeModal('modalExamen')">&times;</button></div>
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group"><label>Patient</label>
                        <select name="id_patient" required>
                            <option value="">-- Choisir --</option>
                            <?php foreach ($patients_list as $p): ?><option value="<?= $p['id_patient'] ?>"><?= e($p['nom'].' '.$p['prenom']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Médecin prescripteur</label>
                        <select name="id_medecin">
                            <option value="">-- Non spécifié --</option>
                            <?php foreach ($medecins_list as $m): ?><option value="<?= $m['id_medecin'] ?>">Dr <?= e($m['nom'].' '.$m['prenom']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Type d'examen</label><input type="text" name="type_examen" placeholder="ex: Test de paludisme (GE/TDR)" required></div>
                    <div class="form-group"><label>Date de l'examen</label><input type="date" name="date_examen" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="form-group"><label>Statut</label>
                        <select name="statut">
                            <option value="en_attente">En attente</option>
                            <option value="termine">Terminé</option>
                            <option value="annule">Annulé</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:1/-1;"><label>Résultat</label><textarea name="resultat" rows="2"></textarea></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalExamen')">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
