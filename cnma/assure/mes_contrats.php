<?php
require_once __DIR__ . '/../includes/session.php';
pfe_session_start('assure');
include('../includes/config.php');
if(!isset($_SESSION['id_user']) || $_SESSION['role'] != 'ASSURE') { header("Location: ../pages/login.php"); exit(); }
$id_user = $_SESSION['id_user'];
$page_title = "Mes contrats";

$assure = mysqli_fetch_assoc(mysqli_query($conn,"SELECT a.id_assure FROM assure a JOIN utilisateur u ON a.id_personne=u.id_personne WHERE u.id_user=$id_user LIMIT 1"));
$id_assure = $assure ? $assure['id_assure'] : 0;
$contrats = mysqli_query($conn,"
    SELECT c.*, v.marque, v.modele, v.matricule, v.annee, v.type, v.carrosserie, v.nombre_places, v.numero_serie,
           ag.nom_agence, ag.wilaya,
           (SELECT GROUP_CONCAT(g.nom_garantie SEPARATOR ', ')
            FROM contrat_garantie cg
            JOIN garantie g ON cg.id_garantie=g.id_garantie
            WHERE cg.id_contrat=c.id_contrat) AS garanties
    FROM contrat c
    LEFT JOIN vehicule v ON c.id_vehicule=v.id_vehicule
    LEFT JOIN agence ag ON c.id_agence=ag.id_agence
    WHERE c.id_assure=$id_assure ORDER BY c.id_contrat DESC
");

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mes contrats</title>
<link rel="stylesheet" href="../css/style_assure.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.detail-card { margin-top:18px; background:#fff; border:1px solid #dfe3e6; border-radius:12px; padding:18px; }
.detail-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px; }
.detail-item { display:flex; flex-direction:column; gap:4px; }
.detail-item .label { font-size:11px; font-weight:700; color:#607d8b; text-transform:uppercase; letter-spacing:0.5px; }
.detail-item .value { font-size:14px; font-weight:600; color:#263238; }
.detail-actions { display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-top:16px; }
.btn-details { display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border:1px solid #90a4ae; border-radius:8px; background:#fff; color:#37474f; text-decoration:none; cursor:pointer; font-size:12px; }
.detail-panel { display:none; margin-top:16px; }
.detail-panel.open { display:block; }
.small-chip { display:inline-flex; align-items:center; gap:6px; background:#eceff1; border-radius:999px; padding:4px 10px; font-size:12px; color:#455a64; }
</style>
</head>
<body>
<?php  include('sidebar_assure.php'); ?>
<?php include('header_assure.php'); ?>
<div class="assure-main">
    <div class="page-heading">
        <h2><i class="fa fa-file-contract"></i> Mes contrats</h2>
    </div>

    <?php if(mysqli_num_rows($contrats)==0): ?>
    <div style="text-align:center;padding:60px;background:white;border-radius:14px;">
        <i class="fa fa-file-contract" style="font-size:48px;color:#cfd8dc;display:block;margin-bottom:16px;"></i>
        <p style="color:#90a4ae;">Aucun contrat pour le moment</p>
    </div>
    <?php else: while($c = mysqli_fetch_assoc($contrats)):
        $sc = ['actif'=>['green','Actif'],'expire'=>['red','Expiré'],'suspendu'=>['orange','Suspendu']];
        $si = $sc[$c['statut']] ?? ['gray',$c['statut']];
        $expire_bientot = (strtotime($c['date_expiration']) - time()) < 30*24*3600 && $c['statut']=='actif';
    $date1 = new DateTime($c['date_effet']);
$date2 = new DateTime($c['date_expiration']);
$duree = $date1->diff($date2)->days;
    ?>
    <div class="assure-card" style="border-left:4px solid <?= $c['statut']=='actif'?'#2e7d32':'#f57c00'; ?>;">
       <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">

    <!-- LEFT -->
    <div>
        <div style="font-size:18px;font-weight:700;color:#0d47a1;">
            <?= $c['numero_police']; ?>
        </div>
        <div style="font-size:13px;color:#546e7a;margin-top:4px;">
            <?= $c['marque'].' '.$c['modele'].' — '.$c['matricule'].' ('.$c['annee'].')'; ?>
        </div>
    </div>

    <!-- RIGHT -->
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <span class="badge-etat <?= $si[0]; ?>"><?= $si[1]; ?></span>

      <button type="button" class="btn-details" onclick="toggleDetail(<?= $c['id_contrat']; ?>)" title="Afficher le détail du contrat">
            <i class="fa fa-eye"></i> Détails
        </button>

        <?php if($expire_bientot): ?>
        <span class="badge-etat orange">
            <i class="fa fa-exclamation-triangle"></i> Expire bientôt
        </span>
        <?php endif; ?>
    </div>

</div>
      <div style="display:flex;flex-wrap:wrap;gap:40px;margin-top:22px;align-items:center;">

    <div>
        <div style="font-size:11px;color:#78909c;font-weight:700;text-transform:uppercase;">
            Expiration
        </div>

        <div style="font-weight:700;margin-top:4px;color:<?= $expire_bientot?'#e65100':'#263238'; ?>">
            <?= date('d/m/Y', strtotime($c['date_expiration'])) ?>
        </div>
    </div>

    <div>
        <div style="font-size:11px;color:#78909c;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;">
            Net à payer
        </div>

        <div style="font-weight:800;color:#0d47a1;font-size:20px;margin-top:4px;">
            <?= number_format($c['net_a_payer'],2,',',' '); ?> DA
        </div>
    </div>

</div>
          
        </div>
        <div id="contract-detail-<?= $c['id_contrat']; ?>" class="detail-panel">
            <div class="detail-card">
                <h3 class="detail-section-title">
    <i class="fa fa-file-contract"></i>
    Informations générales
</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="label">Numéro de police</div>
                        <div class="value"><?= htmlspecialchars($c['numero_police']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Statut</div>
                        <div class="value"><?= htmlspecialchars(ucfirst($c['statut'])) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Date d'effet</div>
                        <div class="value"><?= htmlspecialchars($c['date_effet']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Date d'expiration</div>
                        <div class="value"><?= htmlspecialchars($c['date_expiration']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Durée</div>
                      <div class="value"><?= round($duree / 30) ?> mois</div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Agence</div>
                        <div class="value"><?= htmlspecialchars($c['nom_agence']) ?> — <?= htmlspecialchars($c['wilaya']) ?></div>
                    </div>
                   
                </div>
                <div class="detail-separator"></div>

<h3 class="detail-section-title">
    <i class="fa fa-car"></i>
    Véhicule assuré
</h3>
<div class="detail-grid">

   
 <div class="detail-item">
                        <div class="label">Véhicule</div>
                        <div class="value"><?= htmlspecialchars($c['marque']) ?> <?= htmlspecialchars($c['modele']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Immatriculation</div>
                        <div class="value"><?= htmlspecialchars($c['matricule']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Année / Type</div>
                        <div class="value"><?= htmlspecialchars($c['annee'] ?? '') ?> / <?= htmlspecialchars($c['type'] ?? '') ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Carrosserie</div>
                        <div class="value"><?= htmlspecialchars($c['carrosserie'] ?? '') ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Nombre de places</div>
                        <div class="value"><?= htmlspecialchars($c['nombre_places'] ?? '') ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="label">N° de série</div>
                        <div class="value"><?= htmlspecialchars($c['numero_serie'] ?? '') ?></div>
                    </div>   </div>

                    <div class="detail-separator"></div>

<h3 class="detail-section-title">
    <i class="fa fa-money-bill-wave"></i>
    Détails financiers
</h3>
<div class="detail-separator"></div>


                <div class="detail-grid" style="margin-top:18px;">
                    <div class="detail-item">
                        <div class="label">Prime de base</div>
                        <div class="value"><?= number_format($c['prime_base'],2,',',' ') ?> DA</div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Réduction</div>
                        <div class="value">- <?= number_format($c['reduction'],2,',',' ') ?> DA</div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Majoration</div>
                        <div class="value">+ <?= number_format($c['majoration'],2,',',' ') ?> DA</div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Complément</div>
                        <div class="value"><?= number_format($c['complement'],2,',',' ') ?> DA</div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Prime nette</div>
                        <div class="value"><?= number_format($c['prime_nette'],2,',',' ') ?> DA</div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Net à payer</div>
                       <div class="value total-price">
    <?= number_format($c['net_a_payer'],2,',',' ') ?> DA
</div>
                    </div>
                </div>
                <div class="detail-separator"></div>
                <h3 class="detail-section-title">
    <i class="fa fa-shield-halved"></i>
    Garanties couvertes
</h3>
                <div class="detail-grid" style="margin-top:18px;">
                    <div class="detail-item" style="grid-column:1 / -1;">
                       
                        <div class="value"><?= htmlspecialchars($c['garanties'] ?? 'Aucune') ?></div>
                    </div>
                </div>
               
            </div>
        </div>
    </div>
    <?php endwhile; endif; ?>
</div>
<script>
function toggleDetail(id) {
    var el = document.getElementById('contract-detail-' + id);
    if (!el) return;
    el.classList.toggle('open');
}
</script>
</body>
</html>
