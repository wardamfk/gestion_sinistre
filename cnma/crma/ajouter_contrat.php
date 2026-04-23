<?php
include("../includes/auth.php");
include("../includes/config.php");

if($_SESSION['role'] != 'CRMA') {
    header("Location: ../pages/login.php");
    exit();
}

// Assurés
$sql_assure = "SELECT a.id_assure, p.nom, p.prenom
               FROM assure a
               JOIN personne p ON a.id_personne = p.id_personne";
$result_assure = mysqli_query($conn, $sql_assure);

// Véhicules
$sql_vehicule = "SELECT id_vehicule, matricule, marque, modele FROM vehicule";
$result_vehicule = mysqli_query($conn, $sql_vehicule);

// Agence CRMA
$sql_agence = "SELECT id_agence, nom_agence FROM agence WHERE type_agence='CRMA'";
$result_agence = mysqli_query($conn, $sql_agence);

// Ajouter contrat
if(isset($_POST['ajouter'])) {
    $numero_police = $_POST['numero_police'];
    $id_assure = $_POST['id_assure'];
    $id_vehicule = $_POST['id_vehicule'];
    $date_effet = $_POST['date_effet'];
    $date_expiration = $_POST['date_expiration'];
    $prime_base = $_POST['prime_base'];
    $reduction = $_POST['reduction'];
    $majoration = $_POST['majoration'];
     $complement = $_POST['complement'];
   // Récupérer taxe et timbre depuis la table parametre
$sql_param = "SELECT nom, valeur FROM parametre";
$result_param = mysqli_query($conn, $sql_param);

$taxe = 0;
$timbre = 0;

while($row = mysqli_fetch_assoc($result_param)){
    if($row['nom'] == 'taxe'){
        $taxe = $row['valeur'];
    }
    if($row['nom'] == 'timbre'){
        $timbre = $row['valeur'];
    }
}
// Calculs automatiques
// Calcul prime nette
$prime_nette = $prime_base - $reduction + $majoration;

// Calcul taxes et timbres
$total_taxes = $prime_nette * $taxe;
$total_timbres = $timbre;

// Calcul net à payer
$net_a_payer = $prime_nette + $total_taxes + $total_timbres + $complement;
 
    $statut = $_POST['statut'];
   
    $date_creation = date('Y-m-d');
    $id_agence = $_POST['id_agence'];

   $insert = "INSERT INTO contrat 
(numero_police, id_assure, id_vehicule, date_effet, date_expiration, prime_base, reduction, majoration, prime_nette, complement, total_taxes, total_timbres, net_a_payer, statut, date_creation, id_agence)
VALUES
('$numero_police', '$id_assure', '$id_vehicule', '$date_effet', '$date_expiration', '$prime_base', '$reduction', '$majoration', '$prime_nette', '$complement', '$total_taxes', '$total_timbres', '$net_a_payer', '$statut', '$date_creation', '$id_agence')";
  if(mysqli_query($conn, $insert)) {
    $success = "Contrat ajouté avec succès";
} else {
    $error = "Erreur lors de l'ajout du contrat";
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ajouter contrat</title>
   
<link rel="stylesheet" href="../css/style.css">

 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include("../includes/sidebar.php"); ?>
<?php include("../includes/header.php"); ?>

<div class="main">
    <h2>Ajouter contrat</h2>

    <?php if(isset($success)) echo "<p style='color:green;'>$success</p>"; ?>
    <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <form method="POST" class="form">

        <label>Numéro police</label>
        <input type="text" name="numero_police" required>

        <label>Assuré</label>
        <select name="id_assure" required>
            <?php while($row = mysqli_fetch_assoc($result_assure)) { ?>
                <option value="<?php echo $row['id_assure']; ?>">
                    <?php echo $row['nom']." ".$row['prenom']; ?>
                </option>
            <?php } ?>
        </select>

     <label>Véhicule</label>
<select name="id_vehicule" required>
<?php while($row = mysqli_fetch_assoc($result_vehicule)) { ?>
    <option value="<?php echo $row['id_vehicule']; ?>">
        <?php echo $row['matricule']." - ".$row['marque']." - ".$row['modele']; ?>
    </option>
<?php } ?>
</select>
        <label>Agence</label>
        <select name="id_agence" required>
            <?php while($row = mysqli_fetch_assoc($result_agence)) { ?>
                <option value="<?php echo $row['id_agence']; ?>">
                    <?php echo $row['nom_agence']; ?>
                </option>
            <?php } ?>
        </select>

        <label>Date effet</label>
        <input type="date" name="date_effet">

        <label>Date expiration</label>
        <input type="date" name="date_expiration">

        <label>Prime base</label>
        <input type="number" step="0.01" name="prime_base">

        <label>Réduction</label>
        <input type="number" step="0.01" name="reduction">

        <label>Majoration</label>
        <input type="number" step="0.01" name="majoration">

        <label>Prime nette</label>
        <input type="number" step="0.01" name="prime_nette" readonly>

        <label>Complément</label>
        <input type="number" step="0.01" name="complement">

        <label>Total taxes</label>
        <input type="number" step="0.01"name="total_taxes" readonly>

        <label>Total timbres</label>
        <input type="number" step="0.01" name="total_timbres" readonly>

        <label>Net à payer</label>
        <input type="number" step="0.01" name="net_a_payer" readonly>

        <label>Statut</label>
        <select name="statut">
            <option value="actif">Actif</option>
            <option value="expire">Expiré</option>
            <option value="suspendu">Suspendu</option>
        </select>

       

        <button type="submit" name="ajouter" class="btn">Ajouter contrat</button>

    </form>
</div>
<script>
function calculer() {
    let base = parseFloat(document.getElementsByName("prime_base")[0].value) || 0;
    let reduction = parseFloat(document.getElementsByName("reduction")[0].value) || 0;
    let majoration = parseFloat(document.getElementsByName("majoration")[0].value) || 0;
    let complement = parseFloat(document.getElementsByName("complement")[0].value) || 0;

    let taxe = 0.19;
    let timbre = 1500;

    let prime_nette = base - reduction + majoration;
    let total_taxes = prime_nette * taxe;
    let net = prime_nette + total_taxes + timbre + complement;

    document.getElementsByName("prime_nette")[0].value = prime_nette.toFixed(2);
    document.getElementsByName("total_taxes")[0].value = total_taxes.toFixed(2);
    document.getElementsByName("total_timbres")[0].value = timbre.toFixed(2);
    document.getElementsByName("net_a_payer")[0].value = net.toFixed(2);
}

document.querySelectorAll("input").forEach(input => {
    input.addEventListener("input", calculer);
});
</script>
</body>
</html>