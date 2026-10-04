<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$page_title = 'Gestion des patients';
$msg = '';

// --- Suppression ---
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM patients WHERE id_patient = ?");
    $stmt->execute([(int)$_GET['delete']]);
    header('Location: patients.php?ok=1');
    exit;
}

// --- Ajout / Modification ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_patient'] ?? '';
    $data = [
        'nom' => trim($_POST['nom']),
        'prenom' => trim($_POST['prenom']),
        'date_naissance' => $_POST['date_naissance'] ?: null,
        'sexe' => $_POST['sexe'],
        'adresse' => trim($_POST['adresse']),
        'fokontany' => trim($_POST['fokontany']),
        'telephone' => trim($_POST['telephone']),
        'groupe_sanguin' => trim($_POST['groupe_sanguin']),
        'allergies' => trim($_POST['allergies']),
        'personne_contact' => trim($_POST['personne_contact']),
    ];

    if ($id) {
        $sql = "UPDATE patients SET nom=?, prenom=?, date_naissance=?, sexe=?, adresse=?, fokontany=?, telephone=?, groupe_sanguin=?, allergies=?, personne_contact=? WHERE id_patient=?";
        $pdo->prepare($sql)->execute([...array_values($data), $id]);
    } else {
        $numero = 'P' . date('Ymd') . '-' . str_pad(($pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn() + 1), 4, '0', STR_PAD_LEFT);
        $sql = "INSERT INTO patients (numero_dossier, nom, prenom, date_naissance, sexe, adresse, fokontany, telephone, groupe_sanguin, allergies, personne_contact)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)";
        $pdo->prepare($sql)->execute([$numero, ...array_values($data)]);
    }
    header('Location: patients.php?ok=1');
    exit;
}

$search = trim($_GET['q'] ?? '');
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE nom LIKE ? OR prenom LIKE ? OR numero_dossier LIKE ? ORDER BY date_enregistrement DESC");
    $like = "%$search%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM patients ORDER BY date_enregistrement DESC");
}
$patients = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?><div class="alert alert-success">Opération effectuée avec succès.</div><?php endif; ?>

<div class="card">
    <div class="toolbar">
        <form class="search-box" method="get">
            <input type="text" name="q" placeholder="Rechercher un patient..." value="<?= e($search) ?>">
        </form>
        <button class="btn btn-primary" onclick="openPatientModal()">+ Nouveau patient</button>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>N° dossier</th><th>Nom &amp; prénom</th><th>Sexe</th><th>Naissance</th><th>Fokontany</th><th>Téléphone</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($patients as $p): ?>
                <tr>
                    <td><?= e($p['numero_dossier']) ?></td>
                    <td><?= e($p['nom'] . ' ' . $p['prenom']) ?></td>
                    <td><?= e($p['sexe']) ?></td>
                    <td><?= $p['date_naissance'] ? date('d/m/Y', strtotime($p['date_naissance'])) : '-' ?></td>
                    <td><?= e($p['fokontany']) ?></td>
                    <td><?= e($p['telephone']) ?></td>
                    <td>
                        <a href="dossiers.php?patient=<?= $p['id_patient'] ?>" class="btn btn-secondary btn-sm">Dossier</a>
                        <button class="btn btn-secondary btn-sm" onclick='editPatient(<?= json_encode($p, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Modifier</button>
                        <a href="patients.php?delete=<?= $p['id_patient'] ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete('Supprimer ce patient et tout son dossier médical ?')">Suppr.</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$patients): ?>
                <tr><td colspan="7" style="text-align:center;color:#90a4ae;">Aucun patient trouvé</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modale Patient -->
<div class="modal-overlay" id="modalPatient">
    <div class="modal-box">
        <form method="POST">
            <div class="modal-header">
                <h3 id="modalPatientTitle">Nouveau patient</h3>
                <button type="button" class="close-btn" onclick="closeModal('modalPatient')">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_patient" id="f_id_patient">
                <div class="form-grid">
                    <div class="form-group"><label>Nom</label><input type="text" name="nom" id="f_nom" required></div>
                    <div class="form-group"><label>Prénom</label><input type="text" name="prenom" id="f_prenom" required></div>
                    <div class="form-group"><label>Date de naissance</label><input type="date" name="date_naissance" id="f_date_naissance"></div>
                    <div class="form-group"><label>Sexe</label>
                        <select name="sexe" id="f_sexe"><option value="M">Masculin</option><option value="F">Féminin</option></select>
                    </div>
                    <div class="form-group"><label>Groupe sanguin</label><input type="text" name="groupe_sanguin" id="f_groupe_sanguin" placeholder="ex: O+"></div>
                    <div class="form-group"><label>Téléphone</label><input type="text" name="telephone" id="f_telephone"></div>
                    <div class="form-group"><label>Fokontany</label><input type="text" name="fokontany" id="f_fokontany"></div>
                    <div class="form-group" style="grid-column:1/-1;"><label>Adresse</label><input type="text" name="adresse" id="f_adresse"></div>
                    <div class="form-group" style="grid-column:1/-1;"><label>Allergies connues</label><textarea name="allergies" id="f_allergies" rows="2"></textarea></div>
                    <div class="form-group" style="grid-column:1/-1;"><label>Personne à contacter</label><input type="text" name="personne_contact" id="f_personne_contact"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalPatient')">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPatientModal() {
    document.getElementById('modalPatientTitle').innerText = 'Nouveau patient';
    document.querySelector('#modalPatient form').reset();
    document.getElementById('f_id_patient').value = '';
    openModal('modalPatient');
}
function editPatient(p) {
    document.getElementById('modalPatientTitle').innerText = 'Modifier le patient';
    document.getElementById('f_id_patient').value = p.id_patient;
    document.getElementById('f_nom').value = p.nom;
    document.getElementById('f_prenom').value = p.prenom;
    document.getElementById('f_date_naissance').value = p.date_naissance ?? '';
    document.getElementById('f_sexe').value = p.sexe;
    document.getElementById('f_groupe_sanguin').value = p.groupe_sanguin ?? '';
    document.getElementById('f_telephone').value = p.telephone ?? '';
    document.getElementById('f_fokontany').value = p.fokontany ?? '';
    document.getElementById('f_adresse').value = p.adresse ?? '';
    document.getElementById('f_allergies').value = p.allergies ?? '';
    document.getElementById('f_personne_contact').value = p.personne_contact ?? '';
    openModal('modalPatient');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
