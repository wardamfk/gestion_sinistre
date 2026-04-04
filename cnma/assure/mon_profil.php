<?php
session_start();
include('../includes/config.php');
if(!isset($_SESSION['id_user']) || $_SESSION['role'] != 'ASSURE') { header("Location: ../pages/login.php"); exit(); }
$id_user = $_SESSION['id_user'];
$page_title = "Mon profil";

$user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT u.*, p.*, a.num_permis, a.type_permis, a.date_delivrance_permis, a.actif AS statut_assure FROM utilisateur u LEFT JOIN personne p ON u.id_personne=p.id_personne LEFT JOIN assure a ON a.id_personne=u.id_personne WHERE u.id_user=$id_user"));

$success = $error = '';
if(isset($_POST['modifier'])) {
    $tel = mysqli_real_escape_string($conn, $_POST['telephone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $adresse = mysqli_real_escape_string($conn, $_POST['adresse']);
    $id_personne = $user['id_personne'];
    mysqli_query($conn,"UPDATE personne SET telephone='$tel', email='$email', adresse='$adresse' WHERE id_personne=$id_personne");
    // Màj email utilisateur
    mysqli_query($conn,"UPDATE utilisateur SET email='$email' WHERE id_user=$id_user");
    $_SESSION['nom'] = $user['nom'].' '.$user['prenom'];
    $success = "Profil mis à jour avec succès";
    $user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT u.*, p.*, a.num_permis, a.type_permis, a.date_delivrance_permis, a.actif AS statut_assure FROM utilisateur u LEFT JOIN personne p ON u.id_personne=p.id_personne LEFT JOIN assure a ON a.id_personne=u.id_personne WHERE u.id_user=$id_user"));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mon profil</title>
<link rel="stylesheet" href="../css/style_assure.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php include('sidebar_assure.php'); ?>
<?php include('header_assure.php'); ?>
<div class="assure-main">
    <div class="page-heading">
        <h2><i class="fa fa-user-circle"></i> Mon profil</h2>
        <span class="badge-etat <?= $user['statut_assure']?'green':'red'; ?>"><?= $user['statut_assure']?'Actif':'Suspendu'; ?></span>
    </div>

    <?php if($success) echo "<div class='msg success'><i class='fa fa-check-circle'></i> $success</div>"; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:22px;">

        <!-- Infos consultation -->
        <div class="assure-card">
            <h3><i class="fa fa-id-card"></i> Mes informations</h3>
            <div class="info-row"><span class="lbl">Nom</span><span class="val"><?= htmlspecialchars($user['nom'].' '.$user['prenom']); ?></span></div>
            <div class="info-row"><span class="lbl">Type</span><span class="val"><?= ucfirst($user['type_personne']); ?></span></div>
            <div class="info-row"><span class="lbl">N° identité</span><span class="val"><?= $user['num_identite'] ?: '—'; ?></span></div>
            <div class="info-row"><span class="lbl">N° permis</span><span class="val"><?= $user['num_permis'] ?: '—'; ?></span></div>
            <div class="info-row"><span class="lbl">Type permis</span><span class="val"><?= $user['type_permis'] ?: '—'; ?></span></div>
            <div class="info-row"><span class="lbl">Date délivrance</span><span class="val"><?= $user['date_delivrance_permis'] ?: '—'; ?></span></div>
        </div>

        <!-- Infos modifiables -->
        <div class="assure-card">
            <h3><i class="fa fa-edit"></i> Modifier mes coordonnées</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="text" name="telephone" value="<?= htmlspecialchars($user['telephone']); ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>">
                </div>
                <div class="form-group">
                    <label>Adresse</label>
                    <input type="text" name="adresse" value="<?= htmlspecialchars($user['adresse']); ?>">
                </div>
                <button type="submit" name="modifier" class="assure-btn primary"><i class="fa fa-save"></i> Enregistrer</button>
            </form>
        </div>

    </div>
</div>
</body>
</html>