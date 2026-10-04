<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$page_title = 'Analyse IA';

$resultat = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_patient = $_POST['id_patient'];
    $age = (int)($_POST['age'] ?? 0);
    $poids = (float)($_POST['poids'] ?? 0);
    $temperature = (float)($_POST['temperature'] ?? 0);
    $tension = trim($_POST['tension_arterielle'] ?? '');
    $pouls = (int)($_POST['pouls'] ?? 0);
    $symptomes = strtolower(trim($_POST['symptomes'] ?? ''));

    // --- Moteur d'analyse assistée à base de règles cliniques (heuristique) ---
    $alertes = [];
    $score = 0;

    if ($temperature >= 39) { $alertes[] = "Fièvre élevée (≥ 39°C) — risque d'infection sévère ou de paludisme grave."; $score += 3; }
    elseif ($temperature >= 38) { $alertes[] = "Fièvre modérée détectée."; $score += 1; }

    if ($pouls > 120) { $alertes[] = "Tachycardie marquée (pouls > 120 bpm)."; $score += 2; }
    elseif ($pouls > 100) { $alertes[] = "Pouls légèrement élevé."; $score += 1; }
    elseif ($pouls > 0 && $pouls < 50) { $alertes[] = "Bradycardie (pouls < 50 bpm) à surveiller."; $score += 2; }

    if ($tension) {
        $parts = preg_split('/[\/\-]/', $tension);
        if (count($parts) === 2) {
            $sys = (int)$parts[0]; $dia = (int)$parts[1];
            if ($sys >= 18 || $dia >= 11) { $alertes[] = "Tension artérielle très élevée — suspicion d'hypertension sévère."; $score += 3; }
            elseif ($sys >= 14 || $dia >= 9) { $alertes[] = "Tension artérielle élevée."; $score += 1; }
            elseif ($sys > 0 && $sys < 9) { $alertes[] = "Tension artérielle basse — risque d'hypotension."; $score += 2; }
        }
    }

    if ($age < 5 && $temperature >= 38.5) { $alertes[] = "Enfant de moins de 5 ans avec fièvre : vigilance accrue recommandée (paludisme, déshydratation)."; $score += 2; }
    if ($age >= 65) { $alertes[] = "Patient âgé (≥ 65 ans) : suivi rapproché conseillé."; $score += 1; }

    $mots_cles_urgents = ['sang', 'convulsion', 'inconscient', 'difficulté respiratoire', 'essoufflement', 'douleur thoracique'];
    foreach ($mots_cles_urgents as $mot) {
        if (strpos($symptomes, $mot) !== false) { $alertes[] = "Symptôme d'alerte mentionné : « $mot » — orienter vers une prise en charge urgente."; $score += 3; }
    }

    if ($score >= 5) $niveau = 'eleve';
    elseif ($score >= 2) $niveau = 'modere';
    else $niveau = 'faible';

    if (!$alertes) $alertes[] = "Aucune anomalie significative détectée sur la base des données saisies.";

    $resultat = ['niveau' => $niveau, 'alertes' => $alertes, 'score' => $score];

    $pdo->prepare("INSERT INTO analyses_ia (id_patient, donnees_entree, resultat_analyse, niveau_risque) VALUES (?,?,?,?)")
        ->execute([$id_patient, json_encode($_POST), implode(' | ', $alertes), $niveau]);
}

$patients_list = $pdo->query("SELECT id_patient, nom, prenom, numero_dossier FROM patients ORDER BY nom")->fetchAll();
$historique = $pdo->query("
    SELECT a.*, p.nom, p.prenom FROM analyses_ia a
    JOIN patients p ON p.id_patient = a.id_patient
    ORDER BY a.date_analyse DESC LIMIT 10
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h3>Analyse assistée des constantes du patient</h3>
    <p style="color:#78909c;font-size:13px;margin-top:-8px;">
        Outil d'aide à la décision basé sur des règles cliniques (seuils de température, tension, pouls, âge, symptômes).
        Il ne remplace pas le jugement médical du praticien.
    </p>
    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>Patient</label>
                <select name="id_patient" required>
                    <option value="">-- Choisir un patient --</option>
                    <?php foreach ($patients_list as $p): ?>
                        <option value="<?= $p['id_patient'] ?>"><?= e($p['nom'] . ' ' . $p['prenom'] . ' (' . $p['numero_dossier'] . ')') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Âge (années)</label><input type="number" name="age" min="0" required></div>
            <div class="form-group"><label>Poids (kg)</label><input type="number" step="0.1" name="poids"></div>
            <div class="form-group"><label>Température (°C)</label><input type="number" step="0.1" name="temperature" required></div>
            <div class="form-group"><label>Tension artérielle</label><input type="text" name="tension_arterielle" placeholder="ex: 12/8"></div>
            <div class="form-group"><label>Pouls (bpm)</label><input type="number" name="pouls"></div>
            <div class="form-group" style="grid-column:1/-1;"><label>Symptômes observés</label><textarea name="symptomes" rows="2" placeholder="ex: fièvre, toux, essoufflement..."></textarea></div>
        </div>
        <div style="margin-top:16px;"><button type="submit" class="btn btn-primary">Lancer l'analyse</button></div>
    </form>

    <?php if ($resultat): ?>
        <div class="risque-box risque-<?= $resultat['niveau'] ?>">
            <strong>Niveau de risque estimé :
                <?= ['faible' => 'FAIBLE', 'modere' => 'MODÉRÉ', 'eleve' => 'ÉLEVÉ'][$resultat['niveau']] ?>
            </strong>
            <ul style="margin:10px 0 0;padding-left:18px;">
                <?php foreach ($resultat['alertes'] as $a): ?><li><?= e($a) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Historique des analyses récentes</h3>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Date</th><th>Patient</th><th>Résultat</th><th>Niveau</th></tr></thead>
            <tbody>
            <?php foreach ($historique as $h): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($h['date_analyse'])) ?></td>
                    <td><?= e($h['nom'] . ' ' . $h['prenom']) ?></td>
                    <td style="max-width:400px;"><?= e($h['resultat_analyse']) ?></td>
                    <td>
                        <?php $b = ['faible'=>'ok','modere'=>'warn','eleve'=>'danger'][$h['niveau_risque']]; ?>
                        <span class="badge <?= $b ?>"><?= ucfirst($h['niveau_risque']) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$historique): ?><tr><td colspan="4" style="text-align:center;color:#90a4ae;">Aucune analyse effectuée</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
