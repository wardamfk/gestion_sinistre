<?php
// ============================================================
// complement_cnma.php
// ============================================================
session_start();
include '../includes/config.php';
function notifyAssure($conn, $id_dossier, $id_expediteur, $type, $msg) {
    $msg = mysqli_real_escape_string($conn, $msg);
    $assure_user = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT u.id_user FROM utilisateur u 
        JOIN assure a ON u.id_personne=a.id_personne
        JOIN contrat c ON c.id_assure=a.id_assure
        JOIN dossier d ON d.id_contrat=c.id_contrat
        WHERE d.id_dossier=$id_dossier AND u.role='ASSURE' LIMIT 1
    "));
    if($assure_user) {
        mysqli_query($conn, "INSERT INTO notification (id_dossier,id_expediteur,id_destinataire,type,message) VALUES ($id_dossier,$id_expediteur,{$assure_user['id_user']},'$type','$msg')");
    }

    notifyAssure($conn, $id_dossier, $user_id, 'complement', "Des documents complémentaires sont nécessaires pour votre dossier $num. Veuillez vous connecter à votre espace pour les déposer.");
}
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
    $nouvel_etat = 2; // Retour CRMA
    $id_agent    = $dossier['cree_par'];
    $num         = $dossier['numero_dossier'];

    mysqli_query($conn, "UPDATE dossier
        SET id_etat = $nouvel_etat,
            statut_validation = 'non_soumis'
        WHERE id_dossier = $id_dossier");

    mysqli_query($conn, "INSERT INTO historique
        (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat, commentaire)
        VALUES ($id_dossier, 'Demande de complément CNMA', NOW(), $user_id, $ancien_etat, $nouvel_etat,
                'Dossier renvoyé au CRMA pour complément de documents')");

    $msg = "Complément demandé pour le dossier $num. Veuillez compléter les documents manquants et re-transmettre.";
    mysqli_query($conn, "INSERT INTO notification
        (id_dossier, id_expediteur, id_destinataire, type, message)
        VALUES ($id_dossier, $user_id, $id_agent, 'complement', '$msg')");

    header("Location: voir_dossier_cnma.php?id=$id_dossier&msg=complement");
    exit();
}
header("Location: dossiers_attente.php");
?>