<?php
// gerer_vehicules.php — CRUD véhicules
include('../includes/auth.php');
include('../includes/config.php');
if ($_SESSION['role'] != 'CRMA') { header('Location: ../pages/login.php'); exit(); }

$page_title = 'Gestion des véhicules';
$success = $error = '';

/* ======= AJOUTER ======= */
if (isset($_POST['ajouter'])) {
    $marque       = mysqli_real_escape_string($conn, trim($_POST['marque']));
    $modele       = mysqli_real_escape_string($conn, trim($_POST['modele']));
    $couleur      = mysqli_real_escape_string($conn, trim($_POST['couleur']));
    $nb_places    = intval($_POST['nombre_places']);
    $matricule    = mysqli_real_escape_string($conn, trim($_POST['matricule']));
    $chassis      = mysqli_real_escape_string($conn, trim($_POST['numero_chassis']));
    $serie        = mysqli_real_escape_string($conn, trim($_POST['numero_serie']));
    $annee        = intval($_POST['annee']);
    $type         = mysqli_real_escape_string($conn, $_POST['type']);
    $carrosserie  = mysqli_real_escape_string($conn, $_POST['carrosserie']);

    // Vérif doublon matricule
    $chk = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_vehicule FROM vehicule WHERE matricule='$matricule'"))['id_vehicule'] ?? 0;
    if ($chk) {
        $error = "Un véhicule avec la matricule <b>$matricule</b> existe déjà.";
    } else {
        mysqli_query($conn, "INSERT INTO vehicule
            (marque,modele,couleur,nombre_places,matricule,numero_chassis,numero_serie,annee,type,carrosserie)
            VALUES ('$marque','$modele','$couleur',$nb_places,'$matricule','$chassis','$serie',$annee,'$type','$carrosserie')");
        $success = "Véhicule ajouté.";
    }
}

/* ======= MODIFIER ======= */
if (isset($_POST['modifier'])) {
    $id          = intval($_POST['id_vehicule']);
    $marque      = mysqli_real_escape_string($conn, trim($_POST['marque']));
    $modele      = mysqli_real_escape_string($conn, trim($_POST['modele']));
    $couleur     = mysqli_real_escape_string($conn, trim($_POST['couleur']));
    $nb_places   = intval($_POST['nombre_places']);
    $matricule   = mysqli_real_escape_string($conn, trim($_POST['matricule']));
    $chassis     = mysqli_real_escape_string($conn, trim($_POST['numero_chassis']));
    $serie       = mysqli_real_escape_string($conn, trim($_POST['numero_serie']));
    $annee       = intval($_POST['annee']);
    $type        = mysqli_real_escape_string($conn, $_POST['type']);
    $carrosserie = mysqli_real_escape_string($conn, $_POST['carrosserie']);
    mysqli_query($conn, "UPDATE vehicule SET
        marque='$marque',modele='$modele',couleur='$couleur',
        nombre_places=$nb_places,matricule='$matricule',
        numero_chassis='$chassis',numero_serie='$serie',
        annee=$annee,type='$type',carrosserie='$carrosserie'
        WHERE id_vehicule=$id");
    $success = "Véhicule modifié.";
}

/* ======= SUPPRIMER ======= */
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $usage = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) n FROM contrat WHERE id_vehicule=$id"))['n'];
    if ($usage > 0) {
        $error = "Impossible : ce véhicule est lié à des contrats.";
    } else {
        mysqli_query($conn, "DELETE FROM vehicule WHERE id_vehicule=$id");
        $success = "Véhicule supprimé.";
    }
}

/* ======= DONNÉES ======= */
$filtre_q = $_GET['q'] ?? '';
$where = $filtre_q
    ? "WHERE matricule LIKE '%".mysqli_real_escape_string($conn,$filtre_q)."%'
       OR marque LIKE '%".mysqli_real_escape_string($conn,$filtre_q)."%'
       OR modele LIKE '%".mysqli_real_escape_string($conn,$filtre_q)."%'"
    : '';

$vehicules = mysqli_query($conn, "
    SELECT v.*,
           (SELECT COUNT(*) FROM contrat c WHERE c.id_vehicule=v.id_vehicule) as nb_contrats
    FROM vehicule v
    $where
    ORDER BY v.id_vehicule DESC");
$total = mysqli_num_rows($vehicules);

$edit = null;
if (isset($_GET['edit'])) {
    $edit = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM vehicule WHERE id_vehicule=".intval($_GET['edit'])));
}

$types_v = ['Tourisme','Utilitaire','Camion','Bus','Moto','Agricole'];
$carrosseries = ['Berline','Hatchback','SUV','Pick-up','Fourgon','Camion'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Véhicules — CRMA</title>
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
        <h1><i class="fa fa-car"></i> Véhicules</h1>
        <p class="sub">Parc véhicules des assurés CRMA</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modal-add')">
        <i class="fa fa-plus"></i> Nouveau véhicule
    </button>
</div>

<?php if ($success) echo "<div class='msg msg-success'><i class='fa fa-check-circle'></i> $success</div>"; ?>
<?php if ($error)   echo "<div class='msg msg-error'><i class='fa fa-exclamation-circle'></i> ".strip_tags($error,'<b>')."</div>"; ?>

<!-- FILTRES -->
<form method="GET" class="filter-bar">
    <input type="text" name="q" placeholder="Rechercher matricule, marque, modèle…" value="<?= htmlspecialchars($filtre_q) ?>">
    <button type="submit" class="btn btn-outline btn-sm"><i class="fa fa-search"></i> Filtrer</button>
    <a href="gerer_vehicules.php" class="btn btn-ghost btn-sm"><i class="fa fa-times"></i></a>
</form>

<!-- TABLE -->
<div class="crma-table-wrapper">
    <div class="table-toolbar">
        <span style="font-size:13px;color:var(--gray-500)"><?= $total ?> véhicule(s)</span>
    </div>
    <table class="crma-table">
        <thead>
            <tr><th>Matricule</th><th>Marque / Modèle</th><th>Année</th><th>Type</th><th>Couleur</th><th>Contrats</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($v = mysqli_fetch_assoc($vehicules)): ?>
        <tr>
            <td>
                <div class="num-cell" style="font-size:14px;font-weight:600;color:var(--green-700)"><?= htmlspecialchars($v['matricule']) ?></div>
                <div style="font-size:11px;color:var(--gray-400)">#<?= $v['id_vehicule'] ?></div>
            </td>
            <td>
                <div style="font-weight:500"><?= htmlspecialchars($v['marque'].' '.$v['modele']) ?></div>
                <div style="font-size:11px;color:var(--gray-400)"><?= htmlspecialchars($v['carrosserie']) ?></div>
            </td>
            <td class="num-cell"><?= $v['annee'] ?></td>
            <td><span class="badge badge-blue"><?= htmlspecialchars($v['type']) ?></span></td>
            <td><?= htmlspecialchars($v['couleur']) ?></td>
            <td style="text-align:center">
                <?php if ($v['nb_contrats'] > 0): ?>
                <span class="badge badge-green"><?= $v['nb_contrats'] ?></span>
                <?php else: echo '<span style="color:var(--gray-300)">0</span>'; endif; ?>
            </td>
            <td>
                <div style="display:flex;gap:4px">
                    <a href="?edit=<?= $v['id_vehicule'] ?>" class="btn btn-outline btn-xs"><i class="fa fa-pen"></i></a>
                    <?php if ($v['nb_contrats'] == 0): ?>
                    <a href="?del=<?= $v['id_vehicule'] ?>"
                       class="btn btn-xs btn-danger"
                       onclick="return confirm('Supprimer ce véhicule ?')"><i class="fa fa-trash"></i></a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($total == 0): ?>
        <tr><td colspan="7"><div class="empty-state"><i class="fa fa-car"></i><p>Aucun véhicule</p></div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- MODAL AJOUTER -->
<div class="modal-overlay" id="modal-add">
<div class="modal-box">
    <h3><i class="fa fa-car" style="color:var(--green-700)"></i> Nouveau véhicule</h3>
    <form method="POST">
        <div class="form-grid-2">
            <div class="form-group"><label>Marque *</label><input type="text" name="marque" required></div>
            <div class="form-group"><label>Modèle *</label><input type="text" name="modele" required></div>
        </div>
        <div class="form-grid-2">
            <div class="form-group"><label>Matricule *</label><input type="text" name="matricule" required placeholder="Ex: 12345-67-89"></div>
            <div class="form-group"><label>Année</label><input type="number" name="annee" min="1970" max="2030" value="<?= date('Y') ?>"></div>
        </div>
        <div class="form-grid-2">
            <div class="form-group"><label>Couleur</label><input type="text" name="couleur"></div>
            <div class="form-group"><label>Nb places</label><input type="number" name="nombre_places" min="1" max="100" value="5"></div>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label>Type</label>
                <select name="type">
                    <?php foreach ($types_v as $t): ?><option><?= $t ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Carrosserie</label>
                <select name="carrosserie">
                    <?php foreach ($carrosseries as $c): ?><option><?= $c ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-grid-2">
            <div class="form-group"><label>N° châssis</label><input type="text" name="numero_chassis"></div>
            <div class="form-group"><label>N° série</label><input type="text" name="numero_serie"></div>
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
    <h3><i class="fa fa-pen" style="color:var(--green-700)"></i> Modifier — <?= htmlspecialchars($edit['matricule']) ?></h3>
    <form method="POST">
        <input type="hidden" name="id_vehicule" value="<?= $edit['id_vehicule'] ?>">
        <div class="form-grid-2">
            <div class="form-group"><label>Marque</label><input type="text" name="marque" value="<?= htmlspecialchars($edit['marque']) ?>"></div>
            <div class="form-group"><label>Modèle</label><input type="text" name="modele" value="<?= htmlspecialchars($edit['modele']) ?>"></div>
        </div>
        <div class="form-grid-2">
            <div class="form-group"><label>Matricule</label><input type="text" name="matricule" value="<?= htmlspecialchars($edit['matricule']) ?>"></div>
            <div class="form-group"><label>Année</label><input type="number" name="annee" value="<?= $edit['annee'] ?>"></div>
        </div>
        <div class="form-grid-2">
            <div class="form-group"><label>Couleur</label><input type="text" name="couleur" value="<?= htmlspecialchars($edit['couleur']) ?>"></div>
            <div class="form-group"><label>Nb places</label><input type="number" name="nombre_places" value="<?= $edit['nombre_places'] ?>"></div>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label>Type</label>
                <select name="type">
                    <?php foreach ($types_v as $t): ?>
                    <option <?= $edit['type']==$t?'selected':'' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Carrosserie</label>
                <select name="carrosserie">
                    <?php foreach ($carrosseries as $c): ?>
                    <option <?= $edit['carrosserie']==$c?'selected':'' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-grid-2">
            <div class="form-group"><label>N° châssis</label><input type="text" name="numero_chassis" value="<?= htmlspecialchars($edit['numero_chassis']) ?>"></div>
            <div class="form-group"><label>N° série</label><input type="text" name="numero_serie" value="<?= htmlspecialchars($edit['numero_serie']) ?>"></div>
        </div>
        <div style="display:flex;gap:10px;margin-top:20px">
            <button type="submit" name="modifier" class="btn btn-primary" style="flex:1"><i class="fa fa-save"></i> Modifier</button>
            <a href="gerer_vehicules.php" class="btn btn-outline">Annuler</a>
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
