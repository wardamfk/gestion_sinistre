<?php
require_once __DIR__ . '/../includes/session.php';
pfe_session_start('crma');
include '../includes/config.php';
require_once __DIR__ . '/../includes/reglement_logic.php';

$id = intval($_GET['id']);
$id_dossier = intval($_GET['dossier']);
$statut = $_GET['statut'];
$user_id = $_SESSION['id_user'];

if(!in_array($statut,['disponible','remis'])) die("Statut invalide");

mysqli_query($conn,"UPDATE reglement SET statut='$statut' WHERE id_reglement=$id");
// 🔥 HISTORIQUE REGLEMENT
if($statut == 'disponible') {
    $action = "Règlement disponible";
} elseif($statut == 'remis') {
    $action = "Règlement remis à l’assuré (quittance signée)";
}

$totals = pfe_reglement_compute_totals($conn, $id_dossier);

// récupérer état dossier actuel
$dossier = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id_etat FROM dossier WHERE id_dossier = $id_dossier"));

$etat_actuel = intval($dossier['id_etat']);
$nouvel_etat = $etat_actuel;
if ($etat_actuel !== 5 && $etat_actuel !== 14) {
    if ($totals['total_reserve'] > 0 && $totals['total_regle'] >= $totals['total_reserve']) {
        $nouvel_etat = 8;
    } elseif ($totals['total_regle'] > 0) {
        $nouvel_etat = 7;
    }
}

// insertion historique
mysqli_query($conn,"INSERT INTO historique (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
VALUES ($id_dossier, '$action', NOW(), $user_id, $etat_actuel, $nouvel_etat)");

pfe_reglement_sync_after_change($conn, $id_dossier, (int)$user_id);

header("Location: voir_dossier.php?id=$id_dossier&tab=reglements");
