<?php
session_start();
include('../includes/config.php');
if(!isset($_SESSION['id_user']) || $_SESSION['role'] != 'ASSURE') { header("Location: ../pages/login.php"); exit(); }
$id_user = $_SESSION['id_user'];
$page_title = "Mes documents";

$assure = mysqli_fetch_assoc(mysqli_query($conn,"SELECT a.id_assure FROM assure a JOIN utilisateur u ON a.id_personne=u.id_personne WHERE u.id_user=$id_user LIMIT 1"));
$id_assure = $assure ? $assure['id_assure'] : 0;

// Upload document (si complément demandé)
$success = $error = '';
if(isset($_POST['upload']) && !empty($_FILES['fichier']['name'])) {
    $id_dossier = intval($_POST['id_dossier']);
    // Vérifier que ce dossier appartient à l'assuré
    $check = mysqli_fetch_assoc(mysqli_query($conn,"SELECT d.id_dossier FROM dossier d JOIN contrat c ON d.id_contrat=c.id_contrat WHERE d.id_dossier=$id_dossier AND c.id_assure=$id_assure"));
    if($check) {
        $nom = $_FILES['fichier']['name'];
        $tmp = $_FILES['fichier']['tmp_name'];
        move_uploaded_file($tmp, "../uploads/".$nom);
        $type = intval($_POST['type_doc']);
        mysqli_query($conn,"INSERT INTO document (id_dossier,nom_fichier,date_upload,upload_par,id_type_document) VALUES ($id_dossier,'$nom',NOW(),$id_user,$type)");
        $success = "Document ajouté avec succès";
    } else { $error = "Accès non autorisé"; }
}

$documents = mysqli_query($conn,"
    SELECT doc.*, d.numero_dossier, t.nom_type
    FROM document doc
    JOIN dossier d ON doc.id_dossier=d.id_dossier
    JOIN contrat c ON d.id_contrat=c.id_contrat
    LEFT JOIN type_document t ON doc.id_type_document=t.id_type_document
    WHERE c.id_assure=$id_assure
    ORDER BY doc.date_upload DESC
");

// Dossiers avec complément demandé
$dossiers_complement = mysqli_query($conn,"SELECT d.id_dossier,d.numero_dossier FROM dossier d JOIN contrat c ON d.id_contrat=c.id_contrat WHERE c.id_assure=$id_assure AND d.id_etat=6");
$types = mysqli_query($conn,"SELECT * FROM type_document");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mes documents</title>
<link rel="stylesheet" href="../css/style_assure.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php include('sidebar_assure.php'); ?>
<?php include('header_assure.php'); ?>
<div class="assure-main">
    <div class="page-heading">
        <h2><i class="fa fa-file-alt"></i> Mes documents</h2>
    </div>

    <?php if($success) echo "<div class='msg success'><i class='fa fa-check-circle'></i> $success</div>"; ?>
    <?php if($error) echo "<div class='msg warning'><i class='fa fa-exclamation-triangle'></i> $error</div>"; ?>

    <?php if(mysqli_num_rows($dossiers_complement) > 0): ?>
    <div class="assure-card" style="border-left:4px solid #f57c00;">
        <h3><i class="fa fa-upload"></i> Ajouter un document (complément demandé)</h3>
        <form method="POST" enctype="multipart/form-data" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:14px;align-items:end;">
            <div class="form-group" style="margin:0;">
                <label>Dossier concerné</label>
                <select name="id_dossier" required>
                    <?php while($dd = mysqli_fetch_assoc($dossiers_complement)): ?>
                    <option value="<?= $dd['id_dossier']; ?>"><?= $dd['numero_dossier']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label>Type de document</label>
                <select name="type_doc" required>
                    <?php while($t = mysqli_fetch_assoc($types)): ?>
                    <option value="<?= $t['id_type_document']; ?>"><?= $t['nom_type']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label>Fichier</label>
                <input type="file" name="fichier" required style="padding:8px;">
            </div>
            <button type="submit" name="upload" class="assure-btn primary"><i class="fa fa-upload"></i> Envoyer</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="assure-card">
        <h3><i class="fa fa-folder"></i> Tous mes documents</h3>
        <?php if(mysqli_num_rows($documents)==0): ?>
        <p style="text-align:center;color:#90a4ae;padding:30px;">Aucun document pour le moment</p>
        <?php else: ?>
        <table class="assure-table">
            <thead><tr><th>Dossier</th><th>Type</th><th>Fichier</th><th>Date</th><th>Action</th></tr></thead>
            <tbody>
            <?php while($d = mysqli_fetch_assoc($documents)): ?>
            <tr>
                <td><b style="color:#0d47a1;"><?= $d['numero_dossier']; ?></b></td>
                <td><?= $d['nom_type']; ?></td>
                <td><i class="fa fa-file" style="color:#546e7a;"></i> <?= $d['nom_fichier']; ?></td>
                <td><?= $d['date_upload']; ?></td>
                <td><a href="../uploads/<?= $d['nom_fichier']; ?>" target="_blank" class="assure-btn primary sm"><i class="fa fa-eye"></i> Voir</a></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>