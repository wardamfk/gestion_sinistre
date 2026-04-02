<?php
include('../includes/config.php');
session_start();

$id_dossier = $_POST['id_dossier'];
$type = $_POST['type'];
$user = $_SESSION['id_user'];

$nom_fichier = $_FILES['fichier']['name'];
$tmp = $_FILES['fichier']['tmp_name'];

$destination = "../uploads/" . $nom_fichier;

/* Déplacer le fichier */
move_uploaded_file($tmp, $destination);

/* Insérer dans la base */
mysqli_query($conn, "INSERT INTO document 
(id_dossier, nom_fichier, date_upload, upload_par, id_type_document)
VALUES ('$id_dossier', '$nom_fichier', NOW(), '$user', '$type')");

/* Historique */
mysqli_query($conn, "INSERT INTO historique (id_dossier, action, date_action, fait_par)
VALUES ('$id_dossier','Ajout document','$user', NOW())");

header("Location: voir_dossier.php?id=".$id_dossier."#documents");
?>