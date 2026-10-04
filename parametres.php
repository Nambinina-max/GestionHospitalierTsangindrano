<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$page_title = 'Paramètres';
$msg = '';
$msg_type = 'success';

// --- Mise à jour des informations générales (admin uniquement) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'infos' && is_admin()) {
    foreach (['nom_hopital', 'adresse', 'telephone', 'email'] as $cle) {
        $pdo->prepare("UPDATE parametres SET valeur = ? WHERE cle = ?")->execute([trim($_POST[$cle]), $cle]);
    }
    $msg = "Informations mises à jour.";
}

// --- Changement de mot de passe (utilisateur courant) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'password') {
    $me = current_user();
    if (!password_verify($_POST['ancien'], $me['mot_de_passe'])) {
        $msg = "Erreur : ancien mot de passe incorrect."; $msg_type = 'error';
    } elseif ($_POST['nouveau'] !== $_POST['confirmation']) {
        $msg = "Erreur : la confirmation ne correspond pas."; $msg_type = 'error';
    } else {
        $hash = password_hash($_POST['nouveau'], PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id_utilisateur = ?")->execute([$hash, $me['id_utilisateur']]);
        $msg = "Mot de passe modifié avec succès.";
    }
}

// --- Photo de profil (utilisateur courant) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'photo_profil') {
    $result = handle_image_upload($_FILES['photo'] ?? null, 'users', 'user');
    if ($result && isset($result['error'])) {
        $msg = $result['error']; $msg_type = 'error';
    } elseif ($result && isset($result['path'])) {
        $pdo->prepare("UPDATE utilisateurs SET photo = ? WHERE id_utilisateur = ?")->execute([$result['path'], $_SESSION['user_id']]);
        $msg = "Photo de profil mise à jour.";
    } else {
        $msg = "Veuillez choisir une image."; $msg_type = 'error';
    }
}

// --- Photo d'un autre utilisateur (admin uniquement) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'photo_utilisateur' && is_admin()) {
    $uid = (int)$_POST['id_utilisateur'];
    $result = handle_image_upload($_FILES['photo'] ?? null, 'users', 'user');
    if ($result && isset($result['error'])) {
        $msg = $result['error']; $msg_type = 'error';
    } elseif ($result && isset($result['path'])) {
        $pdo->prepare("UPDATE utilisateurs SET photo = ? WHERE id_utilisateur = ?")->execute([$result['path'], $uid]);
        $msg = "Photo mise à jour.";
    }
}

// --- Ajout d'un nouvel utilisateur (admin uniquement) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'nouvel_utilisateur' && is_admin()) {
    $hash = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO utilisateurs (nom_complet, nom_utilisateur, mot_de_passe, role, email, telephone) VALUES (?,?,?,?,?,?)")
        ->execute([trim($_POST['nom_complet']), trim($_POST['nom_utilisateur']), $hash, $_POST['role'], trim($_POST['email']), trim($_POST['telephone'])]);
    $msg = "Nouvel utilisateur créé.";
}

// --- Personnalisation des icônes du menu (admin uniquement) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'icones' && is_admin()) {
    $cles = $pdo->query("SELECT cle_menu FROM icones_menu")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($cles as $cle) {
        $type = $_POST['icon_type'][$cle] ?? 'emoji';
        if ($type === 'image' && !empty($_FILES['icon_file']['name'][$cle])) {
            $fileArr = [
                'name' => $_FILES['icon_file']['name'][$cle],
                'type' => $_FILES['icon_file']['type'][$cle],
                'tmp_name' => $_FILES['icon_file']['tmp_name'][$cle],
                'error' => $_FILES['icon_file']['error'][$cle],
                'size' => $_FILES['icon_file']['size'][$cle],
            ];
            $result = handle_image_upload($fileArr, 'icons', 'icon');
            if ($result && isset($result['path'])) {
                $pdo->prepare("UPDATE icones_menu SET type='image', valeur=? WHERE cle_menu=?")->execute([$result['path'], $cle]);
            } elseif ($result && isset($result['error'])) {
                $msg = $cle . ' : ' . $result['error']; $msg_type = 'error';
            }
        } elseif ($type === 'emoji' && trim($_POST['icon_emoji'][$cle] ?? '') !== '') {
            $pdo->prepare("UPDATE icones_menu SET type='emoji', valeur=? WHERE cle_menu=?")->execute([trim($_POST['icon_emoji'][$cle]), $cle]);
        }
    }
    if (!$msg) $msg = "Icônes du menu mises à jour.";
}

$params = $pdo->query("SELECT * FROM parametres")->fetchAll(PDO::FETCH_KEY_PAIR);
$utilisateurs = is_admin() ? $pdo->query("SELECT * FROM utilisateurs ORDER BY nom_complet")->fetchAll() : [];
$icones = is_admin() ? $pdo->query("SELECT * FROM icones_menu ORDER BY id_icone")->fetchAll() : [];
$me = current_user();

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($msg): ?><div class="alert alert-<?= $msg_type === 'error' ? 'error' : 'success' ?>"><?= e($msg) ?></div><?php endif; ?>

<div class="card">
    <h3>Ma photo de profil</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="photo_profil">
        <div class="profile-photo-row">
            <div class="profile-photo-preview">
                <?php if (!empty($me['photo'])): ?>
                    <img src="<?= e($me['photo']) ?>" alt="">
                <?php else: ?>
                    <?= e(strtoupper(substr($me['nom_complet'] ?? 'A', 0, 1))) ?>
                <?php endif; ?>
            </div>
            <div>
                <input type="file" name="photo" accept="image/png,image/jpeg,image/gif,image/webp" required>
                <div style="font-size:11.5px;color:#90a4ae;margin-top:6px;">JPG, PNG, GIF ou WEBP — 2 Mo max</div>
                <button type="submit" class="btn btn-primary btn-sm" style="margin-top:10px;">Mettre à jour ma photo</button>
            </div>
        </div>
    </form>
</div>

<?php if (is_admin()): ?>
<div class="card">
    <h3>Informations générales du centre de santé</h3>
    <form method="POST">
        <input type="hidden" name="action" value="infos">
        <div class="form-grid">
            <div class="form-group"><label>Nom du centre</label><input type="text" name="nom_hopital" value="<?= e($params['nom_hopital'] ?? '') ?>"></div>
            <div class="form-group"><label>Adresse</label><input type="text" name="adresse" value="<?= e($params['adresse'] ?? '') ?>"></div>
            <div class="form-group"><label>Téléphone</label><input type="text" name="telephone" value="<?= e($params['telephone'] ?? '') ?>"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= e($params['email'] ?? '') ?>"></div>
        </div>
        <div style="margin-top:16px;"><button class="btn btn-primary" type="submit">Enregistrer</button></div>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <h3>Changer mon mot de passe</h3>
    <form method="POST" style="max-width:400px;">
        <input type="hidden" name="action" value="password">
        <div class="form-group" style="margin-bottom:12px;"><label>Ancien mot de passe</label><input type="password" name="ancien" required></div>
        <div class="form-group" style="margin-bottom:12px;"><label>Nouveau mot de passe</label><input type="password" name="nouveau" required></div>
        <div class="form-group" style="margin-bottom:12px;"><label>Confirmation</label><input type="password" name="confirmation" required></div>
        <button class="btn btn-primary" type="submit">Modifier</button>
    </form>
</div>

<?php if (is_admin()): ?>
<div class="card">
    <h3>Personnalisation des icônes du menu</h3>
    <p style="color:#78909c;font-size:13px;margin-top:-8px;">
        Pour chaque élément du menu, choisissez soit un emoji/caractère, soit une image (elle sera utilisée à la place de l'emoji).
    </p>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="icones">
        <div class="icon-editor-grid">
            <?php foreach ($icones as $ic): ?>
                <div class="icon-editor-item">
                    <div class="current-icon">
                        <?php if ($ic['type'] === 'image'): ?>
                            <img src="<?= e($ic['valeur']) ?>" alt="">
                        <?php else: ?>
                            <?= e($ic['valeur']) ?>
                        <?php endif; ?>
                    </div>
                    <div class="icon-fields">
                        <label><?= e($ic['libelle']) ?></label>
                        <input type="hidden" name="icon_type[<?= e($ic['cle_menu']) ?>]" value="emoji" id="type_<?= e($ic['cle_menu']) ?>">
                        <input type="text" name="icon_emoji[<?= e($ic['cle_menu']) ?>]"
                               placeholder="Emoji, ex: 🏠"
                               value="<?= $ic['type'] === 'emoji' ? e($ic['valeur']) : '' ?>"
                               onfocus="document.getElementById('type_<?= e($ic['cle_menu']) ?>').value='emoji'">
                        <input type="file" name="icon_file[<?= e($ic['cle_menu']) ?>]" accept="image/*"
                               onchange="document.getElementById('type_<?= e($ic['cle_menu']) ?>').value='image'">
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top:18px;"><button type="submit" class="btn btn-primary">Enregistrer les icônes</button></div>
    </form>
</div>
<?php endif; ?>

<?php if (is_admin()): ?>
<div class="card">
    <div class="toolbar">
        <h3 style="margin:0;">Comptes utilisateurs</h3>
        <button class="btn btn-primary" onclick="openModal('modalUser')">+ Nouvel utilisateur</button>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Photo</th><th>Nom complet</th><th>Identifiant</th><th>Rôle</th><th>Email</th><th>Statut</th></tr></thead>
            <tbody>
            <?php foreach ($utilisateurs as $u): ?>
                <tr>
                    <td>
                        <form method="POST" enctype="multipart/form-data" style="display:flex;align-items:center;gap:8px;">
                            <input type="hidden" name="action" value="photo_utilisateur">
                            <input type="hidden" name="id_utilisateur" value="<?= $u['id_utilisateur'] ?>">
                            <div class="profile-photo-preview" style="width:34px;height:34px;font-size:13px;">
                                <?php if (!empty($u['photo'])): ?>
                                    <img src="<?= e($u['photo']) ?>" alt="">
                                <?php else: ?>
                                    <?= e(strtoupper(substr($u['nom_complet'], 0, 1))) ?>
                                <?php endif; ?>
                            </div>
                            <input type="file" name="photo" accept="image/*" style="width:110px;font-size:10.5px;"
                                   onchange="this.form.submit()">
                        </form>
                    </td>
                    <td><?= e($u['nom_complet']) ?></td>
                    <td><?= e($u['nom_utilisateur']) ?></td>
                    <td><span class="badge info"><?= e(ucfirst($u['role'])) ?></span></td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="badge <?= $u['actif'] ? 'ok' : 'danger' ?>"><?= $u['actif'] ? 'Actif' : 'Inactif' ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="modalUser">
    <div class="modal-box">
        <form method="POST">
            <input type="hidden" name="action" value="nouvel_utilisateur">
            <div class="modal-header"><h3>Nouvel utilisateur</h3><button type="button" class="close-btn" onclick="closeModal('modalUser')">&times;</button></div>
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group"><label>Nom complet</label><input type="text" name="nom_complet" required></div>
                    <div class="form-group"><label>Identifiant</label><input type="text" name="nom_utilisateur" required></div>
                    <div class="form-group"><label>Mot de passe</label><input type="password" name="mot_de_passe" required></div>
                    <div class="form-group"><label>Rôle</label>
                        <select name="role">
                            <option value="receptionniste">Réceptionniste</option>
                            <option value="medecin">Médecin</option>
                            <option value="pharmacien">Pharmacien</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Email</label><input type="email" name="email"></div>
                    <div class="form-group"><label>Téléphone</label><input type="text" name="telephone"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalUser')">Annuler</button>
                <button type="submit" class="btn btn-primary">Créer</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
