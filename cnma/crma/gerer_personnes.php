<?php
// gerer_personnes.php — Référentiel personnes (lecture + modification + suppression)
// La création se fait désormais depuis : Assurés / Experts / Tiers
include('../includes/auth.php');
include('../includes/config.php');
if ($_SESSION['role'] != 'CRMA') { header('Location: ../pages/login.php'); exit(); }

$page_title = 'Gestion des personnes';
$success = $error = '';

/* ======= SUPPRESSION ======= */
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $usage = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT
            (SELECT COUNT(*) FROM assure WHERE id_personne=$id)+
            (SELECT COUNT(*) FROM tiers  WHERE id_personne=$id)+
            (SELECT COUNT(*) FROM expert WHERE id_personne=$id)
        as n"))['n'];
    if ($usage > 0) {
        $error = "❌ Impossible de supprimer : cette personne est utilisée dans le système.";
    } else {
        mysqli_query($conn, "DELETE FROM personne WHERE id_personne=$id");
        $success = "Personne supprimée.";
    }
}

/* ======= MODIFICATION ======= */
if (isset($_POST['modifier'])) {
    $id     = intval($_POST['id_personne']);
    $type   = $_POST['type_personne'];
    $nom    = mysqli_real_escape_string($conn, trim($_POST['nom'] ?? ''));
    $prenom = mysqli_real_escape_string($conn, trim($_POST['prenom'] ?? ''));
    $raison = mysqli_real_escape_string($conn, trim($_POST['raison_sociale'] ?? ''));
    $tel    = mysqli_real_escape_string($conn, trim($_POST['telephone'] ?? ''));
    $adr    = mysqli_real_escape_string($conn, trim($_POST['adresse'] ?? ''));
    $email  = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $lieu_naissance = mysqli_real_escape_string($conn, $_POST['lieu_naissance'] ?? '');
    $date_naissance = $_POST['date_naissance'] ?? null;
   

    mysqli_query($conn, "UPDATE personne SET
        type_personne='$type', nom='$nom', prenom='$prenom',
        raison_sociale='$raison', telephone='$tel', adresse='$adr', email='$email',
        date_naissance ".($date_naissance ? "='$date_naissance'" : "=NULL").",
        lieu_naissance='$lieu_naissance'
        WHERE id_personne=$id");
    $success = "✅ Personne modifiée.";
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
$cnt_assure    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM personne WHERE statut_personne='assure'"))['n'];
$cnt_expert    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM personne WHERE statut_personne='expert'"))['n'];
$cnt_adversaire= mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM personne WHERE statut_personne='adversaire'"))['n'];
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
.modal-box{background:#fff;border-radius:16px;padding:30px;width:680px;max-width:95vw;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.18)}
.modal-box h3{font-size:16px;font-weight:600;margin-bottom:22px;padding-bottom:14px;border-bottom:1px solid var(--gray-100);display:flex;align-items:center;gap:8px}
.create-tip{display:flex;align-items:center;gap:10px;background:var(--green-50);border:1px solid var(--green-200);border-radius:var(--radius-lg);padding:14px 18px;margin-bottom:20px;font-size:13px;color:var(--green-800)}
.create-tip a{color:var(--green-700);font-weight:600;text-decoration:none}
.create-tip a:hover{text-decoration:underline}
</style>
</head>
<body>
<?php include('../includes/sidebar.php'); ?>
<?php include('../includes/header.php'); ?>

<div class="crma-main">

<!-- PAGE HEADING — sans bouton "Nouvelle personne" -->
<div class="page-heading">
    <div>
        <h1><i class="fa fa-users"></i> Personnes</h1>
        <p class="sub">Référentiel centralisé — assurés, experts, adversaires</p>
    </div>
</div>

<!-- TIP : où créer les personnes -->
<div class="create-tip">
    <i class="fa fa-lightbulb" style="font-size:16px;flex-shrink:0;"></i>
    <div>
        Pour ajouter une nouvelle personne, utilisez directement :
        <a href="gerer_assures.php">Assurés</a>,
        <a href="gerer_experts.php">Experts</a> ou
        <a href="gerer_tiers.php">Tiers adversaires</a> — la personne y sera créée automatiquement en même temps.
    </div>
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
    <input type="text" name="q" placeholder="Rechercher nom, prénom, téléphone…"
           value="<?= htmlspecialchars($filtre_search) ?>">
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
                <th>#</th><th>Nom complet</th><th>CIN</th><th>Rôle</th>
                <th>Téléphone</th><th>Adresse</th><th>Email</th><th>Actions</th>
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
            <td><?= $p['num_identite']
                ? htmlspecialchars($p['num_identite'])
                : '<span style="color:var(--gray-300)">—</span>' ?></td>
            <td><span class="badge <?= $sinfo[0] ?>"><?= $sinfo[1] ?></span></td>
            <td class="num-cell"><?= htmlspecialchars($p['telephone']) ?></td>
            <td style="font-size:13px"><?= htmlspecialchars($p['adresse']) ?></td>
            <td style="font-size:13px;color:var(--blue-700)"><?= htmlspecialchars($p['email']) ?></td>
            <td>
                <div style="display:flex;gap:4px;flex-wrap:wrap">
                    <a href="?edit=<?= $p['id_personne'] ?>" class="btn btn-outline btn-xs" title="Modifier">
                        <i class="fa fa-pen"></i>
                    </a>
                    <?php if ($p['statut_personne']=='assure'): ?>
                    <a href="gerer_assures.php" class="btn btn-xs btn-info" title="Gérer assuré">
                        <i class="fa fa-id-card"></i>
                    </a>
                    <?php elseif ($p['statut_personne']=='expert'): ?>
                    <a href="gerer_experts.php" class="btn btn-xs btn-teal" title="Gérer expert">
                        <i class="fa fa-user-tie"></i>
                    </a>
                    <?php elseif ($p['statut_personne']=='adversaire'): ?>
                    <a href="gerer_tiers.php" class="btn btn-xs btn-warning" title="Gérer tiers">
                        <i class="fa fa-user-shield"></i>
                    </a>
                    <?php endif; ?>
                    <a href="#" class="btn btn-xs btn-danger"
                       onclick="confirmDelete(event, <?= $p['id_personne'] ?>)">
                        <i class="fa fa-trash"></i>
                    </a>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($total == 0): ?>
        <tr><td colspan="8"><div class="empty-state"><i class="fa fa-users"></i><p>Aucune personne trouvée</p></div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ====== MODAL MODIFIER ====== -->
<?php if ($edit): ?>
<div class="modal-overlay open" id="modal-edit">
<div class="modal-box">
    <h3><i class="fa fa-pen" style="color:var(--green-700)"></i> Modifier la personne</h3>
    <form method="POST">
        <input type="hidden" name="id_personne" value="<?= $edit['id_personne'] ?>">
        <div class="form-grid-2">
            <div class="form-group">
                <label>Type de personne</label>
                <select name="type_personne" id="type_edit" onchange="toggleType('edit')">
                    <option value="physique" <?= $edit['type_personne']=='physique'?'selected':'' ?>>Physique</option>
                    <option value="morale"   <?= $edit['type_personne']=='morale'  ?'selected':'' ?>>Morale</option>
                </select>
            </div>
<div class="form-group">
    <label>Rôle</label>
    <input type="text" value="<?= ucfirst($edit['statut_personne']) ?>" disabled>
</div>

        </div>
        <div id="champs-physique-edit" style="display:<?= $edit['type_personne']=='physique'?'':'none' ?>">
            <div class="form-grid-2">
                <div class="form-group"><label>Nom</label><input type="text" name="nom" value="<?= htmlspecialchars($edit['nom']) ?>"></div>
                <div class="form-group"><label>Prénom</label><input type="text" name="prenom" value="<?= htmlspecialchars($edit['prenom']) ?>"></div>
            </div>
            <div class="form-grid-2">
                <div class="form-group"><label>Date de naissance</label><input type="date" name="date_naissance" value="<?= $edit['date_naissance'] ?>"></div>
                <div class="form-group"><label>Lieu de naissance</label><input type="text" name="lieu_naissance" value="<?= htmlspecialchars($edit['lieu_naissance']) ?>"></div>
            </div>
        </div>
        <div id="champs-morale-edit" style="display:<?= $edit['type_personne']=='morale'?'':'none' ?>">
            <div class="form-group"><label>Raison sociale</label><input type="text" name="raison_sociale" value="<?= htmlspecialchars($edit['raison_sociale'] ?? '')?>"></div>
        </div>
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

function confirmDelete(e, id) {
    e.preventDefault();
    Swal.fire({
        title: 'Supprimer cette personne ?',
        text: 'Cette action est irréversible',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then(r => { if (r.isConfirmed) window.location.href = '?del=' + id; });
}
</script>
</body>
</html>