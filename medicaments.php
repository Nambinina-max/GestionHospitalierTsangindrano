<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$page_title = 'Médicaments';

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM medicaments WHERE id_medicament = ?")->execute([(int)$_GET['delete']]);
    header('Location: medicaments.php?ok=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_medicament'] ?? '';
    $data = [
        trim($_POST['nom']), trim($_POST['forme']), trim($_POST['dosage']),
        (int)$_POST['quantite_stock'], (int)$_POST['seuil_alerte'], (float)$_POST['prix_unitaire'],
        $_POST['date_peremption'] ?: null, trim($_POST['fournisseur']),
    ];
    if ($id) {
        $pdo->prepare("UPDATE medicaments SET nom=?, forme=?, dosage=?, quantite_stock=?, seuil_alerte=?, prix_unitaire=?, date_peremption=?, fournisseur=? WHERE id_medicament=?")
            ->execute([...$data, $id]);
    } else {
        $pdo->prepare("INSERT INTO medicaments (nom, forme, dosage, quantite_stock, seuil_alerte, prix_unitaire, date_peremption, fournisseur) VALUES (?,?,?,?,?,?,?,?)")
            ->execute($data);
    }
    header('Location: medicaments.php?ok=1');
    exit;
}

$search = trim($_GET['q'] ?? '');
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM medicaments WHERE nom LIKE ? ORDER BY nom");
    $stmt->execute(["%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM medicaments ORDER BY nom");
}
$medicaments = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?><div class="alert alert-success">Opération effectuée avec succès.</div><?php endif; ?>

<div class="card">
    <div class="toolbar">
        <form class="search-box" method="get"><input type="text" name="q" placeholder="Rechercher un médicament..." value="<?= e($search) ?>"></form>
        <button class="btn btn-primary" onclick="openMedModal()">+ Nouveau médicament</button>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Nom</th><th>Forme</th><th>Dosage</th><th>Stock</th><th>Seuil</th><th>Prix unit.</th><th>Péremption</th><th>Fournisseur</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($medicaments as $m):
                $bas = $m['quantite_stock'] <= $m['seuil_alerte'];
                $bientot_perime = $m['date_peremption'] && strtotime($m['date_peremption']) < strtotime('+3 months');
            ?>
                <tr>
                    <td><?= e($m['nom']) ?></td>
                    <td><?= e($m['forme']) ?></td>
                    <td><?= e($m['dosage']) ?></td>
                    <td><span class="badge <?= $bas ? 'danger' : 'ok' ?>"><?= $m['quantite_stock'] ?></span></td>
                    <td><?= $m['seuil_alerte'] ?></td>
                    <td><?= number_format($m['prix_unitaire'], 0, ',', ' ') ?> Ar</td>
                    <td>
                        <?= $m['date_peremption'] ? date('d/m/Y', strtotime($m['date_peremption'])) : '-' ?>
                        <?php if ($bientot_perime): ?><span class="badge warn">Proche</span><?php endif; ?>
                    </td>
                    <td><?= e($m['fournisseur']) ?></td>
                    <td>
                        <button class="btn btn-secondary btn-sm" onclick='editMed(<?= json_encode($m, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Modifier</button>
                        <a href="medicaments.php?delete=<?= $m['id_medicament'] ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete()">Suppr.</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$medicaments): ?><tr><td colspan="9" style="text-align:center;color:#90a4ae;">Aucun médicament enregistré</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="modalMed">
    <div class="modal-box">
        <form method="POST">
            <div class="modal-header"><h3 id="modalMedTitle">Nouveau médicament</h3><button type="button" class="close-btn" onclick="closeModal('modalMed')">&times;</button></div>
            <div class="modal-body">
                <input type="hidden" name="id_medicament" id="fm_id">
                <div class="form-grid">
                    <div class="form-group"><label>Nom</label><input type="text" name="nom" id="fm_nom" required></div>
                    <div class="form-group"><label>Forme</label><input type="text" name="forme" id="fm_forme" placeholder="Comprimé, Sirop..."></div>
                    <div class="form-group"><label>Dosage</label><input type="text" name="dosage" id="fm_dosage" placeholder="ex: 500mg"></div>
                    <div class="form-group"><label>Quantité en stock</label><input type="number" name="quantite_stock" id="fm_stock" required></div>
                    <div class="form-group"><label>Seuil d'alerte</label><input type="number" name="seuil_alerte" id="fm_seuil" value="10"></div>
                    <div class="form-group"><label>Prix unitaire (Ar)</label><input type="number" step="0.01" name="prix_unitaire" id="fm_prix"></div>
                    <div class="form-group"><label>Date de péremption</label><input type="date" name="date_peremption" id="fm_perime"></div>
                    <div class="form-group"><label>Fournisseur</label><input type="text" name="fournisseur" id="fm_fourn"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalMed')">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
function openMedModal() {
    document.getElementById('modalMedTitle').innerText = 'Nouveau médicament';
    document.querySelector('#modalMed form').reset();
    document.getElementById('fm_id').value = '';
    openModal('modalMed');
}
function editMed(m) {
    document.getElementById('modalMedTitle').innerText = 'Modifier le médicament';
    document.getElementById('fm_id').value = m.id_medicament;
    document.getElementById('fm_nom').value = m.nom;
    document.getElementById('fm_forme').value = m.forme ?? '';
    document.getElementById('fm_dosage').value = m.dosage ?? '';
    document.getElementById('fm_stock').value = m.quantite_stock;
    document.getElementById('fm_seuil').value = m.seuil_alerte;
    document.getElementById('fm_prix').value = m.prix_unitaire;
    document.getElementById('fm_perime').value = m.date_peremption ?? '';
    document.getElementById('fm_fourn').value = m.fournisseur ?? '';
    openModal('modalMed');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
