<?php
// ============================================================
// cloturer_dossier_cnma.php
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

    notifyAssure($conn, $id_dossier, $user_id, 'cloture', "Votre dossier $num a été clôturé. Merci de votre confiance. N'hésitez pas à contacter votre agence pour toute question.");
}
if($_SESSION['role'] != 'CNMA') { header("Location: login.php"); exit(); }

if(isset($_GET['id'])) {
    $id_dossier = intval($_GET['id']);
    $user_id    = $_SESSION['id_user'];

    $dossier = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_etat, cree_par, numero_dossier FROM dossier WHERE id_dossier=$id_dossier"));

    if(!$dossier || $dossier['id_etat'] != 8) {
        header("Location: voir_dossier_cnma.php?id=$id_dossier"); exit();
    }

    $ancien_etat = 8;
    $nouvel_etat = 14;
    $id_agent    = $dossier['cree_par'];
    $num         = $dossier['numero_dossier'];

    mysqli_query($conn, "UPDATE dossier
        SET id_etat = $nouvel_etat,
            date_cloture = CURDATE()
        WHERE id_dossier = $id_dossier");

    mysqli_query($conn, "INSERT INTO historique
        (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat, commentaire)
        VALUES ($id_dossier, 'Clôture dossier CNMA', NOW(), $user_id, $ancien_etat, $nouvel_etat,
                'Dossier clôturé définitivement par la CNMA')");

    $msg = "Le dossier $num a été clôturé définitivement par la CNMA.";
    mysqli_query($conn, "INSERT INTO notification
        (id_dossier, id_expediteur, id_destinataire, type, message)
        VALUES ($id_dossier, $user_id, $id_agent, 'cloture', '$msg')");

    header("Location: voir_dossier_cnma.php?id=$id_dossier&msg=cloture");
    exit();
}
header("Location: tous_dossiers_cnma.php");
?>