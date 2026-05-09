<?php
require_once __DIR__ . '/mailer.php';

function pfe_reglement_compute_totals(mysqli $conn, int $id_dossier): array {
    $totals = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT
            IFNULL((SELECT SUM(montant) FROM reglement WHERE id_dossier = $id_dossier), 0) AS total_regle,
            IFNULL((SELECT SUM(montant) FROM reserve WHERE id_dossier = $id_dossier AND statut = 'actif'), 0) AS total_reserve
    "));

    return [
        'total_regle' => floatval($totals['total_regle'] ?? 0),
        'total_reserve' => floatval($totals['total_reserve'] ?? 0),
    ];
}

function pfe_reglement_get_dossier_etat(mysqli $conn, int $id_dossier): ?int {
    $dossier = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_etat FROM dossier WHERE id_dossier = $id_dossier"));
    return $dossier ? intval($dossier['id_etat']) : null;
}



function pfe_reglement_send_definitive_email(mysqli $conn, int $id_dossier, int $user_id): void {
  
    $assureInfo = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        u.id_user AS assure_user_id,
        p.email AS assure_email,
        COALESCE(p.nom, p.raison_sociale, '') AS assure_nom,
        COALESCE(p.prenom, '') AS assure_prenom,
        d.numero_dossier,
        ag.nom_agence

    FROM dossier d

    JOIN contrat c
        ON c.id_contrat = d.id_contrat

    JOIN agence ag
        ON ag.id_agence = c.id_agence

    JOIN assure a
        ON a.id_assure = c.id_assure

    JOIN personne p
        ON p.id_personne = a.id_personne

    LEFT JOIN utilisateur u
        ON u.id_personne = p.id_personne
        AND u.role = 'ASSURE'

    WHERE d.id_dossier = $id_dossier

    LIMIT 1
"));
    if (!$assureInfo || empty($assureInfo['assure_email']) || empty($assureInfo['assure_user_id'])) {
        return;
    }

    $toEmail = trim((string)$assureInfo['assure_email']);
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $toName = trim($assureInfo['assure_prenom'] . ' ' . $assureInfo['assure_nom']);
    if ($toName === '') {
        $toName = $toEmail;
    }

    $num = htmlspecialchars($assureInfo['numero_dossier'], ENT_QUOTES, 'UTF-8');
    $subject = "Paiement de votre dossier sinistre — $num";

    $totals = pfe_reglement_compute_totals($conn, $id_dossier);
    $montant_total = number_format(
        $totals['total_regle'], 
        2,
        ',',
        ' '
    );

    $content =
'<p style="margin:0 0 14px;font-size:15px;line-height:1.7">
Nous vous informons que le paiement de votre dossier sinistre est désormais finalisé.
</p>

<div class="divider"></div>

<div class="row">
    <div class="label">Numéro dossier</div>
    <div class="value">' . pfe_mailer_escape($num) . '</div>
</div>

<div class="row">
    <div class="label">Montant total</div>
    <div class="value">' . $montant_total . ' DA</div>
</div>

<div class="row">
    <div class="label">Statut</div>
    <div class="value">Paiement effectué</div>
</div>

<div class="row">
    <div class="label">Agence CRMA</div>
    <div class="value">' . pfe_mailer_escape($assureInfo['nom_agence']) . '</div>
</div>

<div class="divider"></div>

<p style="margin:0;font-size:14px;line-height:1.7">
Votre dossier a été traité avec succès.
Pour toute information complémentaire, veuillez contacter votre agence CRMA.
</p>';

    $text = "Le paiement de votre dossier $num a été effectué avec succès pour un montant total de $montant_total DA. Pour toute information complémentaire, veuillez contacter votre agence CRMA.";

    mysqli_query($conn, "INSERT INTO notification (id_dossier, id_expediteur, id_destinataire, type, message)
        VALUES ($id_dossier, $user_id, " . intval($assureInfo['assure_user_id']) . ", 'reglement','Le paiement de votre dossier sinistre est désormais finalisé.')");
    $idNotif = (int)mysqli_insert_id($conn);

  $html = pfe_mailer_template(
    $subject,
    $content,
    "Paiement du dossier $num"
);
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

function pfe_reglement_sync_after_change(mysqli $conn, int $id_dossier, int $user_id): array {
    $totals = pfe_reglement_compute_totals($conn, $id_dossier);
    $old_etat = pfe_reglement_get_dossier_etat($conn, $id_dossier);
    if ($old_etat === null) {
        return array_merge($totals, ['old_etat' => null, 'new_etat' => null, 'email_sent' => false]);
    }

    $new_etat = $old_etat;
    if ($old_etat !== 5 && $old_etat !== 14) {
        if ($totals['total_reserve'] > 0 && $totals['total_regle'] >= $totals['total_reserve']) {
            $new_etat = 8;
        } elseif ($totals['total_regle'] > 0) {
            $new_etat = 7;
        } else {
            $new_etat = $old_etat === 8 ? 7 : $old_etat;
        }
    }

    if ($new_etat !== $old_etat) {
        mysqli_query($conn, "UPDATE dossier SET id_etat = $new_etat WHERE id_dossier = $id_dossier");
    }

    $email_sent = false;
    if ($old_etat !== 8 && $new_etat === 8) {
        pfe_reglement_send_definitive_email($conn, $id_dossier, $user_id);
        $email_sent = true;
    }

    return array_merge($totals, ['old_etat' => $old_etat, 'new_etat' => $new_etat, 'email_sent' => $email_sent]);
}
