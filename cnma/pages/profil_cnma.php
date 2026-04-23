<?php
session_start();
include('../includes/config.php');

if (!isset($_SESSION['id_user'])) {
    header("Location: ../pages/login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

/* ===== RÉCUP USER ===== */
$user = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT id_user, nom, email, telephone, mot_de_passe
    FROM utilisateur
    WHERE id_user = $id_user
"));

$success = $error = $pwd_success = $pwd_error = '';

/* ===== UPDATE PROFIL ===== */
if (isset($_POST['update'])) {
    $nom = mysqli_real_escape_string($conn, $_POST['nom']);
    $tel = mysqli_real_escape_string($conn, $_POST['telephone']);

    mysqli_query($conn, "
        UPDATE utilisateur 
        SET nom='$nom', telephone='$tel' 
        WHERE id_user=$id_user
    ");

    $success = "Profil mis à jour";
}

/* ===== CHANGE PASSWORD ===== */
if (isset($_POST['change_pwd'])) {
    $ancien = $_POST['ancien'];
    $new    = $_POST['new'];
    $confirm= $_POST['confirm'];

    if (!password_verify($ancien, $user['mot_de_passe'])) {
        $pwd_error = "Mot de passe incorrect";
    } elseif ($new !== $confirm) {
        $pwd_error = "Confirmation incorrecte";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);

        mysqli_query($conn, "
            UPDATE utilisateur 
            SET mot_de_passe='$hash'
            WHERE id_user=$id_user
        ");

        $pwd_success = "Mot de passe changé";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Profil CNMA</title>

<link rel="stylesheet" href="../css/style_assure.css">
<link rel="stylesheet" href="../css/style_cnma.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>

<?php include('sidebar_cnma.php'); ?>
<?php include('header_cnma.php'); ?>

<div class="assure-main">

<!-- HERO -->
<div class="profil-hero">
    <div class="profil-avatar">
        <?= strtoupper(substr($user['nom'],0,1)); ?>
    </div>
    <div class="profil-hero-info">
        <h3><?= htmlspecialchars($user['nom']); ?></h3>
        <div class="profil-hero-meta">
            <span><i class="fa fa-envelope"></i> <?= $user['email']; ?></span>
            <span><i class="fa fa-phone"></i> <?= $user['telephone'] ?: 'Non renseigné'; ?></span>
        </div>
    </div>
</div>

<!-- MESSAGES -->
<?php if($success) echo "<div class='msg success'>$success</div>"; ?>
<?php if($error) echo "<div class='msg warning'>$error</div>"; ?>

<!-- PROFIL -->
<div class="assure-card">
    <h3><i class="fa fa-user"></i> Informations</h3>

    <div class="info-row">
        <span class="lbl">Nom</span>
        <span class="val"><?= $user['nom']; ?></span>
    </div>

    <div class="info-row">
        <span class="lbl">Email</span>
        <span class="val"><?= $user['email']; ?></span>
    </div>

    <div class="info-row">
        <span class="lbl">Téléphone</span>
        <span class="val"><?= $user['telephone'] ?: '—'; ?></span>
    </div>
</div>

<!-- UPDATE -->
<div class="assure-card">
    <h3><i class="fa fa-pen"></i> Modifier</h3>

    <form method="POST">
        <div class="form-group">
            <label>Nom</label>
            <input type="text" name="nom" value="<?= $user['nom']; ?>" required>
        </div>

        <div class="form-group">
            <label>Téléphone</label>
            <input type="text" name="telephone" value="<?= $user['telephone']; ?>">
        </div>

        <button name="update" class="assure-btn primary">
            <i class="fa fa-save"></i> Enregistrer
        </button>
    </form>
</div>

<!-- PASSWORD -->
<div class="assure-card">
    <h3><i class="fa fa-lock"></i> Mot de passe</h3>

    <?php if($pwd_success) echo "<div class='msg success'>$pwd_success</div>"; ?>
    <?php if($pwd_error) echo "<div class='msg warning'>$pwd_error</div>"; ?>

    <form method="POST">
        <div class="form-group">
            <label>Ancien mot de passe</label>
            <input type="password" name="ancien" required>
        </div>

        <div class="form-group">
            <label>Nouveau mot de passe</label>
            <input type="password" name="new" required>
        </div>

        <div class="form-group">
            <label>Confirmer</label>
            <input type="password" name="confirm" required>
        </div>

        <button name="change_pwd" class="assure-btn primary">
            <i class="fa fa-key"></i> Modifier
        </button>
    </form>
</div>

</div>

</body>
</html>