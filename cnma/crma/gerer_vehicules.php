<?php
include('../includes/auth.php');
include('../includes/config.php');
if ($_SESSION['role'] != 'CRMA') { header('Location: ../pages/login.php'); exit(); }

$page_title = 'Gestion des véhicules';
$success = $error = '';

/* ======= AJOUTER ======= */
if (isset($_POST['ajouter'])) {
    $marque      = mysqli_real_escape_string($conn, trim($_POST['marque']));
    $modele      = mysqli_real_escape_string($conn, trim($_POST['modele']));
    $couleur     = mysqli_real_escape_string($conn, trim($_POST['couleur']));
    $nb_places   = intval($_POST['nombre_places']);
    $matricule   = mysqli_real_escape_string($conn, trim(strtoupper($_POST['matricule'])));
    $chassis     = mysqli_real_escape_string($conn, trim($_POST['numero_chassis']));
    $serie       = mysqli_real_escape_string($conn, trim($_POST['numero_serie']));
    $annee       = intval($_POST['annee']);
    $type        = mysqli_real_escape_string($conn, $_POST['type']);
    $carrosserie = mysqli_real_escape_string($conn, $_POST['carrosserie']);

    $chk = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_vehicule FROM vehicule WHERE matricule='$matricule'"))['id_vehicule'] ?? 0;
    if ($chk) {
        $error = "Un véhicule avec la matricule <b>$matricule</b> existe déjà.";
    } else {
        mysqli_query($conn, "INSERT INTO vehicule
            (marque,modele,couleur,nombre_places,matricule,numero_chassis,numero_serie,annee,type,carrosserie)
            VALUES ('$marque','$modele','$couleur',$nb_places,'$matricule','$chassis','$serie',$annee,'$type','$carrosserie')");
        $success = "Véhicule ajouté avec succès.";
    }
}

/* ======= MODIFIER ======= */
if (isset($_POST['modifier'])) {
    $id          = intval($_POST['id_vehicule']);
    $marque      = mysqli_real_escape_string($conn, trim($_POST['marque']));
    $modele      = mysqli_real_escape_string($conn, trim($_POST['modele']));
    $couleur     = mysqli_real_escape_string($conn, trim($_POST['couleur']));
    $nb_places   = intval($_POST['nombre_places']);
    $matricule   = mysqli_real_escape_string($conn, trim(strtoupper($_POST['matricule'])));
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
    $success = "Véhicule modifié avec succès.";
}

/* ======= SUPPRIMER ======= */
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $usage = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) n FROM contrat WHERE id_vehicule=$id"))['n'];
    if ($usage > 0) {
        $error = "Impossible : ce véhicule est lié à $usage contrat(s).";
    } else {
        mysqli_query($conn, "DELETE FROM vehicule WHERE id_vehicule=$id");
        $success = "Véhicule supprimé.";
    }
}

/* ======= DONNÉES ======= */
$filtre_q    = $_GET['q'] ?? '';
$filtre_type = $_GET['type_f'] ?? '';
$where = "WHERE 1=1";
if ($filtre_q)    $where .= " AND (matricule LIKE '%".mysqli_real_escape_string($conn,$filtre_q)."%'
                               OR marque LIKE '%".mysqli_real_escape_string($conn,$filtre_q)."%'
                               OR modele LIKE '%".mysqli_real_escape_string($conn,$filtre_q)."%')";
if ($filtre_type) $where .= " AND type='".mysqli_real_escape_string($conn,$filtre_type)."'";

$vehicules = mysqli_query($conn, "
    SELECT v.*,
           (SELECT COUNT(*) FROM contrat c WHERE c.id_vehicule=v.id_vehicule) as nb_contrats,
           (SELECT nom FROM assure a JOIN personne p ON a.id_personne=p.id_personne
            JOIN contrat c ON c.id_assure=a.id_assure WHERE c.id_vehicule=v.id_vehicule LIMIT 1) as nom_proprietaire,
           (SELECT prenom FROM assure a JOIN personne p ON a.id_personne=p.id_personne
            JOIN contrat c ON c.id_assure=a.id_assure WHERE c.id_vehicule=v.id_vehicule LIMIT 1) as prenom_proprietaire
    FROM vehicule v $where ORDER BY v.id_vehicule DESC");
$total = mysqli_num_rows($vehicules);

/* Stats */
$nb_total   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM vehicule"))['n'];
$nb_associes= mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT id_vehicule) n FROM contrat"))['n'];

$types_v     = ['Tourisme','Utilitaire','Camion','Bus','Moto','Agricole'];
$carrosseries= ['Berline','Hatchback','SUV','Pick-up','Fourgon','Camion'];

/* Édition */
$edit = null;
if (isset($_GET['edit'])) {
    $edit = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM vehicule WHERE id_vehicule=".intval($_GET['edit'])));
}

/* Voir contrats d'un véhicule */
$voir_contrats = null;
$contrats_veh  = [];
if (isset($_GET['contrats'])) {
    $vid = intval($_GET['contrats']);
    $voir_contrats = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM vehicule WHERE id_vehicule=$vid"));
    $res_c = mysqli_query($conn, "
        SELECT c.*, p.nom, p.prenom, f.nom_formule, ag.nom_agence
        FROM contrat c
        JOIN assure a ON c.id_assure=a.id_assure
        JOIN personne p ON a.id_personne=p.id_personne
        JOIN formule f ON c.id_formule=f.id_formule
        JOIN agence ag ON c.id_agence=ag.id_agence
        WHERE c.id_vehicule = $vid ORDER BY c.id_contrat DESC
    ");
    while ($row = mysqli_fetch_assoc($res_c)) $contrats_veh[] = $row;
}

$type_icons = [
    'Tourisme'   => ['fa-car', 'badge-blue'],
    'Utilitaire' => ['fa-van-shuttle', 'badge-amber'],
    'Camion'     => ['fa-truck', 'badge-gray'],
    'Bus'        => ['fa-bus', 'badge-teal'],
    'Moto'       => ['fa-motorcycle', 'badge-purple'],
    'Agricole'   => ['fa-tractor', 'badge-green'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Véhicules — CRMA</title>
<link rel="stylesheet" href="../css/style_crma.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:900;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal-box{background:#fff;border-radius:16px;padding:32px;width:700px;max-width:95vw;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.18)}
.modal-box.wide{width:900px}
.modal-box h3{font-size:16px;font-weight:700;margin-bottom:22px;padding-bottom:14px;border-bottom:1px solid var(--gray-100);display:flex;align-items:center;gap:10px;color:var(--gray-800)}
/* Fix form layout */
.modal-box .form-group{margin-bottom:18px}
.modal-box .form-group label{display:block;font-size:11.5px;font-weight:700;color:var(--gray-600);text-transform:uppercase;letter-spacing:.5px;margin-bottom:7px}
.modal-box .form-group input,
.modal-box .form-group select{width:100%;padding:11px 14px;border:1.5px solid var(--gray-300);border-radius:var(--radius);font-size:14px;font-family:'DM Sans',sans-serif;color:var(--gray-800);background:#fafafa;transition:border-color .2s,box-shadow .2s}
.modal-box .form-group input:focus,
.modal-box .form-group select:focus{border-color:var(--green-600);box-shadow:0 0 0 3px rgba(22,163,74,.12);outline:none;background:#fff}
.modal-box .form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.modal-box .form-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.matricule-plate{display:inline-block;background:#1a1a2e;color:#fff;font-family:'DM Mono',monospace;font-size:15px;font-weight:700;padding:6px 14px;border-radius:6px;letter-spacing:2px;border:2px solid #333}
.veh-stats{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px}
.veh-stat{background:#fff;border:1px solid var(--gray-200);border-radius:var(--radius);padding:14px 18px;text-align:center}
.veh-stat .n{font-size:26px;font-weight:700;font-family:'DM Mono',monospace}
.veh-stat .l{font-size:11px;color:var(--gray-500);text-transform:uppercase;letter-spacing:.5px}
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

<!-- Mini stats -->
<div class="veh-stats">
    <div class="veh-stat">
        <div class="n" style="color:var(--green-700)"><?= $nb_total ?></div>
        <div class="l">Total véhicules</div>
    </div>
    <div class="veh-stat">
        <div class="n" style="color:var(--blue-700)"><?= $nb_associes ?></div>
        <div class="l">Avec contrat actif</div>
    </div>
</div>

<!-- FILTRES -->
<form method="GET" class="filter-bar">
    <input type="text" name="q" placeholder="Matricule, marque, modèle…" value="<?= htmlspecialchars($filtre_q) ?>">
    <select name="type_f">
        <option value="">Tous les types</option>
        <?php foreach($types_v as $tv): ?>
        <option value="<?= $tv ?>" <?= $filtre_type==$tv?'selected':'' ?>><?= $tv ?></option>
        <?php endforeach; ?>
    </select>
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
            <tr><th>Matricule</th><th>Marque / Modèle</th><th>Propriétaire</th><th>Année</th><th>Type</th><th>Couleur</th><th>Contrats</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($v = mysqli_fetch_assoc($vehicules)):
            $ti = $type_icons[$v['type']] ?? ['fa-car','badge-gray'];
        ?>
        <tr>
            <td>
                <div class="matricule-plate"><?= htmlspecialchars($v['matricule']) ?></div>
                <div style="font-size:11px;color:var(--gray-400);margin-top:4px">#<?= $v['id_vehicule'] ?></div>
            </td>
            <td>
                <div style="font-weight:600;color:var(--gray-900)"><?= htmlspecialchars($v['marque'].' '.$v['modele']) ?></div>
                <div style="font-size:11px;color:var(--gray-400)"><?= htmlspecialchars($v['carrosserie']) ?> · <?= $v['nombre_places'] ?> places</div>
            </td>
            <td>
                <?php if ($v['nom_proprietaire']): ?>
                <div style="font-weight:500"><?= htmlspecialchars($v['nom_proprietaire'].' '.$v['prenom_proprietaire']) ?></div>
                <?php else: ?>
                <span style="color:var(--gray-300);font-size:12px">Non associé</span>
                <?php endif; ?>
            </td>
            <td class="num-cell"><?= $v['annee'] ?></td>
            <td><span class="badge <?= $ti[1] ?>"><i class="fa <?= $ti[0] ?>"></i> <?= htmlspecialchars($v['type']) ?></span></td>
            <td style="font-size:13px"><?= htmlspecialchars($v['couleur']) ?></td>
            <td style="text-align:center">
                <?php if ($v['nb_contrats'] > 0): ?>
                <span class="badge badge-green"><?= $v['nb_contrats'] ?></span>
                <?php else: echo '<span style="color:var(--gray-300)">0</span>'; endif; ?>
            </td>
            <td>
                <div style="display:flex;gap:5px;flex-wrap:wrap">
                    <!-- Voir contrats -->
                    <?php if ($v['nb_contrats'] > 0): ?>
                    <a href="?contrats=<?= $v['id_vehicule'] ?>" class="btn btn-teal btn-xs" title="Voir contrats liés">
                        <i class="fa fa-file-contract"></i> Contrats
                    </a>
                    <?php endif; ?>
                    <!-- Modifier -->
                    <a href="?edit=<?= $v['id_vehicule'] ?>" class="btn btn-outline btn-xs" title="Modifier">
                        <i class="fa fa-pen"></i>
                    </a>
                    <!-- Supprimer -->
                    <?php if ($v['nb_contrats'] == 0): ?>
                    <button class="btn btn-danger btn-xs" onclick="confirmDeleteVehicule(<?= $v['id_vehicule'] ?>, '<?= htmlspecialchars($v['matricule']) ?>')" title="Supprimer">
                        <i class="fa fa-trash"></i>
                    </button>
                    <?php else: ?>
                    <button class="btn btn-xs" style="background:var(--gray-200);color:var(--gray-400);cursor:not-allowed" title="Lié à des contrats">
                        <i class="fa fa-lock"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($total == 0): ?>
        <tr><td colspan="8"><div class="empty-state"><i class="fa fa-car"></i><p>Aucun véhicule trouvé</p></div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ====== MODAL AJOUTER ====== -->
<div class="modal-overlay" id="modal-add">
<div class="modal-box">
    <h3><i class="fa fa-car" style="color:var(--green-700)"></i> Nouveau véhicule</h3>
    <form method="POST">
        <div class="form-grid-3">
            <div class="form-group">
                <label>Marque <span style="color:red">*</span></label>
                <input type="text" name="marque" required placeholder="Ex: Toyota">
            </div>
            <div class="form-group">
                <label>Modèle <span style="color:red">*</span></label>
                <input type="text" name="modele" required placeholder="Ex: Corolla">
            </div>
            <div class="form-group">
                <label>Couleur</label>
                <input type="text" name="couleur" placeholder="Ex: Blanc">
            </div>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label>Matricule <span style="color:red">*</span></label>
                <input type="text" name="matricule" required placeholder="Ex: 12345-16-001" style="font-family:'DM Mono',monospace;font-weight:600;text-transform:uppercase">
            </div>
            <div class="form-group">
                <label>Année</label>
                <input type="number" name="annee" min="1970" max="2030" value="<?= date('Y') ?>">
            </div>
        </div>
        <div class="form-grid-3">
            <div class="form-group">
                <label>Nb places</label>
                <input type="number" name="nombre_places" min="1" max="100" value="5">
            </div>
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
        <div style="height:1px;background:var(--gray-100);margin:4px 0 18px"></div>
        <div class="form-grid-2">
            <div class="form-group">
                <label>N° châssis</label>
                <input type="text" name="numero_chassis" placeholder="Ex: WBA3A5C57DF256561">
            </div>
            <div class="form-group">
                <label>N° série</label>
                <input type="text" name="numero_serie" placeholder="N° de série du véhicule">
            </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:24px">
            <button type="submit" name="ajouter" class="btn btn-primary" style="flex:1;justify-content:center;padding:12px">
                <i class="fa fa-save"></i> Enregistrer le véhicule
            </button>
            <button type="button" class="btn btn-outline" onclick="closeModal('modal-add')" style="padding:12px 20px">Annuler</button>
        </div>
    </form>
</div>
</div>

<!-- ====== MODAL MODIFIER ====== -->
<?php if ($edit): ?>
<div class="modal-overlay open" id="modal-edit">
<div class="modal-box">
    <h3><i class="fa fa-pen" style="color:var(--green-700)"></i> Modifier — <span class="matricule-plate" style="font-size:13px;padding:4px 10px"><?= htmlspecialchars($edit['matricule']) ?></span></h3>
    <form method="POST">
        <input type="hidden" name="id_vehicule" value="<?= $edit['id_vehicule'] ?>">
        <div class="form-grid-3">
            <div class="form-group">
                <label>Marque <span style="color:red">*</span></label>
                <input type="text" name="marque" value="<?= htmlspecialchars($edit['marque']) ?>" required>
            </div>
            <div class="form-group">
                <label>Modèle <span style="color:red">*</span></label>
                <input type="text" name="modele" value="<?= htmlspecialchars($edit['modele']) ?>" required>
            </div>
            <div class="form-group">
                <label>Couleur</label>
                <input type="text" name="couleur" value="<?= htmlspecialchars($edit['couleur']) ?>">
            </div>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label>Matricule <span style="color:red">*</span></label>
                <input type="text" name="matricule" value="<?= htmlspecialchars($edit['matricule']) ?>" required style="font-family:'DM Mono',monospace;font-weight:600;text-transform:uppercase">
            </div>
            <div class="form-group">
                <label>Année</label>
                <input type="number" name="annee" value="<?= $edit['annee'] ?>">
            </div>
        </div>
        <div class="form-grid-3">
            <div class="form-group">
                <label>Nb places</label>
                <input type="number" name="nombre_places" value="<?= $edit['nombre_places'] ?>">
            </div>
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
        <div style="height:1px;background:var(--gray-100);margin:4px 0 18px"></div>
        <div class="form-grid-2">
            <div class="form-group">
                <label>N° châssis</label>
                <input type="text" name="numero_chassis" value="<?= htmlspecialchars($edit['numero_chassis']) ?>">
            </div>
            <div class="form-group">
                <label>N° série</label>
                <input type="text" name="numero_serie" value="<?= htmlspecialchars($edit['numero_serie']) ?>">
            </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:24px">
            <button type="submit" name="modifier" class="btn btn-primary" style="flex:1;justify-content:center;padding:12px">
                <i class="fa fa-save"></i> Enregistrer les modifications
            </button>
            <a href="gerer_vehicules.php" class="btn btn-outline" style="padding:12px 20px">Annuler</a>
        </div>
    </form>
</div>
</div>
<?php endif; ?>

<!-- ====== MODAL VOIR CONTRATS ====== -->
<?php if ($voir_contrats): ?>
<div class="modal-overlay open" id="modal-contrats">
<div class="modal-box wide">
    <h3>
        <i class="fa fa-file-contract" style="color:var(--blue-700)"></i>
        Contrats liés à <span class="matricule-plate" style="font-size:13px;padding:4px 10px"><?= htmlspecialchars($voir_contrats['matricule']) ?></span>
        <span class="badge badge-blue" style="margin-left:auto"><?= count($contrats_veh) ?> contrat(s)</span>
    </h3>
    <?php if (empty($contrats_veh)): ?>
    <div class="empty-state"><i class="fa fa-file-contract"></i><p>Aucun contrat lié</p></div>
    <?php else: ?>
    <table class="crma-table">
        <thead>
            <tr><th>N° Police</th><th>Assuré</th><th>Formule</th><th>Agence</th><th>Effet</th><th>Expiration</th><th>Net à payer</th><th>Statut</th></tr>
        </thead>
        <tbody>
        <?php
        $sb = ['actif'=>['badge-green','Actif'],'expire'=>['badge-red','Expiré'],'suspendu'=>['badge-amber','Suspendu']];
        foreach ($contrats_veh as $c):
            $s = $sb[$c['statut']] ?? ['badge-gray',$c['statut']];
        ?>
        <tr>
            <td style="font-family:'DM Mono',monospace;font-weight:700;color:var(--green-700)"><?= htmlspecialchars($c['numero_police']) ?></td>
            <td style="font-weight:500"><?= htmlspecialchars($c['nom'].' '.$c['prenom']) ?></td>
            <td><span class="badge badge-purple" style="font-size:11px"><?= htmlspecialchars($c['nom_formule']) ?></span></td>
            <td style="font-size:12px"><?= htmlspecialchars($c['nom_agence']) ?></td>
            <td style="font-size:12px;font-family:'DM Mono',monospace"><?= $c['date_effet'] ?></td>
            <td style="font-size:12px;font-family:'DM Mono',monospace"><?= $c['date_expiration'] ?></td>
            <td style="font-family:'DM Mono',monospace;font-weight:600;color:var(--green-800)"><?= number_format($c['net_a_payer'],0,',',' ') ?> DA</td>
            <td><span class="badge <?= $s[0] ?>"><?= $s[1] ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <div style="margin-top:20px;display:flex;justify-content:flex-end">
        <a href="gerer_vehicules.php" class="btn btn-outline">Fermer</a>
    </div>
</div>
</div>
<?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if(e.target===m) m.classList.remove('open'); });
});

function confirmDeleteVehicule(id, matricule) {
    Swal.fire({
        title: 'Supprimer ce véhicule ?',
        html: `Véhicule <b>${matricule}</b> sera supprimé définitivement.`,
        icon: 'warning',
        iconColor: '#ef4444',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fa fa-trash"></i> Supprimer',
        cancelButtonText: 'Annuler',
        focusCancel: true
    }).then(r => {
        if (r.isConfirmed) window.location.href = '?del=' + id;
    });
}
</script>
</body>
</html>