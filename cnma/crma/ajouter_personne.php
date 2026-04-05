<?php
include("../includes/auth.php");
include("../includes/config.php");

if($_SESSION['role'] != 'CRMA') {
    header("Location: ../pages/login.php");
    exit();
}

// Message succès
if(isset($_GET['success'])) {
    $success = "Personne ajoutée avec succès";
}

// Ajouter personne
if(isset($_POST['ajouter'])) {

    $type = $_POST['type_personne'];
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $raison = $_POST['raison_sociale'];
    $cin = $_POST['num_identite'];
    $tel = $_POST['telephone'];
    $adresse = $_POST['adresse'];
    $email = $_POST['email'];

    $sql = "INSERT INTO personne 
            (type_personne, nom, prenom, raison_sociale, num_identite, telephone, adresse, email)
            VALUES 
            ('$type', '$nom', '$prenom', '$raison', '$cin', '$tel', '$adresse', '$email')";

    if(mysqli_query($conn, $sql)) {
        header("Location: ajouter_personne.php?success=1");
        exit();
    } else {
        $error = "Erreur lors de l'ajout";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ajouter personne</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/style_crma.css">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include("../includes/sidebar.php"); ?>
<?php include("../includes/header.php"); ?>

<div class="main">
    <h2>Ajouter une personne</h2>

    <?php if(isset($success)) echo "<p style='color:green;'>$success</p>"; ?>
    <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <form method="POST" class="form">

        <label>Type personne</label>
        <select name="type_personne" id="type_personne" required>
            <option value="physique">Physique</option>
            <option value="morale">Morale</option>
        </select>

        <div id="physique">
            <label>Nom</label>
            <input type="text" name="nom">

            <label>Prénom</label>
            <input type="text" name="prenom">

            <label>Num identité</label>
            <input type="text" name="num_identite">
        </div>

        <div id="morale" style="display:none;">
            <label>Raison sociale</label>
            <input type="text" name="raison_sociale">
        </div>

        <label>Téléphone</label>
        <input type="text" name="telephone">

        <label>Adresse</label>
        <input type="text" name="adresse">

        <label>Email</label>
        <input type="email" name="email">

        <button type="submit" name="ajouter" class="btn">Enregistrer</button>

    </form>
</div>

<script>
document.getElementById("type_personne").addEventListener("change", function() {
    if(this.value == "physique") {
        document.getElementById("physique").style.display = "block";
        document.getElementById("morale").style.display = "none";
    } else {
        document.getElementById("physique").style.display = "none";
        document.getElementById("morale").style.display = "block";
    }
});
</script>

</body>
</html>