<?php

require_once __DIR__ . '/../includes/session.php';
pfe_session_start('crma');

include '../includes/config.php';
require_once __DIR__ . '/../includes/reglement_logic.php';

$id_dossier = intval($_GET['id']);
$user_id = $_SESSION['id_user'];
$id_agence = intval($_SESSION['id_agence'] ?? 0);

// Sécurité : un CRMA ne peut confirmer la remise que pour un dossier de son agence
$check = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT d.id_dossier
    FROM dossier d
    JOIN utilisateur u ON d.cree_par = u.id_user
    WHERE d.id_dossier = $id_dossier
      AND u.id_agence = $id_agence
    LIMIT 1
"));
if (!$check) {
    // Dossier hors périmètre (autre agence) ou introuvable
    header("Location: dashboard_crma.php");
    exit;
}

mysqli_begin_transaction($conn);

try {

    mysqli_query($conn,"
        UPDATE reglement
        SET statut='remis'
        WHERE id_dossier=$id_dossier
        AND statut='disponible'
    ");

    mysqli_query($conn,"
        INSERT INTO historique
        (id_dossier, action, date_action, fait_par)
        VALUES
        (
            $id_dossier,
            'Remise totale des règlements à l’assuré',
            NOW(),
            $user_id
        )
    ");

    pfe_reglement_sync_after_change(
        $conn,
        $id_dossier,
        $user_id
    );

    mysqli_commit($conn);

}
catch(Exception $e){

    mysqli_rollback($conn);

}

header("Location: voir_dossier.php?id=$id_dossier&tab=reglements");

exit;
