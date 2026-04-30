<?php
include('../includes/config.php');
session_start();
$id_user = $_SESSION['id_user'];
$role = $_SESSION['role'];
$id_agence = $_SESSION['id_agence'];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Mes dossiers</title>

    <link rel="stylesheet" href="http://localhost/PfeCnma/cnma/css/style.css">
     <link rel="stylesheet" href="../css/style_crma.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">    
   
</head>
<body>

<?php include('../includes/header.php'); ?>
<?php include('../includes/sidebar.php'); ?>

<div class="main">
    <h2>Mes dossiers</h2>

<?php
if($role == 'CNMA'){

    $sql = "SELECT 
    d.id_dossier,
    d.numero_dossier,
    d.date_creation,
    d.delai_declaration,

    p.nom AS nom_assure,
    p.prenom AS prenom_assure,

    pt.nom AS nom_tiers,
    pt.prenom AS prenom_tiers,
    t.compagnie_assurance,
    t.responsable,

    ed.nom_etat,
    d.id_etat,

    (SELECT IFNULL(SUM(montant),0) 
     FROM reserve r 
     WHERE r.id_dossier = d.id_dossier) AS total_reserve,

    (SELECT IFNULL(SUM(montant),0) 
     FROM reglement r 
     WHERE r.id_dossier = d.id_dossier) AS total_regle

    FROM dossier d
    LEFT JOIN contrat c ON d.id_contrat = c.id_contrat
    LEFT JOIN assure ass ON c.id_assure = ass.id_assure
    LEFT JOIN personne p ON ass.id_personne = p.id_personne
    LEFT JOIN tiers t ON d.id_tiers = t.id_tiers
    LEFT JOIN personne pt ON t.id_personne = pt.id_personne
    LEFT JOIN etat_dossier ed ON d.id_etat = ed.id_etat

    ORDER BY d.id_dossier DESC";
}
else if($role == 'CRMA'){

    $sql = "SELECT 
    d.id_dossier,
    d.numero_dossier,
    d.date_creation,
    d.delai_declaration,

    p.nom AS nom_assure,
    p.prenom AS prenom_assure,

    pt.nom AS nom_tiers,
    pt.prenom AS prenom_tiers,
    t.compagnie_assurance,
    t.responsable,

    ed.nom_etat,
    d.id_etat,

    (SELECT IFNULL(SUM(montant),0) 
     FROM reserve r 
     WHERE r.id_dossier = d.id_dossier) AS total_reserve,

    (SELECT IFNULL(SUM(montant),0) 
     FROM reglement r 
     WHERE r.id_dossier = d.id_dossier) AS total_regle

    FROM dossier d
    LEFT JOIN utilisateur u ON d.cree_par = u.id_user
    LEFT JOIN contrat c ON d.id_contrat = c.id_contrat
    LEFT JOIN assure ass ON c.id_assure = ass.id_assure
    LEFT JOIN personne p ON ass.id_personne = p.id_personne
    LEFT JOIN tiers t ON d.id_tiers = t.id_tiers
    LEFT JOIN personne pt ON t.id_personne = pt.id_personne
    LEFT JOIN etat_dossier ed ON d.id_etat = ed.id_etat

    WHERE u.id_agence = '$id_agence'

    ORDER BY d.id_dossier DESC";
}
else if($role == 'ASSURE'){

    $sql = "SELECT 
    d.id_dossier,
    d.numero_dossier,
    d.date_creation,
    d.delai_declaration,

    p.nom AS nom_assure,
    p.prenom AS prenom_assure,

    pt.nom AS nom_tiers,
    pt.prenom AS prenom_tiers,
    t.compagnie_assurance,
    t.responsable,

    ed.nom_etat,
    d.id_etat,

    (SELECT IFNULL(SUM(montant),0) 
     FROM reserve r 
     WHERE r.id_dossier = d.id_dossier) AS total_reserve,

    (SELECT IFNULL(SUM(montant),0) 
     FROM reglement r 
     WHERE r.id_dossier = d.id_dossier) AS total_regle

    FROM dossier d
    LEFT JOIN contrat c ON d.id_contrat = c.id_contrat
    LEFT JOIN assure ass ON c.id_assure = ass.id_assure
    LEFT JOIN personne p ON ass.id_personne = p.id_personne
    LEFT JOIN tiers t ON d.id_tiers = t.id_tiers
    LEFT JOIN personne pt ON t.id_personne = pt.id_personne
    LEFT JOIN etat_dossier ed ON d.id_etat = ed.id_etat

    WHERE ass.id_personne = (
        SELECT id_personne FROM utilisateur WHERE id_user = '$id_user'
    )

    ORDER BY d.id_dossier DESC";
}
$result = mysqli_query($conn, $sql);
?>

<table class="table">
    <tr>
        <th>N° Dossier</th>
        <th>Date</th>
        <th>Assuré</th>
        <th>Tiers</th>
        <th>Etat</th>
        <th>Réserve</th>
        <th>Réglé</th>
        <th>Délai déclaration</th>
        <th>Actions</th>
    </tr>

<?php while($row = mysqli_fetch_assoc($result)) { ?>

<tr>
    <td class="col-dossier">
        <?php 
        $num = $row['numero_dossier'];
        $parts = explode('-', $num);
        echo $parts[0] . "-" . $parts[1] . "<br>" . $parts[2];
        ?>
    </td>

    <td><?php echo $row['date_creation']; ?></td>

    <td>
        <?php echo $row['nom_assure'] . " " . $row['prenom_assure']; ?>
    </td>

    <td>
        <?php 
        echo $row['nom_tiers'] . " " . $row['prenom_tiers'] . "<br>";
        echo "<small>Assurance: " . $row['compagnie_assurance'] . "</small><br>";
        echo "<small>Resp: " . $row['responsable'] . "</small>";
        ?>
    </td>

    <td>
     <?php
        $etat = $row['id_etat'];
        $nom_etat = $row['nom_etat'];

        $class = "badge";

        if($etat == 2) $class .= " blue";
        elseif($etat == 3) $class .= " purple";
        elseif($etat == 4) $class .= " green";
        elseif($etat == 5) $class .= " red";
        elseif($etat == 7) $class .= " dark";
        elseif($etat == 8) $class .= " gray";
        elseif($etat == 9) $class .= " orange";
        else $class .= " gray";

        echo "<span class='$class'>$nom_etat</span>";
     ?>
    </td>

    <td>
       <span class="money">
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
        <?php echo $row['delai_declaration']; ?> j
    </td>

    <td>
        <!-- Bouton ouvrir -->
        <a class="btn" href="voir_dossier.php?id=<?php echo $row['id_dossier']; ?>&tab=informations">
            <i class="fa fa-folder-open"></i> Ouvrir
        </a>

        <!-- Bouton règlement -->
        <?php if($row['id_etat'] == 4 || $row['id_etat'] == 7){ ?>
            <a class="btn" href="voir_dossier.php?id=<?php echo $row['id_dossier']; ?>&tab=reglements">
                <i class="fa fa-sack-dollar"></i> Règlement
            </a>
        <?php } ?>
    </td>
</tr>

<?php } ?>

</table>

</div>
</body>
</html>