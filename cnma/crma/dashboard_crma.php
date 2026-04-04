<?php
include("../includes/auth.php");
include("../includes/config.php");

if($_SESSION['role'] != 'CRMA') {
    header("Location: ../pages/login.php");
    exit();
}

// Nombre dossiers créés par cet agent
$id_user = $_SESSION['id_user'];

$sql1 = "SELECT COUNT(*) as total FROM dossier WHERE cree_par = '$id_user'";
$res1 = mysqli_query($conn, $sql1);
$row1 = mysqli_fetch_assoc($res1);
$total = $row1['total'] ? $row1['total'] : 0;


// Dossiers envoyés CNMA
$sql2 = "SELECT COUNT(*) as envoyes FROM dossier WHERE id_etat = 3 AND cree_par = '$id_user'";
$res2 = mysqli_query($conn, $sql2);
$row2 = mysqli_fetch_assoc($res2);
$envoyes = $row2['envoyes'] ? $row2['envoyes'] : 0;

// Dossiers réglés
$sql3 = "SELECT COUNT(*) as payes FROM dossier WHERE id_etat = 14 AND cree_par = '$id_user'";
$res3 = mysqli_query($conn, $sql3);
$row3 = mysqli_fetch_assoc($res3);
$payes = $row3['payes'] ? $row3['payes'] : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>DashboarCRMA</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include("../includes/sidebar.php"); ?>
<?php include("../includes/header.php"); ?>

<div class="main">
    <h2>Tableau de bord CRMA</h2>

    <div class="cards-container">
        <div class="card">
            <h3>Mes dossiers</h3>
            <p class="number"><?php echo $total; ?></p>
        </div>

        <div class="card">
            <h3>Dossiers envoyés CNMA</h3>
            <p class="number"><?php echo $envoyes; ?></p>
        </div>

        <div class="card">
            <h3>Dossiers réglés</h3>
            <p class="number"><?php echo $payes; ?></p>
        </div>
    </div>

    <div class="actions">
        <a href="ajouter_personne.php" class="btn">
            <i class="fa fa-user-plus"></i> Ajouter personne
        </a>

        <a href="creer_dossier.php" class="btn">
            <i class="fa fa-folder-plus"></i> Créer dossier
        </a>

        <a href="mes_dossiers.php" class="btn">
            <i class="fa fa-folder-open"></i> Mes dossiers
        </a>
    </div>
</div>

</body>
</html>