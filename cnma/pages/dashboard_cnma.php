<?php
include("../includes/auth.php");

if($_SESSION['role'] != 'CNMA') {
    header("Location: ../pages/login.php");
    exit();
}
?>
<?php
include("../includes/auth.php");
include("../includes/config.php");

if($_SESSION['role'] != 'CNMA') {
    header("Location: ../pages/login.php");
    exit();
}

// Nombre total dossiers
$sql1 = "SELECT COUNT(*) as total FROM dossier";
$res1 = mysqli_query($conn, $sql1);
$total = mysqli_fetch_assoc($res1)['total'];

// Dossiers en attente CNMA
$sql2 = "SELECT COUNT(*) as attente FROM dossier WHERE id_etat = 3";
$res2 = mysqli_query($conn, $sql2);
$attente = mysqli_fetch_assoc($res2)['attente'];

// Montant total réserves
$sql3 = "SELECT SUM(montant) as total_reserve FROM reserve";
$res3 = mysqli_query($conn, $sql3);
$reserve = mysqli_fetch_assoc($res3)['total_reserve'];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard CNMA</title>
    <link rel="stylesheet" href="../css/style.css">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
</head>
<body>


<div class="card">
    Nombre total des dossiers : <?php echo $total; ?>
</div>

<div class="card">
    Dossiers en attente : <?php echo $attente; ?>
</div>

<div class="card">
    Montant total des réserves : <?php echo $reserve; ?> DA
</div>

</body>
</html>