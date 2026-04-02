<?php
include('../includes/config.php');
session_start();

$id = $_GET['id'];
$id_dossier = $_GET['dossier'];
$user_id = $_SESSION['id_user'];

$conn->begin_transaction();

// 1. Ancien état
$sql_old = "SELECT id_etat FROM dossier WHERE id_dossier = ?";
$stmt_old = $conn->prepare($sql_old);
$stmt_old->bind_param("i", $id_dossier);
$stmt_old->execute();
$ancien_etat = $stmt_old->get_result()->fetch_assoc()['id_etat'];

// 2. Supprimer réserve
$stmt = $conn->prepare("DELETE FROM reserve WHERE id_reserve = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

// 3. Recalcul total réserve
$stmt2 = $conn->prepare("SELECT SUM(montant) as total FROM reserve WHERE id_dossier = ?");
$stmt2->bind_param("i", $id_dossier);
$stmt2->execute();
$res = $stmt2->get_result()->fetch_assoc();
$total = $res['total'] ?? 0;

// 4. Update dossier
$stmt3 = $conn->prepare("UPDATE dossier SET total_reserve = ? WHERE id_dossier = ?");
$stmt3->bind_param("di", $total, $id_dossier);
$stmt3->execute();

// 5. Nouvel état = même état
$nouvel_etat = $ancien_etat;
$action = "Suppression réserve";

// 6. Historique
$sql_hist = "INSERT INTO historique
(id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
VALUES (?, ?, NOW(), ?, ?, ?)";
$stmt_hist = $conn->prepare($sql_hist);
$stmt_hist->bind_param("isiii", $id_dossier, $action, $user_id, $ancien_etat, $nouvel_etat);
$stmt_hist->execute();

$conn->commit();

header("Location: voir_dossier.php?id=".$id_dossier."&tab=reserves");
exit();
?>