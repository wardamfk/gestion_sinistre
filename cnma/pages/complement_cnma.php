<?php
// ============================================================
// complement_cnma.php
// Complement CNMA : etat 6, motif obligatoire, notification CRMA seule
// ============================================================
session_start();
include '../includes/config.php';

if ($_SESSION['role'] != 'CNMA') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dossiers_attente.php");
    exit();
}

$id_dossier = intval($_POST['id_dossier'] ?? 0);
$id_motif   = intval($_POST['id_motif'] ?? 0);
$user_id    = intval($_SESSION['id_user']);

if (!$id_dossier || !$id_motif) {
    header("Location: voir_dossier_cnma.php?id=$id_dossier&msg=motif_required");
    exit();
}

$dossier = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT id_etat, cree_par, numero_dossier
    FROM dossier
    WHERE id_dossier = $id_dossier
"));

if (!$dossier || intval($dossier['id_etat']) !== 3) {
    header("Location: voir_dossier_cnma.php?id=$id_dossier");
    exit();
}

$motif = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT id_motif, nom_motif
    FROM motif
    WHERE id_motif = $id_motif AND id_etat = 6
"));

if (!$motif) {
    header("Location: voir_dossier_cnma.php?id=$id_dossier&msg=motif_required");
    exit();
}

$ancien_etat = intval($dossier['id_etat']);
$nouvel_etat = 6;
$id_agent    = intval($dossier['cree_par']);
$num         = $dossier['numero_dossier'];
$motif_nom   = $motif['nom_motif'];

mysqli_query($conn, "UPDATE dossier
    SET id_etat = $nouvel_etat,
        statut_validation = 'non_soumis'
    WHERE id_dossier = $id_dossier");

$commentaire = mysqli_real_escape_string($conn, "Complement demande par la CNMA. Motif : $motif_nom");
mysqli_query($conn, "INSERT INTO historique
    (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat, commentaire, id_motif)
    VALUES ($id_dossier, 'Demande de complement CNMA', NOW(), $user_id, $ancien_etat, $nouvel_etat, '$commentaire', $id_motif)");

// Notification CRMA uniquement : l'assure ne recoit rien pour un complement CNMA.
$msg_crma = mysqli_real_escape_string($conn, "Complement demande pour le dossier $num. Motif : $motif_nom");
mysqli_query($conn, "INSERT INTO notification
    (id_dossier, id_expediteur, id_destinataire, type, message)
    VALUES ($id_dossier, $user_id, $id_agent, 'complement', '$msg_crma')");

header("Location: voir_dossier_cnma.php?id=$id_dossier&msg=complement");
exit();
?>
