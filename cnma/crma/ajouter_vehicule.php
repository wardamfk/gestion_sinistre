<?php
include("../includes/auth.php");
include("../includes/config.php");

if($_SESSION['role'] != 'CRMA') {
    header("Location: ../pages/login.php");
    exit();
}

// Récupérer les assurés
$sql = "SELECT assure.id_assure, personne.nom, personne.prenom
        FROM assure
        JOIN personne ON assure.id_personne = personne.id_personne";
$result = mysqli_query($conn, $sql);

// Ajouter véhicule
if(isset($_POST['ajouter'])) {

    $id_assure = $_POST['id_assure'];
    $marque = $_POST['marque'];
    $modele = $_POST['modele'];
    $couleur = $_POST['couleur'];
    $nombre_places = $_POST['nombre_places'];
    $matricule = $_POST['matricule'];
    $numero_chassis = $_POST['numero_chassis'];
    $numero_serie = $_POST['numero_serie'];
    $annee = $_POST['annee'];
    $type = $_POST['type'];
    $carrosserie = $_POST['carrosserie'];

    $insert = "INSERT INTO vehicule 
    (marque, modele, couleur, nombre_places, matricule, numero_chassis, numero_serie, annee, type, carrosserie)
    VALUES 
    ('$marque', '$modele', '$couleur', '$nombre_places', '$matricule', '$numero_chassis', '$numero_serie', '$annee', '$type', '$carrosserie')";

    if(mysqli_query($conn, $insert)) {
        $success = "Véhicule ajouté avec succès";
    } else {
        $error = mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ajouter véhicule</title>
    <link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>

<?php include("../includes/sidebar.php"); ?>
<?php include("../includes/header.php"); ?>

<div class="main">
    <h2>Ajouter véhicule</h2>

    <?php if(isset($success)) echo "<p style='color:green;'>$success</p>"; ?>
    <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <form method="POST" class="form">

        <label>Choisir assuré</label>
        <select name="id_assure" required>
            <option value="">-- Choisir --</option>
            <?php while($row = mysqli_fetch_assoc($result)) { ?>
                <option value="<?php echo $row['id_assure']; ?>">
                    <?php echo $row['nom'] . " " . $row['prenom']; ?>
                </option>
            <?php } ?>
        </select>

        <label>Marque</label>
        <input type="text" name="marque" required>

        <label>Modèle</label>
        <input type="text" name="modele" required>

        <label>Couleur</label>
        <input type="text" name="couleur">

        <label>Nombre de places</label>
        <input type="number" name="nombre_places">

        <label>Immatriculation</label>
        <input type="text" name="matricule" required>

        <label>Numéro châssis</label>
        <input type="text" name="numero_chassis">

        <label>Numéro série</label>
        <input type="text" name="numero_serie">

        <label>Année</label>
        <input type="number" name="annee">

        <label>Type</label>
        <select name="type">
            <option value="Tourisme">Tourisme</option>
            <option value="Utilitaire">Utilitaire</option>
            <option value="Camion">Camion</option>
            <option value="Bus">Bus</option>
            <option value="Moto">Moto</option>
            <option value="Agricole">Agricole</option>
        </select>

        <label>Carrosserie</label>
        <select name="carrosserie">
            <option value="Berline">Berline</option>
            <option value="Hatchback">Hatchback</option>
            <option value="SUV">SUV</option>
            <option value="Pick-up">Pick-up</option>
            <option value="Fourgon">Fourgon</option>
            <option value="Camion">Camion</option>
        </select>

        <button type="submit" name="ajouter" class="btn">Ajouter véhicule</button>

    </form>
</div>

</body>
</html>