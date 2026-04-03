<?php
include("../includes/auth.php");
include("../includes/config.php");

if($_SESSION['role'] != 'CNMA') {
    header("Location: login.php");
    exit();
}

$sql = "
SELECT
    d.id_dossier, d.numero_dossier, d.date_creation, d.date_transmission,
    d.total_reserve, d.delai_declaration,
    p.nom AS nom_assure, p.prenom AS prenom_assure,
    pt.nom AS nom_tiers, pt.prenom AS prenom_tiers,
    t.compagnie_assurance,
    e.nom_etat,
    (SELECT IFNULL(SUM(montant),0) FROM reglement r WHERE r.id_dossier = d.id_dossier) AS total_regle,
    u.nom AS agent_nom
FROM dossier d
LEFT JOIN contrat c ON d.id_contrat = c.id_contrat
LEFT JOIN assure ass ON c.id_assure = ass.id_assure
LEFT JOIN personne p ON ass.id_personne = p.id_personne
LEFT JOIN tiers t ON d.id_tiers = t.id_tiers
LEFT JOIN personne pt ON t.id_personne = pt.id_personne
LEFT JOIN etat_dossier e ON d.id_etat = e.id_etat
LEFT JOIN utilisateur u ON d.cree_par = u.id_user
WHERE d.id_etat = 3
ORDER BY d.date_transmission ASC, d.date_creation ASC
";

$result = mysqli_query($conn, $sql);
$nb = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dossiers en attente — CNMA</title>
    <link rel="stylesheet" href="../css/style.css">
     <link rel="stylesheet" href="../css/style_cnma.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
        .badge-count { background:#f39c12; color:white; border-radius:20px; padding:4px 14px; font-size:14px; font-weight:bold; }
        .urgent { background: #fff3cd !important; }
        .reserve-high { color: #e74c3c; font-weight: bold; }
    </style>
</head>
<body>

<?php include("sidebar_cnma.php"); ?>
<?php include("../includes/header.php"); ?>

<div class="main">
    <div class="page-header">
        <h2 style="margin:0; color:#1f3a5f;">
            <i class="fa fa-clock" style="color:#f39c12;"></i>
            Dossiers en attente de validation
        </h2>
        <span class="badge-count"><?php echo $nb; ?> dossier(s)</span>
    </div>

    <?php if($nb == 0): ?>
    <div style="background:#d4edda; padding:20px; border-radius:10px; color:#155724; text-align:center; font-size:16px;">
        <i class="fa fa-check-circle fa-2x"></i><br><br>
        Aucun dossier en attente — Tout est traité !
    </div>
    <?php else: ?>

    <table class="table">
        <tr>
            <th>N° Dossier</th>
            <th>Date création</th>
            <th>Transmis le</th>
            <th>Agent CRMA</th>
            <th>Assuré</th>
            <th>Tiers / Compagnie</th>
            <th>Réserve</th>
            <th>Réglé</th>
            <th>Action</th>
        </tr>

        <?php while($row = mysqli_fetch_assoc($result)):
            // Dossier urgent si transmis depuis plus de 5 jours
            $urgence = false;
            if($row['date_transmission']) {
                $diff = (new DateTime())->diff(new DateTime($row['date_transmission']))->days;
                $urgence = ($diff >= 5);
            }
        ?>
        <tr <?php echo $urgence ? "class='urgent'" : ""; ?>>
            <td class="col-dossier">
                <?php
                $parts = explode('-', $row['numero_dossier']);
                echo $parts[0]."-".$parts[1]."<br>".$parts[2];
                ?>
                <?php if($urgence) echo "<br><span style='color:red;font-size:11px;'><i class='fa fa-exclamation-triangle'></i> Urgent</span>"; ?>
            </td>
            <td><?php echo $row['date_creation']; ?></td>
            <td><?php echo $row['date_transmission'] ?: '<span style="color:#999">—</span>'; ?></td>
            <td><?php echo $row['agent_nom']; ?></td>
            <td><?php echo $row['nom_assure'].' '.$row['prenom_assure']; ?></td>
            <td>
                <?php echo $row['nom_tiers'].' '.$row['prenom_tiers']; ?><br>
                <small><?php echo $row['compagnie_assurance']; ?></small>
            </td>
            <td>
                <span class="money <?php echo $row['total_reserve'] > 500000 ? 'reserve-high' : ''; ?>">
                    <?php echo number_format($row['total_reserve'], 2, ',', ' '); ?>
                    <small>DA</small>
                </span>
            </td>
            <td>
                <span class="money">
                    <?php echo number_format($row['total_regle'], 2, ',', ' '); ?>
                    <small>DA</small>
                </span>
            </td>
            <td>
                <a href="voir_dossier_cnma.php?id=<?php echo $row['id_dossier']; ?>" class="btn">
                    <i class="fa fa-gavel"></i> Traiter
                </a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php endif; ?>
</div>
</body>
</html>
