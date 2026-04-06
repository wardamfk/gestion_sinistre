<?php
// gerer_personnes.php — CRUD personnes avec statut_personne
include('../includes/auth.php');
include('../includes/config.php');
if ($_SESSION['role'] != 'CRMA') { header('Location: ../pages/login.php'); exit(); }

$page_title = 'Gestion des personnes';
$success = $error = '';

/* ======= SUPPRESSION ======= */
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    // Vérifier usage
    $usage = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT (SELECT COUNT(*) FROM assure WHERE id_personne=$id)+
                (SELECT COUNT(*) FROM tiers  WHERE id_personne=$id) as n"))['n'];
    if ($usage > 0) {
        $error = "Impossible de supprimer : cette personne est utilisée dans le système.";
    } else {
        mysqli_query($conn, "DELETE FROM personne WHERE id_personne=$id");
        $success = "Personne supprimée.";
    }
}

/* ======= AJOUT ======= */
if (isset($_POST['ajouter'])) {
    $type    = $_POST['type_personne'];
    $nom     = mysqli_real_escape_string($conn, trim($_POST['nom']));
    $prenom  = mysqli_real_escape_string($conn, trim($_POST['prenom']));
    $raison  = mysqli_real_escape_string($conn, trim($_POST['raison_sociale']));
    $cin     = mysqli_real_escape_string($conn, trim($_POST['num_identite']));
    $tel     = mysqli_real_escape_string($conn, trim($_POST['telephone']));
    $adr     = mysqli_real_escape_string($conn, trim($_POST['adresse']));
    $email   = mysqli_real_escape_string($conn, trim($_POST['email']));
    $statut  = $_POST['statut_personne'];

    $sql = "INSERT INTO personne
            (type_personne,nom,prenom,raison_sociale,num_identite,telephone,adresse,email,statut_personne)
            VALUES ('$type','$nom','$prenom','$raison','$cin','$tel','$adr','$email','$statut')";
    if (mysqli_query($conn, $sql)) {
        $success = "Personne ajoutée avec succès.";
    } else {
        $error = "Erreur : " . mysqli_error($conn);
    }
}

/* ======= MODIFICATION ======= */
if (isset($_POST['modifier'])) {
    $id     = intval($_POST['id_personne']);
    $nom    = mysqli_real_escape_string($conn, trim($_POST['nom']));
    $prenom = mysqli_real_escape_string($conn, trim($_POST['prenom']));
    $raison = mysqli_real_escape_string($conn, trim($_POST['raison_sociale']));
    $tel    = mysqli_real_escape_string($conn, trim($_POST['telephone']));
    $adr    = mysqli_real_escape_string($conn, trim($_POST['adresse']));
    $email  = mysqli_real_escape_string($conn, trim($_POST['email']));
    $statut = $_POST['statut_personne'];

    mysqli_query($conn, "UPDATE personne SET
        nom='$nom', prenom='$prenom', raison_sociale='$raison',
        telephone='$tel', adresse='$adr', email='$email',
        statut_personne='$statut'
        WHERE id_personne=$id");
    $success = "Personne modifiée.";
}

/* ======= FILTRE ======= */
$filtre_statut = $_GET['statut'] ?? '';
$filtre_search = $_GET['q'] ?? '';
$where = "WHERE 1=1";
if ($filtre_statut) $where .= " AND statut_personne='".mysqli_real_escape_string($conn,$filtre_statut)."'";
if ($filtre_search) $where .= " AND (nom LIKE '%".mysqli_real_escape_string($conn,$filtre_search)."%'
                                OR prenom LIKE '%".mysqli_real_escape_string($conn,$filtre_search)."%'
                                OR telephone LIKE '%".mysqli_real_escape_string($conn,$filtre_search)."%')";

$personnes = mysqli_query($conn, "SELECT * FROM personne $where ORDER BY id_personne DESC");
$total = mysqli_num_rows($personnes);

/* Personne à éditer */
$edit = null;
if (isset($_GET['edit'])) {
    $edit = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM personne WHERE id_personne=".intval($_GET['edit'])));
}

/* Stats rapides */
$cnt_assure   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM personne WHERE statut_personne='assure'"))['n'];
$cnt_expert   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM personne WHERE statut_personne='expert'"))['n'];
$cnt_adversaire = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM personne WHERE statut_personne='adversaire'"))['n'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gestion des personnes — CRMA</title>
<link rel="stylesheet" href="../css/style_crma.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:900;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal-box{background:#fff;border-radius:16px;padding:30px;width:560px;max-width:95vw;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.18)}
.modal-box h3{font-size:16px;font-weight:600;margin-bottom:22px;padding-bottom:14px;border-bottom:1px solid var(--gray-100);display:flex;align-items:center;gap:8px}
</style>
</head>
<body>
<?php include('../includes/sidebar.php'); ?>
<?php include('../includes/header.php'); ?>

<div class="crma-main">

<!-- PAGE HEADING -->
<div class="page-heading">
    <div>
        <h1><i class="fa fa-users"></i> Personnes</h1>
        <p class="sub">Référentiel centralisé — assurés, experts, adversaires</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modal-add')">
        <i class="fa fa-plus"></i> Nouvelle personne
    </button>
</div>

<!-- STATS -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px;">
    <div class="stat-card sc-blue">
        <div class="sc-icon"><i class="fa fa-users"></i></div>
        <div class="sc-body"><div class="sc-n"><?= $total ?></div><div class="sc-l">Total</div></div>
    </div>
    <div class="stat-card sc-green">
        <div class="sc-icon"><i class="fa fa-id-card"></i></div>
        <div class="sc-body"><div class="sc-n"><?= $cnt_assure ?></div><div class="sc-l">Assurés</div></div>
    </div>
    <div class="stat-card sc-teal">
        <div class="sc-icon"><i class="fa fa-user-tie"></i></div>
        <div class="sc-body"><div class="sc-n"><?= $cnt_expert ?></div><div class="sc-l">Experts</div></div>
    </div>
    <div class="stat-card sc-amber">
        <div class="sc-icon"><i class="fa fa-user-shield"></i></div>
        <div class="sc-body"><div class="sc-n"><?= $cnt_adversaire ?></div><div class="sc-l">Adversaires</div></div>
    </div>
</div>

<?php if ($success) echo "<div class='msg msg-success'><i class='fa fa-check-circle'></i> $success</div>"; ?>
<?php if ($error)   echo "<div class='msg msg-error'><i class='fa fa-exclamation-circle'></i> $error</div>"; ?>

<!-- FILTRES -->
<form method="GET" class="filter-bar">
    <input type="text" name="q" placeholder="Rechercher nom, prénom, téléphone…" value="<?= htmlspecialchars($filtre_search) ?>">
    <select name="statut">
        <option value="">Tous les rôles</option>
        <option value="assure"     <?= $filtre_statut=='assure'     ?'selected':'' ?>>Assuré</option>
        <option value="expert"     <?= $filtre_statut=='expert'     ?'selected':'' ?>>Expert</option>
        <option value="adversaire" <?= $filtre_statut=='adversaire' ?'selected':'' ?>>Adversaire</option>
    </select>
    <button type="submit" class="btn btn-outline btn-sm"><i class="fa fa-search"></i> Filtrer</button>
    <a href="gerer_personnes.php" class="btn btn-ghost btn-sm"><i class="fa fa-times"></i> Reset</a>
</form>

<!-- TABLE -->
<div class="crma-table-wrapper">
    <div class="table-toolbar">
        <span style="font-size:13px;color:var(--gray-500)"><?= $total ?> personne(s)</span>
    </div>
    <table class="crma-table">
        <thead>
            <tr>
                <th>#</th><th>Nom complet</th><th>Rôle</th><th>Téléphone</th>
                <th>Adresse</th><th>Email</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $statut_badges = [
            'assure'     => ['badge-green',  'Assuré'],
            'expert'     => ['badge-teal',   'Expert'],
            'adversaire' => ['badge-amber',  'Adversaire'],
        ];
        mysqli_data_seek($personnes, 0);
        while ($p = mysqli_fetch_assoc($personnes)):
            $sinfo = $statut_badges[$p['statut_personne']] ?? ['badge-gray','—'];
        ?>
        <tr>
            <td style="color:var(--gray-400);font-size:12px;">#<?= $p['id_personne'] ?></td>
            <td>
                <div style="font-weight:500;color:var(--gray-800)">
                    <?= htmlspecialchars($p['type_personne']=='morale'
                        ? $p['raison_sociale']
                        : $p['nom'].' '.$p['prenom']) ?>
                </div>
                <div style="font-size:11px;color:var(--gray-400)"><?= ucfirst($p['type_personne']) ?></div>
            </td>
            <td><span class="badge <?= $sinfo[0] ?>"><?= $sinfo[1] ?></span></td>
            <td class="num-cell"><?= htmlspecialchars($p['telephone']) ?></td>
            <td style="font-size:13px"><?= htmlspecialchars($p['adresse']) ?></td>
            <td style="font-size:13px;color:var(--blue-700)"><?= htmlspecialchars($p['email']) ?></td>
            <td>
                <div style="display:flex;gap:4px;flex-wrap:wrap">
                    <a href="?edit=<?= $p['id_personne'] ?>" class="btn btn-outline btn-xs">
                        <i class="fa fa-pen"></i>
                    </a>
                    <?php if ($p['statut_personne']=='assure'): ?>
                    <a href="gerer_assures.php?from=<?= $p['id_personne'] ?>" class="btn btn-xs btn-info" title="Gérer assuré">
                        <i class="fa fa-id-card"></i>
                    </a>
                    <?php endif; ?>
                    <a href="?del=<?= $p['id_personne'] ?>&<?= http_build_query($_GET) ?>"
                       class="btn btn-xs btn-danger"
                       onclick="return confirm('Supprimer cette personne ?')">
                        <i class="fa fa-trash"></i>
                    </a>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($total == 0): ?>
        <tr><td colspan="7"><div class="empty-state"><i class="fa fa-users"></i><p>Aucune personne trouvée</p></div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ====== MODAL AJOUTER ====== -->
<div class="modal-overlay" id="modal-add">
<div class="modal-box">
    <h3><i class="fa fa-user-plus" style="color:var(--green-700)"></i> Nouvelle personne</h3>
    <form method="POST">
        <div class="form-grid-2">
            <div class="form-group">
                <label>Type de personne</label>
                <select name="type_personne" id="type_add" onchange="toggleType('add')">
                    <option value="physique">Physique</option>
                    <option value="morale">Morale</option>
                </select>
            </div>
            <div class="form-group">
                <label>Rôle dans le système</label>
                <select name="statut_personne">
                    <option value="assure">Assuré</option>
                    <option value="expert">Expert</option>
                    <option value="adversaire">Adversaire (tiers)</option>
                </select>
            </div>
        </div>
        <div id="champs-physique-add">
            <div class="form-grid-2">
                <div class="form-group"><label>Nom</label><input type="text" name="nom"></div>
                <div class="form-group"><label>Prénom</label><input type="text" name="prenom"></div>
            </div>
            <div class="form-group"><label>N° identité (CIN / Passeport)</label><input type="text" name="num_identite"></div>
        </div>
        <div id="champs-morale-add" style="display:none">
            <div class="form-group"><label>Raison sociale</label><input type="text" name="raison_sociale"></div>
        </div>
        <div class="form-grid-2">
            <div class="form-group"><label>Téléphone</label><input type="text" name="telephone"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email"></div>
        </div>
        <div class="form-group"><label>Adresse</label><input type="text" name="adresse"></div>
        <div style="display:flex;gap:10px;margin-top:20px">
            <button type="submit" name="ajouter" class="btn btn-primary" style="flex:1">
                <i class="fa fa-save"></i> Enregistrer
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
    <h3><i class="fa fa-pen" style="color:var(--green-700)"></i> Modifier la personne</h3>
    <form method="POST">
        <input type="hidden" name="id_personne" value="<?= $edit['id_personne'] ?>">
        <div class="form-group">
            <label>Rôle dans le système</label>
            <select name="statut_personne">
                <option value="assure"     <?= $edit['statut_personne']=='assure'     ?'selected':'' ?>>Assuré</option>
                <option value="expert"     <?= $edit['statut_personne']=='expert'     ?'selected':'' ?>>Expert</option>
                <option value="adversaire" <?= $edit['statut_personne']=='adversaire' ?'selected':'' ?>>Adversaire (tiers)</option>
            </select>
        </div>
        <?php if ($edit['type_personne'] == 'physique'): ?>
        <div class="form-grid-2">
            <div class="form-group"><label>Nom</label><input type="text" name="nom" value="<?= htmlspecialchars($edit['nom']) ?>"></div>
            <div class="form-group"><label>Prénom</label><input type="text" name="prenom" value="<?= htmlspecialchars($edit['prenom']) ?>"></div>
        </div>
        <?php else: ?>
        <div class="form-group"><label>Raison sociale</label><input type="text" name="raison_sociale" value="<?= htmlspecialchars($edit['raison_sociale']) ?>"></div>
        <?php endif; ?>
        <div class="form-grid-2">
            <div class="form-group"><label>Téléphone</label><input type="text" name="telephone" value="<?= htmlspecialchars($edit['telephone']) ?>"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($edit['email']) ?>"></div>
        </div>
        <div class="form-group"><label>Adresse</label><input type="text" name="adresse" value="<?= htmlspecialchars($edit['adresse']) ?>"></div>
        <div style="display:flex;gap:10px;margin-top:20px">
            <button type="submit" name="modifier" class="btn btn-primary" style="flex:1">
                <i class="fa fa-save"></i> Enregistrer
            </button>
            <a href="gerer_personnes.php" class="btn btn-outline">Annuler</a>
        </div>
    </form>
</div>
</div>
<?php endif; ?>

</div><!-- /crma-main -->
<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function toggleType(suffix) {
    const v = document.getElementById('type_'+suffix).value;
    document.getElementById('champs-physique-'+suffix).style.display = v==='physique'?'':'none';
    document.getElementById('champs-morale-'+suffix).style.display   = v==='morale' ?'':'none';
}
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if(e.target===m) m.classList.remove('open'); });
});
</script>
</body>
</html>
