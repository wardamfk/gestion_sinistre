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

/* Garanties depuis la BDD */
$garanties_list = [];
$res_gar = mysqli_query($conn, "SELECT * FROM garantie ORDER BY id_garantie");
while ($g = mysqli_fetch_assoc($res_gar)) $garanties_list[] = $g;

/* ======= AJOUTER CONTRAT (avec véhicule intégré) ======= */
if (isset($_POST['ajouter'])) {
    // 1. Créer le véhicule
    $marque      = mysqli_real_escape_string($conn, trim($_POST['marque']));
    $modele      = mysqli_real_escape_string($conn, trim($_POST['modele']));
    $couleur     = mysqli_real_escape_string($conn, trim($_POST['couleur']));
    $nb_places   = intval($_POST['nombre_places']);
    $matricule   = mysqli_real_escape_string($conn, trim(strtoupper($_POST['matricule'])));
    $chassis     = mysqli_real_escape_string($conn, trim($_POST['numero_chassis']));
    $serie       = mysqli_real_escape_string($conn, trim($_POST['numero_serie']));
    $annee       = intval($_POST['annee']);
    $type_veh    = mysqli_real_escape_string($conn, $_POST['type_vehicule']);
    $carrosserie = mysqli_real_escape_string($conn, $_POST['carrosserie']);
    $capital     = (float)$_POST['capital'];

    // Vérif matricule
 // Vérif matricule
$chk_mat = mysqli_fetch_assoc(mysqli_query($conn, 
"SELECT id_vehicule FROM vehicule WHERE matricule='$matricule'"));

if ($chk_mat) {
    $error = "Matricule existe déjà.";
}

// Vérif châssis
$chk_ch = mysqli_fetch_assoc(mysqli_query($conn, 
"SELECT id_vehicule FROM vehicule WHERE numero_chassis='$chassis'"));

if ($chk_ch) {
    $error = "Châssis existe déjà.";
}

// Vérif série
$chk_ser = mysqli_fetch_assoc(mysqli_query($conn, 
"SELECT id_vehicule FROM vehicule WHERE numero_serie='$serie'"));

if ($chk_ser) {
    $error = "Série existe déjà.";
}


// INSERT seulement si tout est OK
if (!$error) {

    mysqli_query($conn, "INSERT INTO vehicule
        (marque,modele,couleur,nombre_places,matricule,numero_chassis,numero_serie,annee,type,carrosserie)
        VALUES ('$marque','$modele','$couleur',$nb_places,'$matricule','$chassis','$serie',$annee,'$type_veh','$carrosserie')");

    $id_vehicule = mysqli_insert_id($conn);
}
if ($error) {
    return;
}
        // 2. Données contrat
        $numero_police = mysqli_real_escape_string($conn, trim($_POST['numero_police']));
        $id_assure     = intval($_POST['id_assure']);
        $id_agence     = intval($_SESSION['id_agence']); // auto depuis session
        $date_effet    = $_POST['date_effet'];
        $date_exp      = $_POST['date_expiration'];
        $duree         = intval($_POST['duree']);
        $prime_base    = (float)$_POST['prime_base'];
        $reduction     = (float)$_POST['reduction'];
        $majoration    = (float)$_POST['majoration'];
        $complement    = (float)$_POST['complement'];
        $date_creation = date('Y-m-d');

        $prime_nette   = $prime_base - $reduction + $majoration + $complement;
        $taxe_montant  = $prime_nette * $TAXE;
        $net_a_payer   = $prime_nette + $TIMBRE + $taxe_montant;

        $chk = mysqli_num_rows(mysqli_query($conn, "SELECT id_contrat FROM contrat WHERE numero_police='$numero_police'"));
        if ($chk > 0) {
            $error = "Le numéro de police <b>$numero_police</b> existe déjà.";
        } else {
            mysqli_query($conn, "INSERT INTO contrat
                (numero_police,id_assure,id_vehicule,date_effet,date_expiration,
                 prime_base,reduction,majoration,prime_nette,complement,
                 net_a_payer,statut,date_creation,id_agence,duree,capital,taxe)
                VALUES ('$numero_police',$id_assure,$id_vehicule,'$date_effet','$date_exp',
                        $prime_base,$reduction,$majoration,$prime_nette,$complement,
                        $net_a_payer,'actif','$date_creation',$id_agence,$duree,$capital,$TAXE)");
            $id_contrat = mysqli_insert_id($conn);

            // 3. Lier garanties
            $garanties_sel = $_POST['garanties'] ?? [];
            // RC toujours incluse
            if (!in_array('1', $garanties_sel)) $garanties_sel[] = '1';
            foreach ($garanties_sel as $id_g) {
                $id_g = intval($id_g);
                mysqli_query($conn, "INSERT IGNORE INTO contrat_garantie (id_contrat,id_garantie) VALUES ($id_contrat,$id_g)");
            }
            $success = "Contrat <b>$numero_police</b> créé avec succès.";
        }
    }


/* ======= DUPLIQUER ======= */
if (isset($_GET['dup'])) {
    $id_src = intval($_GET['dup']);
    $src = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM contrat WHERE id_contrat=$id_src"));
    if ($src) {
        $nouveau_num = $src['numero_police'].'-REN-'.date('Y');
        $chk2 = mysqli_num_rows(mysqli_query($conn, "SELECT id_contrat FROM contrat WHERE numero_police='$nouveau_num'"));
        if ($chk2 > 0) { $error = "Un contrat de renouvellement existe déjà pour cette police."; }
        else {
            $date_effet_new = date('Y-m-d');
            $date_exp_new   = date('Y-m-d', strtotime('+1 year'));
            mysqli_query($conn, "INSERT INTO contrat
                (numero_police,id_assure,id_vehicule,date_effet,date_expiration,
                 prime_base,reduction,majoration,prime_nette,complement,
                 net_a_payer,statut,date_creation,id_agence,duree,capital,taxe)
                VALUES ('$nouveau_num',{$src['id_assure']},{$src['id_vehicule']},
                '$date_effet_new','$date_exp_new',
                {$src['prime_base']},{$src['reduction']},{$src['majoration']},{$src['prime_nette']},
                {$src['complement']},{$src['net_a_payer']},'actif','".date('Y-m-d')."',
                {$src['id_agence']},{$src['duree']},{$src['capital']},{$src['taxe']})");
            $success = "Contrat dupliqué — N° police : <b>$nouveau_num</b>.";
        }
    }
}

/* ======= CHANGER STATUT ======= */
if (isset($_GET['statut'], $_GET['id'])) {
    $id = intval($_GET['id']);
    $statut = in_array($_GET['statut'],['actif','expire','suspendu','resilie']) ? $_GET['statut'] : 'actif';
    mysqli_query($conn, "UPDATE contrat SET statut='$statut' WHERE id_contrat=$id");
    header("Location: gerer_contrats.php?ok=statut"); exit();
}

/* ======= SUPPRIMER ======= */
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $usage = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM dossier WHERE id_contrat=$id"))['n'];
    if ($usage > 0) { $error = "Impossible : ce contrat a $usage dossier(s) associé(s)."; }
    else { mysqli_query($conn, "DELETE FROM contrat WHERE id_contrat=$id"); $success = "Contrat supprimé."; }
}

/* ======= FILTRES & LISTE ======= */
$filtre_q      = $_GET['q'] ?? '';
$filtre_statut = $_GET['statut_f'] ?? '';
$id_agence_sess= intval($_SESSION['id_agence']);
$where = "WHERE c.id_agence=$id_agence_sess";
if ($filtre_q)      $where .= " AND (c.numero_police LIKE '%".mysqli_real_escape_string($conn,$filtre_q)."%'
                                  OR p.nom LIKE '%".mysqli_real_escape_string($conn,$filtre_q)."%'
                                  OR p.prenom LIKE '%".mysqli_real_escape_string($conn,$filtre_q)."%')";
if ($filtre_statut) $where .= " AND c.statut='".mysqli_real_escape_string($conn,$filtre_statut)."'";

$contrats = mysqli_query($conn, "
    SELECT c.*,
           p.nom AS nom_assure, p.prenom AS prenom_assure,
           v.marque, v.modele, v.matricule,
           (SELECT COUNT(*) FROM dossier d WHERE d.id_contrat=c.id_contrat) as nb_dossiers,
           (SELECT GROUP_CONCAT(g.nom_garantie SEPARATOR ', ')
            FROM contrat_garantie cg JOIN garantie g ON cg.id_garantie=g.id_garantie
            WHERE cg.id_contrat=c.id_contrat) as garanties
    FROM contrat c
    JOIN assure a ON c.id_assure=a.id_assure
    JOIN personne p ON a.id_personne=p.id_personne
    JOIN vehicule v ON c.id_vehicule=v.id_vehicule
    $where
    ORDER BY c.id_contrat DESC");
$total = mysqli_num_rows($contrats);

$nb_expire   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM contrat WHERE id_agence=$id_agence_sess AND statut='expire'"))['n'];
$nb_resilie  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM contrat WHERE id_agence=$id_agence_sess AND statut='resilie'"))['n'];

$assures  = mysqli_query($conn, "SELECT a.id_assure,p.nom,p.prenom FROM assure a JOIN personne p ON a.id_personne=p.id_personne WHERE a.actif=1 ORDER BY p.nom");

$statut_badge = [
    'actif'    => ['badge-green', 'Actif'],
    'expire'   => ['badge-red',   'Expiré'],
    'suspendu' => ['badge-amber', 'Suspendu'],
    'resilie'  => ['badge-gray',  'Résilié'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Contrats — CRMA</title>
<link rel="stylesheet" href="../css/style_crma.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* === MODAL === */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:900;align-items:center;justify-content:center;backdrop-filter:blur(3px)}
.modal-overlay.open{display:flex}
.modal-box{background:#fff;border-radius:18px;width:820px;max-width:96vw;max-height:94vh;overflow-y:auto;box-shadow:0 30px 80px rgba(0,0,0,.22);animation:modalIn .2s ease;display:flex;flex-direction:column}
@keyframes modalIn{from{transform:translateY(14px);opacity:0}to{transform:translateY(0);opacity:1}}

/* Header modal avec steps */
.modal-header{padding:24px 28px 0;border-bottom:1px solid var(--gray-100);flex-shrink:0}
.modal-header h3{font-size:17px;font-weight:700;color:var(--gray-800);margin-bottom:18px;display:flex;align-items:center;gap:10px}
.modal-header h3 i{width:36px;height:36px;border-radius:10px;background:var(--green-100);color:var(--green-700);display:flex;align-items:center;justify-content:center}

/* Steps nav */
.steps-nav{display:flex;gap:0;margin:0 -28px;padding:0 28px;overflow-x:auto;scrollbar-width:none}
.steps-nav::-webkit-scrollbar{display:none}
.step-btn{display:flex;align-items:center;gap:8px;padding:12px 18px;border:none;background:transparent;font-size:12.5px;font-weight:600;color:var(--gray-400);cursor:pointer;border-bottom:2px solid transparent;white-space:nowrap;transition:all .2s;font-family:'DM Sans',sans-serif}
.step-btn:hover{color:var(--gray-700)}
.step-btn.active{color:var(--green-700);border-bottom-color:var(--green-700)}
.step-btn.done{color:var(--green-600)}
.step-num{width:22px;height:22px;border-radius:50%;background:var(--gray-200);color:var(--gray-500);font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center}
.step-btn.active .step-num{background:var(--green-700);color:#fff}
.step-btn.done .step-num{background:var(--green-600);color:#fff}

/* Body modal */
.modal-body{padding:24px 28px;flex:1}
.step-panel{display:none}
.step-panel.active{display:block}

/* Section headers */
.section-h{font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.7px;margin:22px 0 14px;display:flex;align-items:center;gap:8px}
.section-h::after{content:'';flex:1;height:1px;background:var(--gray-200)}
.section-h i{color:var(--green-700)}

/* Form fields */
.fg{margin-bottom:16px}
.fg label{display:block;font-size:11px;font-weight:700;color:var(--gray-600);text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px}
.fg input,.fg select,.fg textarea{width:100%;padding:10px 13px;border:1.5px solid var(--gray-200);border-radius:9px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--gray-800);background:var(--gray-50);transition:all .18s}
.fg input:focus,.fg select:focus{border-color:var(--green-600);outline:none;background:#fff;box-shadow:0 0 0 3px rgba(22,163,74,.1)}
.fg input[readonly]{background:var(--gray-100);color:var(--gray-600);cursor:default;border-color:var(--gray-200)}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}

/* Durée selector */
.duree-options{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px}
.duree-opt{position:relative}
.duree-opt input[type=radio]{position:absolute;opacity:0;width:0;height:0}
.duree-opt label{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:14px;border:2px solid var(--gray-200);border-radius:12px;cursor:pointer;transition:all .2s;text-align:center}
.duree-opt label .dur-num{font-size:22px;font-weight:700;color:var(--gray-600);font-family:'DM Mono',monospace}
.duree-opt label .dur-lbl{font-size:11px;color:var(--gray-500);margin-top:2px}
.duree-opt input:checked + label{border-color:var(--green-600);background:var(--green-50)}
.duree-opt input:checked + label .dur-num{color:var(--green-700)}

/* Auto date display */
.date-display{display:grid;grid-template-columns:1fr 1fr;gap:14px;background:var(--gray-50);border:1px solid var(--gray-200);border-radius:12px;padding:16px;margin-top:4px}
.date-box{text-align:center}
.date-box .dlbl{font-size:10px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}
.date-box .dval{font-size:16px;font-weight:700;color:var(--green-800);font-family:'DM Mono',monospace}

/* Garanties */
.gar-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.gar-item{position:relative;cursor:pointer}
.gar-item input[type=checkbox]{position:absolute;opacity:0;width:0;height:0}
.gar-card{display:flex;align-items:center;gap:12px;padding:13px 15px;border:2px solid var(--gray-200);border-radius:11px;transition:all .2s;background:#fff}
.gar-item input:checked + .gar-card{border-color:var(--green-500);background:var(--green-50)}
.gar-item.obligatoire .gar-card{border-color:var(--green-400);background:var(--green-50);opacity:.95}
.gar-icon{width:36px;height:36px;border-radius:8px;background:var(--gray-100);color:var(--gray-500);display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
.gar-item input:checked + .gar-card .gar-icon{background:var(--green-100);color:var(--green-700)}
.gar-info{flex:1;min-width:0}
.gar-name{font-size:13px;font-weight:600;color:var(--gray-800)}
.gar-prix{font-size:12px;color:var(--green-700);font-weight:700;font-family:'DM Mono',monospace;margin-top:1px}
.gar-check-mark{width:20px;height:20px;border-radius:50%;border:2px solid var(--gray-300);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .2s}
.gar-item input:checked + .gar-card .gar-check-mark{background:var(--green-600);border-color:var(--green-600);color:#fff}
.obligatoire-badge{font-size:10px;background:var(--green-600);color:#fff;padding:2px 7px;border-radius:10px;margin-left:4px;font-weight:600}

/* Calcul prime */
.prime-table{background:var(--gray-50);border:1px solid var(--gray-200);border-radius:12px;overflow:hidden}
.prime-row{display:flex;justify-content:space-between;align-items:center;padding:11px 16px;border-bottom:1px solid var(--gray-100)}
.prime-row:last-child{border-bottom:none}
.prime-row.total{background:var(--green-50);border-top:2px solid var(--green-200)}
.prime-row.total .p-label{font-weight:700;color:var(--green-800);font-size:15px}
.prime-row.total .p-value{font-size:18px;font-weight:700;color:var(--green-700);font-family:'DM Mono',monospace}
.p-label{font-size:13px;color:var(--gray-600);display:flex;align-items:center;gap:6px}
.p-value{font-size:14px;font-weight:600;font-family:'DM Mono',monospace;color:var(--gray-800)}
.p-input{width:140px;padding:7px 10px;border:1.5px solid var(--gray-300);border-radius:8px;font-size:14px;font-family:'DM Mono',monospace;text-align:right;font-weight:600;color:var(--gray-800);transition:all .18s}
.p-input:focus{border-color:var(--green-600);outline:none;box-shadow:0 0 0 3px rgba(22,163,74,.1)}
.p-minus{color:var(--red-600)}
.p-plus{color:var(--green-700)}
.p-neutral{color:var(--blue-700)}

/* Garanties sélectionnées résumé */
.gar-selected-list{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px;min-height:28px}
.gar-tag{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;background:var(--green-100);color:var(--green-800);border-radius:20px;font-size:12px;font-weight:600}

/* Modal footer */
.modal-footer{padding:16px 28px;border-top:1px solid var(--gray-100);display:flex;gap:10px;justify-content:space-between;flex-shrink:0;background:#fff;border-radius:0 0 18px 18px}

/* Stat pills */
.stat-pills{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px}
.stat-pill{display:flex;align-items:center;gap:7px;padding:9px 16px;border-radius:var(--radius-lg);font-size:13px;font-weight:600;border:1px solid}

/* Table detail row */
.detail-row{background:var(--gray-50)!important}
.detail-row td{padding:16px 20px!important}
.matricule-plate{display:inline-block;background:#1a1a2e;color:#fff;font-family:'DM Mono',monospace;font-size:14px;font-weight:700;padding:5px 13px;border-radius:6px;letter-spacing:2px}

/* Capital field highlight */
.capital-field input{border-color:var(--amber-600)!important;background:#fffbeb!important;font-weight:700!important;font-size:15px!important}
.capital-field input:focus{box-shadow:0 0 0 3px rgba(217,119,6,.15)!important}
.capital-hint{font-size:11.5px;color:var(--amber-600);margin-top:4px;display:flex;align-items:center;gap:4px;font-weight:500}
</style>
</head>
<body>
<?php include('../includes/sidebar.php'); ?>
<?php include('../includes/header.php'); ?>

<div class="crma-main">
<div class="page-heading">
    <div>
        <h1><i class="fa fa-file-contract"></i> Contrats</h1>
        <p class="sub">Gestion des contrats d'assurance automobile · <?= htmlspecialchars($_SESSION['nom_agence'] ?? '') ?></p>
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
    <div class="stat-pill" style="background:var(--blue-50);color:var(--blue-700);border-color:var(--blue-100)"><i class="fa fa-list"></i> Total : <strong><?= $total ?></strong></div>
    <div class="stat-pill" style="background:var(--red-50);color:var(--red-700);border-color:var(--red-100)"><i class="fa fa-ban"></i> Résilié : <strong><?= $nb_resilie ?></strong></div>
    <div class="stat-pill" style="background:var(--red-50);color:var(--red-700);border-color:var(--red-100)"><i class="fa fa-calendar-xmark"></i> Expiré : <strong><?= $nb_expire ?></strong></div>
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
    <div class="table-toolbar"><span style="font-size:13px;color:var(--gray-500)"><?= $total ?> contrat(s)</span></div>
    <table class="crma-table">
        <thead>
            <tr><th>N° Police</th><th>Assuré</th><th>Véhicule</th><th>Durée</th><th>Expiration</th><th>Capital</th><th>Net à payer</th><th>Actions</th></tr>
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
                <div style="font-weight:700;color:var(--green-700);font-size:14px;font-family:'DM Mono',monospace"><?= htmlspecialchars($c['numero_police']) ?></div>
                <div style="font-size:11px;color:var(--gray-400)"><?= $c['date_creation'] ?></div>
            </td>
            <td style="font-weight:500"><?= htmlspecialchars($c['nom_assure'].' '.$c['prenom_assure']) ?></td>
            <td>
                <span class="matricule-plate" style="font-size:12px;padding:3px 10px"><?= htmlspecialchars($c['matricule']) ?></span>
                <div style="font-size:11px;color:var(--gray-400);margin-top:3px"><?= htmlspecialchars($c['marque'].' '.$c['modele']) ?></div>
            </td>
            <td style="font-size:13px;text-align:center"><span class="badge badge-blue"><?= $c['duree'] ?> mois</span></td>
            <td style="font-size:12px;font-family:'DM Mono',monospace;color:<?= $expire_bientot?'var(--amber-600)':'inherit' ?>">
                <?= $c['date_expiration'] ?>
                <?php if ($expire_bientot) echo '<div style="font-size:10px;"><i class="fa fa-triangle-exclamation"></i> Bientôt</div>'; ?>
            </td>
            <td style="font-weight:600;color:var(--amber-600);font-family:'DM Mono',monospace"><?= number_format($c['capital'],0,',',' ') ?> DA</td>
            <td style="font-weight:700;color:var(--green-800);font-family:'DM Mono',monospace"><?= number_format($c['net_a_payer'],0,',',' ') ?> DA</td>
            <td>
                <div style="display:flex;gap:4px">
                    <!-- VOIR -->
                    <button class="btn btn-ghost btn-xs" onclick="toggleDetail(<?= $c['id_contrat'] ?>)" title="Voir">
                        <i class="fa fa-eye"></i>
                    </button>
                    <!-- RÉSILIER = ARCHIVER -->
                    <?php if ($c['statut'] == 'actif'): ?>
                    <button class="btn btn-danger btn-xs" onclick="confirmResilier(<?= $c['id_contrat'] ?>, '<?= htmlspecialchars($c['numero_police']) ?>')" title="Archiver">
                        <i class="fa fa-ban"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <tr id="detail-<?= $c['id_contrat'] ?>" class="detail-row" style="display:none">
            <td colspan="8">
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;font-size:13px;margin-bottom:10px">
                    <div><div style="color:var(--gray-400);font-size:10px;font-weight:700;text-transform:uppercase;margin-bottom:3px">Prime base</div><div style="font-family:'DM Mono',monospace;font-weight:600"><?= number_format($c['prime_base'],2,',',' ') ?> DA</div></div>
                    <div><div style="color:var(--gray-400);font-size:10px;font-weight:700;text-transform:uppercase;margin-bottom:3px">Prime nette</div><div style="font-family:'DM Mono',monospace;font-weight:600"><?= number_format($c['prime_nette'],2,',',' ') ?> DA</div></div>
                    <div><div style="color:var(--gray-400);font-size:10px;font-weight:700;text-transform:uppercase;margin-bottom:3px">Taxe (<?= round($TAXE*100) ?>%)</div><div style="font-family:'DM Mono',monospace;font-weight:600"><?= number_format($c['prime_nette']*$TAXE,2,',',' ') ?> DA</div></div>
                    <div><div style="color:var(--gray-400);font-size:10px;font-weight:700;text-transform:uppercase;margin-bottom:3px">Net à payer</div><div style="font-size:16px;font-weight:700;color:var(--green-700);font-family:'DM Mono',monospace"><?= number_format($c['net_a_payer'],2,',',' ') ?> DA</div></div>
                </div>
                <?php if ($c['garanties']): ?>
                <div style="font-size:11px;color:var(--gray-500);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Garanties :</div>
                <div style="font-size:13px;color:var(--gray-700)"><?= htmlspecialchars($c['garanties']) ?></div>
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

<!-- ====== MODAL NOUVEAU CONTRAT (intelligent) ====== -->
<div class="modal-overlay" id="modal-add">
<div class="modal-box">
    <div class="modal-header">
        <h3><i class="fa fa-file-contract"></i> Nouveau contrat d'assurance</h3>
        <!-- Steps nav -->
        <div class="steps-nav">
            <button class="step-btn active" onclick="goStep(1,this)" id="step-btn-1">
                <span class="step-num">1</span> Contrat &amp; Assuré
            </button>
            <button class="step-btn" onclick="goStep(2,this)" id="step-btn-2">
                <span class="step-num">2</span> Durée &amp; Dates
            </button>
            <button class="step-btn" onclick="goStep(3,this)" id="step-btn-3">
                <span class="step-num">3</span> Véhicule &amp; Capital
            </button>
            <button class="step-btn" onclick="goStep(4,this)" id="step-btn-4">
                <span class="step-num">4</span> Garanties
            </button>
            <button class="step-btn" onclick="goStep(5,this)" id="step-btn-5">
                <span class="step-num">5</span> Prime &amp; Calcul
            </button>
        </div>
    </div>

    <form method="POST" id="form-contrat">
    <div class="modal-body">

        <!-- ======= STEP 1 : CONTRAT & ASSURÉ ======= -->
        <div class="step-panel active" id="step-1">
            <div class="section-h"><i class="fa fa-file-alt"></i> Informations du contrat</div>
            <div class="grid2">
                <div class="fg">
                    <label>N° Police <span style="color:red">*</span></label>
                    <input type="text" name="numero_police" required placeholder="Ex: CRMA-ALG-2026-001" oninput="this.value=this.value.toUpperCase()">
                </div>
                <div class="fg">
                    <label>Assuré <span style="color:red">*</span></label>
                    <select name="id_assure" required>
                        <option value="">— Sélectionner l'assuré —</option>
                        <?php
                        mysqli_data_seek($assures, 0);
                        while ($a = mysqli_fetch_assoc($assures)):
                        ?>
                        <option value="<?= $a['id_assure'] ?>"><?= htmlspecialchars($a['nom'].' '.$a['prenom']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <!-- Agence auto depuis session -->
            <div style="background:var(--green-50);border:1px solid var(--green-200);border-radius:10px;padding:13px 16px;font-size:13px;color:var(--green-800);display:flex;align-items:center;gap:10px">
                <i class="fa fa-building" style="color:var(--green-600)"></i>
                <span>Agence : <strong><?= htmlspecialchars($_SESSION['nom_agence'] ?? 'CRMA') ?>
            </div>
        </div>

        <!-- ======= STEP 2 : DURÉE & DATES ======= -->
        <div class="step-panel" id="step-2">
            <div class="section-h"><i class="fa fa-calendar"></i> Durée du contrat</div>
            <div class="duree-options">
                <div class="duree-opt">
                    <input type="radio" name="duree" id="dur3" value="3" onchange="computeDates()">
                    <label for="dur3"><span class="dur-num">3</span><span class="dur-lbl">mois</span></label>
                </div>
                <div class="duree-opt">
                    <input type="radio" name="duree" id="dur6" value="6" onchange="computeDates()">
                    <label for="dur6"><span class="dur-num">6</span><span class="dur-lbl">mois</span></label>
                </div>
                <div class="duree-opt">
                    <input type="radio" name="duree" id="dur12" value="12" checked onchange="computeDates()">
                    <label for="dur12"><span class="dur-num">12</span><span class="dur-lbl">mois</span></label>
                </div>
            </div>

            <div class="section-h" style="margin-top:10px"><i class="fa fa-clock"></i> Dates automatiques</div>
            <div class="date-display">
                <div class="date-box">
                    <div class="dlbl"><i class="fa fa-play" style="font-size:9px;margin-right:3px;color:var(--green-600)"></i> Date d'effet</div>
                    <div class="dval" id="display-effet">—</div>
                    <div style="font-size:11px;color:var(--gray-400);margin-top:3px">Demain</div>
                </div>
                <div class="date-box">
                    <div class="dlbl"><i class="fa fa-stop" style="font-size:9px;margin-right:3px;color:var(--red-600)"></i> Date d'expiration</div>
                    <div class="dval" id="display-exp">—</div>
                    <div style="font-size:11px;color:var(--gray-400);margin-top:3px" id="display-nb-jours">—</div>
                </div>
            </div>
            <!-- Champs cachés -->
            <input type="hidden" name="date_effet" id="hidden-effet">
            <input type="hidden" name="date_expiration" id="hidden-exp">
        </div>

        <!-- ======= STEP 3 : VÉHICULE & CAPITAL ======= -->
        <div class="step-panel" id="step-3">
            <div class="section-h"><i class="fa fa-car"></i> Informations du véhicule</div>
            <div class="grid3">
                <div class="fg">
                    <label>Marque <span style="color:red">*</span></label>
                    <input type="text" name="marque" required placeholder="Ex: Peugeot">
                </div>
                <div class="fg">
                    <label>Modèle <span style="color:red">*</span></label>
                    <input type="text" name="modele" required placeholder="Ex: 308">
                </div>
                <div class="fg">
                    <label>Couleur</label>
                    <input type="text" name="couleur" placeholder="Ex: Blanc">
                </div>
            </div>
            <div class="grid3">
                <div class="fg">
                    <label>Matricule <span style="color:red">*</span></label>
                    <input type="text" name="matricule" required placeholder="Ex: 12345-16-001" oninput="this.value=this.value.toUpperCase()" style="font-family:'DM Mono',monospace;font-weight:700;letter-spacing:1px">
                </div>
                <div class="fg">
                    <label>Année</label>
                    <input type="number" name="annee" min="1980" max="2030" value="<?= date('Y') ?>">
                </div>
                <div class="fg">
                    <label>Nb places</label>
                    <input type="number" name="nombre_places" min="1" max="100" value="5">
                </div>
            </div>
            <div class="grid2">
                <div class="fg">
                    <label>Type</label>
                    <select name="type_vehicule">
                        <?php foreach(['Tourisme','Utilitaire','Camion','Bus','Moto','Agricole'] as $t): ?>
                        <option><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fg">
                    <label>Carrosserie</label>
                    <select name="carrosserie">
                        <?php foreach(['Berline','Hatchback','SUV','Pick-up','Fourgon','Camion'] as $c): ?>
                        <option><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid2">
                <div class="fg">
                    <label>N° Châssis</label>
                    <input type="text" name="numero_chassis" placeholder="Numéro de châssis">
                </div>
                <div class="fg">
                    <label>N° Série</label>
                    <input type="text" name="numero_serie" placeholder="Numéro de série">
                </div>
            </div>

            <div class="section-h"><i class="fa fa-coins"></i> Capital assuré</div>
            <div class="fg capital-field">
                <label>Capital du véhicule (DA) <span style="color:red">*</span></label>
                <input type="number" name="capital" id="capital" required min="100000" step="10000" placeholder="Ex: 1200000" oninput="recalc()">
                <div class="capital-hint"><i class="fa fa-info-circle"></i> Valeur du véhicule assuré </div>
            </div>
        </div>

        <!-- ======= STEP 4 : GARANTIES ======= -->
        <div class="step-panel" id="step-4">
            <div class="section-h"><i class="fa fa-shield-halved"></i> Sélection des garanties</div>

           
            <div class="gar-grid">
                <?php foreach ($garanties_list as $g):
                    $is_rc = ($g['id_garantie'] == 1);
                    $icon_map = [1=>'fa-car-crash', 2=>'fa-gavel', 3=>'fa-lock', 4=>'fa-fire', 5=>'fa-window-maximize', 7=>'fa-car-bump', 8=>'fa-shield', 9=>'fa-phone-volume', 10=>'fa-users', 11=>'fa-wrench'];
                    $icon = $icon_map[$g['id_garantie']] ?? 'fa-shield-halved';
                ?>
                <div class="gar-item <?= $is_rc ? 'obligatoire' : '' ?>">
                    <input type="checkbox"
                           name="garanties[]"
                           value="<?= $g['id_garantie'] ?>"
                           id="gar_<?= $g['id_garantie'] ?>"
                           data-prix="<?= $g['prix'] ?>"
                           data-nom="<?= htmlspecialchars($g['nom_garantie']) ?>"
                           <?= $is_rc ? 'checked disabled' : '' ?>
                           class="gar-check"
                           onchange="recalcGaranties()">
                    <!-- Champ hidden pour RC (car disabled n'est pas soumis) -->
                    <?php if ($is_rc): ?>
                    <input type="hidden" name="garanties[]" value="1">
                    <?php endif; ?>
                    <label for="gar_<?= $g['id_garantie'] ?>" class="gar-card">
                        <div class="gar-icon"><i class="fa <?= $icon ?>"></i></div>
                        <div class="gar-info">
                            <div class="gar-name">
                                <?= htmlspecialchars($g['nom_garantie']) ?>
                                <?php if ($is_rc): ?><span class="obligatoire-badge">Obligatoire</span><?php endif; ?>
                            </div>
                            <div class="gar-prix"><?= number_format($g['prix'],0,',',' ') ?> DA</div>
                        </div>
                        <div class="gar-check-mark <?= $is_rc ? 'checked' : '' ?>" style="<?= $is_rc ? 'background:var(--green-600);border-color:var(--green-600);color:#fff' : '' ?>">
                            <i class="fa fa-check" style="font-size:9px"></i>
                        </div>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top:16px">
                <div style="font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Garanties sélectionnées :</div>
                <div class="gar-selected-list" id="gar-selected-list">
                    <span class="gar-tag"><i class="fa fa-check" style="font-size:10px"></i> Responsabilité civile</span>
                </div>
            </div>
        </div>

        <!-- ======= STEP 5 : PRIME & CALCUL ======= -->
        <div class="step-panel" id="step-5">
            <div class="section-h"><i class="fa fa-calculator"></i> Calcul de la prime</div>

            <!-- Tableau de calcul -->
            <div class="prime-table">
                <div class="prime-row">
                    <div class="p-label"><i class="fa fa-shield-halved" style="color:var(--green-600)"></i> Prime de base (garanties)</div>
                    <div class="p-value" id="disp-base" style="color:var(--gray-600)">0 DA</div>
                </div>
                <div class="prime-row">
                    <div class="p-label p-minus"><i class="fa fa-minus-circle" style="color:var(--red-500)"></i> Réduction</div>
                    <div><input type="number" name="reduction" id="reduction" class="p-input" value="0" min="0" step="0.01" placeholder="0" oninput="recalc()"></div>
                </div>
                <div class="prime-row">
                    <div class="p-label p-plus"><i class="fa fa-plus-circle" style="color:var(--amber-500)"></i> Majoration</div>
                    <div><input type="number" name="majoration" id="majoration" class="p-input" value="0" min="0" step="0.01" placeholder="0" oninput="recalc()"></div>
                </div>
                <div class="prime-row">
                    <div class="p-label p-neutral"><i class="fa fa-layer-group" style="color:var(--blue-500)"></i> Complément</div>
                    <div><input type="number" name="complement" id="complement" class="p-input" value="500" min="0" step="0.01" oninput="recalc()"></div>
                </div>
                <div class="prime-row" style="background:var(--gray-50)">
                    <div class="p-label" style="font-weight:600"><i class="fa fa-equals"></i> Prime nette</div>
                    <div class="p-value" id="disp-nette" style="font-weight:700;color:var(--blue-800)">0 DA</div>
                </div>
                <div class="prime-row">
                    <div class="p-label" style="color:var(--gray-500)"><i class="fa fa-percent" style="color:var(--gray-400)"></i> Taxe (<?= round($TAXE*100) ?>%)</div>
                    <div class="p-value" id="disp-taxe" style="color:var(--gray-600)">0 DA</div>
                </div>
                <div class="prime-row">
                    <div class="p-label" style="color:var(--gray-500)"><i class="fa fa-stamp" style="color:var(--gray-400)"></i> Timbre dim.</div>
                    <div class="p-value" style="color:var(--gray-600)"><?= number_format($TIMBRE,0,',',' ') ?> DA</div>
                </div>
                <div class="prime-row total">
                    <div class="p-label"><i class="fa fa-money-bill-wave"></i> Net à payer</div>
                    <div class="p-value" id="disp-net">0 DA</div>
                </div>
            </div>

            <!-- Champs cachés pour les valeurs calculées -->
            <input type="hidden" name="prime_base" id="h-prime-base">

            <!-- Récapitulatif -->
            <div style="background:var(--green-50);border:1px solid var(--green-200);border-radius:12px;padding:16px;margin-top:18px">
                <div style="font-size:12px;font-weight:700;color:var(--green-800);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">
                    <i class="fa fa-check-double"></i> Récapitulatif
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px">
                    <div style="color:var(--gray-600)">Assuré :</div><div id="recap-assure" style="font-weight:600;color:var(--gray-800)">—</div>
                    <div style="color:var(--gray-600)">Durée :</div><div id="recap-duree" style="font-weight:600">—</div>
                    <div style="color:var(--gray-600)">Matricule :</div><div id="recap-mat" style="font-weight:600;font-family:'DM Mono',monospace">—</div>
                    <div style="color:var(--gray-600)">Capital :</div><div id="recap-capital" style="font-weight:600;color:var(--amber-600);font-family:'DM Mono',monospace">—</div>
                    <div style="color:var(--gray-600)">Garanties :</div><div id="recap-gar" style="font-weight:600;font-size:12px">—</div>
                </div>
            </div>
        </div>

    </div><!-- /modal-body -->

    <div class="modal-footer">
        <button type="button" class="btn btn-outline" id="btn-prev" onclick="navStep(-1)" style="display:none">
            <i class="fa fa-arrow-left"></i> Précédent
        </button>
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-add')">Annuler</button>
        <div style="display:flex;gap:10px;margin-left:auto">
            <button type="button" class="btn btn-primary" id="btn-next" onclick="navStep(1)">
                Suivant <i class="fa fa-arrow-right"></i>
            </button>
            <button type="submit" name="ajouter" class="btn btn-success" id="btn-submit" style="display:none">
                <i class="fa fa-save"></i> Créer le contrat
            </button>
        </div>
    </div>
    </form>
</div>
</div>

</div><!-- /crma-main -->

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const TAXE   = <?= $TAXE ?>;
const TIMBRE = <?= $TIMBRE ?>;
const TOTAL_STEPS = 5;
let currentStep = 1;

/* ===== NAVIGATION STEPS ===== */
function goStep(n, btn) {
    document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.step-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('step-' + n).classList.add('active');
    document.getElementById('step-btn-' + n).classList.add('active');
    currentStep = n;
    updateNavButtons();
    if (n === 2) computeDates();
    if (n === 5) updateRecap();
}

function navStep(dir) {
    const next = currentStep + dir;
    if (next < 1 || next > TOTAL_STEPS) return;
    // Mark done
    if (dir > 0) document.getElementById('step-btn-' + currentStep).classList.add('done');
    goStep(next, document.getElementById('step-btn-' + next));
}

function updateNavButtons() {
    const prev = document.getElementById('btn-prev');
    const next = document.getElementById('btn-next');
    const sub  = document.getElementById('btn-submit');
    prev.style.display = currentStep > 1 ? 'inline-flex' : 'none';
    next.style.display = currentStep < TOTAL_STEPS ? 'inline-flex' : 'none';
    sub.style.display  = currentStep === TOTAL_STEPS ? 'inline-flex' : 'none';
}

/* ===== DATES AUTO ===== */
function computeDates() {
    const checked = document.querySelector('input[name="duree"]:checked');
    const duree = checked ? parseInt(checked.value) : 12;

    const today = new Date();
    const effet = new Date(today);
    effet.setDate(effet.getDate() + 1);

    const exp = new Date(effet);
    exp.setMonth(exp.getMonth() + duree);

    const fmt = d => d.toLocaleDateString('fr-DZ', {day:'2-digit',month:'2-digit',year:'numeric'});
    const iso = d => d.toISOString().split('T')[0];

    document.getElementById('display-effet').textContent = fmt(effet);
    document.getElementById('display-exp').textContent = fmt(exp);

    const diff = Math.round((exp - effet) / (1000*60*60*24));
    document.getElementById('display-nb-jours').textContent = diff + ' jours';

    document.getElementById('hidden-effet').value = iso(effet);
    document.getElementById('hidden-exp').value   = iso(exp);
}

/* ===== GARANTIES ===== */
function recalcGaranties() {
    let base = 0;
    const selected = [];
    document.querySelectorAll('.gar-check').forEach(cb => {
        if (cb.checked || cb.value === '1') {
            base += parseFloat(cb.dataset.prix) || 0;
            selected.push(cb.dataset.nom);
        }
    });
    // Update selected tags
    const list = document.getElementById('gar-selected-list');
    list.innerHTML = '';
    selected.forEach(n => {
        const tag = document.createElement('span');
        tag.className = 'gar-tag';
        tag.innerHTML = `<i class="fa fa-check" style="font-size:10px"></i> ${n}`;
        list.appendChild(tag);
    });
    document.getElementById('h-prime-base').value = base.toFixed(2);
    recalc();
}

/* ===== CALCUL PRIME ===== */
function recalc() {
    const base  = parseFloat(document.getElementById('h-prime-base').value) || 0;
    const red   = parseFloat(document.getElementById('reduction').value)    || 0;
    const maj   = parseFloat(document.getElementById('majoration').value)   || 0;
    const comp  = parseFloat(document.getElementById('complement').value)   || 0;

    const nette     = base - red + maj + comp;
    const taxeMont  = nette * TAXE;
    const net       = nette + TIMBRE + taxeMont;

    const fmt = v => v.toLocaleString('fr-DZ', {minimumFractionDigits:0}) + ' DA';

    document.getElementById('disp-base').textContent  = fmt(base);
    document.getElementById('disp-nette').textContent = fmt(nette);
    document.getElementById('disp-taxe').textContent  = fmt(taxeMont);
    document.getElementById('disp-net').textContent   = fmt(net);
}

/* ===== RÉCAPITULATIF ===== */
function updateRecap() {
    const assureSel = document.querySelector('[name="id_assure"]');
    const durSel = document.querySelector('input[name="duree"]:checked');
    const mat = document.querySelector('[name="matricule"]')?.value || '—';
    const cap = parseFloat(document.querySelector('[name="capital"]')?.value) || 0;

    const assureText = assureSel?.options[assureSel.selectedIndex]?.text || '—';
    document.getElementById('recap-assure').textContent  = assureText;
    document.getElementById('recap-duree').textContent   = (durSel?.value || 12) + ' mois';
    document.getElementById('recap-mat').textContent     = mat;
    document.getElementById('recap-capital').textContent = cap.toLocaleString('fr-DZ') + ' DA';

    const tags = [];
    document.querySelectorAll('.gar-check').forEach(cb => {
        if (cb.checked || cb.value==='1') tags.push(cb.dataset.nom);
    });
    document.getElementById('recap-gar').textContent = tags.join(', ') || '—';
}

/* ===== MODAL ===== */
function openModal(id) {
    document.getElementById(id).classList.add('open');
    computeDates();
    recalcGaranties();
    goStep(1, document.getElementById('step-btn-1'));
    document.querySelectorAll('.step-btn').forEach(b => b.classList.remove('done'));
}
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if(e.target===m) m.classList.remove('open'); });
});

/* ===== DETAIL TABLE ===== */
function toggleDetail(id) {
    const row = document.getElementById('detail-' + id);
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}

/* ===== CONFIRMATIONS ===== */
function confirmSuspend(id) {
    Swal.fire({title:'Suspendre ce contrat ?',text:'Il peut être réactivé à tout moment.',icon:'question',iconColor:'#d97706',showCancelButton:true,confirmButtonColor:'#d97706',cancelButtonColor:'#6b7280',confirmButtonText:'<i class="fa fa-pause"></i> Suspendre',cancelButtonText:'Annuler'})
    .then(r => { if(r.isConfirmed) window.location.href='?statut=suspendu&id='+id; });
}
function confirmResilier(id, police) {
    Swal.fire({title:'Résilier ce contrat ?',html:`Police <b>${police}</b> sera résiliée.`,icon:'warning',iconColor:'#ef4444',showCancelButton:true,confirmButtonColor:'#ef4444',cancelButtonColor:'#6b7280',confirmButtonText:'<i class="fa fa-ban"></i> Résilier',cancelButtonText:'Annuler',focusCancel:true})
    .then(r => { if(r.isConfirmed) window.location.href='?statut=resilie&id='+id; });
}
function confirmDuplicate(id, police) {
    Swal.fire({title:'Renouveler ce contrat ?',html:`Duplication de <b>${police}</b> pour 12 mois.`,icon:'info',iconColor:'#2563eb',showCancelButton:true,confirmButtonColor:'#2563eb',cancelButtonColor:'#6b7280',confirmButtonText:'<i class="fa fa-copy"></i> Dupliquer',cancelButtonText:'Annuler'})
    .then(r => { if(r.isConfirmed) window.location.href='?dup='+id; });
}
function confirmDeleteContrat(id, police) {
    Swal.fire({title:'Supprimer définitivement ?',html:`Contrat <b>${police}</b> supprimé.`,icon:'warning',iconColor:'#ef4444',showCancelButton:true,confirmButtonColor:'#ef4444',cancelButtonColor:'#6b7280',confirmButtonText:'<i class="fa fa-trash"></i> Supprimer',cancelButtonText:'Annuler',focusCancel:true})
    .then(r => { if(r.isConfirmed) window.location.href='?del='+id; });
}

// Init
computeDates();
recalcGaranties();
</script>
</body>
</html>