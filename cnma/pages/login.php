<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once("../includes/config.php");
if(isset($_POST['login'])) {

    $email = $_POST['email'];
    $password = $_POST['password'];

   $sql = "SELECT u.*, a.nom_agence, a.wilaya
        FROM utilisateur u
        LEFT JOIN agence a ON u.id_agence = a.id_agence
        WHERE u.email='$email' AND u.actif=1";
    $result = mysqli_query($conn, $sql);

    if(mysqli_num_rows($result) == 1) {

        $user = mysqli_fetch_assoc($result);

        if(password_verify($password, $user['mot_de_passe'])) {

            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['id_agence'] = $user['id_agence'];
$_SESSION['nom_agence'] = $user['nom_agence'];
$_SESSION['wilaya'] = $user['wilaya'];

            // REDIRECTION SELON ROLE
          if($user['role'] == 'CNMA') {
    header("Location: dashboard_cnma.php");
    exit();
}
elseif($user['role'] == 'CRMA') {
    header("Location: ../crma/dashboard_crma.php");
    exit();
}
elseif($user['role'] == 'ASSURE') {
    header("Location: ../assure/dashboard_assure.php");
    exit();
}

        } else {
            $error = "Mot de passe incorrect";
        }

    } else {
        $error = "Email incorrect";
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Login CNMA</title>
 <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="login-container">
    <img src="../images/logo.webp" alt="Logo CNMA" class="logo">
    <h2>Gestion des sinistres</h2>

    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Mot de passe" required>
        <button type="submit" name="login">Se connecter</button>
    </form>

    <?php if(isset($error)) { echo "<div class='error'>$error</div>"; } ?>
</div>

</body>
</html>