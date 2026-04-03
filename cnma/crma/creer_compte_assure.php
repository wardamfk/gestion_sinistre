<?php

session_start();
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit();
}
include("../includes/auth.php");
include("../includes/config.php");

if($_SESSION['role'] != 'CRMA') {
    header("Location: ../pages/login.php");
    exit();
}

// Récupérer personnes qui n'ont pas encore de compte
$sql = "SELECT p.* 
        FROM personne p
        LEFT JOIN utilisateur u ON p.id_personne = u.id_personne
        WHERE u.id_personne IS NULL";
$result = mysqli_query($conn, $sql);

// Créer compte
if(isset($_POST['creer'])) {

    $id_personne = $_POST['id_personne'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = "ASSURE";

    $insert = "INSERT INTO utilisateur (id_personne, email, mot_de_passe, role, actif)
               VALUES ('$id_personne', '$email', '$password', '$role', 1)";

    if(mysqli_query($conn, $insert)) {
        $success = "Compte assuré créé avec succès";
    } else {
        $error = mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Créer compte assuré</title>
    
    <link rel="stylesheet" href="../css/style.css">

   
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>

<?php include("../includes/sidebar.php"); ?>
<?php include("../includes/header.php"); ?>

<div class="main">
    <h2>Créer compte assuré</h2>

    <?php if(isset($success)) echo "<p style='color:green;'>$success</p>"; ?>
    <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <form method="POST" class="form">

        <label>Choisir personne</label>
<select name="id_personne" id="personne" required>
    <option value="">-- Choisir --</option>
    <?php while($row = mysqli_fetch_assoc($result)) { ?>
        <option 
            value="<?php echo $row['id_personne']; ?>"
            data-email="<?php echo $row['email']; ?>">
            <?php echo $row['nom'] . " " . $row['prenom']; ?>
        </option>
    <?php } ?>
</select>

        <label>Email</label>
        <input type="email" name="email" id="email" required>

        <label>Mot de passe</label>
        <input type="password" name="password" required>

        <button type="submit" name="creer" class="btn">Créer compte</button>
    </form>
</div>
<script>
document.getElementById("personne").addEventListener("change", function() {
    var email = this.options[this.selectedIndex].getAttribute("data-email");
    document.getElementById("email").value = email;
});
</script>
</body>
</html>