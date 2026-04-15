<?php
include('../includes/auth.php');
include('../includes/config.php');
if ($_SESSION['role'] != 'CRMA') { header('Location: ../pages/login.php'); exit(); }

$page_title = 'Gestion des contrats';
$success = $error = '';

/* Paramètres taxe/timbre */
$params = [];
$pr = mysqli_query($conn, "SELECT nom,valeur FROM parametre");
while ($row = mysqli_fetch_assoc($pr)) $params[$row['nom']] = $row['valeur'];
$TAXE   = (float)($params['taxe']   ?? 0.19);
$TIMBRE = (float)($params['timbre'] ?? 1500);

/* ======= AJOUTER ======= */
if (isset($_POST['ajouter'])) {
    $numero_police = mysqli_real_escape_string($conn, trim($_POST['numero_police']));
    $id_assure     = intval($_POST['id_assure']);
    $id_vehicule   = intval($_POST['id_vehicule']);
    $id_agence     = intval($_POST['id_agence']);
    $id_formule    = intval($_POST['id_formule']);
    $date_effet    = $_POST['date_effet'];
    $date_exp      = $_POST['date_expiration'];
    $prime_base    = (float)$_POST['prime_base'];
    $reduction     = (float)$_POST['reduction'];
    $majoration    = (float)$_POST['majoration'];
    $complement    = (float)$_POST['complement'];
    $statut        = $_POST['statut'];
    $date_creation = date('Y-m-d');

    $prime_nette   = $prime_base - $reduction + $majoration;
    $total_taxes   = $prime_nette * $TAXE;
    $total_timbres = $TIMBRE;
    $net_a_payer   = $prime_nette + $total_taxes + $total_timbres + $complement;

    $chk = mysqli_num_rows(mysqli_query($conn,
        "SELECT id_contrat FROM contrat WHERE numero_police='$numero_police'"));
    if ($chk > 0) {
        $error = "Le numéro de police <b>$numero_police</b> existe déjà.";
    } else {
        mysqli_query($conn, "INSERT INTO contrat
            (numero_police,id_assure,id_vehicule,date_effet,date_expiration,
             prime_base,reduction,majoration,prime_nette,complement,
             total_taxes,total_timbres,net_a_payer,statut,date_creation,id_agence,id_formule)
            VALUES ('$numero_police',$id_assure,$id_vehicule,'$date_effet','$date_exp',
                    $prime_base,$reduction,$majoration,$prime_nette,$complement,
                    $total_taxes,$total_timbres,$net_a_payer,'$statut','$date_creation',$id_agence,$id_formule)");
        $id_contrat = mysqli_insert_id($conn);

        $gars = mysqli_query($conn, "SELECT id_garantie FROM formule_garantie WHERE id_formule=$id_formule");
        while ($g = mysqli_fetch_assoc($gars)) {
            mysqli_query($conn, "INSERT IGNORE INTO contrat_garantie (id_contrat,id_garantie)
                VALUES ($id_contrat,{$g['id_garantie']})");
        }
        $success = "Contrat créé avec succès — garanties associées automatiquement.";
    }
}

/* ======= DUPLIQUER ======= */
if (isset($_GET['dup'])) {
    $id_src = intval($_GET['dup']);
    $src = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM contrat WHERE id_contrat=$id_src"));
    if ($src) {
        // Nouveau numéro police auto
        $nouveau_num = $src['numero_police'].'-REN-'.date('Y');
        $chk2 = mysqli_num_rows(mysqli_query($conn, "SELECT id_contrat FROM contrat WHERE numero_police='$nouveau_num'"));
        if ($chk2 > 0) {
            $error = "Un contrat de renouvellement existe déjà pour cette police.";
        } else {
            $date_effet_new = date('Y-m-d');
            $date_exp_new   = date('Y-m-d', strtotime('+1 year'));
            mysqli_query($conn, "INSERT INTO contrat
                (numero_police,id_assure,id_vehicule,date_effet,date_expiration,
                 prime_base,reduction,majoration,prime_nette,complement,
                 total_taxes,total_timbres,net_a_payer,statut,date_creation,id_agence,id_formule)
                VALUES (
                    '$nouveau_num',{$src['id_assure']},{$src['id_vehicule']},
                    '$date_effet_new','$date_exp_new',
                    {$src['prime_base']},{$src['reduction']},{$src['majoration']},{$src['prime_nette']},
                    {$src['complement']},{$src['total_taxes']},{$src['total_timbres']},{$src['net_a_payer']},
                    'actif','".date('Y-m-d')."',{$src['id_agence']},{$src['id_formule']})");
            $new_id = mysqli_insert_id($conn);
            // Copier les garanties
            $gars_src = mysqli_query($conn, "SELECT id_garantie FROM contrat_garantie WHERE id_contrat=$id_src");
            while ($g = mysqli_fetch_assoc($gars_src)) {
                mysqli_query($conn, "INSERT IGNORE INTO contrat_garantie (id_contrat,id_garantie) VALUES ($new_id,{$g['id_garantie']})");
            }
            $success = "Contrat dupliqué avec succès — N° police : <b>$nouveau_num</b>.";
        }
    }
}

/* ======= CHANGER STATUT ======= */
if (isset($_GET['statut'], $_GET['id'])) {
    $id     = intval($_GET['id']);
    $statut = in_array($_GET['statut'],['actif','expire','suspendu','resilie']) ? $_GET['statut'] : 'actif';
    mysqli_query($conn, "UPDATE contrat SET statut='$statut' WHERE id_contrat=$id");
    header("Location: gerer_contrats.php?ok=statut"); exit();
}

/* ======= SUPPRIMER ======= */
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $usage = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) n FROM dossier WHERE id_contrat=$id"))['n'];
    if ($usage > 0) {
        $error = "Impossible : ce contrat a $usage dossier(s) sinistre associé(s).";
    } else {
        mysqli_query($conn, "DELETE FROM contrat_garantie WHERE id_contrat=$id");
        mysqli_query($conn, "DELETE FROM contrat WHERE id_contrat=$id");
        $success = "Contrat supprimé.";
    }
}

/* ======= FILTRES ======= */
$filtre_q      = $_GET['q'] ?? '';
$filtre_statut = $_GET['statut_f'] ?? '';
$id_agence_sess= $_SESSION['id_agence'];
$where = "WHERE c.id_agence=$id_agence_sess";
if ($filtre_q)      $where .= " AND (c.numero_police LIKE '%".mysqli_real_escape_string($conn,$filtre_q)."%'
                                  OR p.nom LIKE '%".mysqli_real_escape_string($conn,$filtre_q)."%'
                                  OR p.prenom LIKE '%".mysqli_real_escape_string($conn,$filtre_q)."%')";
if ($filtre_statut) $where .= " AND c.statut='".mysqli_real_escape_string($conn,$filtre_statut)."'";

$contrats = mysqli_query($conn, "
    SELECT c.*,
           p.nom AS nom_assure, p.prenom AS prenom_assure,
           v.marque, v.modele, v.matricule,
           f.nom_formule,
           (SELECT COUNT(*) FROM dossier d WHERE d.id_contrat=c.id_contrat) as nb_dossiers
    FROM contrat c
    JOIN assure a ON c.id_assure=a.id_assure
    JOIN personne p ON a.id_personne=p.id_personne
    JOIN vehicule v ON c.id_vehicule=v.id_vehicule
    JOIN formule f ON c.id_formule=f.id_formule
    $where
    ORDER BY c.id_contrat DESC");
$total = mysqli_num_rows($contrats);

/* Stats */
$nb_actif    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM contrat WHERE id_agence=$id_agence_sess AND statut='actif'"))['n'];
$nb_expire   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM contrat WHERE id_agence=$id_agence_sess AND statut='expire'"))['n'];
$nb_suspendu = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM contrat WHERE id_agence=$id_agence_sess AND statut='suspendu'"))['n'];
$nb_resilie  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM contrat WHERE id_agence=$id_agence_sess AND statut='resilie'"))['n'];

/* Données selects */
$assures  = mysqli_query($conn, "SELECT a.id_assure,p.nom,p.prenom FROM assure a JOIN personne p ON a.id_personne=p.id_personne WHERE a.actif=1 ORDER BY p.nom");
$vehicules= mysqli_query($conn, "SELECT id_vehicule,matricule,marque,modele FROM vehicule ORDER BY marque");
$agences  = mysqli_query($conn, "SELECT id_agence,nom_agence FROM agence WHERE type_agence='CRMA'");
$formules = mysqli_query($conn, "SELECT id_formule,nom_formule FROM formule");

$statut_badge = [
    'actif'    => ['badge-green', 'Actif'],
    'expire'   => ['badge-red',   'Expiré'],
    'suspendu' => ['badge-amber', 'Suspendu'],
    'resilie'  => ['badge-gray',  'Résilié'],
];

/* Garanties map pour JS */
$fg = mysqli_query($conn, "SELECT fg.id_formule,g.nom_garantie,g.code_garantie FROM formule_garantie fg JOIN garantie g ON fg.id_garantie=g.id_garantie");
$garanties_map = [];
while ($r = mysqli_fetch_assoc($fg)) $garanties_map[$r['id_formule']][] = ['n'=>$r['nom_garantie'],'c'=>$r['code_garantie']];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Contrats — CRMA</title>
<link rel="stylesheet" href="../css/style_crma.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:900;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal-box{background:#fff;border-radius:16px;padding:32px;width:720px;max-width:95vw;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.18)}
.modal-box h3{font-size:16px;font-weight:700;margin-bottom:22px;padding-bottom:14px;border-bottom:1px solid var(--gray-100);display:flex;align-items:center;gap:10px;color:var(--gray-800)}
/* Fix form layout */
.modal-box .form-group{margin-bottom:18px}
.modal-box .form-group label{display:block;font-size:11.5px;font-weight:700;color:var(--gray-600);text-transform:uppercase;letter-spacing:.5px;margin-bottom:7px}
.modal-box .form-group input,
.modal-box .form-group select{width:100%;padding:11px 14px;border:1.5px solid var(--gray-300);border-radius:var(--radius);font-size:14px;font-family:'DM Sans',sans-serif;color:var(--gray-800);background:#fafafa;transition:border-color .2s,box-shadow .2s}
.modal-box .form-group input:focus,
.modal-box .form-group select:focus{border-color:var(--green-600);box-shadow:0 0 0 3px rgba(22,163,74,.12);outline:none;background:#fff}
.modal-box .form-group input[readonly]{background:var(--gray-100);color:var(--gray-500);cursor:default}
.modal-box .form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.modal-box .form-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.calc-box{background:var(--green-50);border:1px solid var(--green-200);border-radius:var(--radius);padding:16px 18px;margin-top:18px}
.calc-row{display:flex;justify-content:space-between;padding:5px 0;font-size:13px;color:var(--gray-600)}
.calc-row.total{border-top:2px solid var(--green-300);margin-top:8px;padding-top:10px;font-weight:700;font-size:15px;color:var(--green-800)}
/* Stat pills */
.stat-pills{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px}
.stat-pill{display:flex;align-items:center;gap:7px;padding:9px 16px;border-radius:var(--radius-lg);font-size:13px;font-weight:600;border:1px solid}
/* Row detail expand */
.detail-row{background:var(--gray-50) !important}
.detail-row td{padding:16px 20px !important}
</style>
</head>
<body>
<?php include('../includes/sidebar.php'); ?>
<?php include('../includes/header.php'); ?>

<div class="crma-main">
<div class="page-heading">
    <div>
        <h1><i class="fa fa-file-contract"></i> Contrats</h1>
        <p class="sub">Gestion des contrats d'assurance automobile</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modal-add')">
        <i class="fa fa-plus"></i> Nouveau contrat
    </button>
</div>

<?php if ($success) echo "<div class='msg msg-success'><i class='fa fa-check-circle'></i> $success</div>"; ?>
<?php if ($error)   echo "<div class='msg msg-error'><i class='fa fa-exclamation-circle'></i> ".strip_tags($error,'<b>')."</div>"; ?>
<?php if (isset($_GET['ok'])) echo "<div class='msg msg-success'><i class='fa fa-check-circle'></i> Statut mis à jour.</div>"; ?>

<!-- Stat pills -->
<div class="stat-pills">
    <div class="stat-pill" style="background:var(--green-100);color:var(--green-800);border-color:var(--green-200)">
        <i class="fa fa-circle-check"></i> Actifs : <strong><?= $nb_actif ?></strong>
    </div>
    <div class="stat-pill" style="background:var(--red-50);color:var(--red-700);border-color:var(--red-100)">
        <i class="fa fa-calendar-xmark"></i> Expirés : <strong><?= $nb_expire ?></strong>
    </div>
    <div class="stat-pill" style="background:var(--amber-50);color:var(--amber-600);border-color:var(--amber-100)">
        <i class="fa fa-pause"></i> Suspendus : <strong><?= $nb_suspendu ?></strong>
    </div>
    <div class="stat-pill" style="background:var(--gray-100);color:var(--gray-600);border-color:var(--gray-200)">
        <i class="fa fa-ban"></i> Résiliés : <strong><?= $nb_resilie ?></strong>
    </div>
    <div class="stat-pill" style="background:var(--blue-50);color:var(--blue-700);border-color:var(--blue-100)">
        <i class="fa fa-list"></i> Total : <strong><?= $total ?></strong>
    </div>
</div>

<!-- FILTRES -->
<form method="GET" class="filter-bar">
    <input type="text" name="q" placeholder="N° police, assuré…" value="<?= htmlspecialchars($filtre_q) ?>">
    <select name="statut_f">
        <option value="">Tous les statuts</option>
        <option value="actif"    <?= $filtre_statut=='actif'    ?'selected':'' ?>>Actif</option>
        <option value="expire"   <?= $filtre_statut=='expire'   ?'selected':'' ?>>Expiré</option>
        <option value="suspendu" <?= $filtre_statut=='suspendu' ?'selected':'' ?>>Suspendu</option>
        <option value="resilie"  <?= $filtre_statut=='resilie'  ?'selected':'' ?>>Résilié</option>
    </select>
    <button type="submit" class="btn btn-outline btn-sm"><i class="fa fa-search"></i> Filtrer</button>
    <a href="gerer_contrats.php" class="btn btn-ghost btn-sm"><i class="fa fa-times"></i></a>
</form>

<!-- TABLE -->
<div class="crma-table-wrapper">
    <div class="table-toolbar">
        <span style="font-size:13px;color:var(--gray-500)"><?= $total ?> contrat(s)</span>
    </div>
    <table class="crma-table">
        <thead>
            <tr><th>N° Police</th><th>Assuré</th><th>Véhicule</th><th>Formule</th><th>Effet</th><th>Expiration</th><th>Net à payer</th><th>Statut</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($c = mysqli_fetch_assoc($contrats)):
            $sb = $statut_badge[$c['statut']] ?? ['badge-gray',$c['statut']];
            $expire_bientot = $c['statut']=='actif' &&
                (strtotime($c['date_expiration']) - time()) < 30*24*3600 &&
                strtotime($c['date_expiration']) > time();
        ?>
        <tr>
            <td>
                <div class="num-cell" style="color:var(--green-700);font-weight:700;font-size:14px"><?= htmlspecialchars($c['numero_police']) ?></div>
                <div style="font-size:11px;color:var(--gray-400)"><?= $c['date_creation'] ?></div>
            </td>
            <td style="font-weight:500"><?= htmlspecialchars($c['nom_assure'].' '.$c['prenom_assure']) ?></td>
            <td>
                <div style="font-family:'DM Mono',monospace;font-size:12px;font-weight:600"><?= htmlspecialchars($c['matricule']) ?></div>
                <div style="font-size:11px;color:var(--gray-400)"><?= htmlspecialchars($c['marque'].' '.$c['modele']) ?></div>
            </td>
            <td><span class="badge badge-purple" style="font-size:11px"><?= htmlspecialchars($c['nom_formule']) ?></span></td>
            <td class="num-cell" style="font-size:12px"><?= $c['date_effet'] ?></td>
            <td class="num-cell" style="font-size:12px;color:<?= $expire_bientot?'var(--amber-600)':'inherit' ?>">
                <?= $c['date_expiration'] ?>
                <?php if ($expire_bientot) echo '<div style="font-size:10px;color:var(--amber-600)"><i class="fa fa-exclamation-triangle"></i> Bientôt</div>'; ?>
            </td>
            <td class="num-cell" style="font-weight:700;color:var(--green-800)"><?= number_format($c['net_a_payer'],0,',',' ') ?> DA</td>
            <td><span class="badge <?= $sb[0] ?>"><?= $sb[1] ?></span></td>
            <td>
                <div style="display:flex;gap:4px;flex-wrap:wrap">
                    <!-- Voir détail -->
                    <button class="btn btn-ghost btn-xs" onclick="toggleDetail(<?= $c['id_contrat'] ?>)" title="Voir détail">
                        <i class="fa fa-eye"></i>
                    </button>
                    <!-- Dupliquer / Renouveler -->
                    <button class="btn btn-outline btn-xs" onclick="confirmDuplicate(<?= $c['id_contrat'] ?>, '<?= htmlspecialchars($c['numero_police']) ?>')" title="Renouveler / Dupliquer">
                        <i class="fa fa-copy"></i>
                    </button>
                    <!-- Suspendre / Activer -->
                    <?php if ($c['statut']=='actif'): ?>
                    <button class="btn btn-warning btn-xs" onclick="confirmSuspend(<?= $c['id_contrat'] ?>)" title="Suspendre">
                        <i class="fa fa-pause"></i>
                    </button>
                    <?php elseif ($c['statut']=='suspendu'): ?>
                    <a href="?statut=actif&id=<?= $c['id_contrat'] ?>" class="btn btn-success btn-xs" title="Réactiver">
                        <i class="fa fa-play"></i>
                    </a>
                    <?php endif; ?>
                    <!-- Résilier -->
                    <?php if (in_array($c['statut'], ['actif','suspendu'])): ?>
                    <button class="btn btn-danger btn-xs" onclick="confirmResilier(<?= $c['id_contrat'] ?>, '<?= htmlspecialchars($c['numero_police']) ?>')" title="Résilier">
                        <i class="fa fa-ban"></i>
                    </button>
                    <?php endif; ?>
                    <!-- Supprimer -->
                    <?php if ($c['nb_dossiers'] == 0 && in_array($c['statut'],['expire','resilie'])): ?>
                    <button class="btn btn-danger btn-xs" onclick="confirmDeleteContrat(<?= $c['id_contrat'] ?>, '<?= htmlspecialchars($c['numero_police']) ?>')" title="Supprimer définitivement">
                        <i class="fa fa-trash"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <!-- Ligne détail expandable -->
        <tr id="detail-<?= $c['id_contrat'] ?>" class="detail-row" style="display:none">
            <td colspan="9">
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;font-size:13px;margin-bottom:12px">
                    <div>
                        <div style="color:var(--gray-400);font-size:10px;font-weight:700;text-transform:uppercase;margin-bottom:4px">Prime base</div>
                        <div class="num-cell"><?= number_format($c['prime_base'],2,',',' ') ?> DA</div>
                    </div>
                    <div>
                        <div style="color:var(--gray-400);font-size:10px;font-weight:700;text-transform:uppercase;margin-bottom:4px">Réduction</div>
                        <div class="num-cell" style="color:var(--green-700)">− <?= number_format($c['reduction'],2,',',' ') ?> DA</div>
                    </div>
                    <div>
                        <div style="color:var(--gray-400);font-size:10px;font-weight:700;text-transform:uppercase;margin-bottom:4px">Majoration</div>
                        <div class="num-cell" style="color:var(--red-600)">+ <?= number_format($c['majoration'],2,',',' ') ?> DA</div>
                    </div>
                    <div>
                        <div style="color:var(--gray-400);font-size:10px;font-weight:700;text-transform:uppercase;margin-bottom:4px">Prime nette</div>
                        <div class="num-cell" style="font-weight:700"><?= number_format($c['prime_nette'],2,',',' ') ?> DA</div>
                    </div>
                    <div>
                        <div style="color:var(--gray-400);font-size:10px;font-weight:700;text-transform:uppercase;margin-bottom:4px">Taxes (<?= round($TAXE*100) ?>%)</div>
                        <div class="num-cell"><?= number_format($c['total_taxes'],2,',',' ') ?> DA</div>
                    </div>
                    <div>
                        <div style="color:var(--gray-400);font-size:10px;font-weight:700;text-transform:uppercase;margin-bottom:4px">Timbre</div>
                        <div class="num-cell"><?= number_format($c['total_timbres'],2,',',' ') ?> DA</div>
                    </div>
                    <div>
                        <div style="color:var(--gray-400);font-size:10px;font-weight:700;text-transform:uppercase;margin-bottom:4px">Complément</div>
                        <div class="num-cell"><?= number_format($c['complement'],2,',',' ') ?> DA</div>
                    </div>
                    <div>
                        <div style="color:var(--gray-400);font-size:10px;font-weight:700;text-transform:uppercase;margin-bottom:4px">Net à payer</div>
                        <div class="num-cell" style="color:var(--green-700);font-size:16px;font-weight:700"><?= number_format($c['net_a_payer'],2,',',' ') ?> DA</div>
                    </div>
                </div>
                <?php
                $gars_c = mysqli_query($conn, "SELECT g.nom_garantie,g.code_garantie FROM contrat_garantie cg JOIN garantie g ON cg.id_garantie=g.id_garantie WHERE cg.id_contrat={$c['id_contrat']}");
                $gar_list = [];
                while ($g = mysqli_fetch_assoc($gars_c)) $gar_list[] = $g;
                if ($gar_list): ?>
                <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
                    <span style="font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase">Garanties :</span>
                    <?php foreach ($gar_list as $g): ?>
                    <span class="badge badge-teal" style="font-size:11px"><?= htmlspecialchars($g['code_garantie']) ?> — <?= htmlspecialchars($g['nom_garantie']) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($total == 0): ?>
        <tr><td colspan="9"><div class="empty-state"><i class="fa fa-file-contract"></i><p>Aucun contrat</p></div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ====== MODAL AJOUTER ====== -->
<div class="modal-overlay" id="modal-add">
<div class="modal-box">
    <h3><i class="fa fa-file-contract" style="color:var(--green-700)"></i> Nouveau contrat</h3>
    <form method="POST" id="form-contrat">
        <div class="form-grid-2">
            <div class="form-group">
                <label>N° police <span style="color:red">*</span></label>
                <input type="text" name="numero_police" required placeholder="Ex: C2024-001">
            </div>
            <div class="form-group">
                <label>Statut initial</label>
                <select name="statut">
                    <option value="actif">Actif</option>
                    <option value="suspendu">Suspendu</option>
                </select>
            </div>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label>Assuré <span style="color:red">*</span></label>
                <select name="id_assure" required>
                    <option value="">— Sélectionner —</option>
                    <?php while ($a = mysqli_fetch_assoc($assures)): ?>
                    <option value="<?= $a['id_assure'] ?>"><?= htmlspecialchars($a['nom'].' '.$a['prenom']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Véhicule <span style="color:red">*</span></label>
                <select name="id_vehicule" required>
                    <option value="">— Sélectionner —</option>
                    <?php while ($v = mysqli_fetch_assoc($vehicules)): ?>
                    <option value="<?= $v['id_vehicule'] ?>"><?= htmlspecialchars($v['matricule'].' — '.$v['marque'].' '.$v['modele']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label>Agence <span style="color:red">*</span></label>
                <select name="id_agence" required>
                    <?php while ($ag = mysqli_fetch_assoc($agences)): ?>
                    <option value="<?= $ag['id_agence'] ?>" <?= $ag['id_agence']==$id_agence_sess?'selected':'' ?>>
                        <?= htmlspecialchars($ag['nom_agence']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Formule <span style="color:red">*</span></label>
                <select name="id_formule" required onchange="updateFormule(this.value)">
                    <option value="">— Sélectionner —</option>
                    <?php while ($f = mysqli_fetch_assoc($formules)): ?>
                    <option value="<?= $f['id_formule'] ?>"><?= htmlspecialchars($f['nom_formule']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <!-- Garanties preview -->
        <div id="garanties-preview" style="display:none;margin-bottom:16px;padding:12px;background:var(--teal-50);border:1px solid var(--teal-100);border-radius:var(--radius)">
            <div style="font-size:10px;font-weight:700;color:var(--teal-700);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Garanties incluses automatiquement</div>
            <div id="garanties-list" style="display:flex;gap:6px;flex-wrap:wrap"></div>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label>Date d'effet <span style="color:red">*</span></label>
                <input type="date" name="date_effet" required>
            </div>
            <div class="form-group">
                <label>Date d'expiration <span style="color:red">*</span></label>
                <input type="date" name="date_expiration" required>
            </div>
        </div>
        <div style="font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">
            <i class="fa fa-calculator" style="color:var(--green-700)"></i> Calcul de la prime
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label>Prime de base (DA) <span style="color:red">*</span></label>
                <input type="number" step="0.01" name="prime_base" id="prime_base" required placeholder="0.00" oninput="calculer()">
            </div>
            <div class="form-group">
                <label>Réduction (DA)</label>
                <input type="number" step="0.01" name="reduction" id="reduction" value="0" oninput="calculer()">
            </div>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label>Majoration (DA)</label>
                <input type="number" step="0.01" name="majoration" id="majoration" value="0" oninput="calculer()">
            </div>
            <div class="form-group">
                <label>Complément (DA)</label>
                <input type="number" step="0.01" name="complement" id="complement" value="0" oninput="calculer()">
            </div>
        </div>
        <!-- Résumé calculé -->
        <div class="calc-box">
            <div class="calc-row"><span>Prime nette</span><span id="r_nette" style="font-family:'DM Mono',monospace">—</span></div>
            <div class="calc-row"><span>Taxes (<?= round($TAXE*100) ?>%)</span><span id="r_taxes" style="font-family:'DM Mono',monospace">—</span></div>
            <div class="calc-row"><span>Timbre fiscal</span><span style="font-family:'DM Mono',monospace"><?= number_format($TIMBRE,0,',',' ') ?> DA</span></div>
            <div class="calc-row"><span>Complément</span><span id="r_complement" style="font-family:'DM Mono',monospace">—</span></div>
            <div class="calc-row total"><span>Net à payer</span><span id="r_net" style="font-family:'DM Mono',monospace">—</span></div>
        </div>
        <div style="display:flex;gap:10px;margin-top:24px">
            <button type="submit" name="ajouter" class="btn btn-primary" style="flex:1;justify-content:center;padding:12px">
                <i class="fa fa-save"></i> Créer le contrat
            </button>
            <button type="button" class="btn btn-outline" onclick="closeModal('modal-add')" style="padding:12px 20px">Annuler</button>
        </div>
    </form>
</div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const TAXE   = <?= $TAXE ?>;
const TIMBRE = <?= $TIMBRE ?>;

function calculer() {
    const base      = parseFloat(document.getElementById('prime_base').value) || 0;
    const red       = parseFloat(document.getElementById('reduction').value)  || 0;
    const maj       = parseFloat(document.getElementById('majoration').value) || 0;
    const comp      = parseFloat(document.getElementById('complement').value) || 0;
    const nette     = base - red + maj;
    const taxes     = nette * TAXE;
    const net       = nette + taxes + TIMBRE + comp;
    const fmt = v => v.toLocaleString('fr-DZ',{minimumFractionDigits:2}) + ' DA';
    document.getElementById('r_nette').textContent      = fmt(nette);
    document.getElementById('r_taxes').textContent      = fmt(taxes);
    document.getElementById('r_complement').textContent = fmt(comp);
    document.getElementById('r_net').textContent        = fmt(net);
}

const garantiesMap = <?= json_encode($garanties_map) ?>;
function updateFormule(id) {
    const box  = document.getElementById('garanties-preview');
    const list = document.getElementById('garanties-list');
    const gars = garantiesMap[id];
    if (!gars || !gars.length) { box.style.display='none'; return; }
    list.innerHTML = gars.map(g => `<span class="badge badge-teal" style="font-size:11px">${g.c} — ${g.n}</span>`).join('');
    box.style.display = 'block';
}

function toggleDetail(id) {
    const row = document.getElementById('detail-' + id);
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}

function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if(e.target===m) m.classList.remove('open'); });
});

function confirmSuspend(id) {
    Swal.fire({
        title: 'Suspendre ce contrat ?',
        text: 'Le contrat sera suspendu — il peut être réactivé à tout moment.',
        icon: 'question',
        iconColor: '#d97706',
        showCancelButton: true,
        confirmButtonColor: '#d97706',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fa fa-pause"></i> Suspendre',
        cancelButtonText: 'Annuler'
    }).then(r => { if (r.isConfirmed) window.location.href = '?statut=suspendu&id=' + id; });
}

function confirmResilier(id, police) {
    Swal.fire({
        title: 'Résilier ce contrat ?',
        html: `La police <b>${police}</b> sera résiliée définitivement.<br><small style="color:#6b7280">Le contrat restera enregistré pour historique et sera marqué "Résilié".</small>`,
        icon: 'warning',
        iconColor: '#ef4444',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fa fa-ban"></i> Résilier',
        cancelButtonText: 'Annuler',
        focusCancel: true
    }).then(r => { if (r.isConfirmed) window.location.href = '?statut=resilie&id=' + id; });
}

function confirmDuplicate(id, police) {
    Swal.fire({
        title: 'Renouveler / Dupliquer ?',
        html: `Un nouveau contrat sera créé à partir de <b>${police}</b><br>avec les mêmes paramètres pour une durée d'un an.`,
        icon: 'info',
        iconColor: '#2563eb',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fa fa-copy"></i> Dupliquer',
        cancelButtonText: 'Annuler'
    }).then(r => { if (r.isConfirmed) window.location.href = '?dup=' + id; });
}

function confirmDeleteContrat(id, police) {
    Swal.fire({
        title: 'Supprimer définitivement ?',
        html: `Contrat <b>${police}</b> sera supprimé et ne pourra pas être récupéré.`,
        icon: 'warning',
        iconColor: '#ef4444',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fa fa-trash"></i> Supprimer',
        cancelButtonText: 'Annuler',
        focusCancel: true
    }).then(r => { if (r.isConfirmed) window.location.href = '?del=' + id; });
}
</script>
</body>
</html>