<?php
session_start();
include '../includes/config.php';

$id = intval($_GET['id']);
$id_dossier = intval($_GET['dossier']);
$statut = $_GET['statut'];
$user_id = $_SESSION['id_user'];

if(!in_array($statut,['disponible','remis'])) die("Statut invalide");

mysqli_query($conn,"UPDATE reglement SET statut='$statut' WHERE id_reglement=$id");

// Si disponible → notifier assuré
if($statut=='disponible') {
    $dossier = mysqli_fetch_assoc(mysqli_query($conn,"SELECT numero_dossier,id_contrat FROM dossier WHERE id_dossier=$id_dossier"));
    $num = $dossier['numero_dossier'];
    $assure_user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT u.id_user FROM utilisateur u JOIN assure a ON u.id_personne=a.id_personne JOIN contrat c ON c.id_assure=a.id_assure WHERE c.id_contrat={$dossier['id_contrat']} AND u.role='ASSURE' LIMIT 1"));
    if($assure_user) {
        $msg = mysqli_real_escape_string($conn,"Un chèque est disponible pour le dossier $num. Veuillez vous présenter à votre agence CRMA pour le récupérer.");
        mysqli_query($conn,"INSERT INTO notification (id_dossier,id_expediteur,id_destinataire,type,message) VALUES ($id_dossier,$user_id,{$assure_user['id_user']},'reglement','$msg')");
    }
}

header("Location: voir_dossier.php?id=$id_dossier&tab=reglements");