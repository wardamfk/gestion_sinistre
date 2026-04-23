<?php
session_start();
include '../includes/config.php';

if($_SERVER['REQUEST_METHOD']=='POST') {
    $id_dossier = intval($_POST['id_dossier']);
    $montant = floatval($_POST['montant']);
    $date_enc = $_POST['date_encaissement'];
    $id_tiers = intval($_POST['id_tiers']);
    $type = $_POST['type'];
    $commentaire = mysqli_real_escape_string($conn, $_POST['commentaire']);
    $user_id = $_SESSION['id_user'];

 // Vérifier état dossier
$dossier = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id_etat FROM dossier WHERE id_dossier=$id_dossier"));

// 🔴 AJOUT ICI (NE BOUGE PAS CET EMPLACEMENT)
$tiers = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT responsable 
    FROM tiers 
    WHERE id_tiers = $id_tiers
"));

if (!$tiers || !in_array($tiers['responsable'], ['oui','partiel'])) {
    header("Location: voir_dossier.php?id=$id_dossier&tab=encaissements&err=tiers_non_responsable");
    exit();
}

// Vérifier état autorisé
if(!in_array($dossier['id_etat'], [7,8,13,14,19,20])) {
    header("Location: voir_dossier.php?id=$id_dossier&tab=encaissements&err=etat");
    exit();
}
    // Insérer encaissement
    mysqli_query($conn,"INSERT INTO encaissement (id_dossier,montant,date_encaissement,id_tiers,type,commentaire) VALUES ($id_dossier,$montant,'$date_enc',$id_tiers,'$type','$commentaire')");

    // Historique
    mysqli_query($conn,"INSERT INTO historique (id_dossier,action,date_action,fait_par,ancien_etat,nouvel_etat) VALUES ($id_dossier,'Encaissement enregistré — $type',NOW(),$user_id,{$dossier['id_etat']},{$dossier['id_etat']})");

    header("Location: voir_dossier.php?id=$id_dossier&tab=encaissements&added=1");
    exit();
}
header("Location: mes_dossiers.php");