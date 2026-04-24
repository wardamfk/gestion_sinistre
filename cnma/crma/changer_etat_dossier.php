<?php
// ============================================================
// changer_etat_dossier.php — Changement d'état simplifié
// POST: id_dossier, nouvel_etat, id_motif (optionnel), commentaire
// ============================================================
session_start();
include '../includes/config.php';

if (!in_array($_SESSION['role'], ['CRMA', 'CNMA'])) {
    header("Location: ../pages/login.php"); exit();
}

$id_dossier  = intval($_POST['id_dossier'] ?? 0);
$nouvel_etat = intval($_POST['nouvel_etat'] ?? 0);
$id_motif    = !empty($_POST['id_motif']) ? intval($_POST['id_motif']) : null;
$commentaire = trim($_POST['commentaire'] ?? '');
$user_id     = $_SESSION['id_user'];

if (!$id_dossier || !$nouvel_etat) {
    header("Location: voir_dossier.php?id=$id_dossier&err=invalid"); exit();
}

// Vérifier que le motif est fourni si l'état en a des obligatoires
$etat_config = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT motif_obligatoire FROM etat_dossier WHERE id_etat = $nouvel_etat"));

if ($etat_config && $etat_config['motif_obligatoire'] && !$id_motif) {
    header("Location: voir_dossier.php?id=$id_dossier&err=motif_required&tab=info"); exit();
}

// Récupérer l'état actuel
$dossier = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id_etat, numero_dossier, cree_par FROM dossier WHERE id_dossier = $id_dossier"));

if (!$dossier) {
    header("Location: voir_dossier.php?id=$id_dossier&err=not_found"); exit();
}

$ancien_etat = $dossier['id_etat'];
$numero      = $dossier['numero_dossier'];
$id_agent    = $dossier['cree_par'];

// Libellé de l'action pour l'historique
$nouvel_etat_nom = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT nom_etat FROM etat_dossier WHERE id_etat = $nouvel_etat"))['nom_etat'] ?? "État $nouvel_etat";
$action = "Changement d'état → $nouvel_etat_nom";

// Champs supplémentaires selon l'état
$extra_fields = '';
switch ($nouvel_etat) {
    case 11: case 12: case 13: case 14: case 19:
        $extra_fields = ", date_cloture = CURDATE()";
        break;
    case 15: case 18:
        $extra_fields = ", date_cloture = NULL";
        break;
    case 3:
        $extra_fields = ", date_transmission = CURDATE(), transmis_par = $user_id";
        break;
    case 4:
        $extra_fields = ", statut_validation = 'valide', date_validation = CURDATE(), valide_par = $user_id";
        break;
}

// Mettre à jour le dossier
$comm_sql = mysqli_real_escape_string($conn, $commentaire);
mysqli_query($conn,
    "UPDATE dossier SET id_etat = $nouvel_etat $extra_fields WHERE id_dossier = $id_dossier");

// Insérer dans l'historique
$motif_sql  = $id_motif ? $id_motif : 'NULL';
mysqli_query($conn, "
    INSERT INTO historique
        (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat, commentaire, id_motif)
    VALUES
        ($id_dossier, '$action', NOW(), $user_id, $ancien_etat, $nouvel_etat, '$comm_sql', $motif_sql)
");

// Notifications selon le nouvel état
if (in_array($nouvel_etat, [11, 12, 13, 14, 19])) {
    $assure_user = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT u.id_user FROM utilisateur u
        JOIN assure a ON u.id_personne = a.id_personne
        JOIN contrat c ON c.id_assure = a.id_assure
        JOIN dossier d ON d.id_contrat = c.id_contrat
        WHERE d.id_dossier = $id_dossier AND u.role = 'ASSURE' LIMIT 1
    "));
    if ($assure_user) {
        $msg_assure = mysqli_real_escape_string($conn,
            "Votre dossier $numero a changé d'état : $nouvel_etat_nom. Contactez votre agence pour plus d'informations.");
        mysqli_query($conn, "
            INSERT INTO notification (id_dossier, id_expediteur, id_destinataire, type, message)
            VALUES ($id_dossier, $user_id, {$assure_user['id_user']}, 'cloture', '$msg_assure')
        ");
    }
}

if (in_array($nouvel_etat, [4])) {
    $msg_agent = mysqli_real_escape_string($conn,
        "Le dossier $numero a été VALIDÉ par la CNMA. Vous pouvez procéder au règlement.");
    mysqli_query($conn, "
        INSERT INTO notification (id_dossier, id_expediteur, id_destinataire, type, message)
        VALUES ($id_dossier, $user_id, $id_agent, 'validation', '$msg_agent')
    ");
}

header("Location: voir_dossier.php?id=$id_dossier&tab=info&ok=etat_change"); exit();