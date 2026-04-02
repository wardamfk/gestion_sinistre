<?php
session_start();
include '../includes/config.php';

if(isset($_GET['id'])){
    
    $id_dossier = $_GET['id'];
    $user_id = $_SESSION['id_user'];

    // 1. Ancien état
    $sql_old = "SELECT id_etat FROM dossier WHERE id_dossier = ?";
    $stmt_old = $conn->prepare($sql_old);
    $stmt_old->bind_param("i", $id_dossier);
    $stmt_old->execute();
    $result_old = $stmt_old->get_result();
    $ancien_etat = $result_old->fetch_assoc()['id_etat'];

    // 2. Nouvel état = refus CNMA
    $nouvel_etat = 5;
    $action = "Refus CNMA";

    // 3. Update dossier
    $sql_update = "UPDATE dossier 
                   SET id_etat = ?, statut_validation = 'refuse'
                   WHERE id_dossier = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ii", $nouvel_etat, $id_dossier);
    $stmt_update->execute();

    // 4. Historique
    $sql_hist = "INSERT INTO historique
                (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
                VALUES (?, ?, NOW(), ?, ?, ?)";
    $stmt_hist = $conn->prepare($sql_hist);
    $stmt_hist->bind_param("isiii", $id_dossier, $action, $user_id, $ancien_etat, $nouvel_etat);
    $stmt_hist->execute();

    header("Location: voir_dossier.php?id=".$id_dossier);
}
?>