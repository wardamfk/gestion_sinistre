<?php

require_once __DIR__ . '/../includes/session.php';
pfe_session_start('crma');

include '../includes/config.php';
require_once __DIR__ . '/../includes/reglement_logic.php';

$id_dossier = intval($_GET['id']);
$user_id = $_SESSION['id_user'];

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