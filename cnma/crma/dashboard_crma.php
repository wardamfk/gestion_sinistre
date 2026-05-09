<?php
include('../includes/auth.php');
include('../includes/config.php');
if ($_SESSION['role'] != 'CRMA') { header('Location: ../pages/login.php'); exit(); }


$id_user  = $_SESSION['id_user'];
$id_agence= $_SESSION['id_agence'];

// Stats dossiers
$total    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) n FROM dossier WHERE cree_par='$id_user'"))['n'];
$en_cours = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) n FROM dossier WHERE id_etat=2 AND cree_par='$id_user'"))['n'];
$envoyes  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) n FROM dossier WHERE id_etat=3 AND cree_par='$id_user'"))['n'];
$valides  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) n FROM dossier WHERE id_etat=4 AND cree_par='$id_user'"))['n'];
$complement = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) n FROM dossier WHERE id_etat=6 AND cree_par='$id_user'"))['n'];

$classe_sans_suite = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) n FROM dossier WHERE id_etat=11 AND cree_par='$id_user'"))['n'];

$classe_apres_rejet = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) n FROM dossier WHERE id_etat=12 AND cree_par='$id_user'"))['n'];

$attente_recours = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) n FROM dossier WHERE id_etat=13 AND cree_par='$id_user'"))['n'];

$reprise = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) n FROM dossier WHERE id_etat=15 AND cree_par='$id_user'"))['n'];

$contre_expertise = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) n FROM dossier WHERE id_etat=16 AND cree_par='$id_user'"))['n'];

$judiciaire = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) n FROM dossier WHERE id_etat=17 AND cree_par='$id_user'"))['n'];
$expertise = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) n FROM dossier WHERE id_etat=9 AND cree_par='$id_user'"))['n'];

$refuses = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) n FROM dossier WHERE id_etat=5 AND cree_par='$id_user'"))['n'];

$partiel = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) n FROM dossier WHERE id_etat=7 AND cree_par='$id_user'"))['n'];

$total_regle_dossiers = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) n FROM dossier WHERE id_etat=8 AND cree_par='$id_user'"))['n'];
$clotures = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) n FROM dossier WHERE id_etat=14 AND cree_par='$id_user'"))['n'];

// Finances
// ================= FINANCES =================

// Réserves (VRAIE source)
$total_reserve = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT IFNULL(SUM(r.montant),0) n
FROM reserve r
JOIN dossier d ON r.id_dossier = d.id_dossier
WHERE d.cree_par = '$id_user'
"))['n'];

// Réglé
$total_regle = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT IFNULL(SUM(rg.montant),0) n
FROM reglement rg
JOIN dossier d ON rg.id_dossier = d.id_dossier
WHERE d.cree_par = '$id_user'
"))['n'];

// Encaissement
$total_enc = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT IFNULL(SUM(e.montant),0) n
FROM encaissement e
JOIN dossier d ON e.id_dossier = d.id_dossier
WHERE d.cree_par = '$id_user'
"))['n'];

// Reste à régler
$reste = $total_reserve - $total_regle;

// Taux
$taux = $total_reserve > 0 ? round(($total_regle / $total_reserve) * 100,1) : 0;
// Notifications
$nb_notifs = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) n FROM notification WHERE id_destinataire=$id_user AND lu=0"))['n'];


$nb_cheques_attente = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT COUNT(*) as n 
FROM reglement 
WHERE statut='disponible'
"))['n'];
$cheques = mysqli_query($conn,"
SELECT d.numero_dossier, d.id_dossier, r.montant
FROM reglement r
JOIN dossier d ON r.id_dossier = d.id_dossier
WHERE r.statut='disponible'
ORDER BY r.id_reglement DESC
LIMIT 5
");


// Dossiers récents
$derniers = mysqli_query($conn,"
    SELECT 
        d.id_dossier,
        d.numero_dossier,
        d.date_creation,
        d.id_etat,
        IFNULL(SUM(r.montant),0) AS total_reserve,
        e.nom_etat,
        p.nom AS na,
        p.prenom AS pa,
        d.date_sinistre

    FROM dossier d

    LEFT JOIN etat_dossier e ON d.id_etat = e.id_etat
    LEFT JOIN contrat c ON d.id_contrat = c.id_contrat
    LEFT JOIN assure ass ON c.id_assure = ass.id_assure
    LEFT JOIN personne p ON ass.id_personne = p.id_personne

    LEFT JOIN reserve r ON r.id_dossier = d.id_dossier

    WHERE d.cree_par = '$id_user'

    GROUP BY d.id_dossier

    ORDER BY d.id_dossier DESC
    LIMIT 8
");

// Dossiers complement demandé (à traiter)
$a_completer = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) n FROM dossier WHERE cree_par='$id_user' AND id_etat=2 AND statut_validation='non_soumis'"))['n'];

$etat_badge_map = [
    1=>'badge-gray',
    2=>'badge-blue',
    3=>'badge-purple',
    4=>'badge-green',
    5=>'badge-red',
    6=>'badge-amber',
    7=>'badge-teal',
    8=>'badge-green',
    9=>'badge-amber',
    11=>'badge-gray',
    12=>'badge-gray',
    13=>'badge-gray',
    14=>'badge-gray',
    15=>'badge-blue',
    16=>'badge-amber',
    17=>'badge-green'
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
     <a href="notifications.php" class="notif-link">
  <i class="fa fa-bell"></i>
  <span class="notif-text">Notifications</span>
  <span class="notif-badge">4</span>
</a>
        <?php endif; ?>
        <a href="creer_dossier.php" class="btn btn-primary">
            <i class="fa fa-folder-plus"></i> Nouveau dossier
        </a>
    </div>
</div>


<!-- ===== DOSSIERS ===== -->
 <!-- ===== ALERTE CHEQUES ===== -->
<div style="margin-bottom:20px;">

    <div style="
      background:#ffffff;
        border-left:5px solid #e53935;
        padding:15px;
        border-radius:10px;
        box-shadow:0 4px 10px rgba(0,0,0,0.05);
    ">

        <div style="display:flex;justify-content:space-between;align-items:center;">
            
            <div style="font-weight:600;color:#b71c1c;">
                <i class="fa fa-exclamation-triangle"></i> Chèques à remettre
            </div>

           

        </div>

        <div style="font-size:28px;font-weight:bold;margin-top:8px;">
            <?= $nb_cheques_attente ?>
        </div>

        <div style="font-size:13px;color:#555;margin-top:5px;">
            <?= $nb_cheques_attente > 0 
                ? "À remettre "
                : "Aucun chèque en attente" ?>
        </div>

        <?php if($nb_cheques_attente > 0): ?>
        <div style="margin-top:10px;">
         <a href="#" onclick="toggleCheques()" style="color:#2e7d32;font-weight:600;">
    Voir les règlements →
</a>
        </div>

        <?php endif; ?>
        <div id="listeCheques" style="display:none;margin-top:10px;">

    <table class="crma-table">
        <thead>
            <tr>
                <th>Dossier</th>
                <th>Montant</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
        <?php while($row = mysqli_fetch_assoc($cheques)): ?>
        <tr>
            <td>
                <a href="voir_dossier.php?id=<?= $row['id_dossier'] ?>&tab=reglements"
                   style="color:#2e7d32;font-weight:600;">
                   <?= $row['numero_dossier'] ?>
                </a>
            </td>

            <td><?= number_format($row['montant'],2,',',' ') ?> DA</td>

            <td>
                <a href="voir_dossier.php?id=<?= $row['id_dossier'] ?>&tab=reglements"
                   class="btn btn-primary btn-xs">
                   Voir
                </a>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

</div>

    </div>

</div>
<div class="section-title">
    <i class="fa fa-folder"></i> Suivi des dossiers
</div>

<div class="stats-grid">
   <div class="stat-card sc-total">
        <div class="sc-icon"><i class="fa fa-folder"></i></div>
        <div class="sc-body"><div class="sc-n"><?= $total ?></div><div class="sc-l">Les dossiers</div></div>
    </div>
   <div class="stat-card sc-blue">
        <div class="sc-icon"><i class="fa fa-clock"></i></div>
        <div class="sc-body"><div class="sc-n"><?= $en_cours ?></div><div class="sc-l">En cours</div></div>
    </div>
    <div class="stat-card sc-amber">
    <div class="sc-icon"><i class="fa fa-search"></i></div>
    <div class="sc-body"><div class="sc-n"><?= $expertise ?></div><div class="sc-l">Expertise</div></div>
</div>
<div class="stat-card sc-purple">
        <div class="sc-icon"><i class="fa fa-paper-plane"></i></div>
        <div class="sc-body"><div class="sc-n"><?= $envoyes ?></div><div class="sc-l">Envoyés CNMA</div></div>
    </div>
    <div class="stat-card sc-green">
        <div class="sc-icon"><i class="fa fa-check-circle"></i></div>
        <div class="sc-body"><div class="sc-n"><?= $valides ?></div><div class="sc-l">Validés</div></div>
    </div>
    <div class="stat-card sc-red">
    <div class="sc-icon"><i class="fa fa-times-circle"></i></div>
    <div class="sc-body"><div class="sc-n"><?= $refuses ?></div><div class="sc-l">Refusés</div></div>
</div>
<div class="stat-card sc-amber">
    <div class="sc-icon"><i class="fa fa-exclamation"></i></div>
    <div class="sc-body">
        <div class="sc-n"><?= $complement ?></div>
        <div class="sc-l">Complément demandé</div>
    </div>
</div>

   <div class="stat-card sc-teal">
    <div class="sc-icon"><i class="fa fa-hourglass-half"></i></div>
    <div class="sc-body"><div class="sc-n"><?= $partiel ?></div><div class="sc-l">Règlement partiel</div></div>
</div>

<div class="stat-card sc-green">
    <div class="sc-icon"><i class="fa fa-money-bill"></i></div>
    <div class="sc-body"><div class="sc-n"><?= $total_regle_dossiers ?></div><div class="sc-l">Réglés</div></div>
</div>
<div class="stat-card sc-gray">
    <div class="sc-icon"><i class="fa fa-ban"></i></div>
    <div class="sc-body">
        <div class="sc-n"><?= $classe_sans_suite ?></div>
        <div class="sc-l">Classé sans suite</div>
    </div>
</div>
<div class="stat-card sc-gray">
    <div class="sc-icon"><i class="fa fa-times"></i></div>
    <div class="sc-body">
        <div class="sc-n"><?= $classe_apres_rejet ?></div>
        <div class="sc-l">Classé après rejet</div>
    </div>
</div>
<div class="stat-card sc-gray">
    <div class="sc-icon"><i class="fa fa-clock"></i></div>
    <div class="sc-body">
        <div class="sc-n"><?= $attente_recours ?></div>
        <div class="sc-l">Attente recours</div>
    </div>
</div>
<div class="stat-card sc-blue">
    <div class="sc-icon"><i class="fa fa-rotate"></i></div>
    <div class="sc-body">
        <div class="sc-n"><?= $reprise ?></div>
        <div class="sc-l">Repris</div>
    </div>
</div>
<div class="stat-card sc-amber">
    <div class="sc-icon"><i class="fa fa-search"></i></div>
    <div class="sc-body">
        <div class="sc-n"><?= $contre_expertise ?></div>
        <div class="sc-l">Contre expertise</div>
    </div>
</div>
<div class="stat-card sc-green">
    <div class="sc-icon"><i class="fa fa-gavel"></i></div>
    <div class="sc-body">
        <div class="sc-n"><?= $judiciaire ?></div>
        <div class="sc-l">Règlement judiciaire</div>
    </div>
</div>
    <div class="stat-card sc-gray">
        <div class="sc-icon"><i class="fa fa-archive"></i></div>
        <div class="sc-body"><div class="sc-n"><?= $clotures ?></div><div class="sc-l">Clôturés</div></div>
    </div>
</div>


<!-- ===== GRAPHIQUE ETATS ===== -->
<div class="section-title">
    <i class="fa fa-chart-pie"></i> Répartition par état
</div>

<div style="background:#fff;padding:20px;border-radius:12px;max-width:400px">
    <canvas id="chartEtat"></canvas>
</div>

<!-- ===== FINANCE ===== -->
<div class="section-title">
    <i class="fa fa-coins"></i> Situation financière
</div>

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
        <td class="num-cell"><?= number_format($d['total_reserve'] ?? 0, 0, ',', ' ') ?> DA </td>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

const ctx = document.getElementById('chartEtat');

function toggleCheques() {
    let div = document.getElementById("listeCheques");

    if (div.style.display === "none") {
        div.style.display = "block";
    } else {
        div.style.display = "none";
    }
}

new Chart(ctx, {
    type: 'doughnut',
    data: {
  labels: [
    'En cours (<?= (int)$en_cours ?>)',
    'Complément (<?= (int)$complement ?>)',
    'Expertise (<?= (int)$expertise ?>)',
    'Contre expertise (<?= (int)$contre_expertise ?>)',
    'Envoyés CNMA (<?= (int)$envoyes ?>)',
    'Validés (<?= (int)$valides ?>)',
    'Refusés (<?= (int)$refuses ?>)',
    'Partiel (<?= (int)$partiel ?>)',
    'Réglés (<?= (int)$total_regle_dossiers ?>)',
    'Classé sans suite (<?= (int)$classe_sans_suite ?>)',
    'Classé après rejet (<?= (int)$classe_apres_rejet ?>)',
    'Attente recours (<?= (int)$attente_recours ?>)',
    'Repris (<?= (int)$reprise ?>)',
    'Judiciaire (<?= (int)$judiciaire ?>)',
    'Clôturés (<?= (int)$clotures ?>)'
],
        datasets: [{
            data: [
                <?= $en_cours ?>,
                <?= $complement ?>,
                <?= $expertise ?>,
                <?= $contre_expertise ?>,
                <?= $envoyes ?>,
                <?= $valides ?>,
                <?= $refuses ?>,
                <?= $partiel ?>,
                <?= $total_regle_dossiers ?>,
                <?= $classe_sans_suite ?>,
                <?= $classe_apres_rejet ?>,
                <?= $attente_recours ?>,
                <?= $reprise ?>,
                <?= $judiciaire ?>,
                <?= $clotures ?>
            ],
            backgroundColor: [
                '#3b82f6', // en cours
                '#f59e0b', // complément
                '#fbbf24', // expertise
                '#f97316', // contre exp
                '#8b5cf6', // envoyés
                '#16a34a', // validé
                '#dc2626', // refus
                '#0d9488', // partiel
                '#22c55e', // réglé
                '#9ca3af', // classé
                '#6b7280', // classé rejet
                '#4b5563', // recours
                '#2563eb', // repris
                '#14532d',  // judiciaire
                '#000000'   // clôturés
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 12
                }
            }
        }
    }
});
</script>
</body>
</html>
