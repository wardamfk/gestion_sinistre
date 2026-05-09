<?php
require_once __DIR__ . '/../includes/session.php';
pfe_session_start('crma');
include '../includes/config.php';
require_once __DIR__ . '/../includes/reglement_logic.php';

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: mes_dossiers.php"); exit();
}

$id_dossier  = intval($_POST['id_dossier']);
$montant     = floatval($_POST['montant']);
$mode = 'Chèque';
$commentaire = mysqli_real_escape_string($conn, $_POST['commentaire'] ?? '');
$user_id     = $_SESSION['id_user'];

try {
    $conn->begin_transaction();

    // 1. Etat actuel
    $dossier = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_etat FROM dossier WHERE id_dossier = $id_dossier"));

    $ancien_etat = $dossier['id_etat'];

  // INSERT règlement
$stmt = $conn->prepare(
    "INSERT INTO reglement (id_dossier, montant, mode_paiement, date_reglement, saisi_par, commentaire)
     VALUES (?, ?, ?, CURDATE(), ?, ?)"
);
$stmt->bind_param("idsss", $id_dossier, $montant, $mode, $user_id, $commentaire);
$stmt->execute();

// 🔥 récupérer ID
$id_reglement = $conn->insert_id;

// 🔥 générer référence
// 🔥 récupérer numero dossier
$res = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT numero_dossier FROM dossier WHERE id_dossier = $id_dossier"));

$numero = $res['numero_dossier'];

// 🔥 extraire la partie finale (0014 ou 10014)
$parts = explode('-', $numero);
$num_simple = end($parts);

// 🔥 générer référence propre
$reference = 'CHQ-' . $num_simple . '-' . str_pad($id_reglement, 3, '0', STR_PAD_LEFT);

// 🔥 update référence
$stmt_ref = $conn->prepare("UPDATE reglement SET reference_paiement = ? WHERE id_reglement = ?");
$stmt_ref->bind_param("si", $reference, $id_reglement);
$stmt_ref->execute();
    // 🔥 TOTAL RÈGLEMENT (uniquement disponible + remis)
    $totals = pfe_reglement_compute_totals($conn, $id_dossier);
    $total_regle = $totals['total_regle'];

    // 🔥 TOTAL RÉSERVE ACTUELLE
    $res_reserve = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT SUM(montant) as total FROM reserve WHERE id_dossier = $id_dossier AND statut = 'actif'"));

    $reserve_totale = floatval($res_reserve['total']);

    // 🔥 DEPASSEMENT → RESERVE COMPLÉMENTAIRE
    if ($total_regle > $reserve_totale) {

        $complement = $total_regle - $reserve_totale;

        // récupérer une garantie existante
        $id_garantie = 1;
        $r = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT id_garantie FROM reserve WHERE id_dossier = $id_dossier ORDER BY id_reserve LIMIT 1"));
        if ($r) $id_garantie = $r['id_garantie'];

        $comm_res = "Réserve complémentaire après dépassement règlement";

        mysqli_query($conn,
            "INSERT INTO reserve 
            (id_dossier, id_garantie, montant, date_reserve, type_reserve, cree_par, date_creation, commentaire)
            VALUES 
            ($id_dossier, $id_garantie, $complement, CURDATE(), 'complementaire', $user_id, CURDATE(), '$comm_res')");
    }

    // 🔥 DÉTERMINER L'ACTION ET L'ÉTAT AU BESOIN
    $nouvel_etat = $ancien_etat;
    if ($reserve_totale > 0 && $total_regle >= $reserve_totale) {
        $nouvel_etat = 8;
        $action = "Règlement total";
    } elseif ($total_regle > 0) {
        $nouvel_etat = 7;
        $action = "Règlement partiel";
    } else {
        $action = "Règlement saisi";
    }

    // 🔥 HISTORIQUE
    $stmt_h = $conn->prepare(
        "INSERT INTO historique (id_dossier, action, date_action, fait_par, ancien_etat, nouvel_etat)
         VALUES (?, ?, NOW(), ?, ?, ?)"
    );
    $stmt_h->bind_param("isiii", $id_dossier, $action, $user_id, $ancien_etat, $nouvel_etat);
    $stmt_h->execute();

    pfe_reglement_sync_after_change($conn, $id_dossier, $user_id);

    $conn->commit();

    header("Location: voir_dossier.php?id=$id_dossier&tab=reglements&added=1");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    echo "Erreur : " . htmlspecialchars($e->getMessage());
}