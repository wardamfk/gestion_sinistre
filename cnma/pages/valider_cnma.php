<?php
session_start();
include '../includes/config.php';

if($_SESSION['role'] != 'CNMA') { header("Location: login.php"); exit(); }

if(isset($_GET['id'])) {
    $id_dossier = intval($_GET['id']);
    $user_id    = $_SESSION['id_user'];

    $dossier = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_etat, cree_par, numero_dossier FROM dossier WHERE id_dossier=$id_dossier"));

    if(!$dossier || $dossier['id_etat'] != 3) {
        header("Location: voir_dossier_cnma.php?id=$id_dossier"); exit();
    }

    $ancien_etat = 3;
    $nouvel_etat = 4;
    $id_agent    = $dossier['cree_par'];
    $num         = $dossier['numero_dossier'];

    // 1. Update dossier
    mysqli_query($conn, "UPDATE dossier
        SET id_etat = $nouvel_etat,
            statut_validation = 'valide',
            date_validation = CURDATE(),
            valide_par = $user_id
        WHERE id_dossier = $id_dossier");

    // 2. Historique
    $action = "Validation CNMA";
    mysqli_query($conn, "INSERT INTO historique
        (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat, commentaire)
        VALUES ($id_dossier, '$action', NOW(), $user_id, $ancien_etat, $nouvel_etat,
                'Dossier validé par la CNMA — règlement autorisé')");

    // 3. Notification vers l'agent CRMA
    $msg = "Le dossier $num a été VALIDÉ par la CNMA. Vous pouvez procéder au règlement.";
    mysqli_query($conn, "INSERT INTO notification
        (id_dossier, id_expediteur, id_destinataire, type, message)
        VALUES ($id_dossier, $user_id, $id_agent, 'validation', '$msg')");

    header("Location: voir_dossier_cnma.php?id=$id_dossier&msg=valide");
    exit();
}
header("Location: dossiers_attente.php");
?>