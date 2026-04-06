<?php
include('../includes/auth.php');
include('../includes/config.php');
if ($_SESSION['role'] != 'CRMA') { header('Location: ../pages/login.php'); exit(); }

$page_title = 'Tableau de bord';
$id_user  = $_SESSION['id_user'];
$id_agence= $_SESSION['id_agence'];

// Stats dossiers
$total    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) n FROM dossier WHERE cree_par='$id_user'"))['n'];
$en_cours = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) n FROM dossier WHERE id_etat=2 AND cree_par='$id_user'"))['n'];
$envoyes  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) n FROM dossier WHERE id_etat=3 AND cree_par='$id_user'"))['n'];
$valides  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) n FROM dossier WHERE id_etat=4 AND cree_par='$id_user'"))['n'];
$regles   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) n FROM dossier WHERE id_etat IN(7,8) AND cree_par='$id_user'"))['n'];
$clotures = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) n FROM dossier WHERE id_etat=14 AND cree_par='$id_user'"))['n'];

// Finances
$total_reserve = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(d.total_reserve),0) n FROM dossier d WHERE d.cree_par='$id_user'"))['n'];
$total_regle   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(r.montant),0) n FROM reglement r JOIN dossier d ON r.id_dossier=d.id_dossier WHERE d.cree_par='$id_user'"))['n'];
$total_enc     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT IFNULL(SUM(e.montant),0) n FROM encaissement e JOIN dossier d ON e.id_dossier=d.id_dossier WHERE d.cree_par='$id_user'"))['n'];

// Notifications
$nb_notifs = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) n FROM notification WHERE id_destinataire=$id_user AND lu=0"))['n'];

// Dossiers récents
$derniers = mysqli_query($conn,"
    SELECT d.id_dossier,d.numero_dossier,d.date_creation,d.id_etat,d.total_reserve,e.nom_etat,
           p.nom AS na,p.prenom AS pa,d.date_sinistre
    FROM dossier d
    LEFT JOIN etat_dossier e ON d.id_etat=e.id_etat
    LEFT JOIN contrat c ON d.id_contrat=c.id_contrat
    LEFT JOIN assure ass ON c.id_assure=ass.id_assure
    LEFT JOIN personne p ON ass.id_personne=p.id_personne
    WHERE d.cree_par='$id_user'
    ORDER BY d.id_dossier DESC LIMIT 8");

// Dossiers complement demandé (à traiter)
$a_completer = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) n FROM dossier WHERE cree_par='$id_user' AND id_etat=2 AND statut_validation='non_soumis'"))['n'];

$etat_badge_map = [
    1=>'badge-gray',2=>'badge-blue',3=>'badge-purple',4=>'badge-green',
    5=>'badge-red',6=>'badge-amber',7=>'badge-teal',8=>'badge-green',
    9=>'badge-amber',14=>'badge-gray'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Tableau de bord — CRMA</title>
<link rel="stylesheet" href="../css/style_crma.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php include('../includes/sidebar.php'); ?>
<?php include('../includes/header.php'); ?>

<div class="crma-main">

<!-- HEADING -->
<div class="page-heading">
    <div>
        <h1><i class="fa fa-chart-pie"></i> Tableau de bord</h1>
        <p class="sub"><?= htmlspecialchars($_SESSION['nom_agence']??'') ?> · <?= htmlspecialchars($_SESSION['wilaya']??'') ?> · <?= date('d/m/Y') ?></p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <?php if ($nb_notifs > 0): ?>
        <a href="notifications.php" class="btn btn-danger">
            <i class="fa fa-bell"></i> <?= $nb_notifs ?> notification(s)
        </a>
        <?php endif; ?>
        <a href="creer_dossier.php" class="btn btn-primary">
            <i class="fa fa-folder-plus"></i> Nouveau dossier
        </a>
    </div>
</div>

<!-- STATS GRID -->
<div class="stats-grid">
    <div class="stat-card sc-blue">
        <div class="sc-icon"><i class="fa fa-folder"></i></div>
        <div class="sc-body"><div class="sc-n"><?= $total ?></div><div class="sc-l">Mes dossiers</div></div>
    </div>
    <div class="stat-card sc-amber">
        <div class="sc-icon"><i class="fa fa-clock"></i></div>
        <div class="sc-body"><div class="sc-n"><?= $en_cours ?></div><div class="sc-l">En cours</div></div>
    </div>
    <div class="stat-card sc-teal">
        <div class="sc-icon"><i class="fa fa-paper-plane"></i></div>
        <div class="sc-body"><div class="sc-n"><?= $envoyes ?></div><div class="sc-l">Envoyés CNMA</div></div>
    </div>
    <div class="stat-card sc-green">
        <div class="sc-icon"><i class="fa fa-check-circle"></i></div>
        <div class="sc-body"><div class="sc-n"><?= $valides ?></div><div class="sc-l">Validés</div></div>
    </div>
    <div class="stat-card sc-green">
        <div class="sc-icon"><i class="fa fa-money-bill"></i></div>
        <div class="sc-body"><div class="sc-n"><?= $regles ?></div><div class="sc-l">Réglés</div></div>
    </div>
    <div class="stat-card sc-gray">
        <div class="sc-icon"><i class="fa fa-archive"></i></div>
        <div class="sc-body"><div class="sc-n"><?= $clotures ?></div><div class="sc-l">Clôturés</div></div>
    </div>
</div>

<!-- FINANCE BAR -->
<div class="finance-bar">
    <div class="fi-card fi-reserve">
        <div class="fi-icon"><i class="fa fa-shield-halved"></i></div>
        <div><div class="fi-label">Total réserves</div>
        <div class="fi-value"><?= number_format($total_reserve,0,',',' ') ?><small>DA</small></div></div>
    </div>
    <div class="fi-card fi-regle">
        <div class="fi-icon"><i class="fa fa-money-bill-wave"></i></div>
        <div><div class="fi-label">Total réglé</div>
        <div class="fi-value"><?= number_format($total_regle,0,',',' ') ?><small>DA</small></div></div>
    </div>
    <div class="fi-card fi-reste">
        <div class="fi-icon"><i class="fa fa-scale-balanced"></i></div>
        <div><div class="fi-label">Reste à régler</div>
        <div class="fi-value" style="color:<?= ($total_reserve-$total_regle)>0?'var(--red-600)':'var(--green-700)' ?>">
            <?= number_format($total_reserve-$total_regle,0,',',' ') ?><small>DA</small>
        </div></div>
    </div>
    <div class="fi-card fi-enc">
        <div class="fi-icon"><i class="fa fa-arrow-down"></i></div>
        <div><div class="fi-label">Encaissements</div>
        <div class="fi-value"><?= number_format($total_enc,0,',',' ') ?><small>DA</small></div></div>
    </div>
</div>

<!-- ALERT COMPLEMENT -->
<?php if ($a_completer > 0): ?>
<div class="msg msg-warning" style="margin-bottom:20px">
    <i class="fa fa-exclamation-triangle"></i>
    <span><b><?= $a_completer ?> dossier(s)</b> en attente de complément — vérifiez vos notifications.</span>
    <a href="notifications.php" class="btn btn-warning btn-sm" style="margin-left:auto">Voir</a>
</div>
<?php endif; ?>

<!-- DERNIERS DOSSIERS -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
    <div style="font-size:14px;font-weight:600;color:var(--gray-700);display:flex;align-items:center;gap:8px">
        <i class="fa fa-clock" style="color:var(--green-700)"></i> Derniers dossiers
    </div>
    <a href="mes_dossiers.php" class="btn btn-ghost btn-sm"><i class="fa fa-arrow-right"></i> Voir tous</a>
</div>

<div class="crma-table-wrapper">
<table class="crma-table">
    <thead>
        <tr><th>N° Dossier</th><th>Assuré</th><th>Date sinistre</th><th>État</th><th>Réserve</th><th>Action</th></tr>
    </thead>
    <tbody>
    <?php while ($d = mysqli_fetch_assoc($derniers)):
        $bc = $etat_badge_map[$d['id_etat']] ?? 'badge-gray';
    ?>
    <tr>
        <td>
            <a href="voir_dossier.php?id=<?= $d['id_dossier'] ?>"
               style="font-family:'DM Mono',monospace;font-weight:500;color:var(--green-700);text-decoration:none">
               <?= htmlspecialchars($d['numero_dossier']) ?>
            </a>
        </td>
        <td><?= htmlspecialchars($d['na'].' '.$d['pa']) ?></td>
        <td style="font-size:12px;color:var(--gray-500);font-family:'DM Mono',monospace"><?= $d['date_sinistre'] ?></td>
        <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($d['nom_etat']) ?></span></td>
        <td class="num-cell"><?= number_format($d['total_reserve'],0,',',' ') ?> DA</td>
        <td>
            <a href="voir_dossier.php?id=<?= $d['id_dossier'] ?>" class="btn btn-primary btn-xs">
                <i class="fa fa-eye"></i> Ouvrir
            </a>
        </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</div>

<!-- ACTIONS RAPIDES -->
<div style="margin-top:24px">
    <div style="font-size:13px;font-weight:600;color:var(--gray-600);margin-bottom:12px;text-transform:uppercase;letter-spacing:.5px">
        Actions rapides
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="gerer_personnes.php" class="btn btn-outline"><i class="fa fa-user-plus"></i> Personnes</a>
        <a href="gerer_assures.php"   class="btn btn-outline"><i class="fa fa-id-card"></i> Assurés</a>
        <a href="gerer_vehicules.php" class="btn btn-outline"><i class="fa fa-car"></i> Véhicules</a>
        <a href="gerer_contrats.php"  class="btn btn-outline"><i class="fa fa-file-contract"></i> Contrats</a>
        <a href="creer_dossier.php"   class="btn btn-primary"><i class="fa fa-folder-plus"></i> Nouveau dossier</a>
    </div>
</div>

</div><!-- /crma-main -->
</body>
</html>