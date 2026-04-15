<?php
include('../includes/auth.php');
include('../includes/config.php');
if ($_SESSION['role'] != 'CRMA') { header('Location: ../pages/login.php'); exit(); }

$page_title = 'Tiers adversaires';
$success = $error = '';

/* ======= AJOUTER ======= */
if (isset($_POST['ajouter'])) {
    $id_personne = intval($_POST['id_personne']);
    $compagnie   = mysqli_real_escape_string($conn, trim($_POST['compagnie_assurance']));
    $police      = mysqli_real_escape_string($conn, trim($_POST['numero_police']));
    $resp        = $_POST['responsable'];

    $chk = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_tiers FROM tiers WHERE id_personne=$id_personne"))['id_tiers'] ?? 0;
    if ($chk) {
        $error = "Cette personne est déjà enregistrée comme tiers.";
    } else {
        mysqli_query($conn, "INSERT INTO tiers (id_personne,compagnie_assurance,numero_police,responsable)
            VALUES ($id_personne,'$compagnie','$police','$resp')");
        $success = "Tiers ajouté avec succès.";
    }
}

/* ======= MODIFIER ======= */
if (isset($_POST['modifier'])) {
    $id_tiers  = intval($_POST['id_tiers']);
    $compagnie = mysqli_real_escape_string($conn, trim($_POST['compagnie_assurance']));
    $police    = mysqli_real_escape_string($conn, trim($_POST['numero_police']));
    $resp      = $_POST['responsable'];
    mysqli_query($conn, "UPDATE tiers SET compagnie_assurance='$compagnie',
        numero_police='$police',responsable='$resp' WHERE id_tiers=$id_tiers");
    $success = "Tiers modifié.";
}

/* ======= SUPPRIMER ======= */
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $usage = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) n FROM dossier WHERE id_tiers=$id"))['n'];
    if ($usage > 0) {
        $error = "Impossible : ce tiers est lié à des dossiers.";
    } else {
        mysqli_query($conn, "DELETE FROM tiers WHERE id_tiers=$id");
        $success = "Tiers supprimé.";
    }
}

$tiers_list = mysqli_query($conn, "
    SELECT t.*,p.nom,p.prenom,p.telephone,p.adresse,
           (SELECT COUNT(*) FROM dossier d WHERE d.id_tiers=t.id_tiers) as nb_dossiers
    FROM tiers t JOIN personne p ON t.id_personne=p.id_personne
    ORDER BY t.id_tiers DESC");
$total = mysqli_num_rows($tiers_list);

/* Personnes adversaires disponibles */
$personnes_adversaire = mysqli_query($conn,
    "SELECT id_personne, nom, prenom, telephone, adresse
     FROM personne
     WHERE statut_personne='adversaire'
       AND id_personne NOT IN (SELECT id_personne FROM tiers WHERE id_personne IS NOT NULL)
     ORDER BY nom");

$edit = null;
if (isset($_GET['edit'])) {
    $edit = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT t.*,p.nom,p.prenom,p.telephone,p.adresse
         FROM tiers t JOIN personne p ON t.id_personne=p.id_personne
         WHERE t.id_tiers=".intval($_GET['edit'])));
}

$resp_badge = [
    'oui'     => ['badge-red',   'Responsable'],
    'non'     => ['badge-green', 'Non responsable'],
    'partiel' => ['badge-amber', 'Partiel'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Tiers adversaires — CRMA</title>
<link rel="stylesheet" href="../css/style_crma.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ===== MODAL ===== */
.modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.45); z-index: 900;
    align-items: center; justify-content: center;
}
.modal-overlay.open { display: flex; }
.modal-box {
    background: #fff; border-radius: 18px;
    padding: 36px 38px;
    width: 580px; max-width: 96vw; max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 24px 70px rgba(0,0,0,.22);
}
.modal-box h3 {
    font-size: 17px; font-weight: 700; margin-bottom: 26px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--gray-100);
    display: flex; align-items: center; gap: 10px;
    color: var(--gray-800);
}

.modal-box .form-group { margin-bottom: 20px; }
.modal-box .form-group label {
    display: block; font-size: 11.5px; font-weight: 700;
    color: var(--gray-500); text-transform: uppercase;
    letter-spacing: .5px; margin-bottom: 7px;
}
.modal-box .form-group input,
.modal-box .form-group select {
    width: 100%; padding: 11px 14px;
    border: 1.5px solid var(--gray-200);
    border-radius: 10px; font-size: 14px;
    font-family: 'DM Sans', sans-serif;
    color: var(--gray-800); background: var(--gray-50);
    transition: all .18s;
}
.modal-box .form-group input:focus,
.modal-box .form-group select:focus {
    border-color: var(--amber-600); outline: none;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(217,119,6,.12);
}
.modal-box .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.modal-box .btn-row { display: flex; gap: 12px; margin-top: 28px; }
.modal-box .btn-row .btn { flex: 1; justify-content: center; padding: 13px; font-size: 14px; }

.person-hint {
    margin-top: 8px; padding: 10px 14px;
    background: var(--amber-50); border: 1px solid var(--amber-100);
    border-radius: 8px; font-size: 12.5px; color: #78350f;
    display: none;
}
.person-hint.visible { display: block; }
</style>
</head>
<body>
<?php include('../includes/sidebar.php'); ?>
<?php include('../includes/header.php'); ?>

<div class="crma-main">
<div class="page-heading">
    <div>
        <h1><i class="fa fa-user-shield"></i> Tiers adversaires</h1>
        <p class="sub">Gestion des tiers impliqués dans les sinistres</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modal-add')">
        <i class="fa fa-plus"></i> Nouveau tiers
    </button>
</div>

<?php if ($success) echo "<div class='msg msg-success'><i class='fa fa-check-circle'></i> $success</div>"; ?>
<?php if ($error)   echo "<div class='msg msg-error'><i class='fa fa-exclamation-circle'></i> $error</div>"; ?>

<div class="crma-table-wrapper">
    <div class="table-toolbar">
        <span style="font-size:13px;color:var(--gray-500)"><?= $total ?> tiers</span>
    </div>
    <table class="crma-table">
        <thead>
            <tr><th>Nom</th><th>Téléphone</th><th>Compagnie</th><th>N° Police</th><th>Responsabilité</th><th>Dossiers</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($t = mysqli_fetch_assoc($tiers_list)):
            $rb = $resp_badge[$t['responsable']] ?? ['badge-gray','—'];
        ?>
        <tr>
            <td><div style="font-weight:500"><?= htmlspecialchars($t['nom'].' '.$t['prenom']) ?></div></td>
            <td class="num-cell"><?= htmlspecialchars($t['telephone']) ?></td>
            <td><?= htmlspecialchars($t['compagnie_assurance']) ?></td>
            <td class="num-cell" style="font-size:12px"><?= htmlspecialchars($t['numero_police']) ?></td>
            <td><span class="badge <?= $rb[0] ?>"><?= $rb[1] ?></span></td>
            <td style="text-align:center"><span class="badge badge-blue"><?= $t['nb_dossiers'] ?></span></td>
            <td>
                <div style="display:flex;gap:4px">
                    <a href="?edit=<?= $t['id_tiers'] ?>" class="btn btn-outline btn-xs"><i class="fa fa-pen"></i></a>
                    <?php if ($t['nb_dossiers'] == 0): ?>
                    <a href="?del=<?= $t['id_tiers'] ?>"
                       class="btn btn-xs btn-danger"
                       onclick="return confirm('Supprimer ce tiers ?')"><i class="fa fa-trash"></i></a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($total == 0): ?>
        <tr><td colspan="7"><div class="empty-state"><i class="fa fa-user-shield"></i><p>Aucun tiers</p></div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ====== MODAL AJOUTER ====== -->
<div class="modal-overlay" id="modal-add">
<div class="modal-box">
    <h3><i class="fa fa-user-plus" style="color:var(--amber-600)"></i> Nouveau tiers adversaire</h3>

    <p style="font-size:13px;color:var(--gray-500);margin:-10px 0 20px;padding:12px 14px;background:var(--amber-50);border-radius:9px;border-left:3px solid var(--amber-600);">
        <i class="fa fa-info-circle" style="color:var(--amber-600)"></i>
        Sélectionnez une personne avec le statut <b>Adversaire</b> déjà enregistrée.
        <a href="gerer_personnes.php" style="color:var(--amber-600);font-weight:600;">Ajouter une personne</a>
    </p>

    <form method="POST">
        <div class="form-group">
            <label>Personne (statut Adversaire) <span style="color:red">*</span></label>
            <select name="id_personne" required onchange="showTiersHint(this)">
                <option value="">— Sélectionner —</option>
                <?php while ($p = mysqli_fetch_assoc($personnes_adversaire)): ?>
                <option value="<?= $p['id_personne'] ?>"
                        data-tel="<?= htmlspecialchars($p['telephone'] ?? '') ?>"
                        data-adr="<?= htmlspecialchars($p['adresse'] ?? '') ?>">
                    <?= htmlspecialchars($p['nom'].' '.$p['prenom']) ?>
                </option>
                <?php endwhile; ?>
            </select>
            <div class="person-hint" id="hint_tiers">
                <i class="fa fa-phone"></i> <span id="hint_tiers_tel"></span>
                <span id="hint_tiers_adr" style="margin-left:14px;"><i class="fa fa-map-marker-alt"></i> <span id="hint_tiers_adr_text"></span></span>
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Compagnie d'assurance</label>
                <input type="text" name="compagnie_assurance" placeholder="Ex: SAA, CAAR…">
            </div>
            <div class="form-group">
                <label>N° police adverse</label>
                <input type="text" name="numero_police" placeholder="Ex: SAA123456">
            </div>
        </div>

        <div class="form-group">
            <label>Responsabilité du tiers <span style="color:red">*</span></label>
            <select name="responsable" required>
                <option value="">— Choisir —</option>
                <option value="oui">Le tiers est responsable</option>
                <option value="non">Le tiers n'est pas responsable</option>
                <option value="partiel">Responsabilité partagée</option>
            </select>
        </div>

        <div class="btn-row">
            <button type="submit" name="ajouter" class="btn btn-primary">
                <i class="fa fa-save"></i> Ajouter le tiers
            </button>
            <button type="button" class="btn btn-outline" onclick="closeModal('modal-add')">Annuler</button>
        </div>
    </form>
</div>
</div>

<!-- ====== MODAL MODIFIER ====== -->
<?php if ($edit): ?>
<div class="modal-overlay open" id="modal-edit">
<div class="modal-box">
    <h3><i class="fa fa-pen" style="color:var(--amber-600)"></i> Modifier le tiers</h3>
    <form method="POST">
        <input type="hidden" name="id_tiers" value="<?= $edit['id_tiers'] ?>">

        <div class="form-group">
            <label>Personne (non modifiable)</label>
            <input type="text" value="<?= htmlspecialchars($edit['nom'].' '.$edit['prenom']) ?>"
                   readonly style="background:var(--gray-100);color:var(--gray-500);cursor:not-allowed;">
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Compagnie</label>
                <input type="text" name="compagnie_assurance" value="<?= htmlspecialchars($edit['compagnie_assurance']) ?>">
            </div>
            <div class="form-group">
                <label>N° police</label>
                <input type="text" name="numero_police" value="<?= htmlspecialchars($edit['numero_police']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Responsabilité <span style="color:red">*</span></label>
            <select name="responsable" required>
                <option value="oui"     <?= $edit['responsable']=='oui'    ?'selected':'' ?>>Le tiers est responsable</option>
                <option value="non"     <?= $edit['responsable']=='non'    ?'selected':'' ?>>Le tiers n'est pas responsable</option>
                <option value="partiel" <?= $edit['responsable']=='partiel'?'selected':'' ?>>Responsabilité partagée</option>
            </select>
        </div>

        <div class="btn-row">
            <button type="submit" name="modifier" class="btn btn-primary"><i class="fa fa-save"></i> Modifier</button>
            <a href="gerer_tiers.php" class="btn btn-outline">Annuler</a>
        </div>
    </form>
</div>
</div>
<?php endif; ?>

</div>
<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if(e.target===m) m.classList.remove('open'); });
});

function showTiersHint(sel) {
    const opt  = sel.options[sel.selectedIndex];
    const hint = document.getElementById('hint_tiers');
    if (!opt.value) { hint.classList.remove('visible'); return; }
    document.getElementById('hint_tiers_tel').textContent      = opt.getAttribute('data-tel') || '—';
    document.getElementById('hint_tiers_adr_text').textContent = opt.getAttribute('data-adr') || '—';
    hint.classList.add('visible');
}
</script>
</body>
</html>