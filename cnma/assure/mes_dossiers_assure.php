<?php
session_start();
include('../includes/config.php');

if(!isset($_SESSION['id_user']) || $_SESSION['role'] != 'ASSURE') {
    header("Location: ../pages/login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

$assure = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT a.id_assure 
     FROM assure a 
     JOIN utilisateur u ON a.id_personne=u.id_personne 
     WHERE u.id_user=$id_user LIMIT 1"));

$id_assure = $assure ? $assure['id_assure'] : 0;

$etat_map = [
    1=>'En cours de déclaration',
    2=>'En cours de traitement',
    3=>'En cours de validation',
    4=>'Dossier accepté',
    5=>'Dossier refusé',
    6=>'Documents manquants',
    7=>'Paiement partiel',
    8=>'Paiement effectué',
    9=>'En cours de traitement',
    11=>'Classé sans suite',
    12=>'Classé',
    13=>'En attente',
    14=>'Dossier clôturé'
];

$etat_class_map = [
    1=>'gray',2=>'blue',3=>'orange',4=>'green',5=>'red',
    6=>'orange',7=>'teal',8=>'green',9=>'blue',
    11=>'gray',12=>'gray',13=>'gray',14=>'gray'
];

// Voir détail dossier
$id_voir = isset($_GET['id']) ? intval($_GET['id']) : 0;
$dossier_detail = null;
$message_refus_assure = null;

if($id_voir > 0) {
    $dossier_detail = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT d.*, v.marque, v.modele, v.matricule, c.numero_police,
               exp.nom AS nom_expert, exp.prenom AS prenom_expert
        FROM dossier d
        JOIN contrat c ON d.id_contrat=c.id_contrat
        LEFT JOIN vehicule v ON c.id_vehicule=v.id_vehicule
        LEFT JOIN expert exp ON d.id_expert=exp.id_expert
        WHERE d.id_dossier=$id_voir AND c.id_assure=$id_assure
    "));

    if($dossier_detail && intval($dossier_detail['id_etat']) === 5) {
        $refus = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT m.message_assure
            FROM historique h
            JOIN motif m ON h.id_motif = m.id_motif
            WHERE h.id_dossier = $id_voir
              AND h.nouvel_etat = 5
              AND m.id_etat = 5
              AND m.message_assure IS NOT NULL
              AND m.message_assure <> ''
            ORDER BY h.date_action DESC, h.id_historique DESC
            LIMIT 1
        "));
        $message_refus_assure = $refus['message_assure'] ?? null;
    }
}

$dossiers = mysqli_query($conn, "
    SELECT d.id_dossier, d.numero_dossier, d.date_sinistre, d.id_etat,
           IFNULL((SELECT SUM(r.montant) 
                   FROM reglement r 
                   WHERE r.id_dossier=d.id_dossier AND r.statut='remis'),0) AS total_recu,
           v.marque, v.modele
    FROM dossier d
    JOIN contrat c ON d.id_contrat=c.id_contrat
    LEFT JOIN vehicule v ON c.id_vehicule=v.id_vehicule
    WHERE c.id_assure=$id_assure
    ORDER BY d.id_dossier DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Mes dossiers</title>
<link rel="stylesheet" href="../css/style_assure.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include('sidebar_assure.php'); ?>
<?php include('header_assure.php'); ?>

<div class="assure-main">

<?php if($dossier_detail) { ?>

    <div class="page-heading">
        <h2>Dossier <?php echo $dossier_detail['numero_dossier']; ?></h2>
        <a href="mes_dossiers_assure.php" class="assure-btn secondary sm">Retour</a>
    </div>

    <?php if($message_refus_assure): ?>
    <div class="assure-card">
        <h3>Decision du dossier</h3>
        <p style="margin:0;color:#c62828;font-weight:600;">
            <?php echo htmlspecialchars($message_refus_assure); ?>
        </p>
    </div>
    <?php endif; ?>

    <div class="assure-card">
        <h3>Mes paiements</h3>

        <?php
        $regs = mysqli_query($conn, "SELECT * FROM reglement WHERE id_dossier=$id_voir");
        if(mysqli_num_rows($regs) == 0) {
            echo "Aucun paiement";
        } else {
        ?>

        <table class="assure-table">
            <tr>
                <th>Date</th>
                <th>Montant</th>
                <th>Mode</th>
                <th>Statut</th>
            </tr>

            <?php while($r = mysqli_fetch_assoc($regs)) { ?>
            <tr>
                <td><?php echo $r['date_reglement']; ?></td>
                <td><?php echo number_format($r['montant'],2,',',' '); ?> DA</td>
                <td><?php echo $r['mode_paiement']; ?></td>
                <td><?php echo $r['statut']; ?></td>
            </tr>
            <?php } ?>

        </table>

        <?php } ?>
    </div>

<?php } else { ?>

    <div class="page-heading">
        <h2>Mes dossiers sinistres</h2>
    </div>

    <table class="assure-table">
        <tr>
            <th>N° Dossier</th>
            <th>Date</th>
            <th>Véhicule</th>
            <th>État</th>
            <th>Montant reçu</th>
            <th>Action</th>
        </tr>

        <?php while($d = mysqli_fetch_assoc($dossiers)) { 
            $ec = isset($etat_class_map[$d['id_etat']]) ? $etat_class_map[$d['id_etat']] : 'gray';
            $en = isset($etat_map[$d['id_etat']]) ? $etat_map[$d['id_etat']] : 'Inconnu';
        ?>
        <tr>
            <td><?php echo $d['numero_dossier']; ?></td>
            <td><?php echo $d['date_sinistre']; ?></td>
            <td><?php echo $d['marque'].' '.$d['modele']; ?></td>
            <td><?php echo $en; ?></td>
            <td><?php echo number_format($d['total_recu'],2,',',' '); ?> DA</td>
            <td>
                <a href="mes_dossiers_assure.php?id=<?php echo $d['id_dossier']; ?>" class="assure-btn primary sm">
                    Voir
                </a>
            </td>
        </tr>
        <?php } ?>

    </table>

<?php } ?>

</div>
</body>
</html>
