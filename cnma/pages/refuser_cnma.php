<?php
// ============================================================
// refuser_cnma.php
// Refus CNMA : etat 5, motif obligatoire, message_assure vers assure
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
    SELECT id_motif, nom_motif, message_assure
    FROM motif
    WHERE id_motif = $id_motif AND id_etat = 5
"));

if (!$motif || trim((string)$motif['message_assure']) === '') {
    header("Location: voir_dossier_cnma.php?id=$id_dossier&msg=motif_required");
    exit();
}

$ancien_etat = intval($dossier['id_etat']);
$nouvel_etat = 5;
$id_agent    = intval($dossier['cree_par']);
$num         = $dossier['numero_dossier'];
$motif_nom   = $motif['nom_motif'];

mysqli_query($conn, "UPDATE dossier
    SET id_etat = $nouvel_etat,
        statut_validation = 'refuse',
        date_refus = CURDATE()
    WHERE id_dossier = $id_dossier");

$commentaire = mysqli_real_escape_string($conn, "Motif CNMA : $motif_nom");
mysqli_query($conn, "INSERT INTO historique
    (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat, commentaire, id_motif)
    VALUES ($id_dossier, 'Refus CNMA', NOW(), $user_id, $ancien_etat, $nouvel_etat, '$commentaire', $id_motif)");

// Notification CRMA : le motif interne est visible.
$msg_crma = mysqli_real_escape_string($conn, "Le dossier $num a ete refuse par la CNMA. Motif : $motif_nom");
mysqli_query($conn, "INSERT INTO notification
    (id_dossier, id_expediteur, id_destinataire, type, message)
    VALUES ($id_dossier, $user_id, $id_agent, 'refus', '$msg_crma')");

// Notification assure : uniquement le message_assure associe au motif de refus.
$assure_user = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT u.id_user
    FROM utilisateur u
    JOIN assure a ON u.id_personne = a.id_personne
    JOIN contrat c ON c.id_assure = a.id_assure
    JOIN dossier d ON d.id_contrat = c.id_contrat
    WHERE d.id_dossier = $id_dossier AND u.role = 'ASSURE'
    LIMIT 1
"));

if ($assure_user) {
    $msg_assure = mysqli_real_escape_string($conn, $motif['message_assure']);
    mysqli_query($conn, "INSERT INTO notification
        (id_dossier, id_expediteur, id_destinataire, type, message)
        VALUES ($id_dossier, $user_id, {$assure_user['id_user']}, 'refus', '$msg_assure')");
}

header("Location: voir_dossier_cnma.php?id=$id_dossier&msg=refuse");
exit();
?>
