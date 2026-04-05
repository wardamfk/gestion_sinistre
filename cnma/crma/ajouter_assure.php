<?php
include("../includes/auth.php");
include("../includes/config.php");

if($_SESSION['role'] != 'CRMA') {
    header("Location: ../pages/login.php");
    exit();
}

// Message succès
if(isset($_GET['success'])) {
    $success = "Assuré ajouté avec succès";
}

// Récupérer les personnes NON assurées
$sql = "SELECT * FROM personne 
        WHERE id_personne NOT IN (SELECT id_personne FROM assure)";
$result = mysqli_query($conn, $sql);

// Ajouter assuré
if(isset($_POST['ajouter'])) {

    $id_personne = $_POST['id_personne'];
    $date_creation = $_POST['date_creation'];
    $actif = $_POST['statut'];

    $num_permis = $_POST['num_permis'];
    $date_delivrance = $_POST['date_delivrance_permis'];
    $lieu_delivrance = $_POST['lieu_delivrance_permis'];
    $type_permis = $_POST['type_permis'];
   

    $insert = "INSERT INTO assure 
        (id_personne, date_creation, actif, num_permis, date_delivrance_permis, lieu_delivrance_permis, type_permis)
        VALUES 
        ('$id_personne', '$date_creation', '$actif', '$num_permis', '$date_delivrance', '$lieu_delivrance', '$type_permis')";
    
    if(mysqli_query($conn, $insert)) {
        header("Location: ajouter_assure.php?success=1");
        exit();
    } else {
        $error = mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ajouter assuré</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/style_crma.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include("../includes/sidebar.php"); ?>
<?php include("../includes/header.php"); ?>

<div class="main">
    <h2>Ajouter assuré</h2>

    <?php if(isset($success)) echo "<p style='color:green;'>$success</p>"; ?>
    <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <form method="POST" class="form">

       <label>Choisir personne</label>
<select name="id_personne" required>
    <option value="">-- Choisir --</option>
    <?php while($row = mysqli_fetch_assoc($result)) { ?>
        <option value="<?php echo $row['id_personne']; ?>">
            <?php echo $row['nom'] . " " . $row['prenom']; ?>
        </option>
    <?php } ?>
</select>

<label>Date création</label>
<input type="date" name="date_creation" required>
<label>Statut</label>
        <select name="statut">
            <option value="1">Actif</option>
            <option value="0">Suspendu</option>
        </select>

<label>Numéro permis</label>
<input type="text" name="num_permis">

<label>Date délivrance permis</label>
<input type="date" name="date_delivrance_permis">

<label>Lieu délivrance permis</label>
<input type="text" name="lieu_delivrance_permis">

<label>Type permis</label>
<select name="type_permis">
    <option value="A">A</option>
    <option value="B">B</option>
    <option value="C">C</option>
    <option value="D">D</option>
</select>



        
        <button type="submit" name="ajouter" class="btn">Ajouter assuré</button>
    </form>
</div>

</body>
</html>