<?php
require_once __DIR__ . '/../includes/session.php';
pfe_session_start('crma');
include('../includes/config.php');
require_once __DIR__ . '/../includes/reglement_logic.php';

$id = $_GET['id'];
$id_dossier = $_GET['dossier'];
$user_id = $_SESSION['id_user'];

$conn->begin_transaction();

// Ancien état
$sql_old = "SELECT id_etat FROM dossier WHERE id_dossier = ?";
$stmt_old = $conn->prepare($sql_old);
$stmt_old->bind_param("i", $id_dossier);
$stmt_old->execute();
$ancien_etat = $stmt_old->get_result()->fetch_assoc()['id_etat'];

// Supprimer règlement
$stmt = $conn->prepare("DELETE FROM reglement WHERE id_reglement = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$totals = pfe_reglement_compute_totals($conn, $id_dossier);
$nouvel_etat = $ancien_etat;
if ($ancien_etat !== 5 && $ancien_etat !== 14) {
    if ($totals['total_reserve'] > 0 && $totals['total_regle'] >= $totals['total_reserve']) {
        $nouvel_etat = 8;
    } elseif ($totals['total_regle'] > 0) {
        $nouvel_etat = 7;
    }
}
$action = "Suppression règlement";

// Historique
$sql_hist = "INSERT INTO historique
(id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
VALUES (?, ?, NOW(), ?, ?, ?)";
$stmt_hist = $conn->prepare($sql_hist);
$stmt_hist->bind_param("isiii", $id_dossier, $action, $user_id, $ancien_etat, $nouvel_etat);
$stmt_hist->execute();

pfe_reglement_sync_after_change($conn, $id_dossier, $user_id);

$conn->commit();

header("Location: voir_dossier.php?id=".$id_dossier."&tab=reglements");
exit();

