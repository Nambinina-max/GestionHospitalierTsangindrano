<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$page_title = 'Médecins';
$msg = '';
$msg_type = 'success';

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM medecins WHERE id_medecin = ?")->execute([(int)$_GET['delete']]);
    header('Location: medecins.php?ok=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_medecin'] ?? '';
    $data = [trim($_POST['nom']), trim($_POST['prenom']), trim($_POST['specialite']), trim($_POST['telephone']), trim($_POST['email']), trim($_POST['disponibilite'])];

    $result = handle_image_upload($_FILES['photo'] ?? null, 'medecins', 'medecin');
    if ($result && isset($result['error'])) {
        $msg = $result['error'];
        $msg_type = 'error';
    } else {
        $nouvelle_photo = $result['path'] ?? null;

        if ($id) {
            if ($nouvelle_photo) {
                $pdo->prepare("UPDATE medecins SET nom=?, prenom=?, specialite=?, telephone=?, email=?, disponibilite=?, photo=? WHERE id_medecin=?")
                    ->execute([...$data, $nouvelle_photo, $id]);
            } else {
                $pdo->prepare("UPDATE medecins SET nom=?, prenom=?, specialite=?, telephone=?, email=?, disponibilite=? WHERE id_medecin=?")
                    ->execute([...$data, $id]);
            }
        } else {
            $pdo->prepare("INSERT INTO medecins (nom, prenom, specialite, telephone, email, disponibilite, photo) VALUES (?,?,?,?,?,?,?)")
                ->execute([...$data, $nouvelle_photo]);
        }
        header('Location: medecins.php?ok=1');
        exit;
    }
}

$medecins = $pdo->query("SELECT * FROM medecins ORDER BY nom")->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?><div class="alert alert-success">Opération effectuée avec succès.</div><?php endif; ?>
<?php if ($msg): ?><div class="alert alert-<?= $msg_type === 'error' ? 'error' : 'success' ?>"><?= e($msg) ?></div><?php endif; ?>

<div class="card">
    <div class="toolbar">
        <strong>Personnel médical</strong>
        <button class="btn btn-primary" onclick="openMedecinModal()">+ Nouveau médecin</button>
    </div>
    <div class="grid-cards">
        <?php foreach ($medecins as $m): ?>
            <div class="stat-card" style="border-left-color:var(--vert-sante);align-items:flex-start;">
                <div class="icon-box" style="background:var(--vert-sante);overflow:hidden;padding:0;">
                    <?php if (!empty($m['photo'])): ?>
                        <img src="<?= e($m['photo']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        👨‍⚕️
                    <?php endif; ?>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:700;">Dr <?= e($m['nom'] . ' ' . $m['prenom']) ?></div>
                    <div class="lbl"><?= e($m['specialite']) ?></div>
                    <div style="font-size:12px;color:#78909c;margin-top:4px;">📞 <?= e($m['telephone']) ?: '-' ?></div>
                    <div style="font-size:12px;color:#78909c;">🕐 <?= e($m['disponibilite']) ?></div>
                    <div style="margin-top:8px;">
                        <button class="btn btn-secondary btn-sm" onclick='editMedecin(<?= json_encode($m, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Modifier</button>
                        <a href="medecins.php?delete=<?= $m['id_medecin'] ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete()">Suppr.</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$medecins): ?><p style="color:#90a4ae;">Aucun médecin enregistré</p><?php endif; ?>
    </div>
</div>

<div class="modal-overlay" id="modalMedecin">
    <div class="modal-box">
        <form method="POST" enctype="multipart/form-data">
            <div class="modal-header"><h3 id="modalMedecinTitle">Nouveau médecin</h3><button type="button" class="close-btn" onclick="closeModal('modalMedecin')">&times;</button></div>
            <div class="modal-body">
                <input type="hidden" name="id_medecin" id="fd_id">
                <div class="profile-photo-row" id="fd_photo_row" style="display:none;">
                    <div class="profile-photo-preview" id="fd_photo_preview"></div>
                    <div style="font-size:11.5px;color:#90a4ae;">Photo actuelle — choisissez un fichier ci-dessous pour la remplacer</div>
                </div>
                <div class="form-grid">
                    <div class="form-group"><label>Nom</label><input type="text" name="nom" id="fd_nom" required></div>
                    <div class="form-group"><label>Prénom</label><input type="text" name="prenom" id="fd_prenom" required></div>
                    <div class="form-group"><label>Spécialité</label><input type="text" name="specialite" id="fd_specialite"></div>
                    <div class="form-group"><label>Téléphone</label><input type="text" name="telephone" id="fd_telephone"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" id="fd_email"></div>
                    <div class="form-group"><label>Disponibilité</label><input type="text" name="disponibilite" id="fd_dispo"></div>
                    <div class="form-group"><label>Photo</label><input type="file" name="photo" accept="image/png,image/jpeg,image/gif,image/webp"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalMedecin')">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
function openMedecinModal() {
    document.getElementById('modalMedecinTitle').innerText = 'Nouveau médecin';
    document.querySelector('#modalMedecin form').reset();
    document.getElementById('fd_id').value = '';
    document.getElementById('fd_photo_row').style.display = 'none';
    openModal('modalMedecin');
}
function editMedecin(m) {
    document.getElementById('modalMedecinTitle').innerText = 'Modifier le médecin';
    document.getElementById('fd_id').value = m.id_medecin;
    document.getElementById('fd_nom').value = m.nom;
    document.getElementById('fd_prenom').value = m.prenom;
    document.getElementById('fd_specialite').value = m.specialite ?? '';
    document.getElementById('fd_telephone').value = m.telephone ?? '';
    document.getElementById('fd_email').value = m.email ?? '';
    document.getElementById('fd_dispo').value = m.disponibilite ?? '';
    const row = document.getElementById('fd_photo_row');
    const preview = document.getElementById('fd_photo_preview');
    if (m.photo) {
        preview.innerHTML = '<img src="' + m.photo + '" alt="">';
        row.style.display = 'flex';
    } else {
        row.style.display = 'none';
    }
    openModal('modalMedecin');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
