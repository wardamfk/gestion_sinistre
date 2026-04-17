<?php
// ============================================================
// changer_etat_dossier.php
// Handler générique de changement d'état avec gestion des motifs
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

// ---- 1. Vérifier que le motif est fourni quand obligatoire ----
$etat_config = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT motif_obligatoire FROM etat_dossier WHERE id_etat = $nouvel_etat"));

if ($etat_config && $etat_config['motif_obligatoire'] && !$id_motif) {
    header("Location: voir_dossier.php?id=$id_dossier&err=motif_required&tab=info"); exit();
}

// ---- 2. Récupérer l'état actuel ----
$dossier = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id_etat, numero_dossier, cree_par FROM dossier WHERE id_dossier = $id_dossier"));

if (!$dossier) {
    header("Location: voir_dossier.php?id=$id_dossier&err=not_found"); exit();
}

$ancien_etat = $dossier['id_etat'];
$numero      = $dossier['numero_dossier'];
$id_agent    = $dossier['cree_par'];

// ---- 3. Libellé de l'action pour l'historique ----
$action_labels = [
    2  => 'Retour en cours CRMA',
    3  => 'Transmission CNMA',
    9  => 'En cours d\'expertise',
    11 => 'Classé sans suite',
    12 => 'Classé après rejet',
    13 => 'Classé en attente recours',
    14 => 'Clôturé',
    15 => 'Repris',
    16 => 'En cours de contre-expertise',
    17 => 'Règlement définitif judiciaire',
    18 => 'Repris pour recours abouti',
    19 => 'Classé après recours abouti',
    20 => 'Gestion pour recours',
];
$action = $action_labels[$nouvel_etat] ?? "Changement état vers ID $nouvel_etat";

// ---- 4. Champs supplémentaires selon le nouvel état ----
$extra_fields = '';
switch ($nouvel_etat) {
    case 11: // Classé sans suite
    case 12: // Classé après rejet
    case 13: // Classé en attente recours
    case 14: // Clôturé
    case 19: // Classé après recours abouti
        $extra_fields = ", date_cloture = CURDATE()";
        break;
    case 15: // Repris
    case 18: // Repris pour recours abouti
        $extra_fields = ", date_cloture = NULL"; // réouverture
        break;
}

// ---- 5. Mettre à jour le dossier ----
$comm_sql = mysqli_real_escape_string($conn, $commentaire);
mysqli_query($conn,
    "UPDATE dossier SET id_etat = $nouvel_etat $extra_fields WHERE id_dossier = $id_dossier");

// ---- 6. Insérer dans l'historique (avec id_motif) ----
$motif_sql  = $id_motif ? $id_motif : 'NULL';
mysqli_query($conn, "
    INSERT INTO historique
        (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat, commentaire, id_motif)
    VALUES
        ($id_dossier, '$action', NOW(), $user_id, $ancien_etat, $nouvel_etat, '$comm_sql', $motif_sql)
");

// ---- 7. Notifications selon le nouvel état ----
if (in_array($nouvel_etat, [11, 12, 13, 14, 19])) {
    // Notifier l'assuré si clôturé/classé
    $assure_user = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT u.id_user FROM utilisateur u
        JOIN assure a ON u.id_personne = a.id_personne
        JOIN contrat c ON c.id_assure = a.id_assure
        JOIN dossier d ON d.id_contrat = c.id_contrat
        WHERE d.id_dossier = $id_dossier AND u.role = 'ASSURE' LIMIT 1
    "));
    if ($assure_user) {
        $nom_etat_res = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT nom_etat FROM etat_dossier WHERE id_etat = $nouvel_etat"));
        $nom_etat_str = $nom_etat_res['nom_etat'] ?? '';
        $msg_assure = mysqli_real_escape_string($conn,
            "Votre dossier $numero a changé d'état : $nom_etat_str. Contactez votre agence pour plus d'informations.");
        mysqli_query($conn, "
            INSERT INTO notification (id_dossier, id_expediteur, id_destinataire, type, message)
            VALUES ($id_dossier, $user_id, {$assure_user['id_user']}, 'cloture', '$msg_assure')
        ");
    }
}

if ($nouvel_etat == 15 || $nouvel_etat == 18) {
    // Notifier l'agent CRMA si le dossier est repris (depuis CNMA)
    if ($_SESSION['role'] == 'CNMA' && $id_agent != $user_id) {
        $nom_etat_res = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT nom_etat FROM etat_dossier WHERE id_etat = $nouvel_etat"));
        $msg_agent = mysqli_real_escape_string($conn,
            "Le dossier $numero a été repris : {$nom_etat_res['nom_etat']}.");
        mysqli_query($conn, "
            INSERT INTO notification (id_dossier, id_expediteur, id_destinataire, type, message)
            VALUES ($id_dossier, $user_id, $id_agent, 'validation', '$msg_agent')
        ");
    }
}

header("Location: voir_dossier.php?id=$id_dossier&tab=info&ok=etat_change"); exit();
