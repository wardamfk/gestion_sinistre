<?php
include('../includes/config.php');
include('../includes/header.php');
include('../includes/sidebar.php');

if(isset($_POST['ajouter'])){

    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $telephone = $_POST['telephone'];
    $adresse = $_POST['adresse'];

    $compagnie = $_POST['compagnie'];
    $numero_police = $_POST['numero_police'];
    $responsable = $_POST['responsable'];

    // Ajouter personne
    $sql1 = "INSERT INTO personne (nom, prenom, telephone, adresse, type_personne)
             VALUES ('$nom', '$prenom', '$telephone', '$adresse', 'physique')";
    mysqli_query($conn, $sql1);

    $id_personne = mysqli_insert_id($conn);

    // Ajouter tiers
    $sql2 = "INSERT INTO tiers (id_personne, compagnie_assurance, numero_police, responsable)
             VALUES ('$id_personne', '$compagnie', '$numero_police', '$responsable')";
    mysqli_query($conn, $sql2);

    $success = "Tiers ajouté avec succès";
}
?>

<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/style_crma.css">

 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<div class="main">

    <h2>Ajouter un tiers</h2>

    <?php if(isset($success)) { echo "<p class='success'>$success</p>"; } ?>

    <form method="POST" class="form">

        <label>Nom</label>
        <input type="text" name="nom" required>

        <label>Prénom</label>
        <input type="text" name="prenom" required>

        <label>Téléphone</label>
        <input type="text" name="telephone">

        <label>Adresse</label>
        <input type="text" name="adresse">

        <label>Compagnie assurance</label>
        <input type="text" name="compagnie">

        <label>Numéro police</label>
        <input type="text" name="numero_police">

        <label>Responsabilité</label>
        <select name="responsable">
            <option value="oui">Responsable</option>
            <option value="non">Non responsable</option>
            <option value="partiel">Responsabilité partielle</option>
        </select>

        <br><br>
        <button type="submit" name="ajouter" class="btn">Ajouter tiers</button>

    </form>

</div>
</div>

