<?php
session_start();
include('../includes/config.php');

// Récupérer contrat depuis GET
if(isset($_GET['id_contrat'])){
    $id_contrat = $_GET['id_contrat'];
} else {
    $id_contrat = "";
}

if(isset($_POST['creer'])){

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $id_contrat = $_POST['id_contrat'];
    $id_tiers = $_POST['id_tiers'];
    $date_sinistre = $_POST['date_sinistre'];
    $date_declaration = $_POST['date_declaration'];
    $lieu = $_POST['lieu'];
    $description = $_POST['description'];
    $info = $_POST['info'];
    $id_expert = $_POST['id_expert'];
    $responsable = $_POST['responsable'];

    $cree_par = $_SESSION['id_user'];
    $date_creation = date('Y-m-d');
   $id_etat = 2;
$statut_validation = 'non_soumis';

    // Calcul délai déclaration
    $d1 = new DateTime($date_sinistre);
    $d2 = new DateTime($date_declaration);
    $delai = $d1->diff($d2)->days;

    // Générer numéro dossier
    $annee = date('Y');
    $sql_num = "SELECT COUNT(*) as total FROM dossier WHERE YEAR(date_creation) = '$annee'";
    $result_num = mysqli_query($conn, $sql_num);
    $row_num = mysqli_fetch_assoc($result_num);
    $numero = $row_num['total'] + 1;
    $numero_dossier = "DOS-" . $annee . "-" . str_pad($numero, 4, "0", STR_PAD_LEFT);

    // INSERT DOSSIER AVEC EXPERT
    $sql = "INSERT INTO dossier 
    (numero_dossier, date_creation, cree_par, id_etat, id_contrat, id_tiers, date_sinistre, lieu_sinistre, info_complementaire, description, delai_declaration, id_expert, statut_validation)
    VALUES 
    ('$numero_dossier', '$date_creation', '$cree_par', '$id_etat', '$id_contrat', '$id_tiers', '$date_sinistre', '$lieu', '$info', '$description', '$delai', '$id_expert', '$statut_validation')";

    mysqli_query($conn, $sql);

    $id_dossier = mysqli_insert_id($conn);
    // =======================
// UPLOAD DOCUMENTS
// =======================
$documents = [
    "constat" => 1,
    "pv" => 2,
    "photos" => 3,
    "carte_grise" => 4,
    "permis" => 5,
    "devis" => 6
];

foreach($documents as $input => $id_type){
    if(isset($_FILES[$input]) && $_FILES[$input]['name'] != ""){
        
        $nom_fichier = $_FILES[$input]['name'];
        $tmp = $_FILES[$input]['tmp_name'];
        $chemin = "../uploads/" . $nom_fichier;

        move_uploaded_file($tmp, $chemin);

        mysqli_query($conn, "INSERT INTO document
        (id_dossier, nom_fichier, date_upload, upload_par, id_type_document)
        VALUES
        ('$id_dossier', '$nom_fichier', NOW(), '$cree_par', '$id_type')");
    }
}

    // RESPONSABILITE TIERS
    mysqli_query($conn, "UPDATE tiers SET responsable='$responsable' WHERE id_tiers='$id_tiers'");

   // HISTORIQUE CREATION
mysqli_query($conn, "INSERT INTO historique
(id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
VALUES
('$id_dossier', 'Création dossier', NOW(), '$cree_par', NULL, 2)");

// SI EXPERT AFFECTÉ → PASSER EN EXPERTISE
if($id_expert != ""){
    
    // Ancien état = 2
    $ancien_etat = 2;
    $nouvel_etat = 9;

    // Update état dossier
    mysqli_query($conn, "UPDATE dossier 
                         SET id_etat = 9 
                         WHERE id_dossier = '$id_dossier'");

    // Historique affectation expert
    mysqli_query($conn, "INSERT INTO historique
    (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
    VALUES
    ('$id_dossier', 'Affectation expert', NOW(), '$cree_par', '$ancien_etat', '$nouvel_etat')");
}

    header("Location: mes_dossiers.php?success=1");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Créer dossier</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/style_crma.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include('../includes/header.php'); ?>
<?php include('../includes/sidebar.php'); ?>

<div class="main">
    <h2>Créer un dossier sinistre</h2>

<form method="POST" enctype="multipart/form-data" class="form-grid">

<div class="form-section">
    <h3>Informations sinistre</h3>

<label>Contrat</label>
<select name="id_contrat" onchange="window.location='creer_dossier.php?id_contrat='+this.value" required>
    <option value="">-- Sélectionner contrat --</option>
    <?php
    $res = mysqli_query($conn, "SELECT id_contrat, numero_police FROM contrat");
    while($row = mysqli_fetch_assoc($res)){
        $selected = ($id_contrat == $row['id_contrat']) ? "selected" : "";
        echo "<option value='".$row['id_contrat']."' $selected>".$row['numero_police']."</option>";
    }
    ?>
</select>

<label>Tiers</label>
<select name="id_tiers" required>
    <option value="">-- Sélectionner tiers --</option>
    <?php
    $res = mysqli_query($conn, "SELECT t.id_tiers, p.nom, p.prenom, t.compagnie_assurance
    FROM tiers t 
    JOIN personne p ON t.id_personne = p.id_personne");

    while($row = mysqli_fetch_assoc($res)){
        echo "<option value='".$row['id_tiers']."'>"
        .$row['nom']." ".$row['prenom']." - ".$row['compagnie_assurance'].
        "</option>";
    }
    ?>
</select>

<label>Date sinistre</label>
<input type="date" name="date_sinistre" required>

<label>Date déclaration</label>
<input type="date" name="date_declaration" required>

<label>Lieu sinistre</label>
<input type="text" name="lieu" required>

<label>Responsabilité</label>
<select name="responsable">
    <option value="oui">Responsable</option>
    <option value="non">Non responsable</option>
    <option value="partiel">Responsabilité partielle</option>
</select>

<label>Description</label>
<textarea name="description" required></textarea>

<label>Informations complémentaires</label>
<textarea name="info"></textarea>

<h3>Documents</h3>

<label>Constat</label>
<input type="file" name="constat">

<label>Photos</label>
<input type="file" name="photos">

<label>PV Police</label>
<input type="file" name="pv">

<label>Carte grise</label>
<input type="file" name="carte_grise">

<label>Permis</label>
<input type="file" name="permis">

<label>Devis réparation</label>
<input type="file" name="devis">

</div>

<div class="form-section">
    <h3>Expertise</h3>

<label>Expert</label>
<select name="id_expert" required>
    <option value="">-- Sélectionner expert --</option>
    <?php
    $res = mysqli_query($conn, "SELECT id_expert, nom, prenom FROM expert");
    while($row = mysqli_fetch_assoc($res)){
        echo "<option value='".$row['id_expert']."'>".$row['nom']." ".$row['prenom']."</option>";
    }
    ?>
</select>

<button type="submit" name="creer" class="btn">Créer dossier</button>

</div>
</form>
</div>

</body>
</html>