<?php
// gerer_tiers.php — CRUD tiers adversaires
include('../includes/auth.php');
include('../includes/config.php');
if ($_SESSION['role'] != 'CRMA') { header('Location: ../pages/login.php'); exit(); }

$page_title = 'Tiers adversaires';
$success = $error = '';

/* ======= AJOUTER ======= */
if (isset($_POST['ajouter'])) {
    $nom       = mysqli_real_escape_string($conn, trim($_POST['nom']));
    $prenom    = mysqli_real_escape_string($conn, trim($_POST['prenom']));
    $tel       = mysqli_real_escape_string($conn, trim($_POST['telephone']));
    $adr       = mysqli_real_escape_string($conn, trim($_POST['adresse']));
    $compagnie = mysqli_real_escape_string($conn, trim($_POST['compagnie_assurance']));
    $police    = mysqli_real_escape_string($conn, trim($_POST['numero_police']));
    $resp      = $_POST['responsable'];

    mysqli_query($conn, "INSERT INTO personne (type_personne,nom,prenom,telephone,adresse,statut_personne)
        VALUES ('physique','$nom','$prenom','$tel','$adr','adversaire')");
    $id_personne = mysqli_insert_id($conn);

    mysqli_query($conn, "INSERT INTO tiers (id_personne,compagnie_assurance,numero_police,responsable)
        VALUES ($id_personne,'$compagnie','$police','$resp')");
    $success = "Tiers ajouté.";
}

/* ======= MODIFIER ======= */
if (isset($_POST['modifier'])) {
    $id_tiers  = intval($_POST['id_tiers']);
    $id_pers   = intval($_POST['id_personne']);
    $nom       = mysqli_real_escape_string($conn, trim($_POST['nom']));
    $prenom    = mysqli_real_escape_string($conn, trim($_POST['prenom']));
    $tel       = mysqli_real_escape_string($conn, trim($_POST['telephone']));
    $compagnie = mysqli_real_escape_string($conn, trim($_POST['compagnie_assurance']));
    $police    = mysqli_real_escape_string($conn, trim($_POST['numero_police']));
    $resp      = $_POST['responsable'];
    mysqli_query($conn, "UPDATE personne SET nom='$nom',prenom='$prenom',telephone='$tel' WHERE id_personne=$id_pers");
    mysqli_query($conn, "UPDATE tiers SET compagnie_assurance='$compagnie',numero_police='$police',responsable='$resp' WHERE id_tiers=$id_tiers");
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
        $t = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_personne FROM tiers WHERE id_tiers=$id"));
        mysqli_query($conn, "DELETE FROM tiers WHERE id_tiers=$id");
        if ($t) mysqli_query($conn, "DELETE FROM personne WHERE id_personne=".$t['id_personne']);
        $success = "Tiers supprimé.";
    }
}

$tiers_list = mysqli_query($conn, "
    SELECT t.*,p.nom,p.prenom,p.telephone,p.adresse,
           (SELECT COUNT(*) FROM dossier d WHERE d.id_tiers=t.id_tiers) as nb_dossiers
    FROM tiers t JOIN personne p ON t.id_personne=p.id_personne
    ORDER BY t.id_tiers DESC");
$total = mysqli_num_rows($tiers_list);

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
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:900;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal-box{background:#fff;border-radius:16px;padding:30px;width:700px;max-width:95vw;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.18)}
.modal-box h3{font-size:16px;font-weight:600;margin-bottom:22px;padding-bottom:14px;border-bottom:1px solid var(--gray-100);display:flex;align-items:center;gap:8px}
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

<!-- MODAL AJOUTER -->
<div class="modal-overlay" id="modal-add">
<div class="modal-box">
    <h3><i class="fa fa-user-plus" style="color:var(--amber-600)"></i> Nouveau tiers adversaire</h3>
    <form method="POST">
        <div class="form-grid-2">
            <div class="form-group"><label>Nom *</label><input type="text" name="nom" required></div>
            <div class="form-group"><label>Prénom</label><input type="text" name="prenom"></div>
        </div>
        <div class="form-grid-2">
            <div class="form-group"><label>Téléphone</label><input type="text" name="telephone"></div>
            <div class="form-group"><label>Adresse</label><input type="text" name="adresse"></div>
        </div>
        <div class="form-grid-2">
            <div class="form-group"><label>Compagnie d'assurance</label><input type="text" name="compagnie_assurance"></div>
            <div class="form-group"><label>N° police adverse</label><input type="text" name="numero_police"></div>
        </div>
        <div class="form-group">
         <label>Responsabilité du tiers (adversaire)</label>
<select name="responsable" required>
    <option value="">-- Choisir --</option>
    <option value="oui">Le tiers est responsable</option>
    <option value="non">Le tiers n'est pas responsable</option>
    <option value="partiel">Responsabilité partagée</option>
</select>
        </div>
        <div style="display:flex;gap:10px;margin-top:20px">
            <button type="submit" name="ajouter" class="btn btn-primary" style="flex:1"><i class="fa fa-save"></i> Ajouter</button>
            <button type="button" class="btn btn-outline" onclick="closeModal('modal-add')">Annuler</button>
        </div>
    </form>
</div>
</div>

<!-- MODAL MODIFIER -->
<?php if ($edit): ?>
<div class="modal-overlay open" id="modal-edit">
<div class="modal-box">
    <h3><i class="fa fa-pen" style="color:var(--amber-600)"></i> Modifier le tiers</h3>
    <form method="POST">
        <input type="hidden" name="id_tiers"   value="<?= $edit['id_tiers'] ?>">
        <input type="hidden" name="id_personne" value="<?= $edit['id_personne'] ?>">
        <div class="form-grid-2">
            <div class="form-group"><label>Nom</label><input type="text" name="nom" value="<?= htmlspecialchars($edit['nom']) ?>"></div>
            <div class="form-group"><label>Prénom</label><input type="text" name="prenom" value="<?= htmlspecialchars($edit['prenom']) ?>"></div>
        </div>
        <div class="form-group"><label>Téléphone</label><input type="text" name="telephone" value="<?= htmlspecialchars($edit['telephone']) ?>"></div>
        <div class="form-grid-2">
            <div class="form-group"><label>Compagnie</label><input type="text" name="compagnie_assurance" value="<?= htmlspecialchars($edit['compagnie_assurance']) ?>"></div>
            <div class="form-group"><label>N° police</label><input type="text" name="numero_police" value="<?= htmlspecialchars($edit['numero_police']) ?>"></div>
        </div>
        <div class="form-group">
       <label>Responsabilité du tiers (adversaire)</label>
<select name="responsable" required>
    <option value="">-- Choisir --</option>
  <option value="oui" <?= $edit['responsable']=='oui'?'selected':'' ?>>Le tiers est responsable</option>
<option value="non" <?= $edit['responsable']=='non'?'selected':'' ?>>Le tiers n'est pas responsable</option>
<option value="partiel" <?= $edit['responsable']=='partiel'?'selected':'' ?>>Responsabilité partagée</option>
</select>
        </div>
        <div style="display:flex;gap:10px;margin-top:20px">
            <button type="submit" name="modifier" class="btn btn-primary" style="flex:1"><i class="fa fa-save"></i> Modifier</button>
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
</script>
</body>
</html>
