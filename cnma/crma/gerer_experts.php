<?php
// gerer_experts.php — CRUD experts
include('../includes/auth.php');
include('../includes/config.php');
if ($_SESSION['role'] != 'CRMA') { header('Location: ../pages/login.php'); exit(); }

$page_title = 'Gestion des experts';
$success = $error = '';

/* ======= AJOUTER ======= */
if (isset($_POST['ajouter'])) {
    $nom      = mysqli_real_escape_string($conn, trim($_POST['nom']));
    $prenom   = mysqli_real_escape_string($conn, trim($_POST['prenom']));
    $tel      = mysqli_real_escape_string($conn, trim($_POST['telephone']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $activite = mysqli_real_escape_string($conn, trim($_POST['activite']));

    // Créer aussi dans personne avec statut expert
    mysqli_query($conn, "INSERT INTO personne (type_personne,nom,prenom,telephone,email,activite,statut_personne)
        VALUES ('physique','$nom','$prenom','$tel','$email','$activite','expert')");
    $id_personne = mysqli_insert_id($conn);

    mysqli_query($conn, "INSERT INTO expert (nom,prenom,telephone,email,activite)
        VALUES ('$nom','$prenom','$tel','$email','$activite')");
    $success = "Expert ajouté.";
}

/* ======= MODIFIER ======= */
if (isset($_POST['modifier'])) {
    $id       = intval($_POST['id_expert']);
    $nom      = mysqli_real_escape_string($conn, trim($_POST['nom']));
    $prenom   = mysqli_real_escape_string($conn, trim($_POST['prenom']));
    $tel      = mysqli_real_escape_string($conn, trim($_POST['telephone']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $activite = mysqli_real_escape_string($conn, trim($_POST['activite']));
    mysqli_query($conn, "UPDATE expert SET nom='$nom',prenom='$prenom',telephone='$tel',
        email='$email',activite='$activite' WHERE id_expert=$id");
    $success = "Expert modifié.";
}

/* ======= SUPPRIMER ======= */
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $usage = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) n FROM expertise WHERE id_expert=$id"))['n'];
    if ($usage > 0) {
        $error = "Impossible : cet expert a des expertises enregistrées.";
    } else {
        mysqli_query($conn, "DELETE FROM expert WHERE id_expert=$id");
        $success = "Expert supprimé.";
    }
}

$experts = mysqli_query($conn, "
    SELECT e.*,
           (SELECT COUNT(*) FROM expertise ex WHERE ex.id_expert=e.id_expert) as nb_expertises,
           (SELECT COUNT(*) FROM dossier d WHERE d.id_expert=e.id_expert) as nb_dossiers
    FROM expert e ORDER BY e.nom");
$total = mysqli_num_rows($experts);

$edit = null;
if (isset($_GET['edit'])) {
    $edit = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM expert WHERE id_expert=".intval($_GET['edit'])));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Experts — CRMA</title>
<link rel="stylesheet" href="../css/style_crma.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:900;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal-box{background:#fff;border-radius:16px;padding:30px;width:520px;max-width:95vw;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.18)}
.modal-box h3{font-size:16px;font-weight:600;margin-bottom:22px;padding-bottom:14px;border-bottom:1px solid var(--gray-100);display:flex;align-items:center;gap:8px}
</style>
</head>
<body>
<?php include('../includes/sidebar.php'); ?>
<?php include('../includes/header.php'); ?>

<div class="crma-main">
<div class="page-heading">
    <div>
        <h1><i class="fa fa-user-tie"></i> Experts</h1>
        <p class="sub">Gestion des experts automobile</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modal-add')">
        <i class="fa fa-plus"></i> Nouvel expert
    </button>
</div>

<?php if ($success) echo "<div class='msg msg-success'><i class='fa fa-check-circle'></i> $success</div>"; ?>
<?php if ($error)   echo "<div class='msg msg-error'><i class='fa fa-exclamation-circle'></i> $error</div>"; ?>

<div class="crma-table-wrapper">
    <div class="table-toolbar">
        <span style="font-size:13px;color:var(--gray-500)"><?= $total ?> expert(s)</span>
    </div>
    <table class="crma-table">
        <thead>
            <tr><th>Expert</th><th>Contact</th><th>Activité</th><th>Expertises</th><th>Dossiers</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($e = mysqli_fetch_assoc($experts)): ?>
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:36px;height:36px;border-radius:50%;background:var(--teal-50);color:var(--teal-700);display:flex;align-items:center;justify-content:center;font-weight:600;font-size:13px;flex-shrink:0">
                        <?= strtoupper(substr($e['nom'],0,1)) ?>
                    </div>
                    <div>
                        <div style="font-weight:500"><?= htmlspecialchars($e['nom'].' '.$e['prenom']) ?></div>
                        <div style="font-size:11px;color:var(--gray-400)">#<?= $e['id_expert'] ?></div>
                    </div>
                </div>
            </td>
            <td>
                <div class="num-cell" style="font-size:13px"><?= htmlspecialchars($e['telephone']) ?></div>
                <div style="font-size:12px;color:var(--blue-700)"><?= htmlspecialchars($e['email']) ?></div>
            </td>
            <td><?= htmlspecialchars($e['activite']) ?></td>
            <td style="text-align:center">
                <span class="badge badge-teal"><?= $e['nb_expertises'] ?></span>
            </td>
            <td style="text-align:center">
                <span class="badge badge-blue"><?= $e['nb_dossiers'] ?></span>
            </td>
            <td>
                <div style="display:flex;gap:4px">
                    <a href="?edit=<?= $e['id_expert'] ?>" class="btn btn-outline btn-xs"><i class="fa fa-pen"></i></a>
                    <?php if ($e['nb_expertises'] == 0 && $e['nb_dossiers'] == 0): ?>
                    <a href="?del=<?= $e['id_expert'] ?>"
                       class="btn btn-xs btn-danger"
                       onclick="return confirm('Supprimer cet expert ?')"><i class="fa fa-trash"></i></a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($total == 0): ?>
        <tr><td colspan="6"><div class="empty-state"><i class="fa fa-user-tie"></i><p>Aucun expert</p></div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- MODAL AJOUTER -->
<div class="modal-overlay" id="modal-add">
<div class="modal-box">
    <h3><i class="fa fa-user-plus" style="color:var(--teal-700)"></i> Nouvel expert</h3>
    <form method="POST">
        <div class="form-grid-2">
            <div class="form-group"><label>Nom *</label><input type="text" name="nom" required></div>
            <div class="form-group"><label>Prénom *</label><input type="text" name="prenom" required></div>
        </div>
        <div class="form-grid-2">
            <div class="form-group"><label>Téléphone</label><input type="text" name="telephone"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email"></div>
        </div>
        <div class="form-group"><label>Activité / Spécialité</label><input type="text" name="activite" value="Expert automobile"></div>
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
    <h3><i class="fa fa-pen" style="color:var(--teal-700)"></i> Modifier l'expert</h3>
    <form method="POST">
        <input type="hidden" name="id_expert" value="<?= $edit['id_expert'] ?>">
        <div class="form-grid-2">
            <div class="form-group"><label>Nom</label><input type="text" name="nom" value="<?= htmlspecialchars($edit['nom']) ?>"></div>
            <div class="form-group"><label>Prénom</label><input type="text" name="prenom" value="<?= htmlspecialchars($edit['prenom']) ?>"></div>
        </div>
        <div class="form-grid-2">
            <div class="form-group"><label>Téléphone</label><input type="text" name="telephone" value="<?= htmlspecialchars($edit['telephone']) ?>"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($edit['email']) ?>"></div>
        </div>
        <div class="form-group"><label>Activité</label><input type="text" name="activite" value="<?= htmlspecialchars($edit['activite']) ?>"></div>
        <div style="display:flex;gap:10px;margin-top:20px">
            <button type="submit" name="modifier" class="btn btn-primary" style="flex:1"><i class="fa fa-save"></i> Modifier</button>
            <a href="gerer_experts.php" class="btn btn-outline">Annuler</a>
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
