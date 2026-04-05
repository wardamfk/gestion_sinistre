<?php
include("../includes/auth.php");
include("../includes/config.php");
if($_SESSION['role'] != 'CRMA') { header("Location: ../pages/login.php"); exit(); }
$id_user = $_SESSION['id_user'];
$id_agence = $_SESSION['id_agence'];

$total  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as n FROM dossier WHERE cree_par='$id_user'"))['n'];
$envoyes= mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as n FROM dossier WHERE id_etat=3 AND cree_par='$id_user'"))['n'];
$regles = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as n FROM dossier WHERE id_etat IN(7,8) AND cree_par='$id_user'"))['n'];
$clotures=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as n FROM dossier WHERE id_etat=14 AND cree_par='$id_user'"))['n'];
$en_cours=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as n FROM dossier WHERE id_etat=2 AND cree_par='$id_user'"))['n'];

$total_reserve=mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(d.total_reserve),0) as n FROM dossier d WHERE d.cree_par='$id_user'"))['n'];
$total_regle  =mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(r.montant),0) as n FROM reglement r JOIN dossier d ON r.id_dossier=d.id_dossier WHERE d.cree_par='$id_user'"))['n'];
$total_enc    =mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(e.montant),0) as n FROM encaissement e JOIN dossier d ON e.id_dossier=d.id_dossier WHERE d.cree_par='$id_user'"))['n'];

$nb_notifs=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as n FROM notification WHERE id_destinataire=$id_user AND lu=0"))['n'];

// Derniers dossiers
$derniers=mysqli_query($conn,"
SELECT d.id_dossier,d.numero_dossier,d.date_creation,d.id_etat,d.total_reserve,e.nom_etat,
       p.nom AS nom_assure,p.prenom AS prenom_assure
FROM dossier d
LEFT JOIN etat_dossier e ON d.id_etat=e.id_etat
LEFT JOIN contrat c ON d.id_contrat=c.id_contrat
LEFT JOIN assure ass ON c.id_assure=ass.id_assure
LEFT JOIN personne p ON ass.id_personne=p.id_personne
WHERE d.cree_par='$id_user'
ORDER BY d.id_dossier DESC LIMIT 6
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Dashboard CRMA</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/style_crma.css">
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php include("../includes/sidebar.php"); ?>
<?php include("../includes/header.php"); ?>

<div class="main">

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
    <div>
        <h2 style="color:#0d7b1c;font-size:22px;font-weight:700;margin:0;">
            <i class="fa fa-chart-pie" style="background:#e8f5e9;color:#0d7b1c;padding:10px;border-radius:10px;margin-right:10px;"></i>
            Tableau de bord CRMA
        </h2>
        <p style="color:#78909c;margin:6px 0 0;font-size:13px;"><?= $_SESSION['nom_agence']; ?> — <?= $_SESSION['wilaya']; ?></p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="creer_dossier.php" class="crma-btn primary"><i class="fa fa-folder-plus"></i> Nouveau dossier</a>
        <?php if($nb_notifs>0): ?>
        <a href="notifications.php" class="crma-btn danger">
            <i class="fa fa-bell"></i> <?= $nb_notifs; ?> notification(s)
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- STATS -->
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:24px;">
    <?php
    $stats=[
        ['total','fa-folder','Mes dossiers',$total,'blue'],
        ['en_cours','fa-clock','En cours',$en_cours,'orange'],
        ['envoyes','fa-paper-plane','Envoyés CNMA',$envoyes,'purple'],
        ['regles','fa-money-bill','Réglés',$regles,'teal'],
        ['clotures','fa-archive','Clôturés',$clotures,'gray'],
    ];
    foreach($stats as $s){
    ?>
   <div class="crma-card" style="border-left:4px solid <?= ['blue'=>'#0d47a1','orange'=>'#f57c00','purple'=>'#6a1b9a','teal'=>'#00695c','gray'=>'#546e7a'][$s[4]] ?? '#999'; ?>;">
    
    <div class="icon-box" style="background:<?= ['blue'=>'#e3f2fd','orange'=>'#fff3e0','purple'=>'#f3e5f5','teal'=>'#e0f2f1','gray'=>'#eceff1'][$s[4]] ?? '#eee'; ?>;
    color:<?= ['blue'=>'#0d47a1','orange'=>'#f57c00','purple'=>'#6a1b9a','teal'=>'#00695c','gray'=>'#546e7a'][$s[4]] ?? '#333'; ?>;">
        <i class="fa <?= $s[1]; ?>"></i>
    </div>

    <div class="card-info">
        <div class="card-number"><?= $s[3]; ?></div>
        <div class="card-label"><?= $s[2]; ?></div>
    </div>

</div>
    <?php } ?>
</div>

<!-- BILAN FINANCIER -->
<div class="finance-bar-crma" style="grid-template-columns:repeat(3,1fr);">
    <div class="finance-item-crma reserve">
        <div class="fi-icon"><i class="fa fa-shield-halved"></i></div>
        <div>
            <div class="fi-label">Total Réserves</div>
            <div class="fi-value"><?= number_format($total_reserve,2,',',' '); ?> <span>DA</span></div>
        </div>
    </div>
    <div class="finance-item-crma regle">
        <div class="fi-icon"><i class="fa fa-money-bill-wave"></i></div>
        <div>
            <div class="fi-label">Total Réglé</div>
            <div class="fi-value"><?= number_format($total_regle,2,',',' '); ?> <span>DA</span></div>
        </div>
    </div>
    <div class="finance-item-crma enc">
        <div class="fi-icon" style="background:#e0f2f1;color:#00695c;"><i class="fa fa-arrow-down"></i></div>
        <div>
            <div class="fi-label">Encaissements (recours)</div>
            <div class="fi-value" style="color:#00695c;"><?= number_format($total_enc,2,',',' '); ?> <span>DA</span></div>
        </div>
    </div>
</div>

<!-- DERNIERS DOSSIERS -->
<div style="display:flex;justify-content:space-between;align-items:center;margin:20px 0 12px;">
    <h3 style="color:#0d7b1c;font-size:15px;font-weight:700;margin:0;border-left:4px solid #0d7b1c;padding-left:12px;">
        <i class="fa fa-clock"></i> Mes derniers dossiers
    </h3>
    <a href="mes_dossiers.php" class="crma-btn secondary sm"><i class="fa fa-arrow-right"></i> Voir tous</a>
</div>

<table class="crma-table">
    <thead><tr><th>N° Dossier</th><th>Assuré</th><th>Date</th><th>État</th><th>Réserve</th><th>Action</th></tr></thead>
    <tbody>
    <?php
    $etat_colors=[2=>'blue',3=>'purple',4=>'green',5=>'red',7=>'teal',8=>'green',9=>'orange',14=>'gray'];
    while($d=mysqli_fetch_assoc($derniers)):
        $ec=$etat_colors[$d['id_etat']]??'gray';
    ?>
    <tr>
        <td><b style="color:#0d47a1;"><?= $d['numero_dossier']; ?></b></td>
        <td><?= $d['nom_assure'].' '.$d['prenom_assure']; ?></td>
        <td style="color:#78909c;font-size:12px;"><?= $d['date_creation']; ?></td>
        <td><span class="badge-crma <?= $ec; ?>"><?= $d['nom_etat']; ?></span></td>
        <td style="font-family:monospace;font-weight:700;"><?= number_format($d['total_reserve'],2,',',' '); ?> DA</td>
        <td>
            <a href="voir_dossier.php?id=<?= $d['id_dossier']; ?>" class="crma-btn primary sm"><i class="fa fa-eye"></i> Ouvrir</a>
            <?php if(in_array($d['id_etat'],[4,7])): ?>
            <a href="voir_dossier.php?id=<?= $d['id_dossier']; ?>&tab=reglements" class="crma-btn success sm"><i class="fa fa-money-bill"></i></a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<!-- ACTIONS RAPIDES -->
<div style="margin-top:22px;">
    <h3 style="color:#0d7b1c;font-size:15px;font-weight:700;margin:0 0 12px;border-left:4px solid #0d7b1c;padding-left:12px;">
        <i class="fa fa-bolt"></i> Actions rapides
    </h3>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="ajouter_personne.php" class="crma-btn primary"><i class="fa fa-user-plus"></i> Ajouter personne</a>
        <a href="ajouter_assure.php" class="crma-btn primary"><i class="fa fa-id-card"></i> Ajouter assuré</a>
        <a href="ajouter_vehicule.php" class="crma-btn primary"><i class="fa fa-car"></i> Ajouter véhicule</a>
        <a href="ajouter_contrat.php" class="crma-btn primary"><i class="fa fa-file-contract"></i> Ajouter contrat</a>
        <a href="creer_dossier.php" class="crma-btn success"><i class="fa fa-folder-plus"></i> Créer dossier</a>
        <a href="notifications.php" class="crma-btn <?= $nb_notifs>0?'danger':'secondary'; ?>">
            <i class="fa fa-bell"></i> Notifications <?= $nb_notifs>0?"($nb_notifs)":''; ?>
        </a>
    </div>
</div>

</div>
</body>
</html>