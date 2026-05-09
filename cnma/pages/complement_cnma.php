<?php
// ============================================================
// complement_cnma.php
// Complement CNMA : etat 6, motif obligatoire, notification CRMA seule
// ============================================================
require_once __DIR__ . '/../includes/session.php';
pfe_session_start('cnma');
include '../includes/config.php';
require_once __DIR__ . '/../includes/mailer.php';

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

$assureInfo = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT u.id_user AS assure_user_id,
           p.email AS assure_email,
           COALESCE(p.nom, p.raison_sociale, '') AS assure_nom,
           COALESCE(p.prenom, '') AS assure_prenom
    FROM dossier d
    JOIN contrat c ON c.id_contrat = d.id_contrat
    JOIN assure a ON a.id_assure = c.id_assure
    JOIN personne p ON p.id_personne = a.id_personne
    JOIN utilisateur u ON u.id_personne = p.id_personne AND u.role = 'ASSURE'
    WHERE d.id_dossier = $id_dossier
    LIMIT 1
"));

if ($assureInfo && !empty($assureInfo['assure_user_id'])) {
    $msgPlainAssure = "Complément demandé pour votre dossier $num. Motif : $motif_nom. Veuillez transmettre les documents manquants à votre agence CRMA.";
    $msgAssure = mysqli_real_escape_string($conn, $msgPlainAssure);
    mysqli_query($conn, "INSERT INTO notification
        (id_dossier, id_expediteur, id_destinataire, type, message)
        VALUES ($id_dossier, $user_id, {$assureInfo['assure_user_id']}, 'complement', '$msgAssure')");
    $idNotif = (int)mysqli_insert_id($conn);

    $toEmail = trim((string)($assureInfo['assure_email'] ?? ''));
    if ($toEmail !== '') {
        $subject = "Complément demandé pour votre dossier";
        $content = '<p class="muted">Des informations ou documents supplémentaires sont nécessaires pour traiter votre dossier.</p>' .
            '<div class="divider"></div>' .
            '<div class="row"><div class="label">Numéro dossier</div><div class="value">' . pfe_mailer_escape($num) . '</div></div>' .
            '<div class="row"><div class="label">Motif du complément</div><div class="value">' . pfe_mailer_escape($motif_nom) . '</div></div>' .
            '<div class="divider"></div>' .
            '<p style="margin:0;font-size:14px;line-height:1.7">Merci de transmettre les documents manquants à votre agence CRMA afin d’accélérer le traitement.</p>';

        $html = pfe_mailer_template($subject, $content, "Complément demandé pour $num");
        $text = "Complément demandé pour votre dossier.\nDossier: $num\nMotif: $motif_nom\nMerci de transmettre les documents manquants à votre agence CRMA.";
        $toName = trim((string)($assureInfo['assure_prenom'] . ' ' . $assureInfo['assure_nom']));
        $res = pfe_mailer_send($toEmail, $toName, $subject, $html, $text);
        pfe_notification_update_email($conn, $idNotif, [
            'email_to' => $toEmail,
            'email_subject' => $subject,
            'email_body_html' => $html,
            'email_status' => $res['ok'] ? 'sent' : 'failed',
            'email_error' => $res['ok'] ? '' : (string)($res['error'] ?? ''),
            'email_sent_at' => $res['ok'] ? date('Y-m-d H:i:s') : null,
        ]);
    }
}

header("Location: voir_dossier_cnma.php?id=$id_dossier&msg=complement");
exit();
?>
