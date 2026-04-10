<?php
// gerer_contrats.php — CRUD contrats avec calcul automatique
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
    $numero_police  = mysqli_real_escape_string($conn, trim($_POST['numero_police']));
    $id_assure      = intval($_POST['id_assure']);
    $id_vehicule    = intval($_POST['id_vehicule']);
    $id_agence      = intval($_POST['id_agence']);
    $id_formule     = intval($_POST['id_formule']);
    $date_effet     = $_POST['date_effet'];
    $date_exp       = $_POST['date_expiration'];
    $prime_base     = (float)$_POST['prime_base'];
    $reduction      = (float)$_POST['reduction'];
    $majoration     = (float)$_POST['majoration'];
    $complement     = (float)$_POST['complement'];
    $statut         = $_POST['statut'];
    $date_creation  = date('Y-m-d');

    $prime_nette    = $prime_base - $reduction + $majoration;
    $total_taxes    = $prime_nette * $TAXE;
    $total_timbres  = $TIMBRE;
    $net_a_payer    = $prime_nette + $total_taxes + $total_timbres + $complement;

    // Vérif doublon numéro police
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

        // Garanties liées à la formule
        $gars = mysqli_query($conn, "SELECT id_garantie FROM formule_garantie WHERE id_formule=$id_formule");
        while ($g = mysqli_fetch_assoc($gars)) {
            mysqli_query($conn, "INSERT IGNORE INTO contrat_garantie (id_contrat,id_garantie)
                VALUES ($id_contrat,{$g['id_garantie']})");
        }
        $success = "Contrat créé avec garanties automatiques.";
    }
}

/* ======= MODIFIER STATUT ======= */
if (isset($_GET['statut'], $_GET['id'])) {
    $id     = intval($_GET['id']);
    $statut = in_array($_GET['statut'],['actif','expire','suspendu']) ? $_GET['statut'] : 'actif';
    mysqli_query($conn, "UPDATE contrat SET statut='$statut' WHERE id_contrat=$id");
    header("Location: gerer_contrats.php?ok=1"); exit();
}

/* ======= SUPPRIMER ======= */
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $usage = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) n FROM dossier WHERE id_contrat=$id"))['n'];
    if ($usage > 0) {
        $error = "Impossible : ce contrat a des dossiers sinistres associés.";
    } else {
        mysqli_query($conn, "DELETE FROM contrat_garantie WHERE id_contrat=$id");
        mysqli_query($conn, "DELETE FROM contrat WHERE id_contrat=$id");
        $success = "Contrat supprimé.";
    }
}

/* ======= FILTRE ======= */
$filtre_q       = $_GET['q'] ?? '';
$filtre_statut  = $_GET['statut_f'] ?? '';
$id_agence_sess = $_SESSION['id_agence'];
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

/* Données pour les selects */
$assures  = mysqli_query($conn, "SELECT a.id_assure,p.nom,p.prenom FROM assure a JOIN personne p ON a.id_personne=p.id_personne WHERE a.actif=1 ORDER BY p.nom");
$vehicules= mysqli_query($conn, "SELECT id_vehicule,matricule,marque,modele FROM vehicule ORDER BY marque");
$agences  = mysqli_query($conn, "SELECT id_agence,nom_agence FROM agence WHERE type_agence='CRMA'");
$formules = mysqli_query($conn, "SELECT id_formule,nom_formule FROM formule");

$statut_badge = [
    'actif'    => ['badge-green', 'Actif'],
    'expire'   => ['badge-red',   'Expiré'],
    'suspendu' => ['badge-amber', 'Suspendu'],
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
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:900;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal-box{background:#fff;border-radius:16px;padding:30px;width:680px;max-width:95vw;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.18)}
.modal-box h3{font-size:16px;font-weight:600;margin-bottom:22px;padding-bottom:14px;border-bottom:1px solid var(--gray-100);display:flex;align-items:center;gap:8px}
.calc-box{background:var(--green-50);border:1px solid var(--green-200);border-radius:var(--radius);padding:14px 16px;margin-top:16px}
.calc-row{display:flex;justify-content:space-between;padding:4px 0;font-size:13px}
.calc-row.total{border-top:1px solid var(--green-200);margin-top:6px;padding-top:8px;font-weight:600;font-size:14px;color:var(--green-800)}
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

<!-- FILTRES -->
<form method="GET" class="filter-bar">
    <input type="text" name="q" placeholder="N° police, assuré…" value="<?= htmlspecialchars($filtre_q) ?>">
    <select name="statut_f">
        <option value="">Tous les statuts</option>
        <option value="actif"    <?= $filtre_statut=='actif'    ?'selected':'' ?>>Actif</option>
        <option value="expire"   <?= $filtre_statut=='expire'   ?'selected':'' ?>>Expiré</option>
        <option value="suspendu" <?= $filtre_statut=='suspendu' ?'selected':'' ?>>Suspendu</option>
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
            <tr>
                <th>N° Police</th><th>Assuré</th><th>Véhicule</th><th>Formule</th>
                <th>Effet</th><th>Expiration</th><th>Net à payer</th>
                <th>Statut</th><th>Dossiers</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($c = mysqli_fetch_assoc($contrats)):
            $sb = $statut_badge[$c['statut']] ?? ['badge-gray',$c['statut']];
            // Alerte expiration proche (< 30 jours)
            $expire_bientot = $c['statut']=='actif' &&
                (strtotime($c['date_expiration']) - time()) < 30*24*3600 &&
                strtotime($c['date_expiration']) > time();
        ?>
        <tr>
            <td>
                <div class="num-cell" style="color:var(--green-700);font-weight:600"><?= htmlspecialchars($c['numero_police']) ?></div>
                <div style="font-size:11px;color:var(--gray-400)"><?= $c['date_creation'] ?></div>
            </td>
            <td style="font-weight:500"><?= htmlspecialchars($c['nom_assure'].' '.$c['prenom_assure']) ?></td>
            <td>
                <div><?= htmlspecialchars($c['marque'].' '.$c['modele']) ?></div>
                <div class="num-cell" style="font-size:11px;color:var(--gray-500)"><?= htmlspecialchars($c['matricule']) ?></div>
            </td>
            <td><span class="badge badge-purple"><?= htmlspecialchars($c['nom_formule']) ?></span></td>
            <td class="num-cell" style="font-size:12px"><?= $c['date_effet'] ?></td>
            <td class="num-cell" style="font-size:12px;color:<?= $expire_bientot?'var(--amber-600)':'inherit' ?>">
                <?= $c['date_expiration'] ?>
                <?php if ($expire_bientot) echo "<div style='font-size:10px;'>⚠ Bientôt</div>"; ?>
            </td>
            <td class="num-cell" style="font-weight:600"><?= number_format($c['net_a_payer'],0,',',' ') ?> DA</td>
            <td><span class="badge <?= $sb[0] ?>"><?= $sb[1] ?></span></td>
            <td style="text-align:center">
                <?php if ($c['nb_dossiers'] > 0): ?>
                <span class="badge badge-blue"><?= $c['nb_dossiers'] ?></span>
                <?php else: echo '<span style="color:var(--gray-300)">0</span>'; endif; ?>
            </td>
            <td>
                <div style="display:flex;gap:3px;flex-wrap:wrap">
                    <a href="?edit=<?= $c['id_contrat'] ?>#detail-<?= $c['id_contrat'] ?>"
                       class="btn btn-outline btn-xs" title="Voir détail" onclick="toggleDetail(<?= $c['id_contrat'] ?>);return false;">
                        <i class="fa fa-eye"></i>
                    </a>
                    <?php if ($c['statut']=='actif'): ?>
                    <a href="?statut=suspendu&id=<?= $c['id_contrat'] ?>"
                       class="btn btn-xs btn-warning" onclick="return confirm('Suspendre ?')">
                        <i class="fa fa-pause"></i>
                    </a>
                    <?php elseif ($c['statut']=='suspendu'): ?>
                    <a href="?statut=actif&id=<?= $c['id_contrat'] ?>"
                       class="btn btn-xs btn-success">
                        <i class="fa fa-play"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($c['nb_dossiers'] == 0): ?>
                    <a href="?del=<?= $c['id_contrat'] ?>"
                       class="btn btn-xs btn-danger"
                       onclick="return confirm('Supprimer ce contrat ?')"><i class="fa fa-trash"></i></a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <!-- Ligne détail expandable -->
        <tr id="detail-<?= $c['id_contrat'] ?>" style="display:none">
            <td colspan="10" style="background:var(--gray-50);padding:16px 20px">
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;font-size:13px">
                    <div><div style="color:var(--gray-400);font-size:11px;font-weight:600;text-transform:uppercase">Prime base</div><div class="num-cell"><?= number_format($c['prime_base'],2,',',' ') ?> DA</div></div>
                    <div><div style="color:var(--gray-400);font-size:11px;font-weight:600;text-transform:uppercase">Réduction</div><div class="num-cell" style="color:var(--green-700)">- <?= number_format($c['reduction'],2,',',' ') ?> DA</div></div>
                    <div><div style="color:var(--gray-400);font-size:11px;font-weight:600;text-transform:uppercase">Majoration</div><div class="num-cell" style="color:var(--red-600)">+ <?= number_format($c['majoration'],2,',',' ') ?> DA</div></div>
                    <div><div style="color:var(--gray-400);font-size:11px;font-weight:600;text-transform:uppercase">Prime nette</div><div class="num-cell" style="font-weight:700"><?= number_format($c['prime_nette'],2,',',' ') ?> DA</div></div>
                    <div><div style="color:var(--gray-400);font-size:11px;font-weight:600;text-transform:uppercase">Total taxes (19%)</div><div class="num-cell"><?= number_format($c['total_taxes'],2,',',' ') ?> DA</div></div>
                    <div><div style="color:var(--gray-400);font-size:11px;font-weight:600;text-transform:uppercase">Timbre</div><div class="num-cell"><?= number_format($c['total_timbres'],2,',',' ') ?> DA</div></div>
                    <div><div style="color:var(--gray-400);font-size:11px;font-weight:600;text-transform:uppercase">Complément</div><div class="num-cell"><?= number_format($c['complement'],2,',',' ') ?> DA</div></div>
                    <div><div style="color:var(--gray-400);font-size:11px;font-weight:600;text-transform:uppercase">Net à payer</div><div class="num-cell" style="color:var(--green-700);font-size:16px;font-weight:700"><?= number_format($c['net_a_payer'],2,',',' ') ?> DA</div></div>
                </div>
                <?php
                // Garanties
                $gars_c = mysqli_query($conn, "SELECT g.nom_garantie,g.code_garantie FROM contrat_garantie cg JOIN garantie g ON cg.id_garantie=g.id_garantie WHERE cg.id_contrat={$c['id_contrat']}");
                $gar_list = [];
                while ($g = mysqli_fetch_assoc($gars_c)) $gar_list[] = $g;
                if ($gar_list):
                ?>
                <div style="margin-top:14px;display:flex;gap:6px;flex-wrap:wrap">
                    <span style="font-size:11px;font-weight:600;color:var(--gray-400);text-transform:uppercase;line-height:26px;margin-right:4px">Garanties :</span>
                    <?php foreach ($gar_list as $g): ?>
                    <span class="badge badge-teal"><?= htmlspecialchars($g['code_garantie']) ?> — <?= htmlspecialchars($g['nom_garantie']) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($total == 0): ?>
        <tr><td colspan="10"><div class="empty-state"><i class="fa fa-file-contract"></i><p>Aucun contrat</p></div></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- MODAL AJOUTER -->
<div class="modal-overlay" id="modal-add">
<div class="modal-box">
    <h3><i class="fa fa-file-contract" style="color:var(--green-700)"></i> Nouveau contrat</h3>
    <form method="POST" id="form-contrat">
        <div class="form-grid-2">
            <div class="form-group"><label>N° police *</label><input type="text" name="numero_police" required></div>
            <div class="form-group">
                <label>Statut</label>
                <select name="statut">
                    <option value="actif">Actif</option>
                    <option value="suspendu">Suspendu</option>
                </select>
            </div>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label>Assuré *</label>
                <select name="id_assure" required>
                    <option value="">— Sélectionner —</option>
                    <?php while ($a = mysqli_fetch_assoc($assures)): ?>
                    <option value="<?= $a['id_assure'] ?>"><?= htmlspecialchars($a['nom'].' '.$a['prenom']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Véhicule *</label>
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
                <label>Agence *</label>
                <select name="id_agence" required>
                    <?php while ($ag = mysqli_fetch_assoc($agences)): ?>
                    <option value="<?= $ag['id_agence'] ?>" <?= $ag['id_agence']==$id_agence_sess?'selected':'' ?>><?= htmlspecialchars($ag['nom_agence']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Formule *</label>
                <select name="id_formule" required onchange="updateFormule(this.value)">
                    <option value="">— Sélectionner —</option>
                    <?php while ($f = mysqli_fetch_assoc($formules)): ?>
                    <option value="<?= $f['id_formule'] ?>"><?= htmlspecialchars($f['nom_formule']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div id="garanties-preview" style="display:none;margin-bottom:14px">
            <div style="font-size:11px;font-weight:600;color:var(--gray-500);text-transform:uppercase;margin-bottom:6px">Garanties incluses</div>
            <div id="garanties-list" style="display:flex;gap:6px;flex-wrap:wrap"></div>
        </div>
        <div class="form-grid-2">
            <div class="form-group"><label>Date d'effet *</label><input type="date" name="date_effet" required></div>
            <div class="form-group"><label>Date d'expiration *</label><input type="date" name="date_expiration" required></div>
        </div>
        <div style="font-size:11px;font-weight:600;color:var(--gray-500);text-transform:uppercase;margin-bottom:10px">Calcul de la prime</div>
        <div class="form-grid-2">
            <div class="form-group"><label>Prime base (DA) *</label><input type="number" step="0.01" name="prime_base" id="prime_base" oninput="calculer()" required></div>
            <div class="form-group"><label>Réduction (DA)</label><input type="number" step="0.01" name="reduction" id="reduction" value="0" oninput="calculer()"></div>
        </div>
        <div class="form-grid-2">
            <div class="form-group"><label>Majoration (DA)</label><input type="number" step="0.01" name="majoration" id="majoration" value="0" oninput="calculer()"></div>
            <div class="form-group"><label>Complément (DA)</label><input type="number" step="0.01" name="complement" id="complement" value="0" oninput="calculer()"></div>
        </div>
        <!-- Résumé calculé -->
        <div class="calc-box">
            <div class="calc-row"><span>Prime nette</span><span id="r_nette">—</span></div>
            <div class="calc-row"><span>Taxes (<?= round($TAXE*100) ?>%)</span><span id="r_taxes">—</span></div>
            <div class="calc-row"><span>Timbre fiscal</span><span><?= number_format($TIMBRE,0,',',' ') ?> DA</span></div>
            <div class="calc-row"><span>Complément</span><span id="r_complement">—</span></div>
            <div class="calc-row total"><span>Net à payer</span><span id="r_net">—</span></div>
        </div>
        <div style="display:flex;gap:10px;margin-top:20px">
            <button type="submit" name="ajouter" class="btn btn-primary" style="flex:1"><i class="fa fa-save"></i> Créer le contrat</button>
            <button type="button" class="btn btn-outline" onclick="closeModal('modal-add')">Annuler</button>
        </div>
    </form>
</div>
</div>

</div>
<script>
const TAXE   = <?= $TAXE ?>;
const TIMBRE = <?= $TIMBRE ?>;

function calculer() {
    const base       = parseFloat(document.getElementById('prime_base').value) || 0;
    const reduction  = parseFloat(document.getElementById('reduction').value)  || 0;
    const majoration = parseFloat(document.getElementById('majoration').value) || 0;
    const complement = parseFloat(document.getElementById('complement').value) || 0;
    const nette      = base - reduction + majoration;
    const taxes      = nette * TAXE;
    const net        = nette + taxes + TIMBRE + complement;
    const fmt = v => v.toLocaleString('fr-DZ',{minimumFractionDigits:2}) + ' DA';
    document.getElementById('r_nette').textContent     = fmt(nette);
    document.getElementById('r_taxes').textContent     = fmt(taxes);
    document.getElementById('r_complement').textContent= fmt(complement);
    document.getElementById('r_net').textContent       = fmt(net);
}

// Garanties par formule (AJAX simulé via JSON embarqué)
const garantiesParFormule = <?php
    $fg = mysqli_query($conn, "SELECT fg.id_formule,g.nom_garantie,g.code_garantie FROM formule_garantie fg JOIN garantie g ON fg.id_garantie=g.id_garantie ORDER BY fg.id_formule");
    $map = [];
    while ($r = mysqli_fetch_assoc($fg)) $map[$r['id_formule']][] = ['n'=>$r['nom_garantie'],'c'=>$r['code_garantie']];
    echo json_encode($map);
?>;

function updateFormule(id) {
    const box  = document.getElementById('garanties-preview');
    const list = document.getElementById('garanties-list');
    const gars = garantiesParFormule[id];
    if (!gars || !gars.length) { box.style.display='none'; return; }
    list.innerHTML = gars.map(g => `<span class="badge badge-teal">${g.c} — ${g.n}</span>`).join('');
    box.style.display = 'block';
}

function toggleDetail(id) {
    const row = document.getElementById('detail-'+id);
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}

function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if(e.target===m) m.classList.remove('open'); });
});
</script>
</body>
</html>
