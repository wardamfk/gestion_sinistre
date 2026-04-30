<?php
include('../includes/auth.php');
include('../includes/config.php');
if ($_SESSION['role'] != 'CRMA') { header('Location: ../pages/login.php'); exit(); }

$page_title = 'Contrats & Véhicules';
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

    // Vérifications vehicule
    $chk_mat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_vehicule FROM vehicule WHERE matricule='$matricule'"));
    if ($chk_mat) { $error = "La matricule <b>$matricule</b> est déjà enregistrée."; }

    if (!$error) {
        $chk_ch = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_vehicule FROM vehicule WHERE numero_chassis='$chassis' AND numero_chassis != ''"));
        if ($chk_ch && $chassis) { $error = "Ce numéro de châssis est déjà utilisé."; }
    }

    if (!$error) {
        // INSERT véhicule
        mysqli_query($conn, "INSERT INTO vehicule
            (marque,modele,couleur,nombre_places,matricule,numero_chassis,numero_serie,annee,type,carrosserie)
            VALUES ('$marque','$modele','$couleur',$nb_places,'$matricule','$chassis','$serie',$annee,'$type_veh','$carrosserie')");
        $id_vehicule = mysqli_insert_id($conn);

        // 2. Données contrat
        $numero_police = mysqli_real_escape_string($conn, trim($_POST['numero_police']));
        $id_assure     = intval($_POST['id_assure']);
        $id_agence     = intval($_SESSION['id_agence']);
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
            // Supprimer le véhicule créé si le contrat échoue
            mysqli_query($conn, "DELETE FROM vehicule WHERE id_vehicule=$id_vehicule");
        } else {
            mysqli_query($conn, "INSERT INTO contrat
                (numero_police,id_assure,id_vehicule,date_effet,date_expiration,
                 prime_base,reduction,majoration,prime_nette,complement,
                 net_a_payer,statut,date_creation,id_agence,duree,capital,taxe)
                VALUES ('$numero_police',$id_assure,$id_vehicule,'$date_effet','$date_exp',
                        $prime_base,$reduction,$majoration,$prime_nette,$complement,
                        $net_a_payer,'actif','$date_creation',$id_agence,$duree,$capital,$TAXE)");
            $id_contrat = mysqli_insert_id($conn);

            // 3. Garanties
            $garanties_sel = $_POST['garanties'] ?? [];
            if (!in_array('1', $garanties_sel)) $garanties_sel[] = '1';
            foreach ($garanties_sel as $id_g) {
                $id_g = intval($id_g);
                mysqli_query($conn, "INSERT IGNORE INTO contrat_garantie (id_contrat,id_garantie) VALUES ($id_contrat,$id_g)");
            }
            $success = "Contrat <b>$numero_police</b> créé avec succès — Véhicule <b>$matricule</b> enregistré.";
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
                                  OR p.prenom LIKE '%".mysqli_real_escape_string($conn,$filtre_q)."%'
                                  OR v.matricule LIKE '%".mysqli_real_escape_string($conn,$filtre_q)."%')";
if ($filtre_statut) $where .= " AND c.statut='".mysqli_real_escape_string($conn,$filtre_statut)."'";

$contrats = mysqli_query($conn, "
    SELECT c.*,
           p.nom AS nom_assure, p.prenom AS prenom_assure, p.telephone AS tel_assure, p.adresse AS adr_assure,
           v.id_vehicule, v.marque, v.modele, v.couleur, v.matricule, v.annee, v.type AS type_veh,
           v.carrosserie, v.nombre_places, v.numero_chassis, v.numero_serie,
           (SELECT COUNT(*) FROM dossier d WHERE d.id_contrat=c.id_contrat) as nb_dossiers,
           (SELECT GROUP_CONCAT(g.nom_garantie SEPARATOR ', ')
            FROM contrat_garantie cg JOIN garantie g ON cg.id_garantie=g.id_garantie
            WHERE cg.id_contrat=c.id_contrat) as garanties
    FROM contrat c
    JOIN assure a ON c.id_assure=a.id_assure
    JOIN personne p ON a.id_personne=p.id_personne
    LEFT JOIN vehicule v ON c.id_vehicule=v.id_vehicule
    $where
    ORDER BY c.id_contrat DESC");
$total = mysqli_num_rows($contrats);

$nb_expire = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM contrat WHERE id_agence=$id_agence_sess AND statut='expire'"))['n'];

$assures = mysqli_query($conn, "SELECT a.id_assure,p.nom,p.prenom FROM assure a JOIN personne p ON a.id_personne=p.id_personne WHERE a.actif=1 ORDER BY p.nom");

$statut_badge = [
    'actif'    => ['badge-green', 'Actif'],
    'expire'   => ['badge-red',   'Expiré'],
    'suspendu' => ['badge-amber', 'Suspendu'],
    'resilie'  => ['badge-gray',  'Résilié'],
];

$type_veh_icons = [
    'Tourisme'   => 'fa-car',
    'Utilitaire' => 'fa-van-shuttle',
    'Camion'     => 'fa-truck',
    'Bus'        => 'fa-bus',
    'Moto'       => 'fa-motorcycle',
    'Agricole'   => 'fa-tractor',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Contrats & Véhicules — CRMA</title>
<link rel="stylesheet" href="../css/style_crma.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* === MODAL === */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:900;align-items:center;justify-content:center;backdrop-filter:blur(3px)}
.modal-overlay.open{display:flex}
.modal-box{background:#fff;border-radius:18px;width:860px;max-width:96vw;max-height:94vh;overflow-y:auto;box-shadow:0 30px 80px rgba(0,0,0,.22);animation:modalIn .2s ease;display:flex;flex-direction:column}
@keyframes modalIn{from{transform:translateY(14px);opacity:0}to{transform:translateY(0);opacity:1}}

/* Steps */
.modal-header{padding:24px 28px 0;border-bottom:1px solid var(--gray-100);flex-shrink:0}
.modal-header h3{font-size:17px;font-weight:700;color:var(--gray-800);margin-bottom:18px;display:flex;align-items:center;gap:10px}
.steps-nav{display:flex;gap:0;margin:0 -28px;padding:0 28px;overflow-x:auto;scrollbar-width:none}
.steps-nav::-webkit-scrollbar{display:none}
.step-btn{display:flex;align-items:center;gap:8px;padding:12px 18px;border:none;background:transparent;font-size:12.5px;font-weight:600;color:var(--gray-400);cursor:pointer;border-bottom:2px solid transparent;white-space:nowrap;transition:all .2s;font-family:'DM Sans',sans-serif}
.step-btn:hover{color:var(--gray-700)}
.step-btn.active{color:var(--green-700);border-bottom-color:var(--green-700)}
.step-btn.done{color:var(--green-600)}
.step-num{width:22px;height:22px;border-radius:50%;background:var(--gray-200);color:var(--gray-500);font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center}
.step-btn.active .step-num{background:var(--green-700);color:#fff}
.step-btn.done .step-num{background:var(--green-600);color:#fff}
.modal-body{padding:24px 28px;flex:1}
.step-panel{display:none}
.step-panel.active{display:block}
.modal-footer{padding:16px 28px;border-top:1px solid var(--gray-100);display:flex;gap:10px;justify-content:space-between;flex-shrink:0;background:#fff;border-radius:0 0 18px 18px}

/* Form fields */
.fg{margin-bottom:16px}
.fg label{display:block;font-size:11px;font-weight:700;color:var(--gray-600);text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px}
.fg input,.fg select,.fg textarea{width:100%;padding:10px 13px;border:1.5px solid var(--gray-200);border-radius:9px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--gray-800);background:var(--gray-50);transition:all .18s}
.fg input:focus,.fg select:focus{border-color:var(--green-600);outline:none;background:#fff;box-shadow:0 0 0 3px rgba(22,163,74,.1)}
.fg input[readonly]{background:var(--gray-100);color:var(--gray-600);cursor:default}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
.section-h{font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.7px;margin:22px 0 14px;display:flex;align-items:center;gap:8px}
.section-h::after{content:'';flex:1;height:1px;background:var(--gray-200)}
.section-h i{color:var(--green-700)}

/* Garanties */
.gar-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.gar-item{position:relative;cursor:pointer}
.gar-item input[type=checkbox]{position:absolute;opacity:0;width:0;height:0}
.gar-card{display:flex;align-items:center;gap:12px;padding:13px 15px;border:2px solid var(--gray-200);border-radius:11px;transition:all .2s;background:#fff}
.gar-item input:checked + .gar-card{border-color:var(--green-500);background:var(--green-50)}
.gar-item.obligatoire .gar-card{border-color:var(--green-400);background:var(--green-50)}
.gar-icon{width:36px;height:36px;border-radius:8px;background:var(--gray-100);color:var(--gray-500);display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
.gar-item input:checked + .gar-card .gar-icon{background:var(--green-100);color:var(--green-700)}
.gar-info{flex:1}
.gar-name{font-size:13px;font-weight:600;color:var(--gray-800)}
.gar-prix{font-size:12px;color:var(--green-700);font-weight:700;font-family:'DM Mono',monospace}
.gar-check-mark{width:20px;height:20px;border-radius:50%;border:2px solid var(--gray-300);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.gar-item input:checked + .gar-card .gar-check-mark{background:var(--green-600);border-color:var(--green-600);color:#fff}
.obligatoire-badge{font-size:10px;background:var(--green-600);color:#fff;padding:2px 7px;border-radius:10px;margin-left:4px;font-weight:600}

/* Prime calc */
.prime-table{background:var(--gray-50);border:1px solid var(--gray-200);border-radius:12px;overflow:hidden}
.prime-row{display:flex;justify-content:space-between;align-items:center;padding:11px 16px;border-bottom:1px solid var(--gray-100)}
.prime-row:last-child{border-bottom:none}
.prime-row.total{background:var(--green-50);border-top:2px solid var(--green-200)}
.prime-row.total .p-label{font-weight:700;color:var(--green-800);font-size:15px}
.prime-row.total .p-value{font-size:18px;font-weight:700;color:var(--green-700);font-family:'DM Mono',monospace}
.p-label{font-size:13px;color:var(--gray-600);display:flex;align-items:center;gap:6px}
.p-value{font-size:14px;font-weight:600;font-family:'DM Mono',monospace;color:var(--gray-800)}
.p-input{width:140px;padding:7px 10px;border:1.5px solid var(--gray-300);border-radius:8px;font-size:14px;font-family:'DM Mono',monospace;text-align:right;font-weight:600;color:var(--gray-800)}
.p-input:focus{border-color:var(--green-600);outline:none;box-shadow:0 0 0 3px rgba(22,163,74,.1)}

/* Duration pills */
.duree-options{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px}
.duree-opt{position:relative}
.duree-opt input[type=radio]{position:absolute;opacity:0;width:0;height:0}
.duree-opt label{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:14px;border:2px solid var(--gray-200);border-radius:12px;cursor:pointer;transition:all .2s;text-align:center}
.duree-opt label .dur-num{font-size:22px;font-weight:700;color:var(--gray-600);font-family:'DM Mono',monospace}
.duree-opt label .dur-lbl{font-size:11px;color:var(--gray-500);margin-top:2px}
.duree-opt input:checked + label{border-color:var(--green-600);background:var(--green-50)}
.duree-opt input:checked + label .dur-num{color:var(--green-700)}

/* Date display */
.date-display{display:grid;grid-template-columns:1fr 1fr;gap:14px;background:var(--gray-50);border:1px solid var(--gray-200);border-radius:12px;padding:16px;margin-top:4px}
.date-box{text-align:center}
.date-box .dlbl{font-size:10px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}
.date-box .dval{font-size:16px;font-weight:700;color:var(--green-800);font-family:'DM Mono',monospace}

/* Vehicle display in table */
.matricule-plate{display:inline-block;background:#1a1a2e;color:#fff;font-family:'DM Mono',monospace;font-size:14px;font-weight:700;padding:5px 13px;border-radius:6px;letter-spacing:2px;border:2px solid #333}
.veh-info-mini{display:flex;flex-direction:column;gap:2px}
.veh-info-mini .veh-name{font-size:13px;font-weight:600;color:var(--gray-800)}
.veh-info-mini .veh-meta{font-size:11px;color:var(--gray-400)}

/* Detail row */
.detail-row{background:var(--gray-50)!important}
.detail-row td{padding:20px!important}
.detail-section{background:#fff;border:1px solid var(--gray-200);border-radius:12px;padding:18px;margin-bottom:14px}
.detail-section h4{font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.7px;margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--gray-100);display:flex;align-items:center;gap:7px}
.detail-section h4 i{color:var(--green-700)}
.detail-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px}
.detail-item .di-label{font-size:10px;font-weight:700;color:var(--gray-400);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px}
.detail-item .di-val{font-size:13.5px;font-weight:500;color:var(--gray-800)}
.detail-item .di-val.mono{font-family:'DM Mono',monospace}

/* Vehicle modal (view) */
.veh-modal-box{background:#fff;border-radius:18px;width:700px;max-width:96vw;max-height:90vh;overflow-y:auto;box-shadow:0 30px 80px rgba(0,0,0,.22);animation:modalIn .2s ease}
.veh-hero{background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);border-radius:14px 14px 0 0;padding:24px 28px;color:#fff;display:flex;align-items:center;gap:18px}
.veh-hero-icon{width:56px;height:56px;border-radius:14px;background:rgba(255,255,255,.1);border:2px solid rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:24px;color:#fff}
.veh-hero-info h3{font-size:20px;font-weight:700;margin-bottom:4px;font-family:'DM Mono',monospace;letter-spacing:2px}
.veh-hero-info .veh-sub{font-size:13px;color:rgba(255,255,255,.6)}
.veh-detail-body{padding:24px 28px}
.veh-detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px}
.veh-detail-card{background:var(--gray-50);border:1px solid var(--gray-200);border-radius:10px;padding:14px 16px}
.veh-detail-card .vdc-label{font-size:10px;font-weight:700;color:var(--gray-400);text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px}
.veh-detail-card .vdc-val{font-size:15px;font-weight:600;color:var(--gray-800)}
.veh-detail-card .vdc-val.mono{font-family:'DM Mono',monospace}

/* Stat pills */
.stat-pills{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px}
.stat-pill{display:flex;align-items:center;gap:7px;padding:9px 16px;border-radius:var(--radius-lg);font-size:13px;font-weight:600;border:1px solid}
</style>
</head>
<body>
<?php include('../includes/sidebar.php'); ?>
<?php include('../includes/header.php'); ?>

<div class="crma-main">
<div class="page-heading">
    <div>
        <h1><i class="fa fa-file-contract"></i> Contrats &amp; Véhicules</h1>
        <p class="sub">Gestion des contrats d'assurance automobile · <?= htmlspecialchars($_SESSION['nom_agence'] ?? '') ?> — Le véhicule est créé automatiquement avec chaque contrat</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modal-add')">
        <i class="fa fa-plus"></i> Nouveau contrat
    </button>
</div>

<?php if ($success) echo "<div class='msg msg-success'><i class='fa fa-check-circle'></i> $success</div>"; ?>
<?php if ($error)   echo "<div class='msg msg-error'><i class='fa fa-exclamation-circle'></i> ".strip_tags($error,'<b>')."</div>"; ?>
<?php if (isset($_GET['ok'])) echo "<div class='msg msg-success'><i class='fa fa-check-circle'></i> Opération réalisée avec succès.</div>"; ?>

<!-- Stat pills -->
<div class="stat-pills">
    <div class="stat-pill" style="background:var(--blue-50);color:var(--blue-700);border-color:var(--blue-100)"><i class="fa fa-list"></i> Total : <strong><?= $total ?></strong></div>
    <div class="stat-pill" style="background:var(--red-50);color:var(--red-700);border-color:var(--red-100)"><i class="fa fa-calendar-xmark"></i> Expirés : <strong><?= $nb_expire ?></strong></div>
    <div class="stat-pill" style="background:var(--green-100);color:var(--green-700);border-color:var(--green-200)"><i class="fa fa-car"></i> Un véhicule par contrat</div>
</div>

<!-- Filtres -->
<form method="GET" class="filter-bar">
    <input type="text" name="q" placeholder="N° police, assuré, matricule…" value="<?= htmlspecialchars($filtre_q) ?>">
    <select name="statut_f">
        <option value="">Tous les statuts</option>
        <option value="actif"    <?= $filtre_statut=='actif'   ?'selected':'' ?>>Actif</option>
        <option value="expire"   <?= $filtre_statut=='expire'  ?'selected':'' ?>>Expiré</option>
        <option value="suspendu" <?= $filtre_statut=='suspendu'?'selected':'' ?>>Suspendu</option>
        <option value="resilie"  <?= $filtre_statut=='resilie' ?'selected':'' ?>>archivé</option>
    </select>
    <button type="submit" class="btn btn-outline btn-sm"><i class="fa fa-search"></i> Filtrer</button>
    <a href="gerer_contrats.php" class="btn btn-ghost btn-sm"><i class="fa fa-times"></i></a>
</form>

<!-- TABLE -->
<div class="crma-table-wrapper">
    <div class="table-toolbar"><span style="font-size:13px;color:var(--gray-500)"><?= $total ?> contrat(s)</span></div>
    <table class="crma-table">
        <thead>
            <tr>
                <th>N° Police</th>
                <th>Assuré</th>
                <th>Véhicule</th>
                <th>Type / Année</th>
                <th>Durée</th>
                <th>Expiration</th>
                <th>Net à payer</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($c = mysqli_fetch_assoc($contrats)):
            $sb = $statut_badge[$c['statut']] ?? ['badge-gray', $c['statut']];
            $expire_bientot = $c['statut']=='actif' && (strtotime($c['date_expiration']) - time()) < 30*24*3600 && strtotime($c['date_expiration']) > time();
            $veh_icon = $type_veh_icons[$c['type_veh']] ?? 'fa-car';
        ?>
        <tr>
            <td>
                <div style="font-weight:700;color:var(--green-700);font-size:14px;font-family:'DM Mono',monospace"><?= htmlspecialchars($c['numero_police']) ?></div>
                <div style="font-size:11px;color:var(--gray-400)"><?= $c['date_creation'] ?></div>
            </td>
            <td style="font-weight:500">
                <?= htmlspecialchars($c['nom_assure'].' '.$c['prenom_assure']) ?>
            </td>
            <td>
                <div class="veh-info-mini">
                    <span class="matricule-plate"><?= htmlspecialchars($c['matricule'] ?? '—') ?></span>
                    <span class="veh-name" style="margin-top:5px"><?= htmlspecialchars(($c['marque'] ?? '').' '.($c['modele'] ?? '')) ?></span>
                    <span class="veh-meta"><?= htmlspecialchars($c['couleur'] ?? '') ?><?= $c['nombre_places'] ? ' · '.$c['nombre_places'].' places' : '' ?></span>
                </div>
            </td>
            <td>
                <div style="display:flex;flex-direction:column;gap:4px">
                    <span class="badge badge-blue"><i class="fa <?= $veh_icon ?>"></i> <?= htmlspecialchars($c['type_veh'] ?? '—') ?></span>
                    <span style="font-size:12px;color:var(--gray-500);font-family:'DM Mono',monospace"><?= $c['annee'] ?? '—' ?></span>
                    <span style="font-size:11px;color:var(--gray-400)"><?= htmlspecialchars($c['carrosserie'] ?? '') ?></span>
                </div>
            </td>
            <td style="text-align:center"><span class="badge badge-blue"><?= $c['duree'] ?> mois</span></td>
            <td style="font-size:12px;font-family:'DM Mono',monospace;color:<?= $expire_bientot?'var(--amber-600)':'inherit' ?>">
                <?= $c['date_expiration'] ?>
                <?php if ($expire_bientot) echo '<div style="font-size:10px;"><i class="fa fa-triangle-exclamation"></i> Bientôt</div>'; ?>
            </td>
            <td style="font-weight:700;color:var(--green-800);font-family:'DM Mono',monospace">
                <?= number_format($c['net_a_payer'],0,',',' ') ?> DA
            </td>
            <td><span class="badge <?= $sb[0] ?>"><?= $sb[1] ?></span></td>
            <td>
                <div style="display:flex;gap:4px;flex-wrap:wrap">
                    <!-- Voir détail -->
                    <button class="btn btn-ghost btn-xs" onclick="toggleDetail(<?= $c['id_contrat'] ?>)" title="Voir détail">
                        <i class="fa fa-eye"></i>
                    </button>
                  
               
                    <!-- Imprimer contrat -->
                    <a href="print_contrat.php?id=<?= $c['id_contrat'] ?>" target="_blank" class="btn btn-ghost btn-xs" title="Imprimer contrat">
                        <i class="fa fa-print"></i>
                    </a>
                    <!-- Résilier -->
                    <?php if ($c['statut'] == 'actif'): ?>
                    <button class="btn btn-danger btn-xs" onclick="confirmResilier(<?= $c['id_contrat'] ?>, '<?= htmlspecialchars($c['numero_police']) ?>')" title="archiver">
                        <i class="fa fa-ban"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>

        <!-- LIGNE DE DÉTAIL EXPANDABLE -->
        <tr id="detail-<?= $c['id_contrat'] ?>" class="detail-row" style="display:none">
            <td colspan="9">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px">

                 <!-- Véhicule (résumé) -->
<div class="detail-section">
    <h4><i class="fa fa-car"></i> Véhicule</h4>
    <div class="detail-grid">

        <div class="detail-item">
            <div class="di-label">Matricule</div>
            <div class="di-val mono"><?= htmlspecialchars($c['matricule'] ?? '—') ?></div>
        </div>

        <div class="detail-item">
            <div class="di-label">Marque / Modèle</div>
            <div class="di-val"><?= htmlspecialchars(($c['marque'] ?? '').' '.($c['modele'] ?? '')) ?></div>
        </div>

        <div class="detail-item">
            <div class="di-label">Type</div>
            <div class="di-val"><?= htmlspecialchars($c['type_veh'] ?? '—') ?></div>
        </div>

        <div class="detail-item">
            <div class="di-label">Année</div>
            <div class="di-val mono"><?= $c['annee'] ?? '—' ?></div>
        </div>

    </div>

    <!-- bouton accès détail -->
    <div style="margin-top:10px">
        <button onclick="openVehicule(<?= $c['id_vehicule'] ?>)"
                class="btn btn-outline btn-sm">
            <i class="fa fa-eye"></i> Voir détails véhicule
        </button>
    </div>
</div>
                    <!-- Finance -->
                    <div class="detail-section">
                        <h4><i class="fa fa-calculator"></i> Détails financiers</h4>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="di-label">Prime base</div>
                                <div class="di-val mono"><?= number_format($c['prime_base'],2,',',' ') ?> DA</div>
                            </div>
                            <div class="detail-item">
                                <div class="di-label">Réduction</div>
                                <div class="di-val mono" style="color:var(--red-600)">- <?= number_format($c['reduction'],2,',',' ') ?> DA</div>
                            </div>
                            <div class="detail-item">
                                <div class="di-label">Majoration</div>
                                <div class="di-val mono" style="color:var(--amber-600)">+ <?= number_format($c['majoration'],2,',',' ') ?> DA</div>
                            </div>
                            <div class="detail-item">
                                <div class="di-label">Complément</div>
                                <div class="di-val mono">+ <?= number_format($c['complement'],2,',',' ') ?> DA</div>
                            </div>
                            <div class="detail-item">
                                <div class="di-label">Prime nette</div>
                                <div class="di-val mono" style="font-weight:700;color:var(--blue-800)"><?= number_format($c['prime_nette'],2,',',' ') ?> DA</div>
                            </div>
                            <div class="detail-item">
                                <div class="di-label">Taxe (<?= round($TAXE*100) ?>%)</div>
                                <div class="di-val mono"><?= number_format($c['prime_nette']*$TAXE,2,',',' ') ?> DA</div>
                            </div>
                            <div class="detail-item">
                                <div class="di-label">Timbre</div>
                                <div class="di-val mono"><?= number_format($TIMBRE,0,',',' ') ?> DA</div>
                            </div>
                            <div class="detail-item">
                                <div class="di-label">Capital assuré</div>
                                <div class="di-val mono" style="color:var(--amber-600)"><?= number_format($c['capital'],0,',',' ') ?> DA</div>
                            </div>
                            <div class="detail-item" style="grid-column:span 2;background:var(--green-50);border:1px solid var(--green-200);border-radius:8px;padding:10px">
                                <div class="di-label">Net à payer</div>
                                <div class="di-val mono" style="font-size:18px;font-weight:700;color:var(--green-700)"><?= number_format($c['net_a_payer'],2,',',' ') ?> DA</div>
                            </div>
                        </div>
                    </div>

                    <!-- Garanties + Actions -->
                    <div class="detail-section">
                        <h4><i class="fa fa-shield-halved"></i> Garanties</h4>
                        <?php if ($c['garanties']): ?>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px">
                            <?php foreach(explode(', ', $c['garanties']) as $gar): ?>
                            <span class="badge badge-green" style="font-size:11px"><?= htmlspecialchars($gar) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <h4 style="margin-top:14px"><i class="fa fa-bolt"></i> Actions rapides</h4>
                        <div style="display:flex;flex-direction:column;gap:8px">
                            <a href="print_contrat.php?id=<?= $c['id_contrat'] ?>" target="_blank" class="btn btn-primary btn-sm" style="justify-content:center">
                                <i class="fa fa-print"></i> Imprimer le contrat
                            </a>
                          
                         
                            <?php if ($c['nb_dossiers'] > 0): ?>
                            <a href="mes_dossiers.php" class="btn btn-teal btn-sm" style="justify-content:center">
                                <i class="fa fa-folder-open"></i> <?= $c['nb_dossiers'] ?> dossier(s) lié(s)
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($total == 0): ?>
        <tr><td colspan="9"><div class="empty-state"><i class="fa fa-file-contract"></i><p>Aucun contrat</p></div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ====== MODAL NOUVEAU CONTRAT ====== -->
<div class="modal-overlay" id="modal-add">
<div class="modal-box">
    <div class="modal-header">
        <h3><i class="fa fa-file-contract" style="width:32px;height:32px;border-radius:8px;background:var(--green-100);color:var(--green-700);display:flex;align-items:center;justify-content:center;"></i> Nouveau contrat d'assurance</h3>
        <div class="steps-nav">
            <button class="step-btn active" onclick="goStep(1,this)" id="step-btn-1"><span class="step-num">1</span> Contrat &amp; Assuré</button>
            <button class="step-btn" onclick="goStep(2,this)" id="step-btn-2"><span class="step-num">2</span> Durée &amp; Dates</button>
            <button class="step-btn" onclick="goStep(3,this)" id="step-btn-3"><span class="step-num">3</span> Véhicule</button>
            <button class="step-btn" onclick="goStep(4,this)" id="step-btn-4"><span class="step-num">4</span> Garanties</button>
            <button class="step-btn" onclick="goStep(5,this)" id="step-btn-5"><span class="step-num">5</span> Prime &amp; Calcul</button>
        </div>
    </div>

    <form method="POST" id="form-contrat">
    <div class="modal-body">

        <!-- STEP 1 -->
        <div class="step-panel active" id="step-1">
            <div class="section-h"><i class="fa fa-file-alt"></i> Informations du contrat</div>
            <div class="grid2">
                <div class="fg">
                    <label>N° Police <span style="color:red">*</span></label>
                    <input type="text" name="numero_police" required placeholder="Ex: CRMA-ALG-2026-001" oninput="this.value=this.value.toUpperCase()">
                </div>
              <div class="fg">
    <label>Assuré <span style="color:red">*</span></label>

    <div style="display:flex; gap:8px; align-items:center;">

        <select name="id_assure" required style="flex:1;">
            <option value="">— Sélectionner l'assuré —</option>
            <?php mysqli_data_seek($assures, 0); while ($a = mysqli_fetch_assoc($assures)): ?>
            <option value="<?= $a['id_assure'] ?>">
                <?= htmlspecialchars($a['nom'].' '.$a['prenom']) ?>
            </option>
            <?php endwhile; ?>
        </select>

        <!-- 🔥 BOUTON + -->
        <a href="gerer_assures.php" target="_blank"
           style="
           padding:10px 14px;
           border-radius:8px;
           background:#16a34a;
           color:white;
           text-decoration:none;
           font-weight:bold;
           ">
           +
        </a>

    </div>

</div>
            </div>
            <div style="background:var(--green-50);border:1px solid var(--green-200);border-radius:10px;padding:13px 16px;font-size:13px;color:var(--green-800);display:flex;align-items:center;gap:10px">
                <i class="fa fa-building" style="color:var(--green-600)"></i>
                Agence : <strong><?= htmlspecialchars($_SESSION['nom_agence'] ?? 'CRMA') ?></strong>
            </div>
        </div>

        <!-- STEP 2 -->
        <div class="step-panel" id="step-2">
            <div class="section-h"><i class="fa fa-calendar"></i> Durée du contrat</div>
            <div class="duree-options">
                <div class="duree-opt"><input type="radio" name="duree" id="dur3" value="3" onchange="computeDates()"><label for="dur3"><span class="dur-num">3</span><span class="dur-lbl">mois</span></label></div>
                <div class="duree-opt"><input type="radio" name="duree" id="dur6" value="6" onchange="computeDates()"><label for="dur6"><span class="dur-num">6</span><span class="dur-lbl">mois</span></label></div>
                <div class="duree-opt"><input type="radio" name="duree" id="dur12" value="12" checked onchange="computeDates()"><label for="dur12"><span class="dur-num">12</span><span class="dur-lbl">mois</span></label></div>
            </div>
            <div class="section-h" style="margin-top:10px"><i class="fa fa-clock"></i> Dates automatiques</div>
            <div class="date-display">
                <div class="date-box">
                    <div class="dlbl"><i class="fa fa-play" style="font-size:9px;color:var(--green-600)"></i> Date d'effet</div>
                    <div class="dval" id="display-effet">—</div>
                    <div style="font-size:11px;color:var(--gray-400)">Demain</div>
                </div>
                <div class="date-box">
                    <div class="dlbl"><i class="fa fa-stop" style="font-size:9px;color:var(--red-600)"></i> Date d'expiration</div>
                    <div class="dval" id="display-exp">—</div>
                    <div style="font-size:11px;color:var(--gray-400)" id="display-nb-jours">—</div>
                </div>
            </div>
            <input type="hidden" name="date_effet" id="hidden-effet">
            <input type="hidden" name="date_expiration" id="hidden-exp">
        </div>

        <!-- STEP 3 — VÉHICULE -->
        <div class="step-panel" id="step-3">
            <div style="background:var(--blue-50);border:1px solid var(--blue-100);border-radius:10px;padding:12px 16px;margin-bottom:18px;font-size:13px;color:var(--blue-800);display:flex;align-items:center;gap:9px">
                <i class="fa fa-info-circle"></i>
                Le véhicule sera créé automatiquement et associé à ce contrat. La matricule doit être unique.
            </div>
            <div class="section-h"><i class="fa fa-car"></i> Identification</div>
            <div class="grid3">
                <div class="fg"><label>Marque <span style="color:red">*</span></label><input type="text" name="marque" required placeholder="Ex: Peugeot"></div>
                <div class="fg"><label>Modèle <span style="color:red">*</span></label><input type="text" name="modele" required placeholder="Ex: 308"></div>
                <div class="fg"><label>Couleur</label><input type="text" name="couleur" placeholder="Ex: Blanc"></div>
            </div>
            <div class="grid3">
                <div class="fg">
                    <label>Matricule <span style="color:red">*</span></label>
                    <input type="text" name="matricule" required placeholder="Ex: 12345-16-001" oninput="this.value=this.value.toUpperCase()" style="font-family:'DM Mono',monospace;font-weight:700;letter-spacing:1px">
                </div>
                <div class="fg"><label>Année</label><input type="number" name="annee" min="1980" max="2030" value="<?= date('Y') ?>"></div>
                <div class="fg"><label>Nb places</label><input type="number" name="nombre_places" min="1" max="100" value="5"></div>
            </div>
            <div class="grid2">
                <div class="fg">
                    <label>Type</label>
                    <select name="type_vehicule">
                        <?php foreach(['Tourisme','Utilitaire','Camion','Bus','Moto','Agricole'] as $t): ?><option><?= $t ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="fg">
                    <label>Carrosserie</label>
                    <select name="carrosserie">
                        <?php foreach(['Berline','Hatchback','SUV','Pick-up','Fourgon','Camion'] as $c): ?><option><?= $c ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="section-h"><i class="fa fa-hashtag"></i> Identifiants techniques</div>
            <div class="grid2">
                <div class="fg"><label>N° Châssis</label><input type="text" name="numero_chassis" placeholder="Numéro de châssis VIN"></div>
                <div class="fg"><label>N° Série</label><input type="text" name="numero_serie" placeholder="Numéro de série"></div>
            </div>
            <div class="section-h"><i class="fa fa-coins"></i> Capital assuré</div>
            <div class="fg" style="max-width:320px">
                <label>Capital du véhicule (DA) <span style="color:red">*</span></label>
                <input type="number" name="capital" id="capital" required min="100000" step="10000" placeholder="Ex: 1 200 000" oninput="recalc()">
                <div style="font-size:11.5px;color:var(--amber-600);margin-top:4px"><i class="fa fa-info-circle"></i> Valeur vénale du véhicule assuré</div>
            </div>
        </div>

        <!-- STEP 4 — GARANTIES -->
        <div class="step-panel" id="step-4">
            <div class="section-h"><i class="fa fa-shield-halved"></i> Sélection des garanties</div>
            <div class="gar-grid">
                <?php
                $icon_map = [1=>'fa-car-crash',2=>'fa-gavel',3=>'fa-lock',4=>'fa-fire',5=>'fa-window-maximize',7=>'fa-car-bump',8=>'fa-shield',9=>'fa-phone-volume',10=>'fa-users',11=>'fa-wrench'];
                foreach ($garanties_list as $g):
                    $is_rc = ($g['id_garantie'] == 1);
                    $icon = $icon_map[$g['id_garantie']] ?? 'fa-shield-halved';
                ?>
                <div class="gar-item <?= $is_rc ? 'obligatoire' : '' ?>">
                    <input type="checkbox" name="garanties[]" value="<?= $g['id_garantie'] ?>" id="gar_<?= $g['id_garantie'] ?>"
                           data-prix="<?= $g['prix'] ?>" data-nom="<?= htmlspecialchars($g['nom_garantie']) ?>"
                           <?= $is_rc ? 'checked disabled' : '' ?> class="gar-check" onchange="recalcGaranties()">
                    <?php if ($is_rc): ?><input type="hidden" name="garanties[]" value="1"><?php endif; ?>
                    <label for="gar_<?= $g['id_garantie'] ?>" class="gar-card">
                        <div class="gar-icon"><i class="fa <?= $icon ?>"></i></div>
                        <div class="gar-info">
                            <div class="gar-name"><?= htmlspecialchars($g['nom_garantie']) ?><?php if ($is_rc): ?><span class="obligatoire-badge">Obligatoire</span><?php endif; ?></div>
                            <div class="gar-prix"><?= number_format($g['prix'],0,',',' ') ?> DA</div>
                        </div>
                        <div class="gar-check-mark" style="<?= $is_rc ? 'background:var(--green-600);border-color:var(--green-600);color:#fff' : '' ?>"><i class="fa fa-check" style="font-size:9px"></i></div>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:16px">
                <div style="font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Garanties sélectionnées :</div>
                <div style="display:flex;flex-wrap:wrap;gap:6px" id="gar-selected-list">
                    <span class="badge badge-green"><i class="fa fa-check" style="font-size:10px"></i> Responsabilité civile</span>
                </div>
            </div>
        </div>

        <!-- STEP 5 — PRIME -->
        <div class="step-panel" id="step-5">
            <div class="section-h"><i class="fa fa-calculator"></i> Calcul de la prime</div>
            <div class="prime-table">
                <div class="prime-row"><div class="p-label"><i class="fa fa-shield-halved" style="color:var(--green-600)"></i> Prime de base (garanties)</div><div class="p-value" id="disp-base">0 DA</div></div>
                <div class="prime-row"><div class="p-label" style="color:var(--red-600)"><i class="fa fa-minus-circle"></i> Réduction</div><div><input type="number" name="reduction" id="reduction" class="p-input" value="0" min="0" step="0.01" oninput="recalc()"></div></div>
                <div class="prime-row"><div class="p-label" style="color:var(--amber-600)"><i class="fa fa-plus-circle"></i> Majoration</div><div><input type="number" name="majoration" id="majoration" class="p-input" value="0" min="0" step="0.01" oninput="recalc()"></div></div>
                <div class="prime-row"><div class="p-label" style="color:var(--blue-600)"><i class="fa fa-layer-group"></i> Complément</div><div><input type="number" name="complement" id="complement" class="p-input" value="500" min="0" step="0.01" oninput="recalc()"></div></div>
                <div class="prime-row" style="background:var(--gray-50)"><div class="p-label" style="font-weight:600"><i class="fa fa-equals"></i> Prime nette</div><div class="p-value" id="disp-nette" style="font-weight:700;color:var(--blue-800)">0 DA</div></div>
                <div class="prime-row"><div class="p-label" style="color:var(--gray-500)"><i class="fa fa-percent"></i> Taxe (<?= round($TAXE*100) ?>%)</div><div class="p-value" id="disp-taxe">0 DA</div></div>
                <div class="prime-row"><div class="p-label" style="color:var(--gray-500)"><i class="fa fa-stamp"></i> Timbre dim.</div><div class="p-value"><?= number_format($TIMBRE,0,',',' ') ?> DA</div></div>
                <div class="prime-row total"><div class="p-label"><i class="fa fa-money-bill-wave"></i> Net à payer</div><div class="p-value" id="disp-net">0 DA</div></div>
            </div>
            <input type="hidden" name="prime_base" id="h-prime-base">

            <!-- Récapitulatif -->
            <div style="background:var(--green-50);border:1px solid var(--green-200);border-radius:12px;padding:16px;margin-top:18px">
                <div style="font-size:12px;font-weight:700;color:var(--green-800);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px"><i class="fa fa-check-double"></i> Récapitulatif</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px">
                    <div style="color:var(--gray-600)">Assuré :</div><div id="recap-assure" style="font-weight:600">—</div>
                    <div style="color:var(--gray-600)">Durée :</div><div id="recap-duree" style="font-weight:600">—</div>
                    <div style="color:var(--gray-600)">Matricule :</div><div id="recap-mat" style="font-weight:600;font-family:'DM Mono',monospace">—</div>
                    <div style="color:var(--gray-600)">Capital :</div><div id="recap-capital" style="font-weight:600;color:var(--amber-600);font-family:'DM Mono',monospace">—</div>
                    <div style="color:var(--gray-600)">Garanties :</div><div id="recap-gar" style="font-weight:600;font-size:12px">—</div>
                </div>
            </div>
        </div>

    </div><!-- /modal-body -->
    <div class="modal-footer">
        <button type="button" class="btn btn-outline" id="btn-prev" onclick="navStep(-1)" style="display:none"><i class="fa fa-arrow-left"></i> Précédent</button>
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-add')">Annuler</button>
        <div style="display:flex;gap:10px;margin-left:auto">
            <button type="button" class="btn btn-primary" id="btn-next" onclick="navStep(1)">Suivant <i class="fa fa-arrow-right"></i></button>
            <button type="submit" name="ajouter" class="btn btn-success" id="btn-submit" style="display:none"><i class="fa fa-save"></i> Créer le contrat</button>
        </div>
    </div>
    </form>
</div>
</div>

<!-- ====== MODAL VÉHICULE (view) ====== -->
<div class="modal-overlay" id="modal-vehicule">
<div class="veh-modal-box" id="veh-modal-content">
    <!-- Injecté par JS -->
</div>
</div>

</div><!-- /crma-main -->

<!-- Données véhicules pour JS -->
<script>
const TAXE   = <?= $TAXE ?>;
const TIMBRE = <?= $TIMBRE ?>;
const TOTAL_STEPS = 5;
let currentStep = 1;

// ── Données véhicules pour modal ──
const vehicules = {
<?php
mysqli_data_seek($contrats, 0);
while ($c = mysqli_fetch_assoc($contrats)) {
    echo "{$c['id_vehicule']}: " . json_encode([
        'matricule'  => $c['matricule'] ?? '',
        'marque'     => $c['marque'] ?? '',
        'modele'     => $c['modele'] ?? '',
        'type_veh'   => $c['type_veh'] ?? '',
        'carrosserie'=> $c['carrosserie'] ?? '',
        'couleur'    => $c['couleur'] ?? '',
        'annee'      => $c['annee'] ?? '',
        'nb_places'  => $c['nombre_places'] ?? '',
        'chassis'    => $c['numero_chassis'] ?? '',
        'serie'      => $c['numero_serie'] ?? '',
        'id_contrat' => $c['id_contrat'],
        'numero_police' => $c['numero_police'],
    ]) . ",\n";
}
?>
};

/* ── Navigation steps ── */
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
    if (dir > 0) document.getElementById('step-btn-' + currentStep).classList.add('done');
    goStep(next, document.getElementById('step-btn-' + next));
}
function updateNavButtons() {
    document.getElementById('btn-prev').style.display   = currentStep > 1 ? 'inline-flex' : 'none';
    document.getElementById('btn-next').style.display   = currentStep < TOTAL_STEPS ? 'inline-flex' : 'none';
    document.getElementById('btn-submit').style.display = currentStep === TOTAL_STEPS ? 'inline-flex' : 'none';
}

/* ── Dates ── */
function computeDates() {
    const checked = document.querySelector('input[name="duree"]:checked');
    const duree = checked ? parseInt(checked.value) : 12;
    const today = new Date(); const effet = new Date(today); effet.setDate(effet.getDate() + 1);
    const exp = new Date(effet); exp.setMonth(exp.getMonth() + duree);
    const fmt = d => d.toLocaleDateString('fr-DZ', {day:'2-digit',month:'2-digit',year:'numeric'});
    const iso = d => d.toISOString().split('T')[0];
    document.getElementById('display-effet').textContent = fmt(effet);
    document.getElementById('display-exp').textContent   = fmt(exp);
    document.getElementById('display-nb-jours').textContent = Math.round((exp-effet)/(1000*60*60*24)) + ' jours';
    document.getElementById('hidden-effet').value = iso(effet);
    document.getElementById('hidden-exp').value   = iso(exp);
}

/* ── Garanties ── */
function recalcGaranties() {
    let base = 0; const selected = [];
    document.querySelectorAll('.gar-check').forEach(cb => {
        if (cb.checked || cb.value === '1') { base += parseFloat(cb.dataset.prix) || 0; selected.push(cb.dataset.nom); }
    });
    const list = document.getElementById('gar-selected-list');
    list.innerHTML = '';
    selected.forEach(n => {
        const tag = document.createElement('span');
        tag.className = 'badge badge-green';
        tag.innerHTML = `<i class="fa fa-check" style="font-size:10px"></i> ${n}`;
        list.appendChild(tag);
    });
    document.getElementById('h-prime-base').value = base.toFixed(2);
    recalc();
}

/* ── Prime ── */
function recalc() {
    const base  = parseFloat(document.getElementById('h-prime-base').value) || 0;
    const red   = parseFloat(document.getElementById('reduction').value)    || 0;
    const maj   = parseFloat(document.getElementById('majoration').value)   || 0;
    const comp  = parseFloat(document.getElementById('complement').value)   || 0;
    const nette = base - red + maj + comp;
    const taxeMont = nette * TAXE;
    const net = nette + TIMBRE + taxeMont;
    const fmt = v => v.toLocaleString('fr-DZ', {minimumFractionDigits:0}) + ' DA';
    document.getElementById('disp-base').textContent  = fmt(base);
    document.getElementById('disp-nette').textContent = fmt(nette);
    document.getElementById('disp-taxe').textContent  = fmt(taxeMont);
    document.getElementById('disp-net').textContent   = fmt(net);
}

/* ── Récapitulatif ── */
function updateRecap() {
    const assureSel = document.querySelector('[name="id_assure"]');
    const durSel = document.querySelector('input[name="duree"]:checked');
    const mat = document.querySelector('[name="matricule"]')?.value || '—';
    const cap = parseFloat(document.querySelector('[name="capital"]')?.value) || 0;
    document.getElementById('recap-assure').textContent  = assureSel?.options[assureSel.selectedIndex]?.text || '—';
    document.getElementById('recap-duree').textContent   = (durSel?.value || 12) + ' mois';
    document.getElementById('recap-mat').textContent     = mat;
    document.getElementById('recap-capital').textContent = cap.toLocaleString('fr-DZ') + ' DA';
    const tags = [];
    document.querySelectorAll('.gar-check').forEach(cb => { if (cb.checked || cb.value==='1') tags.push(cb.dataset.nom); });
    document.getElementById('recap-gar').textContent = tags.join(', ') || '—';
}

/* ── Modal ── */
function openModal(id) {
    document.getElementById(id).classList.add('open');
    computeDates(); recalcGaranties();
    goStep(1, document.getElementById('step-btn-1'));
    document.querySelectorAll('.step-btn').forEach(b => b.classList.remove('done'));
}
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if(e.target===m) m.classList.remove('open'); });
});

/* ── Table detail ── */
function toggleDetail(id) {
    const row = document.getElementById('detail-' + id);
    if (!row) return;
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}

/* ── Modal véhicule ── */
function openVehicule(id) {
    const v = vehicules[id];
    if (!v) return;
    const typeIcons = {Tourisme:'fa-car',Utilitaire:'fa-van-shuttle',Camion:'fa-truck',Bus:'fa-bus',Moto:'fa-motorcycle',Agricole:'fa-tractor'};
    const icon = typeIcons[v.type_veh] || 'fa-car';
    document.getElementById('veh-modal-content').innerHTML = `
        <div class="veh-hero">
            <div class="veh-hero-icon"><i class="fa ${icon}" style="font-size:26px"></i></div>
            <div class="veh-hero-info">
                <h3>${v.matricule}</h3>
                <div class="veh-sub">${v.marque} ${v.modele} · ${v.type_veh} · ${v.annee} — Contrat : ${v.numero_police}</div>
            </div>
        </div>
        <div class="veh-detail-body">
            <div style="font-size:12px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.7px;margin-bottom:12px">Informations complètes</div>
            <div class="veh-detail-grid">
                <div class="veh-detail-card"><div class="vdc-label">Marque</div><div class="vdc-val">${v.marque}</div></div>
                <div class="veh-detail-card"><div class="vdc-label">Modèle</div><div class="vdc-val">${v.modele}</div></div>
                <div class="veh-detail-card"><div class="vdc-label">Couleur</div><div class="vdc-val">${v.couleur || '—'}</div></div>
                <div class="veh-detail-card"><div class="vdc-label">Carrosserie</div><div class="vdc-val">${v.carrosserie || '—'}</div></div>
                <div class="veh-detail-card"><div class="vdc-label">Type</div><div class="vdc-val">${v.type_veh || '—'}</div></div>
                <div class="veh-detail-card"><div class="vdc-label">Année</div><div class="vdc-val mono">${v.annee || '—'}</div></div>
                <div class="veh-detail-card"><div class="vdc-label">Nombre de places</div><div class="vdc-val">${v.nb_places || '—'}</div></div>
                <div class="veh-detail-card"><div class="vdc-label">N° Châssis</div><div class="vdc-val mono" style="font-size:12px">${v.chassis || '—'}</div></div>
                <div class="veh-detail-card"><div class="vdc-label">N° Série</div><div class="vdc-val mono" style="font-size:12px">${v.serie || '—'}</div></div>
            </div>
            <div style="display:flex;gap:10px;margin-top:20px">
     <button onclick="editVehicule(${id})" class="btn btn-primary btn-sm">
    <i class="fa fa-pen"></i> Modifier
</button>
                <a href="print_contrat.php?id=${v.id_contrat}" target="_blank" class="btn btn-outline btn-sm"><i class="fa fa-print"></i> Imprimer le contrat</a>
                <button onclick="closeModal('modal-vehicule')" class="btn btn-ghost btn-sm" style="margin-left:auto">Fermer</button>
            </div>
        </div>`;
    document.getElementById('modal-vehicule').classList.add('open');
}
function editVehicule(id) {
    const v = vehicules[id];

    document.getElementById('veh-modal-content').innerHTML = `
        <div class="veh-detail-body">

        <div class="section-h"><i class="fa fa-pen"></i> Modifier véhicule</div>

        <div class="veh-detail-grid">
  
            <div class="fg">
                <label>Marque</label>
                <input id="marque" value="${v.marque}">
            </div>

            <div class="fg">
                <label>Modèle</label>
                <input id="modele" value="${v.modele}">
            </div>

            <div class="fg">
                <label>Couleur</label>
                <input id="couleur" value="${v.couleur}">
            </div>

            <div class="fg">
                <label>Matricule</label>
                <input value="${v.matricule}" readonly>
            </div>   
               <div class="fg">
    <label>N° Châssis</label>
<input value="${v.chassis}" readonly>
</div>
<div class="fg">  
 <label>N° Série</label>
<input value="${v.serie}" readonly>     
 </div> 

            <div class="fg">
                <label>Type</label>
                <select id="type">
                    <option ${v.type_veh=='Tourisme'?'selected':''}>Tourisme</option>
                    <option ${v.type_veh=='Utilitaire'?'selected':''}>Utilitaire</option>
                    <option ${v.type_veh=='Camion'?'selected':''}>Camion</option>
                    <option ${v.type_veh=='Bus'?'selected':''}>Bus</option>
                    <option ${v.type_veh=='Moto'?'selected':''}>Moto</option>
                    <option ${v.type_veh=='Agricole'?'selected':''}>Agricole</option>
                </select>
            </div>

        </div>

        <div style="display:flex;gap:10px;margin-top:20px">
            <button onclick="saveVehicule(${id})" class="btn btn-success btn-sm">
                <i class="fa fa-save"></i> Enregistrer
            </button>

            <button onclick="openVehicule(${id})" class="btn btn-outline btn-sm">
                Annuler
            </button>
        </div>

        </div>
    `;
}
function saveVehicule(id) {

    fetch('update_vehicule.php?id=' + id, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            marque: document.getElementById('marque').value,
            modele: document.getElementById('modele').value,
            couleur: document.getElementById('couleur').value,
            matricule: document.getElementById('matricule').value,
            type: document.getElementById('type').value
        })
    })
    .then(() => location.reload());
}
/* ── Confirmations ── */
function confirmResilier(id, police) {
    if(confirm(`Résilier le contrat ${police} ?`)) window.location.href=`?statut=resilie&id=${id}`;
}

// Init
computeDates(); recalcGaranties();
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>